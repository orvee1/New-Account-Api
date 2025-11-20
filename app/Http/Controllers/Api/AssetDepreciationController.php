<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssetDepreciationResource;
use App\Models\AssetDepreciation;
use App\Models\FixedAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssetDepreciationController extends Controller
{
    /**
     * GET /api/v1/asset-depreciations?fixed_asset_id=&active=1&search=&per_page=15
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $q = AssetDepreciation::query()
            ->where('company_id', $user->company_id)
            ->with(['asset:id,name,tag_serial_number'])
            ->when($request->filled('fixed_asset_id'), fn($query) =>
                $query->where('fixed_asset_id', (int)$request->get('fixed_asset_id'))
            )
            ->when($request->filled('active'), fn($query) =>
                $query->where('is_active', (bool)$request->boolean('active'))
            )
            ->when($request->filled('search'), function ($query) use ($request) {
                $s = $request->get('search');
                $query->where(function ($sub) use ($s) {
                    $sub->where('debit_acc_name', 'like', "%{$s}%")
                        ->orWhere('credit_acc_name', 'like', "%{$s}%")
                        ->orWhere('method', 'like', "%{$s}%")
                        ->orWhere('frequency', 'like', "%{$s}%");
                });
            })
            ->orderByDesc('id');

        $perPage = (int) $request->get('per_page', 15);
        return AssetDepreciationResource::collection($q->paginate($perPage));
    }

    /**
     * POST /api/v1/asset-depreciations
     * Body: camelCase বা snake_case – দুটোই চলবে
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $this->normalize($request);

        // validate
        $this->validatePayload($request);

        // asset company ownership check
        $asset = FixedAsset::where('id', $data['fixed_asset_id'] ?? 0)
            ->where('company_id', $user->company_id)
            ->first();

        if (!$asset) {
            return response()->json(['message' => 'Invalid asset for this company.'], 422);
        }

        // amount auto-compute (Straight Line only) if not provided
        if (!isset($data['amount']) && ($data['method'] ?? null) === 'Straight Line') {
            $data['amount'] = $this->computeAmountFromAsset($asset, $data['frequency'] ?? 'Monthly');
        }

        $data['company_id'] = $user->company_id;
        $data['created_by'] = $user->id;

        // keep only one active rule per asset (optional safety)
        if (($data['is_active'] ?? true) === true) {
            $exists = AssetDepreciation::where('company_id', $user->company_id)
                ->where('fixed_asset_id', $data['fixed_asset_id'])
                ->where('is_active', true)
                ->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'An active depreciation rule already exists for this asset.'
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $row = AssetDepreciation::create($data);
            DB::commit();

            return (new AssetDepreciationResource(
                $row->loadMissing('asset:id,name,tag_serial_number')
            ))->additional(['message' => 'Depreciation entry created']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create depreciation entry',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/v1/asset-depreciations/{asset_depreciation}
     */
    public function show(AssetDepreciation $asset_depreciation)
    {
        $this->authorizeCompany($asset_depreciation);
        $asset_depreciation->loadMissing('asset:id,name,tag_serial_number');
        return new AssetDepreciationResource($asset_depreciation);
    }

    /**
     * PUT/PATCH /api/v1/asset-depreciations/{asset_depreciation}
     */
    public function update(Request $request, AssetDepreciation $asset_depreciation)
    {
        $this->authorizeCompany($asset_depreciation);

        $user = Auth::user();
        $data = $this->normalize($request);

        $this->validatePayload($request, $asset_depreciation->id);

        // If fixed_asset_id changed, validate ownership
        if (array_key_exists('fixed_asset_id', $data)) {
            $asset = FixedAsset::where('id', $data['fixed_asset_id'])
                ->where('company_id', $user->company_id)->first();
            if (!$asset) {
                return response()->json(['message' => 'Invalid asset for this company.'], 422);
            }
        } else {
            $asset = $asset_depreciation->asset;
        }

        // recompute amount if not provided & method is Straight Line
        if (!isset($data['amount']) && (($data['method'] ?? $asset_depreciation->method) === 'Straight Line')) {
            $freq = $data['frequency'] ?? $asset_depreciation->frequency ?? 'Monthly';
            $data['amount'] = $this->computeAmountFromAsset($asset, $freq);
        }

        // one active rule per asset
        if (isset($data['is_active']) && (bool)$data['is_active'] === true) {
            $exists = AssetDepreciation::where('company_id', $user->company_id)
                ->where('fixed_asset_id', $data['fixed_asset_id'] ?? $asset_depreciation->fixed_asset_id)
                ->where('is_active', true)
                ->where('id', '!=', $asset_depreciation->id)
                ->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'An active depreciation rule already exists for this asset.'
                ], 422);
            }
        }

        $data['updated_by'] = $user->id;

        DB::beginTransaction();
        try {
            $asset_depreciation->update($data);
            DB::commit();

            return (new AssetDepreciationResource(
                $asset_depreciation->fresh()->loadMissing('asset:id,name,tag_serial_number')
            ))->additional(['message' => 'Depreciation entry updated']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update depreciation entry',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * DELETE /api/v1/asset-depreciations/{asset_depreciation}
     */
    public function destroy(AssetDepreciation $asset_depreciation)
    {
        $this->authorizeCompany($asset_depreciation);
        $asset_depreciation->delete();
        return response()->json(['message' => 'Depreciation entry deleted']);
    }

    // ------------- Helpers -------------

    private function authorizeCompany(AssetDepreciation $row): void
    {
        if ($row->company_id !== (Auth::user()->company_id ?? null)) {
            abort(403, 'Unauthorized.');
        }
    }

    private function computeAmountFromAsset(FixedAsset $asset, string $frequency = 'Monthly'): float
    {
        $original = (float) $asset->amount;
        $life     = (int) ($asset->useful_life ?? 0);
        $salvage  = (float) ($asset->salvage_value ?? 0);

        if ($original <= 0 || $life <= 0) return 0.0;

        $annual = ($original - $salvage) / $life;
        if (strtolower($frequency) === 'yearly') {
            return round($annual, 2);
        }
        return round($annual / 12, 2); // monthly default
    }

    private function normalize(Request $req): array
    {
        $map = [
            'assetId'        => 'fixed_fixed_asset_id',
            'fixed_fixed_asset_id' => 'fixed_fixed_asset_id',
            'method'         => 'method',
            'frequency'      => 'frequency',
            'timeOfEntry'    => 'time_of_entry',
            'time_of_entry'  => 'time_of_entry',
            'amount'         => 'amount',
            'debitAcc'       => 'debit_acc_name',
            'debit_acc'      => 'debit_acc_name',
            'debit_acc_name' => 'debit_acc_name',
            'creditAcc'      => 'credit_acc_name',
            'credit_acc'     => 'credit_acc_name',
            'credit_acc_name'=> 'credit_acc_name',
            'startDate'      => 'start_date',
            'start_date'     => 'start_date',
            'endDate'        => 'end_date',
            'end_date'       => 'end_date',
            'isActive'       => 'is_active',
            'is_active'      => 'is_active',
        ];

        $out = [];
        foreach ($map as $in => $outKey) {
            if ($req->has($in)) {
                $out[$outKey] = $req->input($in);
            }
        }

        if (!empty($out['start_date'])) $out['start_date'] = date('Y-m-d', strtotime($out['start_date']));
        if (!empty($out['end_date']))   $out['end_date']   = date('Y-m-d', strtotime($out['end_date']));
        if (isset($out['is_active']))   $out['is_active']  = (bool)$out['is_active'];

        if (isset($out['debit_acc_name']))  $out['debit_acc_name']  = trim($out['debit_acc_name']);
        if (isset($out['credit_acc_name'])) $out['credit_acc_name'] = trim($out['credit_acc_name']);

        return $out;
    }

    private function validatePayload(Request $request, ?int $id = null): void
    {
        $companyId = Auth::user()->company_id ?? 0;

        $request->validate([
            'fixed_asset_id'       => ['required','integer',
                Rule::exists('fixed_assets', 'id')->where(fn($q) => $q->where('company_id', $companyId))
            ],
            'method'         => ['required','string','in:Straight Line,Reducing Balance'],
            'frequency'      => ['required','string','in:Monthly,Yearly'],
            'time_of_entry'  => ['nullable','string','max:255'],
            'amount'         => ['nullable','numeric','min:0'],
            'debit_acc_name' => ['required','string','max:255'],
            'credit_acc_name'=> ['required','string','max:255'],
            'start_date'     => ['required','date'],
            'end_date'       => ['nullable','date','after_or_equal:start_date'],
            'is_active'      => ['sometimes','boolean'],
        ]);
    }
}
