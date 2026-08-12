<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetDepreciation;
use App\Models\AssetDisposal;
use App\Models\ChartAccount;
use App\Models\FixedAsset;
use App\Models\JournalLine;
use App\Services\AccountingPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssetDisposalController extends Controller
{
    public function __construct(
        private AccountingPostingService $postingService
    ) {
    }

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
            $this->syncJournalEntry($row);
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
            $this->postingService->deleteForReference($asset_disposal->company_id, AssetDisposal::class, $asset_disposal->id);
            $asset_disposal->update($data);
            $this->syncJournalEntry($asset_disposal->refresh());
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
        DB::transaction(function () use ($asset_disposal) {
            $this->postingService->deleteForReference($asset_disposal->company_id, AssetDisposal::class, $asset_disposal->id);
            $asset_disposal->delete();
        });
        return response()->json(['message' => 'Disposal deleted']);
    }

    private function authorizeCompany(AssetDisposal $row): void
    {
        if ($row->company_id !== (Auth::user()->company_id ?? null)) {
            abort(403, 'Unauthorized.');
        }
    }

    private function syncJournalEntry(AssetDisposal $row): void
    {
        $row->loadMissing('asset');

        if (! $row->asset) {
            return;
        }

        $assetCost = round((float) $row->asset->amount, 2);
        $proceeds = round((float) $row->disposal_value, 2);
        $accumulatedDepreciation = min($assetCost, $this->accumulatedDepreciationForAsset($row->asset));
        $bookValue = round($assetCost - $accumulatedDepreciation, 2);
        $gainOrLoss = round($proceeds - $bookValue, 2);

        $lines = [];

        if ($proceeds > 0) {
            $lines[] = [
                'key' => 'cash',
                'debit' => $proceeds,
                'credit' => 0,
                'narration' => "Asset disposal proceeds {$row->asset->name}",
            ];
        }

        if ($accumulatedDepreciation > 0) {
            $lines[] = [
                'key' => 'accumulated_depreciation',
                'debit' => $accumulatedDepreciation,
                'credit' => 0,
                'narration' => "Remove accumulated depreciation {$row->asset->name}",
            ];
        }

        if ($gainOrLoss < 0) {
            $lines[] = [
                'key' => 'loss_on_asset_disposal',
                'debit' => abs($gainOrLoss),
                'credit' => 0,
                'narration' => "Loss on disposal {$row->asset->name}",
            ];
        }

        if ($assetCost > 0) {
            $lines[] = [
                'key' => 'fixed_asset',
                'debit' => 0,
                'credit' => $assetCost,
                'narration' => "Remove fixed asset {$row->asset->name}",
            ];
        }

        if ($gainOrLoss > 0) {
            $lines[] = [
                'key' => 'gain_on_asset_disposal',
                'debit' => 0,
                'credit' => $gainOrLoss,
                'narration' => "Gain on disposal {$row->asset->name}",
            ];
        }

        if (count($lines) < 2) {
            $row->forceFill(['journal_entry_id' => null])->saveQuietly();
            return;
        }

        $entry = $this->postingService->post([
            'company_id' => $row->company_id,
            'reference_type' => AssetDisposal::class,
            'reference_id' => $row->id,
            'entry_date' => $row->disposed_at?->toDateString() ?? now()->toDateString(),
            'description' => "Asset disposal - {$row->asset->name}",
            'created_by' => Auth::id(),
            'status' => 'posted',
            'entry_type' => 'normal',
            'lines' => $lines,
        ]);

        $row->forceFill(['journal_entry_id' => $entry->id])->saveQuietly();
    }

    private function accumulatedDepreciationForAsset(FixedAsset $asset): float
    {
        $depreciationIds = AssetDepreciation::query()
            ->where('company_id', $asset->company_id)
            ->where('fixed_asset_id', $asset->id)
            ->pluck('id');

        if ($depreciationIds->isEmpty()) {
            return 0.0;
        }

        $accountId = ChartAccount::query()
            ->where('company_id', $asset->company_id)
            ->where('code', config('coa_map.accumulated_depreciation'))
            ->value('id');

        if (! $accountId) {
            return 0.0;
        }

        $totals = JournalLine::query()
            ->selectRaw('SUM(debit) as debit, SUM(credit) as credit')
            ->where('company_id', $asset->company_id)
            ->where('account_id', $accountId)
            ->whereHas('journalEntry', function ($query) use ($depreciationIds) {
                $query->whereIn('status', ['posted', 'reversed'])
                    ->where('reference_type', \App\Models\AssetDepreciation::class)
                    ->whereIn('reference_id', $depreciationIds);
            })
            ->first();

        return round((float) ($totals->credit ?? 0) - (float) ($totals->debit ?? 0), 2);
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
