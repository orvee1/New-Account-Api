<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'companyId' => $this->company_id,
            'employeeNumber' => $this->employee_number,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'fullName' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'dateOfBirth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'nidNumber' => $this->nid_number,
            'address' => $this->address,
            'department' => $this->department,
            'designation' => $this->designation,
            'dateOfJoining' => $this->date_of_joining?->toDateString(),
            'employmentType' => $this->employment_type,
            'status' => $this->status,
            'bankAccountNumber' => $this->bank_account_number,
            'bankName' => $this->bank_name,
            'ifscCode' => $this->ifsc_code,
            'notes' => $this->notes,
            'salarySetup' => $this->when($this->relationLoaded('salarySetup'), fn() => new SalarySetupResource($this->salarySetup)),
            'createdBy' => $this->created_by,
            'updatedBy' => $this->updated_by,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
            'deletedAt' => optional($this->deleted_at)?->toISOString(),
        ];
    }
}
