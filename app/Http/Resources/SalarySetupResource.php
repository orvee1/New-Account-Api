<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalarySetupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'companyId' => $this->company_id,
            'employeeId' => $this->employee_id,
            'basicSalary' => (float) $this->basic_salary,
            'houseRentAllowance' => (float) $this->house_rent_allowance,
            'medicalAllowance' => (float) $this->medical_allowance,
            'conveyanceAllowance' => (float) $this->conveyance_allowance,
            'otherAllowances' => (float) $this->other_allowances,
            'grossSalary' => (float) $this->gross_salary,
            'providentFundPercent' => (float) $this->provident_fund_percent,
            'providentFundAmount' => (float) $this->provident_fund_amount,
            'incomeTaxPercent' => (float) $this->income_tax_percent,
            'incomeTaxAmount' => (float) $this->income_tax_amount,
            'otherDeductions' => (float) $this->other_deductions,
            'netSalary' => (float) $this->net_salary,
            'effectiveDate' => $this->effective_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'employee' => $this->when($this->relationLoaded('employee'), fn() => new EmployeeResource($this->employee)),
            'createdBy' => $this->created_by,
            'updatedBy' => $this->updated_by,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'deletedAt' => optional($this->deleted_at)?->toISOString(),
        ];
    }
}
