<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreChartAccountRequest extends FormRequest
{
    public function authorize(): bool
    {return true;}

    public function rules(): array
    {
        $companyId = Auth::user()?->company_id;

        return [
            'account_no'        => [
                'nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9.\-\/\s]+$/',
                Rule::unique('chart_accounts', 'account_no')
                    ->where(fn($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')),
            ],
            'account_name'      => ['required', 'string', 'max:255'],

            'node_type'         => ['required', Rule::in(['group', 'ledger'])],
            'major_type'        => ['nullable', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])],

            'account_type'      => ['nullable', 'string', 'max:255'],
            'detail_type'       => ['nullable', 'string', 'max:255'],

            'parent_account_id' => [
                'nullable', 'integer',
                Rule::exists('chart_accounts', 'id')
                    ->where(fn($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')),
            ],

            'is_active'         => ['sometimes', 'boolean'],
            'opening_balance'   => ['sometimes', 'numeric', 'between:-999999999999999999.99,999999999999999999.99'],
            'opening_date'      => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'account_no'        => 'account number',
            'account_name'      => 'account name',
            'node_type'         => 'node type',
            'major_type'        => 'major type',
            'parent_account_id' => 'parent account',
        ];
    }

    public function messages(): array
    {
        return [
            'account_no.unique'            => 'This account number already exists for the company.',
            'parent_account_id.exists'     => 'Selected parent account is invalid for this company.',
            'opening_date.before_or_equal' => 'Opening date cannot be in the future.',
            'account_no.regex'             => 'Account number may contain letters, numbers, dot, dash, slash and spaces only.',
        ];
    }
}
