<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'payroll_month' => ['required', 'integer', 'min:1', 'max:12'],
            'payroll_year' => ['required', 'integer', 'min:2000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'payment_date' => ['nullable', 'date'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'processing_notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $in = collect($this->all());
        $map = [
            'payrollMonth' => 'payroll_month',
            'payrollYear' => 'payroll_year',
            'startDate' => 'start_date',
            'endDate' => 'end_date',
            'paymentDate' => 'payment_date',
            'employeeIds' => 'employee_ids',
            'processingNotes' => 'processing_notes',
        ];
        foreach ($map as $camel => $snake) {
            if ($in->has($camel) && !$in->has($snake)) {
                $this->merge([$snake => $in->get($camel)]);
            }
        }
    }
}
