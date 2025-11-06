<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomFieldRequest;
use App\Models\CustomField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomFieldController extends Controller
{
    public function index()
    {
        $customFields = CustomField::orderBy('name')->get();

        return view('custom-fields.index', compact('customFields'));
    }

    public function store(StoreCustomFieldRequest $request): JsonResponse
    {
        $data = $request->validated();
        $slug = Str::slug($data['name']);

        if (CustomField::where('slug', $slug)->exists()) {
            return response()->json([
                'message' => 'A custom field with a similar name already exists.',
            ], 422);
        }

        $customField = CustomField::create([
            'name' => $data['name'],
            'slug' => $slug,
            'field_type' => $data['field_type'],
            'options' => $this->normalizeOptions($data['options'] ?? []),
            'is_required' => (bool) ($data['is_required'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return response()->json([
            'message' => 'Custom field created successfully.',
            'row' => view('custom-fields.partials.field_row', [
                'field' => $customField,
            ])->render(),
        ]);
    }

    public function update(Request $request, CustomField $customField): JsonResponse
    {
        $data = $request->validate([
            'is_required' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $customField->fill($data);
        $customField->save();

        return response()->json([
            'message' => 'Custom field updated successfully.',
        ]);
    }

    public function destroy(CustomField $customField): JsonResponse
    {
        if ($customField->values()->exists()) {
            return response()->json([
                'message' => 'Cannot delete this field while it has existing contact values. Consider deactivating it instead.',
            ], 422);
        }

        $customField->delete();

        return response()->json([
            'message' => 'Custom field deleted successfully.',
        ]);
    }

    protected function normalizeOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $option) {
            $option = trim((string) $option);
            if ($option !== '') {
                $normalized[] = $option;
            }
        }

        return $normalized;
    }
}
