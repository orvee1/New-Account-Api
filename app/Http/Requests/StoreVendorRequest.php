<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $companyId = auth()->user()?->company_id;

        return [
            'name'                 => ['required','string','max:255'],
            'display_name'         => ['nullable','string','max:255'],
            'proprietor_name'      => ['nullable','string','max:255'],
            'vendor_number'        => ['nullable','string','max:50', "unique:vendors,vendor_number,NULL,id,company_id,{$companyId}"],
            'phone_number'         => ['nullable','string','max:50'],
            'address'              => ['nullable','string','max:500'],
            'nid'                  => ['nullable','string','max:50'],
            'email'                => ['nullable','email','max:255'],
            'bank_details'         => ['nullable','string'],
            'credit_limit'         => ['nullable','numeric','min:0'],
            'notes'                => ['nullable','string'],
            'opening_balance'      => ['nullable','numeric','min:0'],
            'opening_balance_date' => ['nullable','date'],
            'custom_fields'        => ['nullable','array'],
            'custom_fields.*.name'    => ['required_with:custom_fields','string','max:255'],
            'custom_fields.*.value'   => ['nullable'],
            'custom_fields.*.options' => ['nullable','string'],
        ];
    }
}
