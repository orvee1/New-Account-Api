<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesInvoiceRequest;
use App\Http\Resources\SalesInvoiceResource;
use App\Models\ProductUnit;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    public function index(Request $request)
    {
        /** @var Builder $q */
        $q = SalesInvoice::query()
            ->where('company_id', auth()->user()->company_id)
            ->with('items')
            ->orderByDesc('id');

        if ($term = $request->get('q')) {
            $q->where(function ($s) use ($term) {
                $s->where('invoice_no', 'like', "%{$term}%");
            });
        }
        if ($request->filled('status')) {
            $q->where('status', $request->get('status'));
        }
        if ($request->filled('customer_id')) {
            $q->where('customer_id', $request->get('customer_id'));
        }
        if ($request->filled('from_date')) {
            $q->whereDate('invoice_date', '>=', $request->get('from_date'));
        }
        if ($request->filled('to_date')) {
            $q->whereDate('invoice_date', '<=', $request->get('to_date'));
        }

        $per = (int) ($request->get('per_page', 20));
        return SalesInvoiceResource::collection($q->paginate($per)->withQueryString());
    }

    public function store(SalesInvoiceRequest $request)
    {
        $data      = $request->validated();
        $companyId = auth()->user()->company_id;

        $invoice = DB::transaction(function () use ($data, $companyId) {
            $invoice = SalesInvoice::create([
                'company_id'      => $companyId,
                'customer_id'     => $data['sale_type'] === 'cash' ? null : ($data['customer_id'] ?? null),
                'sale_type'       => $data['sale_type'],
                'invoice_no'      => $data['invoice_no'] ?? $this->generateNo('INV'),
                'invoice_date'    => $data['invoice_date'],
                'due_date'        => $data['sale_type'] === 'cash'
                    ? $data['invoice_date']
                    : ($data['due_date'] ?? null),
                'notes'           => $data['notes'] ?? null,
                'terms'           => $data['terms'] ?? null,
                'shipping_amount' => $data['shipping_amount'] ?? 0,
                'status'          => 'Unpaid',
            ]);

            $subtotal = 0; $totalDisc = 0; $totalVat = 0;

            foreach ($data['line_items'] as $li) {
                $qtyUnit   = ! empty($li['quantity_unit_id']) ? ProductUnit::find($li['quantity_unit_id']) : null;
                $qtyFactor = $qtyUnit?->factor ?? 1;
                $baseQty   = (float) $li['quantity_input'] * (float) $qtyFactor;

                $billUnit   = ! empty($li['billing_unit_id']) ? ProductUnit::find($li['billing_unit_id']) : null;
                $billFactor = $billUnit?->factor ?? 1;

                $rateBilling   = (float) ($li['rate_for_billing_unit'] ?? 0);
                $unitPriceBase = $billFactor > 0 ? $rateBilling / $billFactor : 0;
                $lineSubtotal  = $baseQty * $unitPriceBase;

                $discAmt = (float) ($li['discount_amount'] ?? 0);
                if ($discAmt <= 0 && ! empty($li['discount_percent'])) {
                    $discAmt = $lineSubtotal * ((float) $li['discount_percent'] / 100);
                }

                $afterDisc  = $lineSubtotal - $discAmt;
                $vatPercent = (float) ($li['vat_percent'] ?? 0);
                $vatAmt     = $afterDisc * ($vatPercent / 100);
                $lineTotal  = $afterDisc + $vatAmt;

                SalesInvoiceItem::create([
                    'sales_invoice_id'      => $invoice->id,
                    'product_id'            => $li['product_id'],
                    'qty_input'             => $li['quantity_input'],
                    'qty_unit_id'           => $qtyUnit?->id,
                    'qty_unit_factor'       => $qtyFactor,
                    'base_qty'              => $baseQty,
                    'billing_unit_id'       => $billUnit?->id,
                    'billing_unit_factor'   => $billFactor,
                    'rate_per_billing_unit' => $rateBilling,
                    'unit_price_base'       => $unitPriceBase,
                    'line_subtotal'         => $lineSubtotal,
                    'discount_percent'      => (float) ($li['discount_percent'] ?? 0),
                    'discount_amount'       => $discAmt,
                    'vat_percent'           => $vatPercent,
                    'vat_amount'            => $vatAmt,
                    'line_total'            => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $totalDisc += $discAmt;
                $totalVat += $vatAmt;
            }

            $invoice->update([
                'subtotal'       => round($subtotal, 2),
                'total_discount' => round($totalDisc, 2),
                'total_vat'      => round($totalVat, 2),
                'grand_total'    => round($subtotal - $totalDisc + $totalVat + ($invoice->shipping_amount ?? 0), 2),
            ]);

            return $invoice->load('items');
        });

        return (new SalesInvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load('items');
        return new SalesInvoiceResource($salesInvoice);
    }

    public function update(SalesInvoiceRequest $request, SalesInvoice $salesInvoice)
    {
        $data = $request->validated();

        $invoice = DB::transaction(function () use ($data, $salesInvoice) {
            $salesInvoice->update([
                'customer_id'     => $data['sale_type'] === 'cash' ? null : ($data['customer_id'] ?? null),
                'sale_type'       => $data['sale_type'],
                'invoice_no'      => $data['invoice_no'] ?? $salesInvoice->invoice_no,
                'invoice_date'    => $data['invoice_date'] ?? $salesInvoice->invoice_date,
                'due_date'        => $data['sale_type'] === 'cash'
                    ? ($data['invoice_date'] ?? $salesInvoice->invoice_date)
                    : ($data['due_date'] ?? $salesInvoice->due_date),
                'notes'           => $data['notes'] ?? $salesInvoice->notes,
                'terms'           => $data['terms'] ?? $salesInvoice->terms,
                'shipping_amount' => array_key_exists('shipping_amount', $data) ? $data['shipping_amount'] : $salesInvoice->shipping_amount,
            ]);

            // Rebuild items (simple approach)
            $salesInvoice->items()->delete();

            $subtotal = 0; $totalDisc = 0; $totalVat = 0;

            foreach ($data['line_items'] as $li) {
                $qtyUnit   = ! empty($li['quantity_unit_id']) ? ProductUnit::find($li['quantity_unit_id']) : null;
                $qtyFactor = $qtyUnit?->factor ?? 1;
                $baseQty   = (float) $li['quantity_input'] * (float) $qtyFactor;

                $billUnit   = ! empty($li['billing_unit_id']) ? ProductUnit::find($li['billing_unit_id']) : null;
                $billFactor = $billUnit?->factor ?? 1;

                $rateBilling   = (float) ($li['rate_for_billing_unit'] ?? 0);
                $unitPriceBase = $billFactor > 0 ? $rateBilling / $billFactor : 0;
                $lineSubtotal  = $baseQty * $unitPriceBase;

                $discAmt = (float) ($li['discount_amount'] ?? 0);
                if ($discAmt <= 0 && ! empty($li['discount_percent'])) {
                    $discAmt = $lineSubtotal * ((float) $li['discount_percent'] / 100);
                }

                $afterDisc  = $lineSubtotal - $discAmt;
                $vatPercent = (float) ($li['vat_percent'] ?? 0);
                $vatAmt     = $afterDisc * ($vatPercent / 100);
                $lineTotal  = $afterDisc + $vatAmt;

                SalesInvoiceItem::create([
                    'sales_invoice_id'      => $salesInvoice->id,
                    'product_id'            => $li['product_id'],
                    'qty_input'             => $li['quantity_input'],
                    'qty_unit_id'           => $qtyUnit?->id,
                    'qty_unit_factor'       => $qtyFactor,
                    'base_qty'              => $baseQty,
                    'billing_unit_id'       => $billUnit?->id,
                    'billing_unit_factor'   => $billFactor,
                    'rate_per_billing_unit' => $rateBilling,
                    'unit_price_base'       => $unitPriceBase,
                    'line_subtotal'         => $lineSubtotal,
                    'discount_percent'      => (float) ($li['discount_percent'] ?? 0),
                    'discount_amount'       => $discAmt,
                    'vat_percent'           => $vatPercent,
                    'vat_amount'            => $vatAmt,
                    'line_total'            => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $totalDisc += $discAmt;
                $totalVat += $vatAmt;
            }

            $salesInvoice->update([
                'subtotal'       => round($subtotal, 2),
                'total_discount' => round($totalDisc, 2),
                'total_vat'      => round($totalVat, 2),
                'grand_total'    => round($subtotal - $totalDisc + $totalVat + ($salesInvoice->shipping_amount ?? 0), 2),
            ]);

            return $salesInvoice->load('items');
        });

        return new SalesInvoiceResource($invoice);
    }

    public function destroy(SalesInvoice $salesInvoice)
    {
        // নরমালি ফাইন্যান্স ডকুমেন্ট ডিলিট করা ভালো প্র্যাকটিস নয়; চাইলে soft-delete যোগ করো
        $salesInvoice->items()->delete();
        $salesInvoice->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /** Stock Posting (out) */
    public function post(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load(['items', 'items.invoice']); // ensure items loaded

        return DB::transaction(function () use ($salesInvoice) {

            foreach ($salesInvoice->items as $it) {
                // signed base qty: sales => OUT => negative
                $signedBaseQty = -1 * (float) $it->base_qty;

                \App\Models\InventoryMovement::create([
                    'product_id'     => $it->product_id,
                    'warehouse_id'   => null, // চাইলে FE/BE থেকে আনো
                    'batch_id'       => null, // চাইলে FE/BE থেকে আনো

                    'quantity_base'  => $signedBaseQty,
                    'unit_cost_base' => 0, // TODO: avg/FIFO implement later

                    'document_type'  => 'sales_invoice',
                    'document_id'    => $salesInvoice->id,

                    'meta'           => [
                        'invoice_no'          => $salesInvoice->invoice_no,
                        'invoice_date'        => optional($salesInvoice->invoice_date)->toDateString(),
                        'product_label'       => optional($it->product)->name ?? null, // যদি relation add করো
                        'qty_input'           => $it->qty_input,
                        'qty_unit_id'         => $it->qty_unit_id,
                        'qty_unit_factor'     => $it->qty_unit_factor,
                        'billing_unit_id'     => $it->billing_unit_id,
                        'billing_unit_factor' => $it->billing_unit_factor,
                        'unit_price_base'     => $it->unit_price_base,
                        'line_subtotal'       => $it->line_subtotal,
                        
                        'discount_percent'    => $it->discount_percent,
                        'discount_amount'     => $it->discount_amount,
                        'vat_percent'         => $it->vat_percent,
                        'vat_amount'          => $it->vat_amount,
                        'line_total'          => $it->line_total,
                    ],
                    'created_by'     => auth()->id(),
                ]);
            }

            // চাইলে invoice status ফ্ল্যাগ দাও (e.g., 'Posted')
            // $salesInvoice->update(['status' => 'Posted']);

            return response()->json(['message' => 'Invoice posted to stock (inventory_movements created).']);
        });
    }

    /** Optional: Reverse posting (void) */
    public function void(SalesInvoice $salesInvoice)
    {
        // এখানে business policy অনুযায়ী reversal করবেন (inventory_movements-এ বিপরীত এন্ট্রি)
        return response()->json(['message' => 'Void not implemented yet']);
    }

    protected function generateNo(string $prefix): string
    {
        return $prefix . '-' . substr((string) now()->timestamp, -6);
    }
}
