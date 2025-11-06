<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactCustomFieldValue;
use App\Models\ContactMerge;
use App\Support\Concerns\SynchronizesContactData;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ContactMerger
{
    use SynchronizesContactData;

    public function analyze(Contact $master, Contact $secondary): array
    {
        $master->loadMissing([
            'emails' => function ($query) {
                $query->orderByDesc('is_primary');
            },
            'phones' => function ($query) {
                $query->orderByDesc('is_primary');
            },
            'customFieldValues.customField',
        ]);

        $secondary->loadMissing([
            'emails' => function ($query) {
                $query->orderByDesc('is_primary');
            },
            'phones' => function ($query) {
                $query->orderByDesc('is_primary');
            },
            'customFieldValues.customField',
        ]);

        $plan = [
            'attributes_to_fill' => [],
            'attributes_retained' => [],
            'emails_to_append' => [],
            'phones_to_append' => [],
            'custom_fields' => [],
            'files' => [],
            'secondary_snapshot' => [
                'contact_id' => $secondary->id,
                'name' => $secondary->name,
                'email' => $secondary->email,
                'phone' => $secondary->phone,
                'gender' => $secondary->gender,
                'profile_image_path' => $secondary->profile_image_path,
                'document_path' => $secondary->document_path,
            ],
        ];

        $this->analyzeAttributes($plan, $master, $secondary);
        $this->analyzeEmails($plan, $master, $secondary);
        $this->analyzePhones($plan, $master, $secondary);
        $this->analyzeCustomFields($plan, $master, $secondary);
        $this->analyzeFiles($plan, $master, $secondary);

        return $plan;
    }

    public function merge(Contact $master, Contact $secondary): array
    {
        $plan = $this->analyze($master->fresh(), $secondary->fresh());

        $result = DB::transaction(function () use ($master, $secondary, $plan) {
            $master->refresh();
            $secondary->refresh();

            $attributePayload = Arr::get($plan, 'attributes_to_fill', []);
            if (!empty($attributePayload)) {
                foreach ($attributePayload as $attribute => $data) {
                    $master->{$attribute} = $data['value'];
                }
            }

            $master->save();

            // Ensure primary email/phone records are in sync.
            $this->syncPrimaryEmail($master, $master->email);
            $this->syncPrimaryPhone($master, $master->phone);

            foreach (Arr::get($plan, 'emails_to_append', []) as $emailPayload) {
                $this->appendEmail($master, $emailPayload);
            }

            foreach (Arr::get($plan, 'phones_to_append', []) as $phonePayload) {
                $this->appendPhone($master, $phonePayload);
            }

            $this->applyCustomFieldsPlan($master, $secondary, Arr::get($plan, 'custom_fields', []));

            $this->applyFilePlan($master, $plan['files'] ?? [], $secondary);

            $masterSummary = $master->merge_summary ?? [];
            $masterSummary['merged_contact_ids'] = array_values(array_unique(array_merge(
                Arr::get($masterSummary, 'merged_contact_ids', []),
                [$secondary->id]
            )));
            $masterSummary['last_merged_at'] = Carbon::now()->toISOString();
            $master->merge_summary = $masterSummary;
            $master->save();

            $secondary->status = 'merged';
            $secondary->merged_into_id = $master->id;
            $secondary->merged_at = Carbon::now();
            $secondary->merge_summary = [
                'merged_into' => $master->id,
                'merged_at' => Carbon::now()->toISOString(),
            ];
            $secondary->save();

            $mergeRecord = ContactMerge::create([
                'master_contact_id' => $master->id,
                'secondary_contact_id' => $secondary->id,
                'merged_attributes' => Arr::only($plan, [
                    'attributes_to_fill',
                    'attributes_retained',
                    'emails_to_append',
                    'phones_to_append',
                ]),
                'merged_custom_fields' => $plan['custom_fields'] ?? [],
                'merged_files' => $plan['files'] ?? [],
            ]);

            $master->load(['emails', 'phones', 'customFieldValues.customField', 'mergedChildren']);

            return [
                'merge' => $mergeRecord,
                'master' => $master,
                'secondary' => $secondary,
                'plan' => $plan,
            ];
        });

        return $result;
    }

    protected function analyzeAttributes(array &$plan, Contact $master, Contact $secondary): void
    {
        $attributes = ['name', 'email', 'phone', 'gender'];

        foreach ($attributes as $attribute) {
            $masterValue = $this->normalizeAttribute($master->{$attribute} ?? null);
            $secondaryValue = $this->normalizeAttribute($secondary->{$attribute} ?? null);

            if ($attribute === 'name' && $masterValue) {
                // Always retain the master name to avoid overwriting the main identifier.
                $plan['attributes_retained'][$attribute] = [
                    'master_value' => $masterValue,
                    'secondary_value' => $secondaryValue,
                ];
                continue;
            }

            if (!$masterValue && $secondaryValue) {
                $plan['attributes_to_fill'][$attribute] = [
                    'value' => $secondaryValue,
                    'from_contact_id' => $secondary->id,
                ];
            } elseif ($secondaryValue) {
                $plan['attributes_retained'][$attribute] = [
                    'master_value' => $masterValue,
                    'secondary_value' => $secondaryValue,
                ];
            }
        }
    }

    protected function analyzeEmails(array &$plan, Contact $master, Contact $secondary): void
    {
        $masterEmails = $this->collectEmails($master->emails, $master->email);
        $secondaryEmails = $this->collectEmails($secondary->emails, $secondary->email);

        foreach ($secondaryEmails as $emailPayload) {
            $emailLower = strtolower($emailPayload['email']);

            if (isset($plan['attributes_to_fill']['email']) &&
                strtolower($plan['attributes_to_fill']['email']['value']) === $emailLower) {
                // This email will become the master primary, no need to append separately.
                continue;
            }

            if (!array_key_exists($emailLower, $masterEmails)) {
                $plan['emails_to_append'][] = [
                    'email' => $emailPayload['email'],
                    'is_primary' => $emailPayload['is_primary'],
                    'origin_contact_id' => $secondary->id,
                ];
            }
        }
    }

    protected function analyzePhones(array &$plan, Contact $master, Contact $secondary): void
    {
        $masterPhones = $this->collectPhones($master->phones, $master->phone);
        $secondaryPhones = $this->collectPhones($secondary->phones, $secondary->phone);

        foreach ($secondaryPhones as $phonePayload) {
            $phoneKey = $phonePayload['normalized'] ?? $phonePayload['phone'];

            if (isset($plan['attributes_to_fill']['phone']) &&
                $plan['attributes_to_fill']['phone']['value'] === $phonePayload['phone']) {
                continue;
            }

            if (!array_key_exists($phoneKey, $masterPhones)) {
                $plan['phones_to_append'][] = [
                    'phone' => $phonePayload['phone'],
                    'normalized' => $phoneKey,
                    'is_primary' => $phonePayload['is_primary'],
                    'origin_contact_id' => $secondary->id,
                ];
            }
        }
    }

    protected function analyzeCustomFields(array &$plan, Contact $master, Contact $secondary): void
    {
        $masterPrimary = $master->customFieldValues->where('is_primary', true)->keyBy('custom_field_id');
        $secondaryPrimary = $secondary->customFieldValues->where('is_primary', true);

        foreach ($secondaryPrimary as $value) {
            $fieldId = $value->custom_field_id;
            $fieldName = $value->customField?->name ?? 'Custom Field ' . $fieldId;
            $secondaryVal = $this->normalizeAttribute($value->value);
            $masterVal = $this->normalizeAttribute(optional($masterPrimary->get($fieldId))->value ?? null);

            if (!$masterVal && $secondaryVal) {
                $plan['custom_fields'][] = [
                    'custom_field_id' => $fieldId,
                    'field_name' => $fieldName,
                    'action' => 'adopt',
                    'value' => $secondaryVal,
                    'origin_contact_id' => $secondary->id,
                ];
                continue;
            }

            if ($secondaryVal && $secondaryVal !== $masterVal) {
                $plan['custom_fields'][] = [
                    'custom_field_id' => $fieldId,
                    'field_name' => $fieldName,
                    'action' => 'append',
                    'value' => $secondaryVal,
                    'origin_contact_id' => $secondary->id,
                ];
            }
        }
    }

    protected function analyzeFiles(array &$plan, Contact $master, Contact $secondary): void
    {
        $plan['files']['profile_image'] = [
            'master_path' => $master->profile_image_path,
            'secondary_path' => $secondary->profile_image_path,
            'action' => $master->profile_image_path ? 'retain_master' :
                ($secondary->profile_image_path ? 'adopt_secondary' : 'none'),
        ];

        $plan['files']['document'] = [
            'master_path' => $master->document_path,
            'secondary_path' => $secondary->document_path,
            'action' => $master->document_path ? 'retain_master' :
                ($secondary->document_path ? 'adopt_secondary' : 'none'),
        ];
    }

    protected function appendEmail(Contact $master, array $payload): void
    {
        $email = $payload['email'] ?? null;

        if (!$email) {
            return;
        }

        $exists = $master->emails()
            ->where('email', $email)
            ->exists();

        if ($exists) {
            return;
        }

        $master->emails()->create([
            'email' => $email,
            'is_primary' => false,
            'origin_contact_id' => $payload['origin_contact_id'] ?? null,
        ]);
    }

    protected function appendPhone(Contact $master, array $payload): void
    {
        $phone = $payload['phone'] ?? null;

        if (!$phone) {
            return;
        }

        $exists = $master->phones()
            ->where('phone', $phone)
            ->exists();

        if ($exists) {
            return;
        }

        $master->phones()->create([
            'phone' => $phone,
            'is_primary' => false,
            'origin_contact_id' => $payload['origin_contact_id'] ?? null,
        ]);
    }

    protected function applyCustomFieldsPlan(Contact $master, Contact $secondary, array $items): void
    {
        $adoptPayload = [];

        foreach ($items as $item) {
            if ($item['action'] === 'adopt') {
                $adoptPayload[$item['custom_field_id']] = $item['value'];
            }
        }

        if (!empty($adoptPayload)) {
            $this->syncCustomFields($master, $adoptPayload);
        }

        foreach ($items as $item) {
            if ($item['action'] !== 'append') {
                continue;
            }

            $exists = $master->customFieldValues()
                ->where('custom_field_id', $item['custom_field_id'])
                ->where('value', $item['value'])
                ->exists();

            if ($exists) {
                continue;
            }

            $master->customFieldValues()->create([
                'custom_field_id' => $item['custom_field_id'],
                'value' => $item['value'],
                'is_primary' => false,
                'origin_contact_id' => $item['origin_contact_id'] ?? $secondary->id,
            ]);
        }

        // Attach all remaining secondary custom values as historical records.
        $secondary->customFieldValues()
            ->where('is_primary', false)
            ->get()
            ->each(function (ContactCustomFieldValue $value) use ($master, $secondary) {
                $exists = $master->customFieldValues()
                    ->where('custom_field_id', $value->custom_field_id)
                    ->where('value', $value->value)
                    ->exists();

                if ($exists) {
                    return;
                }

                $master->customFieldValues()->create([
                    'custom_field_id' => $value->custom_field_id,
                    'value' => $value->value,
                    'is_primary' => false,
                    'origin_contact_id' => $secondary->id,
                ]);
            });
    }

    protected function applyFilePlan(Contact $master, array $filePlan, Contact $secondary): void
    {
        foreach ($filePlan as $key => $plan) {
            if (($plan['action'] ?? 'none') !== 'adopt_secondary') {
                continue;
            }

            $path = $plan['secondary_path'] ?? null;
            if (!$path) {
                continue;
            }

            if ($key === 'profile_image') {
                $master->profile_image_path = $path;
            }

            if ($key === 'document') {
                $master->document_path = $path;
            }
        }

        $master->save();
    }

    protected function collectEmails(Collection $emails, ?string $primaryEmail): array
    {
        $collection = [];

        if ($primaryEmail) {
            $collection[strtolower($primaryEmail)] = [
                'email' => $primaryEmail,
                'is_primary' => true,
            ];
        }

        foreach ($emails as $email) {
            $collection[strtolower($email->email)] = [
                'email' => $email->email,
                'is_primary' => (bool) $email->is_primary,
            ];
        }

        return $collection;
    }

    protected function collectPhones(Collection $phones, ?string $primaryPhone): array
    {
        $collection = [];

        if ($primaryPhone) {
            $collection[$this->normalizePhone($primaryPhone)] = [
                'phone' => $primaryPhone,
                'normalized' => $this->normalizePhone($primaryPhone),
                'is_primary' => true,
            ];
        }

        foreach ($phones as $phone) {
            $normalized = $this->normalizePhone($phone->phone);
            $collection[$normalized] = [
                'phone' => $phone->phone,
                'normalized' => $normalized,
                'is_primary' => (bool) $phone->is_primary,
            ];
        }

        return $collection;
    }

    protected function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone ?? '') ?? '';
    }

    protected function normalizeAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
