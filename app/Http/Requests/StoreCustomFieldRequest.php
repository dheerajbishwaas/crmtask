<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCustomFieldRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('custom_fields', 'name')],
            'field_type' => ['required', Rule::in(['text', 'number', 'date', 'select'])],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string'],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'field_type' => $this->get('field_type', 'text'),
            'is_required' => filter_var($this->get('is_required', false), FILTER_VALIDATE_BOOLEAN),
            'is_active' => filter_var($this->get('is_active', true), FILTER_VALIDATE_BOOLEAN),
            'slug' => Str::slug($this->get('name', '')),
            'options' => $this->get('options', []),
        ]);
    }
}
