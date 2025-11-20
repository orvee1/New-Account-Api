<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $companyId = Auth::user()->company_id;

        return [
            'name'              => ['required','string','max:255'],
            'display_name'      => ['nullable','string','max:255'],
            'proprietor_name'   => ['nullable','string','max:255'],
            'customer_number'   => [
                'nullable','string','max:50',
                Rule::unique('customers','customer_number')->where('company_id', $companyId)
            ],
            'phone_number'      => ['nullable','string','max:50'],
            'email'             => ['nullable','email','max:255'],
            'address'           => ['nullable','string','max:255'],
            'nid'               => ['nullable','string','max:100'],
            'bank_details'      => ['nullable','string'],
            'notes'             => ['nullable','string'],

            'credit_limit'      => ['nullable','numeric','min:0'],
            'opening_balance'   => ['nullable','numeric','min:0'],
            'opening_balance_date' => ['nullable','date'],
        ];
    }

    // accept both camelCase/snake_case
    protected function prepareForValidation(): void
    {
        $in = collect($this->all());
        $map = [
            'displayName' => 'display_name',
            'proprietorName' => 'proprietor_name',
            'customerNumber' => 'customer_number',
            'phoneNumber' => 'phone_number',
            'bankDetails' => 'bank_details',
            'openingBalance' => 'opening_balance',
            'openingBalanceDate' => 'opening_balance_date',
            'creditLimit' => 'credit_limit',
        ];
        foreach ($map as $camel => $snake) {
            if ($in->has($camel) && !$in->has($snake)) {
                $this->merge([$snake => $in->get($camel)]);
            }
        }
    }
}
