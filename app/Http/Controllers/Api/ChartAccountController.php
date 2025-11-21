<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChartAccountController extends Controller
{
    // GET /api/companies/{company}/chart-accounts
    public function index(Company $company)
    {
        $roots = ChartAccount::query()
            ->where('company_id', $company->id)
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // সরাসরি array রিটার্ন করছি → তোমার পেস্ট করা JSON এর মতো
        return response()->json($roots);
    }

    // POST /api/companies/{company}/chart-accounts
    public function store(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'type'      => ['required', 'in:group,ledger'],
            'parent_id' => ['nullable', 'exists:chart_accounts,id'],
        ]);

        // parent এই company-এর কিনা check
        $parent = null;
        if (!empty($data['parent_id'])) {
            $parent = ChartAccount::where('company_id', $company->id)
                ->where('id', $data['parent_id'])
                ->firstOrFail();

            // Ledger এর নিচে আর ledger/group allow না
            if ($parent->type === 'ledger') {
                return response()->json([
                    'message' => 'Ledger এর নিচে আর group/ledger তৈরি করা যাবে না।'
                ], 422);
            }
        }

        $chart = new ChartAccount();
        $chart->company_id = $company->id;
        $chart->parent_id  = $data['parent_id'] ?? null;
        $chart->name       = $data['name'];
        $chart->type       = $data['type'];
        // code, sort_order, path, depth, created_by সব model booted() এ handle হবে
        $chart->save();

        return response()->json($chart, 201);
    }

    // GET /api/companies/{company}/chart-accounts/{chartAccount}
    public function show(Company $company, ChartAccount $chartAccount)
    {
        abort_if($chartAccount->company_id !== $company->id, 404);

        $chartAccount->load('parent', 'children');
        return response()->json($chartAccount);
    }

    // PUT /api/companies/{company}/chart-accounts/{chartAccount}
    public function update(Request $request, Company $company, ChartAccount $chartAccount)
    {
        abort_if($chartAccount->company_id !== $company->id, 404);

        $data = $request->validate([
            'name'       => ['sometimes', 'string', 'max:255'],
            'code'       => ['sometimes', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer'],
            'is_active'  => ['sometimes', 'boolean'],
        ]);

        $data['updated_by'] = Auth::id();
        $chartAccount->update($data);

        return response()->json($chartAccount);
    }

    // DELETE /api/companies/{company}/chart-accounts/{chartAccount}
    public function destroy(Company $company, ChartAccount $chartAccount)
    {
        abort_if($chartAccount->company_id !== $company->id, 404);

        // চাইলে soft delete করতে পারো; এখন simple delete
        $chartAccount->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
