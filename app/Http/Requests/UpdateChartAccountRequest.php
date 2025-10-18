<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateChartAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->input('company_id', Auth::user()?->company_id);

        // Route model binding থাকলে: $this->route('chart_account')
        $model = $this->route('chart_account') ?? null;
        $id = is_object($model) ? $model->id : ($model ?: $this->input('id'));

        return [

            'account_no' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9.\-\/\s]+$/',
                Rule::unique('chart_accounts', 'account_no')
                    ->ignore($id) // নিজের রেকর্ড বাদ
                    ->where(fn($q) => $q
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at')
                    ),
            ],

            'account_name' => ['required', 'string', 'max:255'],

            'account_type' => ['required', 'string', 'max:255'],

            'detail_type' => ['nullable', 'string', 'max:255'],

            'parent_account_id' => [
                'nullable',
                'integer',
                'min:1',
                // parent অবশ্যই same company + not deleted
                Rule::exists('chart_accounts', 'id')
                    ->where(fn($q) => $q
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at')
                    ),
                // নিজেকে parent করা যাবে না
                Rule::notIn([$id]),
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
            'parent_account_id.not_in' => 'An account cannot be its own parent.',
            'opening_date.before_or_equal' => 'Opening date cannot be in the future.',
            'account_no.regex' => 'Account number may contain letters, numbers, dot, dash, slash and spaces only.',
        ];
    }

    /**
     * Extra logical check: prevent circular reference if needed (optional).
     * Use Validator::after() hook if you want deep cycle detection.
     */
    protected function passedValidation(): void
    {
        // এখানে চাইলে circular parent chain validation যোগ করতে পারো।
    }
}
