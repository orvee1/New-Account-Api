<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChartAccountRequest;
use App\Http\Requests\UpdateChartAccountRequest;
use App\Models\AccountType;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class ChartAccountController extends Controller
{

    public function index(Request $request)
    {
        $request->validate([
            'type'       => ['nullable'],
            'detail_type'=> ['nullable','string','max:255'],
            'per_page'   => ['nullable','integer','min:1','max:100'],
            'trashed'    => ['nullable', Rule::in(['with','only'])],
        ]);

        $user = Auth::user();

        $q       = $request->query('q');
        $type    = $request->query('type');
        $detail  = $request->query('detail_type');
        $perPage = (int) ($request->per_page ?? 15);
        $trashed = $request->query('trashed');

        $query = ChartAccount::query()
            ->where('company_id', $user->company_id ?? 0)
            ->when($trashed === 'with', fn($q) => $q->withTrashed())
            ->when($trashed === 'only', fn($q) => $q->onlyTrashed())
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('account_name', 'like', "%{$q}%")
                      ->orWhere('account_no', 'like', "%{$q}%")
                      ->orWhere('detail_type', 'like', "%{$q}%");
                });
            })
            ->when($type, fn($qq) => $qq->where('account_type', $type))
            ->when($detail, fn($qq) => $qq->where('detail_type', $detail))
            ->orderBy('account_no');

        return response()->json(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    public function options()
    {
        // ইচ্ছা করলে ক্যাশ রাখতে পারো (৫ মিনিট)
        $types = Cache::remember('account_types.grouped', 300, function () {
            $parents = AccountType::parents()
                ->orderBy('name')
                ->with(['children' => function ($q) {
                    $q->orderBy('name');
                }])
                ->get(['id','name','parent_id']);

            $out = [];
            foreach ($parents as $p) {
                // প্রত্যেক parent-এর অধীনে শুধুমাত্র নামের অ্যারে
                $out[$p->name] = $p->children->pluck('name')->values()->all();
            }
            return $out;
        });

        return response()->json( $types );
    }

    public function store(StoreChartAccountRequest $request)
    {
        $data = $request->validated();

        $data['company_id'] = Auth::user()->company_id;

        // created_by / defaults
        $data['created_by'] = Auth::id();
        $data['is_active'] = (bool)($data['is_active'] ?? true);
        $data['parent_account_id'] = $data['parent_account_id'] ?? 0;

        $account = ChartAccount::create($data);

        return response()->json([
            'message' => 'Account created successfully.',
            'data' => $account,
        ], 201);
    }


    public function show(ChartAccount $chartAccount)
    {
        return response()->json($chartAccount);
    }

    public function update(UpdateChartAccountRequest $request, ChartAccount $chart_account)
    {
        $data = $request->validated();
        $data['company_id'] = Auth::user()->company_id;
        $data['updated_by'] = Auth::id();
        $data['parent_account_id'] = $data['parent_account_id'] ?? 0;

        $chart_account->update($data);

        return response()->json([
            'message' => 'Account updated successfully.',
            'data' => $chart_account->fresh(),
        ]);
    }

    public function destroy(ChartAccount $chartAccount)
    {
        $chartAccount->delete();
        return response()->json(['message' => 'Account archived', 'deleted_at' => $chartAccount->deleted_at]);
    }

    public function restore($id)
    {
        $acc = ChartAccount::withTrashed()->findOrFail($id);
        $acc->restore();
        return response()->json(['message' => 'Account restored', 'account' => $acc->fresh()]);
    }

    public function forceDelete($id)
    {
        $acc = ChartAccount::withTrashed()->findOrFail($id);
        $hasChildren = ChartAccount::withTrashed()->where('parent_account_id', $acc->id)->exists();
        if ($hasChildren) {
            return response()->json(['message' => 'Remove or reassign children before permanent delete'], 422);
        }
        $acc->forceDelete();
        return response()->json(['message' => 'Account permanently deleted']);
    }

    private function isDescendant(int $maybeParentId, int $nodeId): bool
    {
        $current = ChartAccount::withTrashed()->find($maybeParentId);
        while ($current) {
            if ((int)$current->id === (int)$nodeId) return true;
            $current = $current->parent_account_id ? ChartAccount::withTrashed()->find($current->parent_account_id) : null;
        }
        return false;
    }
}
