<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalarySetup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

class EmployeeService
{
    public function paginate(array $filters): Paginator
    {
        $query = Employee::query()
            ->where('company_id', auth()->user()->company_id)
            ->when($filters['q'] ?? null, function ($q) use ($filters) {
                $keyword = "%{$filters['q']}%";
                $q->where('employee_number', 'like', $keyword)
                    ->orWhere('first_name', 'like', $keyword)
                    ->orWhere('last_name', 'like', $keyword)
                    ->orWhere('email', 'like', $keyword)
                    ->orWhere('phone', 'like', $keyword);
            })
            ->when($filters['department'] ?? null, fn($q) => $q->where('department', $filters['department']))
            ->when($filters['status'] ?? null, fn($q) => $q->where('status', $filters['status']))
            ->when($filters['employment_type'] ?? null, fn($q) => $q->where('employment_type', $filters['employment_type']))
            ->with('salarySetup')
            ->orderByDesc('id');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data): Employee
    {
        $data['company_id'] = auth()->user()->company_id;
        $data['created_by'] = auth()->id();

        return Employee::create($data);
    }

    public function update(int $id, array $data): Employee
    {
        $employee = Employee::findOrFail($id);
        $data['updated_by'] = auth()->id();
        $employee->update($data);

        return $employee;
    }

    public function delete(Employee $employee): void
    {
        $employee->delete();
    }

    public function restore(int $id): Employee
    {
        $employee = Employee::withTrashed()->findOrFail($id);
        $employee->restore();

        return $employee;
    }
}
