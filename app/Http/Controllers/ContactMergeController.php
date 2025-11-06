<?php

namespace App\Http\Controllers;

use App\Http\Requests\MergeContactsRequest;
use App\Models\Contact;
use App\Services\ContactMerger;
use Illuminate\Http\JsonResponse;

class ContactMergeController extends Controller
{
    public function __construct(private readonly ContactMerger $contactMerger)
    {
    }

    public function preview(MergeContactsRequest $request): JsonResponse
    {
        $master = Contact::findOrFail($request->input('master_contact_id'));
        $secondary = Contact::findOrFail($request->input('secondary_contact_id'));

        if ($secondary->merged_into_id) {
            return response()->json([
                'message' => 'The secondary contact has already been merged into another record.',
            ], 422);
        }

        $plan = $this->contactMerger->analyze($master, $secondary);

        return response()->json([
            'plan' => $plan,
            'modal' => view('contacts.partials.merge_preview', [
                'master' => $master->fresh(['emails', 'phones']),
                'secondary' => $secondary->fresh(['emails', 'phones']),
                'plan' => $plan,
            ])->render(),
        ]);
    }

    public function store(MergeContactsRequest $request): JsonResponse
    {
        $master = Contact::findOrFail($request->input('master_contact_id'));
        $secondary = Contact::findOrFail($request->input('secondary_contact_id'));

        if ($secondary->merged_into_id) {
            return response()->json([
                'message' => 'The secondary contact has already been merged into another record.',
            ], 422);
        }

        $result = $this->contactMerger->merge($master, $secondary);

        $updatedMaster = $result['master'];

        return response()->json([
            'message' => 'Contacts merged successfully.',
            'row' => view('contacts.partials.contact_row', [
                'contact' => $updatedMaster,
            ])->render(),
            'summary' => view('contacts.partials.merge_summary', [
                'plan' => $result['plan'],
                'master' => $updatedMaster,
                'secondary' => $result['secondary'],
            ])->render(),
        ]);
    }
}
