<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PayslipResource;
use App\Models\Payslip;
use App\Services\PayslipService;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function __construct(private PayslipService $service) {}

    /**
     * GET /api/payslips
     * List all payslips with filtering
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'q',
            'employee_id',
            'month',
            'year',
            'status',
            'payroll_run_id',
            'per_page'
        ]);

        $payslips = $this->service->paginate($filters);
        return PayslipResource::collection($payslips);
    }

    /**
     * GET /api/payslips/{payslip}
     * Get a specific payslip
     */
    public function show(Payslip $payslip)
    {
        $payslip->load(['employee', 'payrollRun']);
        return PayslipResource::make($payslip);
    }

    /**
     * GET /api/payslips/employee/{employeeId}
     * Get all payslips for a specific employee
     */
    public function getByEmployee(Request $request, int $employeeId)
    {
        $filters = $request->only(['month', 'year', 'per_page']);

        $payslips = $this->service->getPayslipsForEmployee($employeeId, $filters);
        return PayslipResource::collection($payslips);
    }

    /**
     * GET /api/payslips/payroll-run/{payrollRunId}
     * Get all payslips for a specific payroll run
     */
    public function getByPayrollRun(Request $request, int $payrollRunId)
    {
        $filters = $request->only(['status', 'per_page']);

        $payslips = $this->service->getPayslipsForPayrollRun($payrollRunId, $filters);
        return PayslipResource::collection($payslips);
    }

    /**
     * GET /api/payslips/reports/monthly
     * Generate monthly payroll report
     */
    public function monthlyReport(Request $request)
    {
        $month = $request->integer('month');
        $year = $request->integer('year');
        $companyId = auth()->user()->company_id;

        $payslips = Payslip::where('company_id', $companyId)
            ->where('month', $month)
            ->where('year', $year)
            ->with('employee')
            ->get();

        $totalGross = $payslips->sum('gross_salary');
        $totalDeductions = $payslips->sum('total_deductions');
        $totalNet = $payslips->sum('net_salary');

        return response()->json([
            'month' => $month,
            'year' => $year,
            'total_employees' => $payslips->count(),
            'total_gross_salary' => (float) $totalGross,
            'total_deductions' => (float) $totalDeductions,
            'total_net_salary' => (float) $totalNet,
            'payslips' => PayslipResource::collection($payslips),
        ]);
    }

    /**
     * GET /api/payslips/reports/employee/{employeeId}
     * Generate payroll report for a specific employee
     */
    public function employeeReport(Request $request, int $employeeId)
    {
        $fromMonth = $request->integer('from_month', 1);
        $fromYear = $request->integer('from_year', date('Y'));
        $toMonth = $request->integer('to_month', 12);
        $toYear = $request->integer('to_year', date('Y'));
        $companyId = auth()->user()->company_id;

        $payslips = Payslip::where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->where(function ($query) use ($fromMonth, $fromYear, $toMonth, $toYear) {
                $query->where('year', '>=', $fromYear)
                    ->where('year', '<=', $toYear);
            })
            ->with(['employee', 'payrollRun'])
            ->get();

        $totalGross = $payslips->sum('gross_salary');
        $totalDeductions = $payslips->sum('total_deductions');
        $totalNet = $payslips->sum('net_salary');

        return response()->json([
            'employee_id' => $employeeId,
            'from_month' => $fromMonth,
            'from_year' => $fromYear,
            'to_month' => $toMonth,
            'to_year' => $toYear,
            'total_payslips' => $payslips->count(),
            'total_gross_salary' => (float) $totalGross,
            'total_deductions' => (float) $totalDeductions,
            'total_net_salary' => (float) $totalNet,
            'payslips' => PayslipResource::collection($payslips),
        ]);
    }

    /**
     * GET /api/payslips/reports/department/{department}
     * Generate payroll report for a specific department
     */
    public function departmentReport(Request $request, string $department)
    {
        $month = $request->integer('month');
        $year = $request->integer('year');
        $companyId = auth()->user()->company_id;

        $payslips = Payslip::where('company_id', $companyId)
            ->where('month', $month)
            ->where('year', $year)
            ->with(['employee'])
            ->get()
            ->filter(fn($p) => $p->employee->department === $department);

        $totalGross = $payslips->sum('gross_salary');
        $totalDeductions = $payslips->sum('total_deductions');
        $totalNet = $payslips->sum('net_salary');

        return response()->json([
            'department' => $department,
            'month' => $month,
            'year' => $year,
            'total_employees' => $payslips->count(),
            'total_gross_salary' => (float) $totalGross,
            'total_deductions' => (float) $totalDeductions,
            'total_net_salary' => (float) $totalNet,
            'payslips' => PayslipResource::collection($payslips),
        ]);
    }
}
