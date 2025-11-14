<?php
namespace App\Http\Requests;

use App\Models\ChartAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateChartAccountRequest extends FormRequest
{
    public function authorize(): bool
    {return true;}

    public function rules(): array
    {
        $companyId = Auth::user()?->company_id;

        // route model binding: chart_account
        /** @var ChartAccount|null $model */
        $model = $this->route('chart_account');
        $id    = $model?->id ?? $this->input('id');

        return [
            'account_no'        => [
                'nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9.\-\/\s]+$/',
                Rule::unique('chart_accounts', 'account_no')
                    ->ignore($id)
                    ->where(fn($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')),
            ],
            'account_name'      => ['sometimes', 'required', 'string', 'max:255'],

            'node_type'         => ['sometimes', 'required', Rule::in(['group', 'ledger'])],
            'major_type'        => ['nullable', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])],

            'account_type'      => ['nullable', 'string', 'max:255'],
            'detail_type'       => ['nullable', 'string', 'max:255'],

            'parent_account_id' => [
                'nullable', 'integer',
                Rule::exists('chart_accounts', 'id')
                    ->where(fn($q) => $q->where('company_id', $companyId)->whereNull('deleted_at')),
                Rule::notIn([$id]), // নিজের parent হওয়া যাবে না
            ],

            'is_active'         => ['sometimes', 'boolean'],
            'opening_balance'   => ['sometimes', 'numeric', 'between:-999999999999999999.99,999999999999999999.99'],
            'opening_date'      => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_no.unique'            => 'This account number already exists for the company.',
            'parent_account_id.exists'     => 'Selected parent account is invalid for this company.',
            'parent_account_id.not_in'     => 'An account cannot be its own parent.',
            'opening_date.before_or_equal' => 'Opening date cannot be in the future.',
            'account_no.regex'             => 'Account number may contain letters, numbers, dot, dash, slash and spaces only.',
        ];
    }
}
