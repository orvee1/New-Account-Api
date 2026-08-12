<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use Illuminate\Http\Request;

class AccountingPeriodController extends Controller
{
    public function fiscalYears(Request $request)
    {
        return response()->json(
            FiscalYear::query()
                ->where('company_id', auth()->user()->company_id)
                ->with('periods')
                ->orderByDesc('start_date')
                ->paginate($request->input('per_page', 15))
        );
    }

    public function storeFiscalYear(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'create_monthly_periods' => 'nullable|boolean',
        ]);

        $fiscalYear = FiscalYear::create([
            'company_id' => auth()->user()->company_id,
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'open',
            'created_by' => auth()->id(),
        ]);

        if ($request->boolean('create_monthly_periods', true)) {
            $this->createMonthlyPeriods($fiscalYear);
        }

        return response()->json($fiscalYear->load('periods'), 201);
    }

    public function closeFiscalYear(FiscalYear $fiscalYear)
    {
        $this->authorizeFiscalYear($fiscalYear);

        $fiscalYear->update([
            'status' => 'closed',
            'closed_by' => auth()->id(),
            'closed_at' => now(),
        ]);

        $fiscalYear->periods()->where('status', 'open')->update([
            'status' => 'closed',
            'closed_by' => auth()->id(),
            'closed_at' => now(),
        ]);

        return response()->json($fiscalYear->fresh('periods'));
    }

    public function reopenFiscalYear(FiscalYear $fiscalYear)
    {
        $this->authorizeFiscalYear($fiscalYear);

        $fiscalYear->update([
            'status' => 'open',
            'closed_by' => null,
            'closed_at' => null,
        ]);

        return response()->json($fiscalYear->fresh('periods'));
    }

    public function periods(Request $request)
    {
        return response()->json(
            AccountingPeriod::query()
                ->where('company_id', auth()->user()->company_id)
                ->when($request->filled('fiscal_year_id'), fn($q) => $q->where('fiscal_year_id', $request->integer('fiscal_year_id')))
                ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
                ->orderBy('start_date')
                ->paginate($request->input('per_page', 20))
        );
    }

    public function closePeriod(AccountingPeriod $accountingPeriod)
    {
        $this->authorizePeriod($accountingPeriod);

        $accountingPeriod->update([
            'status' => 'closed',
            'closed_by' => auth()->id(),
            'closed_at' => now(),
        ]);

        return response()->json($accountingPeriod->fresh('fiscalYear'));
    }

    public function lockPeriod(AccountingPeriod $accountingPeriod)
    {
        $this->authorizePeriod($accountingPeriod);

        $accountingPeriod->update([
            'status' => 'locked',
            'closed_by' => auth()->id(),
            'closed_at' => now(),
        ]);

        return response()->json($accountingPeriod->fresh('fiscalYear'));
    }

    public function reopenPeriod(AccountingPeriod $accountingPeriod)
    {
        $this->authorizePeriod($accountingPeriod);

        $accountingPeriod->update([
            'status' => 'open',
            'closed_by' => null,
            'closed_at' => null,
        ]);

        return response()->json($accountingPeriod->fresh('fiscalYear'));
    }

    private function createMonthlyPeriods(FiscalYear $fiscalYear): void
    {
        $cursor = $fiscalYear->start_date->copy()->startOfMonth();
        $end = $fiscalYear->end_date->copy();

        while ($cursor <= $end) {
            $periodStart = $cursor->lessThan($fiscalYear->start_date)
                ? $fiscalYear->start_date->copy()
                : $cursor->copy();
            $monthEnd = $cursor->copy()->endOfMonth();
            $periodEnd = $monthEnd->greaterThan($fiscalYear->end_date)
                ? $fiscalYear->end_date->copy()
                : $monthEnd;

            AccountingPeriod::firstOrCreate(
                [
                    'company_id' => $fiscalYear->company_id,
                    'fiscal_year_id' => $fiscalYear->id,
                    'name' => $cursor->format('F Y'),
                ],
                [
                    'start_date' => $periodStart->toDateString(),
                    'end_date' => $periodEnd->toDateString(),
                    'status' => 'open',
                ]
            );

            $cursor->addMonthNoOverflow()->startOfMonth();
        }
    }

    private function authorizeFiscalYear(FiscalYear $fiscalYear): void
    {
        if ($fiscalYear->company_id !== auth()->user()->company_id) {
            abort(404, 'Fiscal year not found.');
        }
    }

    private function authorizePeriod(AccountingPeriod $period): void
    {
        if ($period->company_id !== auth()->user()->company_id) {
            abort(404, 'Accounting period not found.');
        }
    }
}
