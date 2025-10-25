<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesOrderRequest;
use App\Http\Resources\SalesOrderResource;
use App\Models\ProductUnit;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    public function index(Request $r)
    {
        $q = SalesOrder::where('company_id', auth()->user()->company_id)->with('items')->orderByDesc('id');
        if ($r->filled('q')) $q->where('order_no', 'like', '%' . $r->q . '%');
        if ($r->filled('status')) $q->where('status', $r->status);
        if ($r->filled('customer_id')) $q->where('customer_id', $r->customer_id);

        return SalesOrderResource::collection($q->paginate((int)($r->per_page ?? 20))->withQueryString());
    }

    public function store(SalesOrderRequest $req)
    {
        $data = $req->validated();
        $cid = auth()->user()->company_id;

        $so = DB::transaction(function () use ($data, $cid) {
            $so = SalesOrder::create([
                'company_id' => $cid,
                'customer_id' => $data['customer_id'],
                'order_no' => $data['order_no'] ?? $this->gen('SO'),
                'order_date' => $data['order_date'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'status' => $data['status'] ?? 'Draft',
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            $subtotal = 0;
            $totalDisc = 0;
            foreach ($data['line_items'] as $li) {
                $qtyUnit = !empty($li['quantity_unit_id']) ? ProductUnit::find($li['quantity_unit_id']) : null;
                $qf = $qtyUnit?->factor ?? 1;
                $base = (float)$li['quantity_input'] * (float)$qf;

                $billUnit = !empty($li['billing_unit_id']) ? ProductUnit::find($li['billing_unit_id']) : null;
                $bf = $billUnit?->factor ?? 1;

                $rateBill = (float)($li['rate_for_billing_unit'] ?? 0);
                $unitBase = $bf > 0 ? $rateBill / $bf : 0;
                $lineSub = $base * $unitBase;

                $discAmt = (float)($li['discount_amount'] ?? 0);
                if ($discAmt <= 0 && !empty($li['discount_percent'])) $discAmt = $lineSub * ((float)$li['discount_percent'] / 100);
                $lineTotal = $lineSub - $discAmt;

                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id' => $li['product_id'],
                    'qty_input' => $li['quantity_input'],
                    'qty_unit_id' => $qtyUnit?->id,
                    'qty_unit_factor' => $qf,
                    'base_qty' => $base,
                    'billing_unit_id' => $billUnit?->id,
                    'billing_unit_factor' => $bf,
                    'rate_per_billing_unit' => $rateBill,
                    'unit_price_base' => $unitBase,
                    'line_subtotal' => $lineSub,
                    'discount_percent' => (float)($li['discount_percent'] ?? 0),
                    'discount_amount' => $discAmt,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineSub;
                $totalDisc += $discAmt;
            }

            $tax = (float)($data['tax_amount'] ?? 0);

            $so->update([
                'subtotal' => round($subtotal, 2),
                'total_discount' => round($totalDisc, 2),
                'tax_amount' => round($tax, 2),
                'grand_total' => round($subtotal - $totalDisc + $tax, 2),
            ]);

            return $so->load('items');
        });

        return (new SalesOrderResource($so))->response()->setStatusCode(201);
    }

    public function show(SalesOrder $salesOrder)
    {
        return new SalesOrderResource($salesOrder->load('items'));
    }

    public function update(SalesOrderRequest $req, SalesOrder $salesOrder)
    {
        $data = $req->validated();

        $so = DB::transaction(function () use ($data, $salesOrder) {
            $salesOrder->update([
                'customer_id' => $data['customer_id'],
                'order_no' => $data['order_no'] ?? $salesOrder->order_no,
                'order_date' => $data['order_date'] ?? $salesOrder->order_date,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? $salesOrder->expected_delivery_date,
                'status' => $data['status'] ?? $salesOrder->status,
                'notes' => $data['notes'] ?? $salesOrder->notes,
                'terms' => $data['terms'] ?? $salesOrder->terms,
            ]);

            $salesOrder->items()->delete();

            $subtotal = 0;
            $totalDisc = 0;
            foreach ($data['line_items'] as $li) {
                $qtyUnit = !empty($li['quantity_unit_id']) ? ProductUnit::find($li['quantity_unit_id']) : null;
                $qf = $qtyUnit?->factor ?? 1;
                $base = (float)$li['quantity_input'] * (float)$qf;

                $billUnit = !empty($li['billing_unit_id']) ? ProductUnit::find($li['billing_unit_id']) : null;
                $bf = $billUnit?->factor ?? 1;

                $rateBill = (float)($li['rate_for_billing_unit'] ?? 0);
                $unitBase = $bf > 0 ? $rateBill / $bf : 0;
                $lineSub = $base * $unitBase;

                $discAmt = (float)($li['discount_amount'] ?? 0);
                if ($discAmt <= 0 && !empty($li['discount_percent'])) $discAmt = $lineSub * ((float)$li['discount_percent'] / 100);
                $lineTotal = $lineSub - $discAmt;

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $li['product_id'],
                    'qty_input' => $li['quantity_input'],
                    'qty_unit_id' => $qtyUnit?->id,
                    'qty_unit_factor' => $qf,
                    'base_qty' => $base,
                    'billing_unit_id' => $billUnit?->id,
                    'billing_unit_factor' => $bf,
                    'rate_per_billing_unit' => $rateBill,
                    'unit_price_base' => $unitBase,
                    'line_subtotal' => $lineSub,
                    'discount_percent' => (float)($li['discount_percent'] ?? 0),
                    'discount_amount' => $discAmt,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineSub;
                $totalDisc += $discAmt;
            }

            $tax = (float)($data['tax_amount'] ?? $salesOrder->tax_amount);

            $salesOrder->update([
                'subtotal' => round($subtotal, 2),
                'total_discount' => round($totalDisc, 2),
                'tax_amount' => round($tax, 2),
                'grand_total' => round($subtotal - $totalDisc + $tax, 2),
            ]);

            return $salesOrder->load('items');
        });

        return new SalesOrderResource($so);
    }

    public function destroy(SalesOrder $salesOrder)
    {
        $salesOrder->items()->delete();
        $salesOrder->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function confirm(SalesOrder $salesOrder)
    {
        $salesOrder->update(['status' => 'Confirmed']);
        return response()->json(['message' => 'Sales order confirmed.']);
    }
    public function cancel(SalesOrder $salesOrder)
    {
        $salesOrder->update(['status' => 'Cancelled']);
        return response()->json(['message' => 'Sales order cancelled.']);
    }

    protected function gen(string $p): string
    {
        return $p . '-' . substr((string)now()->timestamp, -6);
    }
}
