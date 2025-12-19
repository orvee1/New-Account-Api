<?php

namespace App\Services;

use App\Models\Payslip;

class PayslipService
{
    public function paginate(array $filters)
    {
        $query = Payslip::query()
            ->where('company_id', auth()->user()->company_id)
            ->with(['employee', 'payrollRun'])
            ->when($filters['q'] ?? null, function ($q) use ($filters) {
                $keyword = "%{$filters['q']}%";
                $q->where('payslip_number', 'like', $keyword)
                    ->orWhereHas(
                        'employee',
                        fn($eq) =>
                        $eq->where('first_name', 'like', $keyword)
                            ->orWhere('last_name', 'like', $keyword)
                    );
            })
            ->when($filters['employee_id'] ?? null, fn($q) => $q->where('employee_id', $filters['employee_id']))
            ->when($filters['month'] ?? null, fn($q) => $q->where('month', $filters['month']))
            ->when($filters['year'] ?? null, fn($q) => $q->where('year', $filters['year']))
            ->when($filters['status'] ?? null, fn($q) => $q->where('status', $filters['status']))
            ->when($filters['payroll_run_id'] ?? null, fn($q) => $q->where('payroll_run_id', $filters['payroll_run_id']))
            ->orderByDesc('id');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function getPayslipsForEmployee(int $employeeId, array $filters = [])
    {
        $query = Payslip::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('employee_id', $employeeId)
            ->with(['payrollRun'])
            ->when($filters['month'] ?? null, fn($q) => $q->where('month', $filters['month']))
            ->when($filters['year'] ?? null, fn($q) => $q->where('year', $filters['year']))
            ->orderByDesc('id');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function getPayslipsForPayrollRun(int $payrollRunId, array $filters = [])
    {
        $query = Payslip::query()
            ->where('company_id', auth()->user()->company_id)
            ->where('payroll_run_id', $payrollRunId)
            ->with(['employee'])
            ->when($filters['status'] ?? null, fn($q) => $q->where('status', $filters['status']))
            ->orderByDesc('id');

        return $query->paginate($filters['per_page'] ?? 20);
    }
}
