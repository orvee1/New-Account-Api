<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChartAccountController extends Controller
{
    public const TYPES = ['asset','liability','equity','income','expense'];

    public const DETAIL_TYPES = [
        'asset'     => ['Current Assets','Non-current Assets','Fixed Assets','Bank','Cash','Accounts Receivable','Inventory','Prepaid Expenses','Other Assets'],
        'liability' => ['Current Liabilities','Non-current Liabilities','Accounts Payable','Taxes Payable','Accrued Expenses','Loans'],
        'equity'    => ['Owner’s Equity','Retained Earnings','Common Stock','Capital'],
        'income'    => ['Operating Income','Non-operating Income','Sales Revenue','Service Revenue','Other Income'],
        'expense'   => ['Operating Expenses','Cost of Goods Sold','Administrative Expenses','Selling Expenses','Other Expenses'],
    ];

    public function index(Request $request)
    {
        $request->validate([
            'company_id' => ['required','exists:companies,id'],
            'type'       => ['nullable', Rule::in(self::TYPES)],
            'detail_type'=> ['nullable','string','max:255'],
            'per_page'   => ['nullable','integer','min:1','max:100'],
            'trashed'    => ['nullable', Rule::in(['with','only'])],
        ]);

        $companyId = (int) $request->company_id;
        $q       = $request->query('q');
        $type    = $request->query('type');
        $detail  = $request->query('detail_type');
        $perPage = (int) ($request->per_page ?? 15);
        $trashed = $request->query('trashed');

        $query = ChartAccount::query()
            ->where('company_id', $companyId)
            ->when($trashed === 'with', fn($q) => $q->withTrashed())
            ->when($trashed === 'only', fn($q) => $q->onlyTrashed())
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('account_no', 'like', "%{$q}%")
                      ->orWhere('detail_type', 'like', "%{$q}%");
                });
            })
            ->when($type, fn($qq) => $qq->where('type', $type))
            ->when($detail, fn($qq) => $qq->where('detail_type', $detail))
            ->orderBy('account_no');

        return response()->json(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    public function options()
    {
        return response()->json([
            'types'        => self::TYPES,
            'detail_types' => self::DETAIL_TYPES,
        ]);
    }

    public function tree(Request $request)
    {
        $request->validate(['company_id' => ['required','exists:companies,id']]);

        $rows = ChartAccount::where('company_id', $request->company_id)
            ->orderBy('account_no')
            ->get(['id','company_id','account_no','name','type','detail_type','parent_id','is_header','is_active','balance']);

        $byId = [];
        foreach ($rows as $r) { $r->children = []; $byId[$r->id] = $r; }
        $roots = [];
        foreach ($byId as $id => $node) {
            if ($node->parent_id && isset($byId[$node->parent_id])) {
                $byId[$node->parent_id]->children[] = $node;
            } else {
                $roots[] = $node;
            }
        }
        return response()->json($roots);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id'  => ['required','exists:companies,id'],
            'account_no'  => ['required','max:20', Rule::unique('chart_accounts')->where(
                fn($q) => $q->where('company_id', $request->company_id)->whereNull('deleted_at')
            )],
            'name'        => ['required','max:255'],
            'type'        => ['required', Rule::in(self::TYPES)],
            'detail_type' => ['nullable','max:255'],
            'parent_id'   => ['nullable','integer','exists:chart_accounts,id'],
            'is_header'   => ['boolean'],
            'is_active'   => ['boolean'],
            'balance'     => ['nullable','numeric'],
        ]);

        if ($request->filled('detail_type')) {
            $t = $request->input('type');
            if (isset(self::DETAIL_TYPES[$t]) && !in_array($request->detail_type, self::DETAIL_TYPES[$t])) {
                return response()->json(['message' => 'Invalid detail_type for type'], 422);
            }
        }

        if ($request->filled('parent_id')) {
            $parentOk = ChartAccount::where('id',$request->parent_id)
                ->where('company_id',$request->company_id)->exists();
            if (!$parentOk) {
                return response()->json(['message' => 'Parent must belong to the same company'], 422);
            }
        }

        $actor = optional($request->user())->id;

        $acc = ChartAccount::create([
            'company_id'  => $request->company_id,
            'account_no'  => $request->account_no,
            'name'        => $request->name,
            'type'        => $request->type,
            'detail_type' => $request->detail_type,
            'parent_id'   => $request->parent_id,
            'is_header'   => (bool)$request->boolean('is_header'),
            'is_active'   => (bool)$request->boolean('is_active', true),
            'balance'     => $request->input('balance', 0),
            'created_by'  => $actor,
            'updated_by'  => $actor,
        ]);

        return response()->json($acc, 201);
    }

    public function show(ChartAccount $chartAccount)
    {
        return response()->json($chartAccount);
    }

    public function update(Request $request, ChartAccount $chartAccount)
    {
        $request->validate([
            'name'        => ['sometimes','required','max:255'],
            'type'        => ['sometimes', Rule::in(self::TYPES)],
            'detail_type' => ['sometimes','nullable','max:255'],
            'parent_id'   => ['sometimes','nullable','integer','exists:chart_accounts,id'],
            'is_header'   => ['sometimes','boolean'],
            'is_active'   => ['sometimes','boolean'],
        ]);

        if ($request->filled('parent_id')) {
            if ((int)$request->parent_id === (int)$chartAccount->id) {
                return response()->json(['message' => 'parent_id cannot be self'], 422);
            }
            $sameCompany = ChartAccount::where('id',$request->parent_id)
                ->where('company_id',$chartAccount->company_id)->exists();
            if (!$sameCompany) {
                return response()->json(['message' => 'Parent must be from the same company'], 422);
            }
            if ($this->isDescendant($request->parent_id, $chartAccount->id)) {
                return response()->json(['message' => 'Cannot move under a descendant'], 422);
            }
        }

        if ($request->has('is_header') && !$request->boolean('is_header')) {
            $hasChildren = ChartAccount::where('parent_id',$chartAccount->id)->exists();
            if ($hasChildren) {
                return response()->json(['message' => 'Account has children; cannot unset header'], 422);
            }
        }

        $newType = $request->input('type', $chartAccount->type);
        if ($request->has('detail_type') && $request->detail_type !== null) {
            if (isset(self::DETAIL_TYPES[$newType]) && !in_array($request->detail_type, self::DETAIL_TYPES[$newType])) {
                return response()->json(['message' => 'Invalid detail_type for type'], 422);
            }
        }

        $chartAccount->fill($request->only([
            'name','type','detail_type','parent_id','is_header','is_active'
        ]));
        $chartAccount->updated_by = optional($request->user())->id;
        $chartAccount->save();

        return response()->json($chartAccount->fresh());
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
        $hasChildren = ChartAccount::withTrashed()->where('parent_id', $acc->id)->exists();
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
            $current = $current->parent_id ? ChartAccount::withTrashed()->find($current->parent_id) : null;
        }
        return false;
    }
}
