<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'companyId' => $this->company_id,
            'payrollRunNumber' => $this->payroll_run_number,
            'payrollMonth' => $this->payroll_month,
            'payrollYear' => $this->payroll_year,
            'startDate' => $this->start_date?->toDateString(),
            'endDate' => $this->end_date?->toDateString(),
            'paymentDate' => $this->payment_date?->toDateString(),
            'totalEmployees' => $this->total_employees,
            'totalGrossSalary' => (float) $this->total_gross_salary,
            'totalDeductions' => (float) $this->total_deductions,
            'totalNetSalary' => (float) $this->total_net_salary,
            'status' => $this->status,
            'processingNotes' => $this->processing_notes,
            'payslips' => $this->when($this->relationLoaded('payslips'), fn() => PayslipResource::collection($this->payslips)),
            'employees' => $this->when($this->relationLoaded('employees'), fn() => EmployeeResource::collection($this->employees)),
            'createdBy' => $this->created_by,
            'processedBy' => $this->processed_by,
            'undoBy' => $this->undo_by,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'deletedAt' => optional($this->deleted_at)?->toISOString(),
        ];
    }
}
