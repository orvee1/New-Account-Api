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
    public function index(Request $request, Company $company)
    {
        // চাইলে এখানে authorize করতে পারো: এই user এই company তে access আছে কি না
        // abort_if(! Auth::user()->canAccessCompany($company), 403);

        $query = ChartAccount::where('company_id', $company->id)
            ->whereNull('parent_id')       // root-level nodes
            ->with('childrenRecursive')    // recursive relation
            ->orderBy('sort_order');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->get());
    }

    // POST /api/companies/{company}/chart-accounts
    public function store(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'type'      => ['required', 'in:group,ledger'],
            'parent_id' => ['nullable', 'exists:chart_accounts,id'],
            'code'      => ['nullable', 'string', 'max:50'],
            'sort_order'=> ['nullable', 'integer'],
        ]);

        // parent company check
        if (!empty($data['parent_id'])) {
            $parent = ChartAccount::where('company_id', $company->id)
                ->where('id', $data['parent_id'])
                ->firstOrFail();

            // যদি type = ledger হয় এবং parent ledger হয় → block
            if ($data['type'] === 'ledger' && $parent->type === 'ledger') {
                return response()->json([
                    'message' => 'Ledger এর নিচে আর group/ledger তৈরি করা যাবে না।'
                ], 422);
            }
        }

        $chart = ChartAccount::create([
            'company_id' => $company->id,
            'parent_id'  => $data['parent_id'] ?? null,
            'name'       => $data['name'],
            'type'       => $data['type'],
            'code'       => $data['code'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'created_by' => Auth::id(),
        ]);

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
            'name'      => ['sometimes', 'string', 'max:255'],
            'code'      => ['sometimes', 'string', 'max:50'],
            'sort_order'=> ['sometimes', 'integer'],
        ]);

        $data['updated_by'] = Auth::id();
        $chartAccount->update($data);

        return response()->json($chartAccount);
    }

    // DELETE /api/companies/{company}/chart-accounts/{chartAccount}
    public function destroy(Company $company, ChartAccount $chartAccount)
    {
        abort_if($chartAccount->company_id !== $company->id, 404);

        $chartAccount->update(['deleted_by' => Auth::id()]);
        $chartAccount->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    // restore / forceDelete গুলোও একইভাবে company check + soft delete সহ করবে
}
