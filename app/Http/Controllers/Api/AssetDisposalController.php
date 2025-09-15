<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetDisposal;
use App\Models\FixedAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssetDisposalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $q = AssetDisposal::query()
            ->where('company_id', $user->company_id)
            ->with(['asset:id,name,tag_serial_number'])
            ->when($request->filled('fixed_asset_id'), fn($qq) =>
                $qq->where('fixed_asset_id', (int) $request->get('fixed_asset_id'))
            )
            ->when($request->filled('type'), fn($qq) =>
                $qq->where('disposal_type', $request->get('type'))
            )
            ->when($request->filled('search'), function ($qq) use ($request) {
                $s = $request->get('search');
                $qq->where(function ($w) use ($s) {
                    $w->where('tag_no','like',"%{$s}%")
                      ->orWhere('disposal_type','like',"%{$s}%")
                      ->orWhere('remarks','like',"%{$s}%");
                });
            })
            ->orderByDesc('id');

        return $q->paginate((int) $request->get('per_page', 15));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $this->normalize($request);

        $this->validatePayload($request);

        // company-ownership নিশ্চিত করা
        $asset = FixedAsset::where('id', $data['fixed_asset_id'])
            ->where('company_id', $user->company_id)
            ->first();

        if (!$asset) {
            return response()->json(['message' => 'Invalid asset for this company.'], 422);
        }

        // এক অ্যাসেটকে একবারই dispose (soft-deleted না থাকলে)
        $exists = AssetDisposal::where('company_id', $user->company_id)
            ->where('fixed_asset_id', $data['fixed_asset_id'])
            ->whereNull('deleted_at')
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'This asset already has a disposal record.'], 422);
        }

        // tag_no না এলে asset থেকে নাও
        if (empty($data['tag_no'])) {
            $data['tag_no'] = $asset->tag_serial_number;
        }
        // disposed_at না এলে আজকের তারিখ
        if (empty($data['disposed_at'])) {
            $data['disposed_at'] = now()->format('Y-m-d');
        }

        $data['company_id'] = $user->company_id;
        $data['created_by'] = $user->id;

        DB::beginTransaction();
        try {
            $row = AssetDisposal::create($data);
            DB::commit();

            return $row->loadMissing('asset:id,name,tag_serial_number');
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to record asset disposal',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function show(AssetDisposal $asset_disposal)
    {
        $this->authorizeCompany($asset_disposal);
        return $asset_disposal->loadMissing('asset:id,name,tag_serial_number');
    }

    public function update(Request $request, AssetDisposal $asset_disposal)
    {
        $this->authorizeCompany($asset_disposal);

        $user = Auth::user();
        $data = $this->normalize($request);
        $this->validatePayload($request, isUpdate:true);

        if (array_key_exists('fixed_asset_id', $data)) {
            $asset = FixedAsset::where('id', $data['fixed_asset_id'])
                ->where('company_id', $user->company_id)->first();
            if (!$asset) {
                return response()->json(['message' => 'Invalid asset for this company.'], 422);
            }
        }

        if (isset($data['disposed_at']) && $data['disposed_at']) {
            $data['disposed_at'] = date('Y-m-d', strtotime($data['disposed_at']));
        }
        if (array_key_exists('tag_no', $data) && $data['tag_no'] === '') {
            $asset = $asset_disposal->asset ?: FixedAsset::find($data['fixed_asset_id'] ?? 0);
            if ($asset) $data['tag_no'] = $asset->tag_serial_number;
        }

        $data['updated_by'] = $user->id;

        DB::beginTransaction();
        try {
            $asset_disposal->update($data);
            DB::commit();
            return $asset_disposal->fresh()->loadMissing('asset:id,name,tag_serial_number');
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update disposal',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(AssetDisposal $asset_disposal)
    {
        $this->authorizeCompany($asset_disposal);
        $asset_disposal->delete();
        return response()->json(['message' => 'Disposal deleted']);
    }

    private function authorizeCompany(AssetDisposal $row): void
    {
        if ($row->company_id !== (Auth::user()->company_id ?? null)) {
            abort(403, 'Unauthorized.');
        }
    }

    private function normalize(Request $req): array
    {
        $map = [
            // asset id aliases
            'fixedAssetId'   => 'fixed_asset_id',
            'fixed_asset_id' => 'fixed_asset_id',
            'selectedAsset'  => 'fixed_asset_id',

            // type
            'disposalType'   => 'disposal_type',
            'disposal_type'  => 'disposal_type',

            // value
            'disposalValue'  => 'disposal_value',
            'disposal_value' => 'disposal_value',
            'value'          => 'disposal_value',

            // tag
            'tagNo'          => 'tag_no',
            'tag_no'         => 'tag_no',

            // optional
            'disposedAt'     => 'disposed_at',
            'disposed_at'    => 'disposed_at',
            'remarks'        => 'remarks',
        ];

        $out = [];
        foreach ($map as $in => $outKey) {
            if ($req->has($in)) {
                $out[$outKey] = $req->input($in);
            }
        }

        // tidy
        if (!empty($out['disposed_at'])) {
            $out['disposed_at'] = date('Y-m-d', strtotime($out['disposed_at']));
        }
        if (isset($out['disposal_value'])) {
            $out['disposal_value'] = (float) $out['disposal_value'];
        }
        if (isset($out['disposal_type'])) $out['disposal_type'] = trim($out['disposal_type']);
        if (isset($out['tag_no']))        $out['tag_no']        = trim($out['tag_no']);

        return $out;
    }

    private function validatePayload(Request $request, bool $isUpdate = false): void
    {
        $companyId = Auth::user()->company_id ?? 0;

        $request->validate([
            'fixed_asset_id'  => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                Rule::exists('fixed_assets', 'id')
                    ->where(fn($q) => $q->where('company_id', $companyId)),
            ],
            'disposal_type'   => [$isUpdate ? 'sometimes' : 'required', 'string', Rule::in(['Sold','Scrapped'])],
            'disposal_value'  => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0'],
            'tag_no'          => ['nullable','string','max:255'],
            'disposed_at'     => ['nullable','date'],
            'remarks'         => ['nullable','string'],
        ]);
    }
}
