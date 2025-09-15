<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $id = $this->route('customer'); // model binding or id

        return [
            'name'              => ['sometimes','required','string','max:255'],
            'display_name'      => ['sometimes','nullable','string','max:255'],
            'proprietor_name'   => ['sometimes','nullable','string','max:255'],
            // customer_number locked (cannot change): prohibit if present
            'customer_number'   => ['prohibited'],
            'phone_number'      => ['sometimes','nullable','string','max:50'],
            'email'             => ['sometimes','nullable','email','max:255'],
            'address'           => ['sometimes','nullable','string','max:255'],
            'nid'               => ['sometimes','nullable','string','max:100'],
            'bank_details'      => ['sometimes','nullable','string'],
            'notes'             => ['sometimes','nullable','string'],

            'credit_limit'      => ['sometimes','nullable','numeric','min:0'],

            // Opening balance fields are locked after create
            'opening_balance'   => ['prohibited'],
            'opening_balance_date' => ['prohibited'],
        ];
    }

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
