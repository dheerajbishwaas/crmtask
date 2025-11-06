<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Models\CustomField;
use App\Support\Concerns\SynchronizesContactData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContactController extends Controller
{
    use SynchronizesContactData;

    public function index(Request $request)
    {
        $contacts = $this->baseQuery()
            ->with(['emails', 'phones', 'customFieldValues.customField'])
            ->orderByDesc('created_at')
            ->get();

        $customFields = CustomField::active()
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => Contact::count(),
            'active' => Contact::whereNull('merged_into_id')->where('status', 'active')->count(),
            'merged' => Contact::where('status', 'merged')->count(),
            'custom_fields' => CustomField::count(),
        ];

        return view('contacts.index', compact('contacts', 'customFields', 'stats'));
    }

    public function list(Request $request): JsonResponse
    {
        $contacts = $this->applyFilters($request, $this->baseQuery())
            ->with(['emails', 'phones', 'customFieldValues.customField', 'mergedChildren'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'html' => view('contacts.partials.table_rows', [
                'contacts' => $contacts,
            ])->render(),
            'count' => $contacts->count(),
        ]);
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $data = $request->validated();

        $contact = DB::transaction(function () use ($request, $data) {
            $contactData = Arr::only($data, [
                'name',
                'email',
                'phone',
                'gender',
            ]);

            $contactData['profile_image_path'] = $this->uploadFile($request, 'profile_image');
            $contactData['document_path'] = $this->uploadFile($request, 'document');

            /** @var \App\Models\Contact $contact */
            $contact = Contact::create($contactData);

            $this->syncPrimaryEmail($contact, $contactData['email'] ?? null);
            $this->syncPrimaryPhone($contact, $contactData['phone'] ?? null);
            $this->syncCustomFields($contact, Arr::get($data, 'custom_fields', []));

            return $contact;
        });

        $contact->load(['emails', 'phones', 'customFieldValues.customField']);

        return response()->json([
            'message' => 'Contact created successfully.',
            'row' => view('contacts.partials.contact_row', [
                'contact' => $contact,
            ])->render(),
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        $data = $request->validated();

        $contact = DB::transaction(function () use ($request, $contact, $data) {
            $contactData = Arr::only($data, [
                'name',
                'email',
                'phone',
                'gender',
            ]);

            $contactData['profile_image_path'] = $this->uploadFile($request, 'profile_image', $contact->profile_image_path);
            $contactData['document_path'] = $this->uploadFile($request, 'document', $contact->document_path);

            $contact->fill($contactData);
            $contact->save();

            $this->syncPrimaryEmail($contact, $contactData['email'] ?? null);
            $this->syncPrimaryPhone($contact, $contactData['phone'] ?? null);
            $this->syncCustomFields($contact, Arr::get($data, 'custom_fields', []));

            return $contact;
        });

        $contact->load(['emails', 'phones', 'customFieldValues.customField']);

        return response()->json([
            'message' => 'Contact updated successfully.',
            'row' => view('contacts.partials.contact_row', [
                'contact' => $contact,
            ])->render(),
        ]);
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $contact->status = 'archived';
        $contact->save();
        $contact->delete();

        return response()->json([
            'message' => 'Contact archived successfully.',
        ]);
    }

    protected function baseQuery()
    {
        return Contact::query()
            ->whereNull('merged_into_id')
            ->where('status', '!=', 'merged');
    }

    protected function applyFilters(Request $request, $query)
    {
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->filled('email')) {
            $email = $request->input('email');
            $query->where(function ($q) use ($email) {
                $q->where('email', 'like', '%' . $email . '%')
                    ->orWhereHas('emails', function ($sub) use ($email) {
                        $sub->where('email', 'like', '%' . $email . '%');
                    });
            });
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->input('gender'));
        }

        if ($request->filled('custom_field_id') && $request->filled('custom_field_value')) {
            $fieldId = (int) $request->input('custom_field_id');
            $value = $request->input('custom_field_value');

            $query->whereHas('customFieldValues', function ($q) use ($fieldId, $value) {
                $q->where('custom_field_id', $fieldId)
                    ->where('value', 'like', '%' . $value . '%');
            });
        }

        return $query;
    }

    protected function uploadFile(Request $request, string $key, ?string $existingPath = null): ?string
    {
        if (!$request->hasFile($key)) {
            return $existingPath;
        }

        $file = $request->file($key);
        $path = $file->store('uploads/' . $key, 'public');

        if ($existingPath && Storage::disk('public')->exists($existingPath)) {
            Storage::disk('public')->delete($existingPath);
        }

        return $path;
    }

}
