<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalarySetup;

class SalarySetupService
{
    public function paginate(array $filters)
    {
        $query = SalarySetup::query()
            ->where('company_id', auth()->user()->company_id)
            ->with('employee')
            ->when($filters['q'] ?? null, function ($q) use ($filters) {
                $keyword = "%{$filters['q']}%";
                $q->whereHas(
                    'employee',
                    fn($eq) =>
                    $eq->where('first_name', 'like', $keyword)
                        ->orWhere('last_name', 'like', $keyword)
                        ->orWhere('email', 'like', $keyword)
                );
            })
            ->when($filters['status'] ?? null, fn($q) => $q->where('status', $filters['status']))
            ->when($filters['employee_id'] ?? null, fn($q) => $q->where('employee_id', $filters['employee_id']))
            ->orderByDesc('id');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data): SalarySetup
    {
        $data['company_id'] = auth()->user()->company_id;
        $data['created_by'] = auth()->id();

        // Calculate gross salary
        $data['gross_salary'] = $this->calculateGrossSalary($data);

        // Calculate deductions
        if (!isset($data['provident_fund_amount']) || $data['provident_fund_amount'] === null) {
            $data['provident_fund_amount'] = ($data['provident_fund_percent'] ?? 0) / 100 * $data['gross_salary'];
        }

        if (!isset($data['income_tax_amount']) || $data['income_tax_amount'] === null) {
            $data['income_tax_amount'] = ($data['income_tax_percent'] ?? 0) / 100 * $data['gross_salary'];
        }

        // Calculate net salary
        $data['net_salary'] = $this->calculateNetSalary($data);

        return SalarySetup::create($data);
    }

    public function update(int $id, array $data): SalarySetup
    {
        $salarySetup = SalarySetup::findOrFail($id);
        $data['updated_by'] = auth()->id();

        // Recalculate gross salary if any component changed
        $data['gross_salary'] = $this->calculateGrossSalary($data);

        // Recalculate deductions
        if (!isset($data['provident_fund_amount']) || $data['provident_fund_amount'] === null) {
            $data['provident_fund_amount'] = ($data['provident_fund_percent'] ?? 0) / 100 * $data['gross_salary'];
        }

        if (!isset($data['income_tax_amount']) || $data['income_tax_amount'] === null) {
            $data['income_tax_amount'] = ($data['income_tax_percent'] ?? 0) / 100 * $data['gross_salary'];
        }

        // Recalculate net salary
        $data['net_salary'] = $this->calculateNetSalary($data);

        $salarySetup->update($data);

        return $salarySetup;
    }

    public function delete(SalarySetup $salarySetup): void
    {
        $salarySetup->delete();
    }

    public function restore(int $id): SalarySetup
    {
        $salarySetup = SalarySetup::withTrashed()->findOrFail($id);
        $salarySetup->restore();

        return $salarySetup;
    }

    private function calculateGrossSalary(array $data): float
    {
        return ($data['basic_salary'] ?? 0)
            + ($data['house_rent_allowance'] ?? 0)
            + ($data['medical_allowance'] ?? 0)
            + ($data['conveyance_allowance'] ?? 0)
            + ($data['other_allowances'] ?? 0);
    }

    private function calculateNetSalary(array $data): float
    {
        return $data['gross_salary']
            - ($data['provident_fund_amount'] ?? 0)
            - ($data['income_tax_amount'] ?? 0)
            - ($data['other_deductions'] ?? 0);
    }
}
