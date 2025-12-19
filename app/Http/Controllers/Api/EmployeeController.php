<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(private EmployeeService $service) {}

    /**
     * GET /api/employees
     * List all employees with filtering
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'q',
            'department',
            'status',
            'employment_type',
            'per_page'
        ]);

        $employees = $this->service->paginate($filters);
        return EmployeeResource::collection($employees);
    }

    /**
     * POST /api/employees
     * Create a new employee
     */
    public function store(StoreEmployeeRequest $request)
    {
        $employee = $this->service->create($request->validated());
        return response()->json(EmployeeResource::make($employee), 201);
    }

    /**
     * GET /api/employees/{employee}
     * Get a specific employee
     */
    public function show(Employee $employee)
    {
        $employee->load('salarySetup');
        return EmployeeResource::make($employee);
    }

    /**
     * PUT /api/employees/{employee}
     * Update an employee
     */
    public function update(StoreEmployeeRequest $request, Employee $employee)
    {
        $updated = $this->service->update($employee->id, $request->validated());
        return EmployeeResource::make($updated);
    }

    /**
     * DELETE /api/employees/{employee}
     * Delete an employee (soft delete)
     */
    public function destroy(Employee $employee)
    {
        $this->service->delete($employee);
        return response()->noContent();
    }

    /**
     * POST /api/employees/{employee}/restore
     * Restore a soft-deleted employee
     */
    public function restore(int $id)
    {
        $employee = $this->service->restore($id);
        return EmployeeResource::make($employee);
    }
}
