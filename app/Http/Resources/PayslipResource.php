<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'companyId' => $this->company_id,
            'payrollRunId' => $this->payroll_run_id,
            'employeeId' => $this->employee_id,
            'payslipNumber' => $this->payslip_number,
            'payslipDate' => $this->payslip_date?->toDateString(),
            'month' => $this->month,
            'year' => $this->year,
            'basicSalary' => (float) $this->basic_salary,
            'houseRentAllowance' => (float) $this->house_rent_allowance,
            'medicalAllowance' => (float) $this->medical_allowance,
            'conveyanceAllowance' => (float) $this->conveyance_allowance,
            'otherAllowances' => (float) $this->other_allowances,
            'grossSalary' => (float) $this->gross_salary,
            'providentFund' => (float) $this->provident_fund,
            'incomeTax' => (float) $this->income_tax,
            'otherDeductions' => (float) $this->other_deductions,
            'totalDeductions' => (float) $this->total_deductions,
            'netSalary' => (float) $this->net_salary,
            'status' => $this->status,
            'employee' => $this->when($this->relationLoaded('employee'), fn() => new EmployeeResource($this->employee)),
            'payrollRun' => $this->when($this->relationLoaded('payrollRun'), fn() => new PayrollRunResource($this->payrollRun)),
            'createdBy' => $this->created_by,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'deletedAt' => optional($this->deleted_at)?->toISOString(),
        ];
    }
}
