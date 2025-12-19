<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\SalarySetup;
use Illuminate\Support\Facades\DB;

class PayrollRunService
{
    public function paginate(array $filters)
    {
        $query = PayrollRun::query()
            ->where('company_id', auth()->user()->company_id)
            ->with(['employees', 'payslips'])
            ->when($filters['q'] ?? null, function ($q) use ($filters) {
                $keyword = "%{$filters['q']}%";
                $q->where('payroll_run_number', 'like', $keyword);
            })
            ->when($filters['status'] ?? null, fn($q) => $q->where('status', $filters['status']))
            ->when($filters['month'] ?? null, fn($q) => $q->where('payroll_month', $filters['month']))
            ->when($filters['year'] ?? null, fn($q) => $q->where('payroll_year', $filters['year']))
            ->orderByDesc('id');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data): PayrollRun
    {
        return DB::transaction(function () use ($data) {
            $companyId = auth()->user()->company_id;
            $employeeIds = $data['employee_ids'];

            // Generate payroll run number
            $lastRun = PayrollRun::where('company_id', $companyId)
                ->latest('id')
                ->first();

            $runNumber = $lastRun
                ? 'PR-' . str_pad((intval(substr($lastRun->payroll_run_number, 3)) + 1), 6, '0', STR_PAD_LEFT)
                : 'PR-' . str_pad(1, 6, '0', STR_PAD_LEFT);

            $totalGross = 0;
            $totalDeductions = 0;

            // Create payroll run
            $payrollRun = PayrollRun::create([
                'company_id' => $companyId,
                'payroll_run_number' => $runNumber,
                'payroll_month' => $data['payroll_month'],
                'payroll_year' => $data['payroll_year'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'payment_date' => $data['payment_date'] ?? null,
                'total_employees' => count($employeeIds),
                'total_gross_salary' => 0,
                'total_deductions' => 0,
                'total_net_salary' => 0,
                'status' => 'pending',
                'processing_notes' => $data['processing_notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Attach employees and create payslips
            foreach ($employeeIds as $employeeId) {
                $employee = Employee::findOrFail($employeeId);
                $salarySetup = $employee->salarySetup()->active()->first();

                if ($salarySetup) {
                    $grossSalary = $salarySetup->gross_salary;
                    $deductions = $salarySetup->provident_fund_amount
                        + $salarySetup->income_tax_amount
                        + $salarySetup->other_deductions;
                    $netSalary = $salarySetup->net_salary;

                    $totalGross += $grossSalary;
                    $totalDeductions += $deductions;

                    // Attach to payroll run
                    $payrollRun->employees()->attach($employeeId, [
                        'gross_salary' => $grossSalary,
                        'deductions' => $deductions,
                        'net_salary' => $netSalary,
                        'status' => 'pending',
                    ]);

                    // Create payslip
                    Payslip::create([
                        'company_id' => $companyId,
                        'payroll_run_id' => $payrollRun->id,
                        'employee_id' => $employeeId,
                        'payslip_number' => $this->generatePayslipNumber($payrollRun->id, $employeeId),
                        'payslip_date' => now(),
                        'month' => $data['payroll_month'],
                        'year' => $data['payroll_year'],
                        'basic_salary' => $salarySetup->basic_salary,
                        'house_rent_allowance' => $salarySetup->house_rent_allowance,
                        'medical_allowance' => $salarySetup->medical_allowance,
                        'conveyance_allowance' => $salarySetup->conveyance_allowance,
                        'other_allowances' => $salarySetup->other_allowances,
                        'gross_salary' => $grossSalary,
                        'provident_fund' => $salarySetup->provident_fund_amount,
                        'income_tax' => $salarySetup->income_tax_amount,
                        'other_deductions' => $salarySetup->other_deductions,
                        'total_deductions' => $deductions,
                        'net_salary' => $netSalary,
                        'status' => 'generated',
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Update payroll run totals
            $payrollRun->update([
                'total_gross_salary' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net_salary' => $totalGross - $totalDeductions,
            ]);

            return $payrollRun->load(['employees', 'payslips']);
        });
    }

    public function processPayroll(PayrollRun $payrollRun): PayrollRun
    {
        if (!$payrollRun->canProcess()) {
            throw new \Exception('Payroll run cannot be processed in current state');
        }

        return DB::transaction(function () use ($payrollRun) {
            $payrollRun->update([
                'status' => 'processed',
                'processed_by' => auth()->id(),
            ]);

            // Update payslips status
            Payslip::where('payroll_run_id', $payrollRun->id)
                ->update(['status' => 'processed']);

            // Update employee payroll status
            $payrollRun->employees()
                ->updateExistingPivot($payrollRun->employees()->pluck('employees.id'), ['status' => 'processed']);

            return $payrollRun->load(['employees', 'payslips']);
        });
    }

    public function undoPayroll(PayrollRun $payrollRun): PayrollRun
    {
        if (!$payrollRun->canUndo()) {
            throw new \Exception('Payroll run cannot be undone in current state');
        }

        return DB::transaction(function () use ($payrollRun) {
            $payrollRun->update([
                'status' => 'undone',
                'undo_by' => auth()->id(),
            ]);

            // Update payslips status
            Payslip::where('payroll_run_id', $payrollRun->id)
                ->update(['status' => 'undone']);

            // Update employee payroll status
            $payrollRun->employees()
                ->updateExistingPivot($payrollRun->employees()->pluck('employees.id'), ['status' => 'undone']);

            return $payrollRun->load(['employees', 'payslips']);
        });
    }

    public function delete(PayrollRun $payrollRun): void
    {
        $payrollRun->delete();
    }

    private function generatePayslipNumber(int $payrollRunId, int $employeeId): string
    {
        $timestamp = now()->format('YmdHis');
        return "PS-{$payrollRunId}-{$employeeId}-{$timestamp}";
    }
}
