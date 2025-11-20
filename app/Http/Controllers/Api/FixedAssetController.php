<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FixedAssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\FixedAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $q = FixedAsset::query()
            ->where('company_id', $user->company_id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $s = $request->get('search');
                $query->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('category', 'like', "%{$s}%")
                        ->orWhere('vendor_name', 'like', "%{$s}%")
                        ->orWhere('tag_serial_number', 'like', "%{$s}%");
                });
            })
            ->when($request->filled('mode'), fn($query) =>
                $query->where('purchase_mode', $request->get('mode'))
            )
            ->when($request->filled('category'), fn($query) =>
                $query->where('category', $request->get('category'))
            )
            ->orderByDesc('id');

        $perPage = (int) $request->get('per_page', 15);

        return AssetResource::collection($q->paginate($perPage));
    }

    public function store(FixedAssetRequest $request)
    {
        $user = Auth::user();

        // camelCase -> snake_case normalize
        $data = $this->normalize($request);

        // Server-side safety: Straight Line হলে rate auto-calc
        if (($data['depreciation_method'] ?? null) === 'Straight Line') {
            $amount = (float) ($data['amount'] ?? 0);
            $life   = (float) ($data['useful_life'] ?? 0);
            $sVal   = (float) ($data['salvage_value'] ?? 0);

            if ($amount > 0 && $life > 0) {
                $annual = ($amount - $sVal) / $life;
                $rate = $annual / $amount * 100;
                $data['depreciation_rate'] = round($rate, 4);
            }
        }

        $data['company_id'] = $user->company_id;
        $data['created_by'] = $user->id;

        DB::beginTransaction();
        try {
            $fixedAsset = FixedAsset::create($data);
            DB::commit();

            return (new AssetResource($fixedAsset))
                ->additional(['message' => 'Asset created successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create asset',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function show(FixedAsset $fixedAsset)
    {
        $this->authorizeCompany($fixedAsset);
        return new AssetResource($fixedAsset);
    }

    public function update(FixedAssetRequest $request, FixedAsset $fixedAsset)
    {
        $this->authorizeCompany($fixedAsset);

        $user = Auth::user();
        $data = $this->normalize($request);

        // Recompute if Straight Line (optional but safer)
        if (($data['depreciation_method'] ?? $fixedAsset->depreciation_method) === 'Straight Line') {
            $amount = (float) ($data['amount'] ?? $fixedAsset->amount ?? 0);
            $life   = (float) ($data['useful_life'] ?? $fixedAsset->useful_life ?? 0);
            $sVal   = (float) ($data['salvage_value'] ?? $fixedAsset->salvage_value ?? 0);

            if ($amount > 0 && $life > 0) {
                $annual = ($amount - $sVal) / $life;
                $rate = $annual / $amount * 100;
                $data['depreciation_rate'] = round($rate, 4);
            }
        }

        $data['updated_by'] = $user->id;

        DB::beginTransaction();
        try {
            $fixedAsset->update($data);
            DB::commit();

            return (new AssetResource($fixedAsset->fresh()))
                ->additional(['message' => 'Asset updated successfully']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update asset',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(FixedAsset $fixedAsset)
    {
        $this->authorizeCompany($fixedAsset);
        $fixedAsset->delete();

        return response()->json(['message' => 'Asset deleted']);
    }

    private function normalize(Request $req): array
    {
        $map = [
            'name'                => 'name',
            'category'            => 'category',
            'purchaseDate'        => 'purchase_date',
            'purchase_date'       => 'purchase_date',
            'amount'              => 'amount',
            'vendorName'          => 'vendor_name',
            'vendor_name'         => 'vendor_name',
            'purchaseMode'        => 'purchase_mode',
            'purchase_mode'       => 'purchase_mode',
            'paymentMode'         => 'payment_mode',
            'payment_mode'        => 'payment_mode',
            'usefulLife'          => 'useful_life',
            'useful_life'         => 'useful_life',
            'salvageValue'        => 'salvage_value',
            'salvage_value'       => 'salvage_value',
            'depreciationMethod'  => 'depreciation_method',
            'depreciation_method' => 'depreciation_method',
            'frequency'           => 'frequency',
            'depreciationRate'    => 'depreciation_rate',
            'depreciation_rate'   => 'depreciation_rate',
            'assetLocation'       => 'asset_location',
            'asset_location'      => 'asset_location',
            'tagSerialNumber'     => 'tag_serial_number',
            'tag_serial_number'   => 'tag_serial_number',
        ];

        $out = [];
        foreach ($map as $in => $outKey) {
            if ($req->has($in)) {
                $out[$outKey] = $req->input($in);
            }
        }

        if (!empty($out['purchase_date'])) {
            $out['purchase_date'] = date('Y-m-d', strtotime($out['purchase_date']));
        }

        return $out;
    }

    private function authorizeCompany(FixedAsset $fixedAsset): void
    {
        if ($fixedAsset->company_id !== (Auth::user()->company_id ?? null)) {
            abort(403, 'Unauthorized for this asset.');
        }
    }
}
