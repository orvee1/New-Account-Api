<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EstimateRequest;
use App\Http\Resources\EstimateResource;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimateController extends Controller
{
    public function index(Request $r)
    {
        $q = Estimate::where('company_id', auth()->user()->company_id)
            ->with('items')->orderByDesc('id');

        if ($r->filled('q')) {
            $q->where('estimate_no', 'like', '%' . $r->q . '%');
        }

        if ($r->filled('customer_id')) {
            $q->where('customer_id', $r->customer_id);
        }

        if ($r->filled('is_draft')) {
            $q->where('is_draft', (bool) $r->is_draft);
        }

        return EstimateResource::collection($q->paginate((int) ($r->per_page ?? 20))->withQueryString());
    }

    public function store(EstimateRequest $req)
    {
        $data = $req->validated();
        $cid  = auth()->user()->company_id;

        $est = DB::transaction(function () use ($data, $cid) {
            $est = Estimate::create([
                'company_id'    => $cid, 'customer_id'                   => $data['customer_id'],
                'estimate_no'   => $data['estimate_no'] ?? $this->gen('EST'),
                'estimate_date' => $data['estimate_date'], 'expiry_date' => $data['expiry_date'] ?? null,
                'is_draft'      => $data['is_draft'] ?? true, 'notes'    => $data['notes'] ?? null,
            ]);

            $subtotal = 0; $totalDisc = 0;
            foreach ($data['line_items'] as $li) {
                $qtyUnit = ! empty($li['quantity_unit_id']) ? ProductUnit::find($li['quantity_unit_id']) : null;
                $qf      = $qtyUnit?->factor ?? 1;
                $base    = (float) $li['quantity_input'] * (float) $qf;

                $billUnit = ! empty($li['billing_unit_id']) ? ProductUnit::find($li['billing_unit_id']) : null;
                $bf       = $billUnit?->factor ?? 1;

                $rateBill = (float) ($li['rate_for_billing_unit'] ?? 0);
                $unitBase = $bf > 0 ? $rateBill / $bf : 0;
                $lineSub  = $base * $unitBase;

                $discAmt = (float) ($li['discount_amount'] ?? 0);
                if ($discAmt <= 0 && ! empty($li['discount_percent'])) {
                    $discAmt = $lineSub * ((float) $li['discount_percent'] / 100);
                }

                $lineTotal = $lineSub - $discAmt;

                EstimateItem::create([
                    'estimate_id'      => $est->id, 'product_id'                                    => $li['product_id'],
                    'qty_input'        => $li['quantity_input'], 'qty_unit_id'                      => $qtyUnit?->id, 'qty_unit_factor' => $qf, 'base_qty' => $base,
                    'billing_unit_id'  => $billUnit?->id, 'billing_unit_factor'                     => $bf, 'rate_per_billing_unit'     => $rateBill,
                    'unit_price_base'  => $unitBase, 'line_subtotal'                                => $lineSub,
                    'discount_percent' => (float) ($li['discount_percent'] ?? 0), 'discount_amount' => $discAmt, 'line_total'           => $lineTotal,
                ]);

                $subtotal += $lineSub; $totalDisc += $discAmt;
            }

            $est->update([
                'subtotal'       => round($subtotal, 2),
                'total_discount' => round($totalDisc, 2),
                'grand_total'    => round($subtotal - $totalDisc, 2),
            ]);

            return $est->load('items');
        });

        return (new EstimateResource($est))->response()->setStatusCode(201);
    }

    public function show(Estimate $estimate)
    {return new EstimateResource($estimate->load('items'));}

    public function update(EstimateRequest $req, Estimate $estimate)
    {
        $data = $req->validated();

        $est = DB::transaction(function () use ($data, $estimate) {
            $estimate->update([
                'customer_id'   => $data['customer_id'],
                'estimate_no'   => $data['estimate_no'] ?? $estimate->estimate_no,
                'estimate_date' => $data['estimate_date'] ?? $estimate->estimate_date,
                'expiry_date'   => $data['expiry_date'] ?? $estimate->expiry_date,
                'is_draft'      => $data['is_draft'] ?? $estimate->is_draft,
                'notes'         => $data['notes'] ?? $estimate->notes,
            ]);

            $estimate->items()->delete();

            $subtotal = 0; $totalDisc = 0;
            foreach ($data['line_items'] as $li) {
                $qtyUnit = ! empty($li['quantity_unit_id']) ? ProductUnit::find($li['quantity_unit_id']) : null;
                $qf      = $qtyUnit?->factor ?? 1;
                $base    = (float) $li['quantity_input'] * (float) $qf;

                $billUnit = ! empty($li['billing_unit_id']) ? ProductUnit::find($li['billing_unit_id']) : null;
                $bf       = $billUnit?->factor ?? 1;

                $rateBill = (float) ($li['rate_for_billing_unit'] ?? 0);
                $unitBase = $bf > 0 ? $rateBill / $bf : 0;
                $lineSub  = $base * $unitBase;

                $discAmt = (float) ($li['discount_amount'] ?? 0);
                if ($discAmt <= 0 && ! empty($li['discount_percent'])) {
                    $discAmt = $lineSub * ((float) $li['discount_percent'] / 100);
                }

                $lineTotal = $lineSub - $discAmt;

                EstimateItem::create([
                    'estimate_id'      => $estimate->id, 'product_id'                               => $li['product_id'],
                    'qty_input'        => $li['quantity_input'], 'qty_unit_id'                      => $qtyUnit?->id, 'qty_unit_factor' => $qf, 'base_qty' => $base,
                    'billing_unit_id'  => $billUnit?->id, 'billing_unit_factor'                     => $bf, 'rate_per_billing_unit'     => $rateBill,
                    'unit_price_base'  => $unitBase, 'line_subtotal'                                => $lineSub,
                    'discount_percent' => (float) ($li['discount_percent'] ?? 0), 'discount_amount' => $discAmt, 'line_total'           => $lineTotal,
                ]);

                $subtotal += $lineSub; $totalDisc += $discAmt;
            }

            $estimate->update([
                'subtotal' => round($subtotal, 2), 'total_discount' => round($totalDisc, 2), 'grand_total' => round($subtotal - $totalDisc, 2),
            ]);

            return $estimate->load('items');
        });

        return new EstimateResource($est);
    }

    public function destroy(Estimate $estimate)
    {
        $estimate->items()->delete();
        $estimate->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function finalize(Estimate $estimate)
    {
        $estimate->update(['is_draft' => false]);
        return response()->json(['message' => 'Estimate finalized']);
    }

    protected function gen(string $p): string
    {return $p . '-' . substr((string) now()->timestamp, -6);}
}
