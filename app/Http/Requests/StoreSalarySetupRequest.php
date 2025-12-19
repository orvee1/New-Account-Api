<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalarySetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'house_rent_allowance' => ['nullable', 'numeric', 'min:0'],
            'medical_allowance' => ['nullable', 'numeric', 'min:0'],
            'conveyance_allowance' => ['nullable', 'numeric', 'min:0'],
            'other_allowances' => ['nullable', 'numeric', 'min:0'],
            'provident_fund_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'provident_fund_amount' => ['nullable', 'numeric', 'min:0'],
            'income_tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'income_tax_amount' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $in = collect($this->all());
        $map = [
            'employeeId' => 'employee_id',
            'basicSalary' => 'basic_salary',
            'houseRentAllowance' => 'house_rent_allowance',
            'medicalAllowance' => 'medical_allowance',
            'conveyanceAllowance' => 'conveyance_allowance',
            'otherAllowances' => 'other_allowances',
            'providentFundPercent' => 'provident_fund_percent',
            'providentFundAmount' => 'provident_fund_amount',
            'incomeTaxPercent' => 'income_tax_percent',
            'incomeTaxAmount' => 'income_tax_amount',
            'otherDeductions' => 'other_deductions',
            'effectiveDate' => 'effective_date',
        ];
        foreach ($map as $camel => $snake) {
            if ($in->has($camel) && !$in->has($snake)) {
                $this->merge([$snake => $in->get($camel)]);
            }
        }
    }
}
