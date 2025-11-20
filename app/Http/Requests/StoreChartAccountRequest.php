<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreChartAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // where থেকে company_id নিলে: $this->company_id ?? Auth::user()?->company_id
        $companyId = $this->input('company_id', Auth::user()?->company_id);

        return [

            'account_no' => [
                'required',
                'string',
                'max:20',
                // আলফানিউমেরিক + ডট/ড্যাশ/স্ল্যাশ/স্পেস—প্রয়োজনে টাইট করো
                'regex:/^[A-Za-z0-9.\-\/\s]+$/',
                // unique(company_id, account_no) with soft-deletes respected
                Rule::unique('chart_accounts', 'account_no')
                    ->where(fn($q) => $q
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at')
                    ),
            ],

            'account_name' => ['required', 'string', 'max:255'],

            'account_type' => ['required', 'string', 'max:255'],
            // চাইলে নির্দিষ্ট ENUM/constant ব্যবহার করতে পারো:
            // Rule::in([10,11,12, ...])

            'detail_type' => ['nullable', 'string', 'max:255'],

            'parent_account_id' => [
                'nullable',
                'integer',
                'min:1',
                // parent অবশ্যই একই কোম্পানির, not deleted
                Rule::exists('chart_accounts', 'id')
                    ->where(fn($q) => $q
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at')
                    ),
            ],

            'is_active' => ['sometimes', 'boolean'],

            'opening_balance' => ['sometimes', 'numeric', 'between:-999999999999999999.99,999999999999999999.99'],

            'opening_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_id' => 'company',
            'account_no' => 'account number',
            'account_name' => 'account name',
            'account_type' => 'account type',
            'detail_type' => 'detail type',
            'parent_account_id' => 'parent account',
            'opening_balance' => 'opening balance',
            'opening_date' => 'opening date',
        ];
    }

    public function messages(): array
    {
        return [
            'account_no.unique' => 'This account number already exists for the company.',
            'parent_account_id.exists' => 'Selected parent account is invalid for this company.',
            'opening_date.before_or_equal' => 'Opening date cannot be in the future.',
            'account_no.regex' => 'Account number may contain letters, numbers, dot, dash, slash and spaces only.',
        ];
    }
}
