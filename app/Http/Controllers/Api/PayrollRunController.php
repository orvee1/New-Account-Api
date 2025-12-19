<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollRunRequest;
use App\Http\Resources\PayrollRunResource;
use App\Models\PayrollRun;
use App\Services\PayrollRunService;
use Illuminate\Http\Request;

class PayrollRunController extends Controller
{
    public function __construct(private PayrollRunService $service) {}

    /**
     * GET /api/payroll-runs
     * List all payroll runs with filtering
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'q',
            'status',
            'month',
            'year',
            'per_page'
        ]);

        $payrollRuns = $this->service->paginate($filters);
        return PayrollRunResource::collection($payrollRuns);
    }

    /**
     * POST /api/payroll-runs
     * Create a new payroll run (generates initial payroll data)
     */
    public function store(StorePayrollRunRequest $request)
    {
        $payrollRun = $this->service->create($request->validated());
        return response()->json(PayrollRunResource::make($payrollRun), 201);
    }

    /**
     * GET /api/payroll-runs/{payrollRun}
     * Get a specific payroll run with all employees and payslips
     */
    public function show(PayrollRun $payrollRun)
    {
        $payrollRun->load(['employees', 'payslips']);
        return PayrollRunResource::make($payrollRun);
    }

    /**
     * PUT /api/payroll-runs/{payrollRun}
     * Update a payroll run (only in pending state)
     */
    public function update(StorePayrollRunRequest $request, PayrollRun $payrollRun)
    {
        if ($payrollRun->status !== 'pending') {
            return response()->json(['message' => 'Cannot update payroll run that is not in pending state'], 422);
        }

        $payrollRun->update($request->validated());
        return PayrollRunResource::make($payrollRun->load(['employees', 'payslips']));
    }

    /**
     * DELETE /api/payroll-runs/{payrollRun}
     * Delete a payroll run (only in pending state)
     */
    public function destroy(PayrollRun $payrollRun)
    {
        if ($payrollRun->status !== 'pending') {
            return response()->json(['message' => 'Cannot delete payroll run that is not in pending state'], 422);
        }

        $this->service->delete($payrollRun);
        return response()->noContent();
    }

    /**
     * POST /api/payroll-runs/{payrollRun}/process
     * Process the payroll run (changes status from pending to processed)
     */
    public function process(PayrollRun $payrollRun)
    {
        try {
            $processed = $this->service->processPayroll($payrollRun);
            return response()->json([
                'message' => 'Payroll run processed successfully',
                'data' => PayrollRunResource::make($processed),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/payroll-runs/{payrollRun}/undo
     * Undo a processed payroll run
     */
    public function undo(PayrollRun $payrollRun)
    {
        try {
            $undone = $this->service->undoPayroll($payrollRun);
            return response()->json([
                'message' => 'Payroll run undone successfully',
                'data' => PayrollRunResource::make($undone),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
