<?php

namespace App\Support\Concerns;

use App\Models\Contact;
use App\Models\CustomField;

trait SynchronizesContactData
{
    protected function syncPrimaryEmail(Contact $contact, ?string $email): void
    {
        $primaryEmail = $contact->emails()->primary()->first();

        if (!$email) {
            if ($primaryEmail) {
                $primaryEmail->delete();
            }
            return;
        }

        if ($primaryEmail) {
            $primaryEmail->update(['email' => $email]);
        } else {
            $contact->emails()->create([
                'email' => $email,
                'is_primary' => true,
            ]);
        }
    }

    protected function syncPrimaryPhone(Contact $contact, ?string $phone): void
    {
        $primaryPhone = $contact->phones()->primary()->first();

        if (!$phone) {
            if ($primaryPhone) {
                $primaryPhone->delete();
            }
            return;
        }

        if ($primaryPhone) {
            $primaryPhone->update(['phone' => $phone]);
        } else {
            $contact->phones()->create([
                'phone' => $phone,
                'is_primary' => true,
            ]);
        }
    }

    protected function syncCustomFields(Contact $contact, array $fields): void
    {
        $fieldIds = array_keys($fields);

        if (empty($fieldIds)) {
            return;
        }

        $customFields = CustomField::whereIn('id', $fieldIds)->get()->keyBy('id');

        foreach ($fields as $fieldId => $value) {
            if (!$customFields->has((int) $fieldId)) {
                continue;
            }

            $value = is_array($value) ? json_encode($value) : $value;
            $existing = $contact->customFieldValues()
                ->where('custom_field_id', $fieldId)
                ->primary()
                ->first();

            if ($value === null || $value === '') {
                if ($existing) {
                    $existing->delete();
                }
                continue;
            }

            if ($existing) {
                $existing->update(['value' => $value]);
            } else {
                $contact->customFieldValues()->create([
                    'custom_field_id' => $fieldId,
                    'value' => $value,
                    'is_primary' => true,
                ]);
            }
        }
    }
}
