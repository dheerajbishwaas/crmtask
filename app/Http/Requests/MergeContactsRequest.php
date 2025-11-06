<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeContactsRequest extends FormRequest
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
            'master_contact_id' => [
                'required',
                'integer',
                'different:secondary_contact_id',
                Rule::exists('contacts', 'id')->whereNull('deleted_at'),
            ],
            'secondary_contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')->whereNull('deleted_at'),
            ],
            'field_strategy' => ['nullable', 'array'],
            'field_strategy.*' => ['nullable', 'in:master,secondary,append'],
        ];
    }
}
