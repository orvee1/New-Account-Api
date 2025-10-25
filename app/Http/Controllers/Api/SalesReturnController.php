<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesReturnRequest;
use App\Http\Resources\SalesReturnResource;
use App\Models\InventoryMovement;
use App\Models\ProductUnit;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReturnController extends Controller
{
    public function index(Request $r)
    {
        $q = SalesReturn::where('company_id', auth()->user()->company_id)->with('items')->orderByDesc('id');
        if ($r->filled('q')) {
            $q->where('return_no', 'like', '%' . $r->q . '%');
        }

        if ($r->filled('customer_id')) {
            $q->where('customer_id', $r->customer_id);
        }

        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }

        return SalesReturnResource::collection($q->paginate((int) ($r->per_page ?? 20))->withQueryString());
    }

    public function store(SalesReturnRequest $req)
    {
        $data = $req->validated();
        $cid  = auth()->user()->company_id;

        $ret = DB::transaction(function () use ($data, $cid) {
            $ret = SalesReturn::create([
                'company_id'  => $cid,
                'customer_id'             => $data['customer_id'],
                'return_no'   => $data['return_no'] ?? $this->gen('SRTN'),
                'return_date' => $data['return_date'],
                'notes'       => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'status'      => 'Saved',
            ]);

            $subtotal = 0;
            $totalDisc = 0;
            foreach ($data['line_items'] as $li) {
                $qtyUnit = ! empty($li['quantity_unit_id']) ? ProductUnit::find($li['quantity_unit_id']) : null;
                $qf      = $qtyUnit?->factor ?? 1;
                $base      = (float) $li['quantity_input'] * (float) $qf;

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

                SalesReturnItem::create([
                    'sales_return_id'  => $ret->id,
                    'product_id'                                    => $li['product_id'],
                    'qty_input'        => $li['quantity_input'],
                    'qty_unit_id'                      => $qtyUnit?->id,
                    'qty_unit_factor' => $qf,
                    'base_qty' => $base,
                    'billing_unit_id'  => $billUnit?->id,
                    'billing_unit_factor'                     => $bf,
                    'rate_per_billing_unit'     => $rateBill,
                    'unit_price_base'  => $unitBase,
                    'line_subtotal'                                => $lineSub,
                    'discount_percent' => (float) ($li['discount_percent'] ?? 0),
                    'discount_amount' => $discAmt,
                    'line_total'           => $lineTotal,
                ]);

                $subtotal += $lineSub;
                $totalDisc += $discAmt;
            }

            $tax = (float) ($data['tax_amount'] ?? 0);

            $ret->update([
                'subtotal'    => round($subtotal, 2),
                'total_discount' => round($totalDisc, 2),
                'tax_amount'  => round($tax, 2),
                'grand_total' => round($subtotal - $totalDisc + $tax, 2),
            ]);

            return $ret->load('items');
        });

        return (new SalesReturnResource($ret))->response()->setStatusCode(201);
    }

    public function show(SalesReturn $salesReturn)
    {
        return new SalesReturnResource($salesReturn->load('items'));
    }

    public function update(SalesReturnRequest $req, SalesReturn $salesReturn)
    {
        $data = $req->validated();

        $ret = DB::transaction(function () use ($data, $salesReturn) {
            $salesReturn->update([
                'customer_id' => $data['customer_id'],
                'return_no'   => $data['return_no'] ?? $salesReturn->return_no,
                'return_date' => $data['return_date'] ?? $salesReturn->return_date,
                'notes'       => $data['notes'] ?? $salesReturn->notes,
                'terms'       => $data['terms'] ?? $salesReturn->terms,
            ]);

            $salesReturn->items()->delete();

            $subtotal = 0;
            $totalDisc = 0;
            foreach ($data['line_items'] as $li) {
                $qtyUnit = ! empty($li['quantity_unit_id']) ? ProductUnit::find($li['quantity_unit_id']) : null;
                $qf      = $qtyUnit?->factor ?? 1;
                $base      = (float) $li['quantity_input'] * (float) $qf;

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

                SalesReturnItem::create([
                    'sales_return_id'  => $salesReturn->id,
                    'product_id'                            => $li['product_id'],
                    'qty_input'        => $li['quantity_input'],
                    'qty_unit_id'                      => $qtyUnit?->id,
                    'qty_unit_factor' => $qf,
                    'base_qty' => $base,
                    'billing_unit_id'  => $billUnit?->id,
                    'billing_unit_factor'                     => $bf,
                    'rate_per_billing_unit'     => $rateBill,
                    'unit_price_base'  => $unitBase,
                    'line_subtotal'                                => $lineSub,
                    'discount_percent' => (float) ($li['discount_percent'] ?? 0),
                    'discount_amount' => $discAmt,
                    'line_total'           => $lineTotal,
                ]);

                $subtotal += $lineSub;
                $totalDisc += $discAmt;
            }

            $tax = (float) ($data['tax_amount'] ?? $salesReturn->tax_amount);

            $salesReturn->update([
                'subtotal'    => round($subtotal, 2),
                'total_discount' => round($totalDisc, 2),
                'tax_amount'  => round($tax, 2),
                'grand_total' => round($subtotal - $totalDisc + $tax, 2),
            ]);

            return $salesReturn->load('items');
        });

        return new SalesReturnResource($ret);
    }

    public function destroy(SalesReturn $salesReturn)
    {
        $salesReturn->items()->delete();
        $salesReturn->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /** Posting: stock-in to inventory_movements (signed +ve quantity_base) */
    public function post(SalesReturn $salesReturn)
    {
        $salesReturn->load('items');

        return DB::transaction(function () use ($salesReturn) {
            foreach ($salesReturn->items as $it) {
                InventoryMovement::create([
                    'product_id'     => $it->product_id,
                    'warehouse_id'   => null,
                    'batch_id'       => null,
                    'quantity_base'  => (float) $it->base_qty, // positive for IN
                    'unit_cost_base' => 0,                     // TODO: costing if needed
                    'document_type'  => 'sales_return',
                    'document_id'    => $salesReturn->id,
                    'meta'           => [
                        'return_no'        => $salesReturn->return_no,
                        'return_date'      => optional($salesReturn->return_date)->toDateString(),
                        'qty_input'        => $it->qty_input,
                        'qty_unit_id'               => $it->qty_unit_id,
                        'qty_unit_factor' => $it->qty_unit_factor,
                        'billing_unit_id'  => $it->billing_unit_id,
                        'billing_unit_factor' => $it->billing_unit_factor,
                        'unit_price_base'  => $it->unit_price_base,
                        'line_subtotal'       => $it->line_subtotal,
                        'discount_percent' => $it->discount_percent,
                        'discount_amount'    => $it->discount_amount,
                        'line_total'       => $it->line_total,
                    ],
                    'created_by'     => auth()->id(),
                ]);
            }
            $salesReturn->update(['status' => 'Posted']);
            return response()->json(['message' => 'Sales return posted to stock.']);
        });
    }

    /** Optional reversal: delete movements */
    public function unpost(SalesReturn $salesReturn)
    {
        return DB::transaction(function () use ($salesReturn) {
            InventoryMovement::where('document_type', 'sales_return')
                ->where('document_id', $salesReturn->id)->delete();
            $salesReturn->update(['status' => 'Saved']);
            return response()->json(['message' => 'Sales return unposted.']);
        });
    }

    protected function gen(string $p): string
    {
        return $p . '-' . substr((string) now()->timestamp, -6);
    }
}
