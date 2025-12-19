<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalarySetupRequest;
use App\Http\Resources\SalarySetupResource;
use App\Models\SalarySetup;
use App\Services\SalarySetupService;
use Illuminate\Http\Request;

class SalarySetupController extends Controller
{
    public function __construct(private SalarySetupService $service) {}

    /**
     * GET /api/salary-setups
     * List all salary setups with filtering
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'q',
            'status',
            'employee_id',
            'per_page'
        ]);

        $salarySetups = $this->service->paginate($filters);
        return SalarySetupResource::collection($salarySetups);
    }

    /**
     * POST /api/salary-setups
     * Create a new salary setup
     */
    public function store(StoreSalarySetupRequest $request)
    {
        $salarySetup = $this->service->create($request->validated());
        return response()->json(SalarySetupResource::make($salarySetup), 201);
    }

    /**
     * GET /api/salary-setups/{salarySetup}
     * Get a specific salary setup
     */
    public function show(SalarySetup $salarySetup)
    {
        $salarySetup->load('employee');
        return SalarySetupResource::make($salarySetup);
    }

    /**
     * PUT /api/salary-setups/{salarySetup}
     * Update a salary setup
     */
    public function update(StoreSalarySetupRequest $request, SalarySetup $salarySetup)
    {
        $updated = $this->service->update($salarySetup->id, $request->validated());
        return SalarySetupResource::make($updated);
    }

    /**
     * DELETE /api/salary-setups/{salarySetup}
     * Delete a salary setup (soft delete)
     */
    public function destroy(SalarySetup $salarySetup)
    {
        $this->service->delete($salarySetup);
        return response()->noContent();
    }

    /**
     * POST /api/salary-setups/{salarySetup}/restore
     * Restore a soft-deleted salary setup
     */
    public function restore(int $id)
    {
        $salarySetup = $this->service->restore($id);
        return SalarySetupResource::make($salarySetup);
    }
}
