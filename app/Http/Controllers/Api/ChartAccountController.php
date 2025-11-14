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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChartAccountController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q'           => ['nullable', 'string'],
            'major_type'  => ['nullable', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])],
            'node_type'   => ['nullable', Rule::in(['group', 'ledger'])],
            'detail_type' => ['nullable', 'string', 'max:255'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
            'trashed'     => ['nullable', Rule::in(['with', 'only'])],
        ]);

        $user    = Auth::user();
        $q       = $request->query('q');
        $major   = $request->query('major_type');
        $node    = $request->query('node_type');
        $detail  = $request->query('detail_type');
        $perPage = (int) ($request->per_page ?? 15);
        $trashed = $request->query('trashed');

        $query = ChartAccount::query()
            ->where('company_id', $user->company_id ?? 0)
            ->when($trashed === 'with', fn($x) => $x->withTrashed())
            ->when($trashed === 'only', fn($x) => $x->onlyTrashed())
            ->when($q, fn($x) => $x->search($q))
            ->when($major, fn($x) => $x->where('major_type', $major))
            ->when($node, fn($x) => $x->where('node_type', $node))
            ->when($detail, fn($x) => $x->where('detail_type', $detail))
            ->orderBy('path'); // tree-friendly ordering

        return response()->json(
            $query->paginate($perPage)->appends($request->query())
        );
    }

    public function options()
    {
        // আগের মতই: AccountType থেকে parent->children গ্রুপ করা
        $types = Cache::remember('account_types.grouped', 300, function () {
            $parents = AccountType::parents()
                ->orderBy('name')
                ->with(['children' => fn($q) => $q->orderBy('name')])
                ->get(['id', 'name', 'parent_id']);

            $out = [];
            foreach ($parents as $p) {
                $out[$p->name] = $p->children->pluck('name')->values()->all();
            }
            return $out;
        });

        return response()->json($types);
    }

    public function store(StoreChartAccountRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        return DB::transaction(function () use ($data, $user) {
            // parent guard
            $parent = null;
            if (! empty($data['parent_account_id'])) {
                $parent = ChartAccount::lockForUpdate()->findOrFail($data['parent_account_id']);
                if ($parent->company_id !== (int) $user->company_id) {
                    return response()->json(['message' => 'Parent must belong to same company.'], 422);
                }
                if ($parent->isLedger()) {
                    return response()->json(['message' => 'Cannot add child under a ledger.'], 422);
                }
            }

            $node = ChartAccount::create([
                 ...$data,
                'company_id' => $user->company_id,
                'created_by' => $user->id,
                'is_active'  => (bool) ($data['is_active'] ?? true),
                'depth'      => $parent ? $parent->depth + 1 : 0,
                'path'       => null, // set after id known
            ]);

            $node->path = ChartAccount::buildPath($parent, $node->id);
            $node->save();

            return response()->json([
                'message' => 'Account created successfully.',
                'data'    => $node->fresh(),
            ], 201);
        });
    }

    public function show(ChartAccount $chartAccount)
    {
        $this->authorizeCompany($chartAccount);
        return response()->json($chartAccount);
    }

    public function update(UpdateChartAccountRequest $request, ChartAccount $chart_account)
    {
        $this->authorizeCompany($chart_account);
        $user = Auth::user();
        $data = $request->validated();

        return DB::transaction(function () use ($data, $chart_account, $user) {
            $parentChanged = array_key_exists('parent_account_id', $data)
            && (int) ($data['parent_account_id'] ?? 0) !== (int) ($chart_account->parent_account_id ?? 0);

            // basic fills
            $chart_account->fill([
                 ...$data,
                'updated_by' => $user->id,
            ])->save();

            if ($parentChanged) {
                $newParent = $chart_account->parent_account_id
                    ? ChartAccount::lockForUpdate()->findOrFail($chart_account->parent_account_id) : null;

                if ($newParent && $newParent->isLedger()) {
                    return response()->json(['message' => 'Cannot move under a ledger.'], 422);
                }
                // prevent cycle: new parent cannot be descendant of current node
                if ($newParent && str_starts_with($newParent->path, $chart_account->getOriginal('path'))) {
                    return response()->json(['message' => 'Cannot move a node under its own descendant.'], 422);
                }

                $oldPath              = $chart_account->getOriginal('path');
                $chart_account->depth = $newParent ? $newParent->depth + 1 : 0;
                $chart_account->path  = ChartAccount::buildPath($newParent, $chart_account->id);
                $chart_account->save();

                // update descendants path/depth
                $desc = ChartAccount::where('company_id', $chart_account->company_id)
                    ->where('path', 'like', $oldPath . '%')
                    ->where('id', '!=', $chart_account->id)
                    ->get();

                foreach ($desc as $child) {
                    $child->path  = preg_replace('#^' . preg_quote($oldPath, '#') . '#', $chart_account->path, $child->path);
                    $child->depth = substr_count(trim($child->path, '/'), '/') - 1;
                    $child->save();
                }
            }

            return response()->json([
                'message' => 'Account updated successfully.',
                'data'    => $chart_account->fresh(),
            ]);
        });
    }

    public function destroy(ChartAccount $chartAccount)
    {
        $this->authorizeCompany($chartAccount);

        if ($chartAccount->children()->exists()) {
            return response()->json(['message' => 'Delete or move children first.'], 422);
        }

        $chartAccount->delete();
        return response()->json(['message' => 'Account archived', 'deleted_at' => $chartAccount->deleted_at]);
    }

    public function restore($id)
    {
        $acc = ChartAccount::withTrashed()->findOrFail($id);
        $this->authorizeCompany($acc);
        $acc->restore();
        return response()->json(['message' => 'Account restored', 'account' => $acc->fresh()]);
    }

    public function forceDelete($id)
    {
        $acc = ChartAccount::withTrashed()->findOrFail($id);
        $this->authorizeCompany($acc);

        $hasChildren = ChartAccount::withTrashed()->where('parent_account_id', $acc->id)->exists();
        if ($hasChildren) {
            return response()->json(['message' => 'Remove or reassign children before permanent delete'], 422);
        }
        $acc->forceDelete();
        return response()->json(['message' => 'Account permanently deleted']);
    }

    private function authorizeCompany(ChartAccount $acc): void
    {
        $user = Auth::user();
        if ((int) $acc->company_id !== (int) $user->company_id) {
            abort(403, 'Forbidden');
        }
    }
}
