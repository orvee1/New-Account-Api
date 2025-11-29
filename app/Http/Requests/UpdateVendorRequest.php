<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => ['sometimes', 'required', 'string', 'max:255'],
            'display_name'         => ['sometimes', 'nullable', 'string', 'max:255'],
            'proprietor_name'      => ['sometimes', 'nullable', 'string', 'max:255'],
            'vendor_number'        => ['prohibited'], // immutable
            'phone_number'         => ['sometimes', 'nullable', 'string', 'max:50'],
            'address'              => ['sometimes', 'nullable', 'string', 'max:500'],
            'nid'                  => ['sometimes', 'nullable', 'string', 'max:50'],
            'email'                => ['sometimes', 'nullable', 'email', 'max:255'],
            'bank_details'         => ['sometimes', 'nullable', 'string'],
            'credit_limit'         => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes'                => ['sometimes', 'nullable', 'string'],
            'opening_balance'      => ['prohibited'], // immutable after create
            'opening_balance_type'      => ['prohibited'], // immutable after create
            'opening_balance_date' => ['prohibited'],
            'custom_fields'        => ['sometimes', 'nullable', 'array'],
            'custom_fields.*.name'    => ['required_with:custom_fields', 'string', 'max:255'],
            'custom_fields.*.value'   => ['nullable'],
            'custom_fields.*.options' => ['nullable', 'string'],
        ];
    }
}
