<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'employee_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_number')->where('company_id', $companyId)
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->where('company_id', $companyId)],
            'phone' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'nid_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'department' => ['required', 'string', 'max:100'],
            'designation' => ['required', 'string', 'max:100'],
            'date_of_joining' => ['required', 'date'],
            'employment_type' => ['required', Rule::in(['permanent', 'contract', 'temporary', 'casual'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'ifsc_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $in = collect($this->all());
        $map = [
            'employeeNumber' => 'employee_number',
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'dateOfBirth' => 'date_of_birth',
            'nidNumber' => 'nid_number',
            'dateOfJoining' => 'date_of_joining',
            'employmentType' => 'employment_type',
            'bankAccountNumber' => 'bank_account_number',
            'bankName' => 'bank_name',
            'ifscCode' => 'ifsc_code',
        ];
        foreach ($map as $camel => $snake) {
            if ($in->has($camel) && !$in->has($snake)) {
                $this->merge([$snake => $in->get($camel)]);
            }
        }
    }
}
