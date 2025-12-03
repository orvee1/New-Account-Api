<?php
namespace App\Services;

use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function create(array $payload): PurchaseOrder
    {
        $companyId = Auth::user()->company_id;

        return DB::transaction(function () use ($payload, $companyId) {

            $order = PurchaseOrder::create([
                'company_id'             => $companyId,
                'vendor_id'              => $payload['vendor_id'],
                'order_no'               => "PO-" . time(),
                'order_date'             => $payload['order_date'],
                'expected_delivery_date' => $payload['expected_delivery_date'] ?? null,
                'notes'                  => $payload['notes'] ?? null,
                'status'                 => 'submitted',
            ]);

            $subtotal      = 0;
            $discountTotal = 0;

            foreach ($payload['items'] as $item) {
                $lineSubtotal = $item['qty'] * $item['rate_per_unit'];
                $lineDiscount = $item['discount_amount'] ?? 0;

                $lineTotal = $lineSubtotal - $lineDiscount;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'company_id'        => $companyId,
                    'product_id'        => $item['product_id'],
                    'qty_unit_id'       => $item['qty_unit_id'],
                    'qty'               => $item['qty'],
                    'qty_base'          => $item['qty_base'],
                    'rate_unit_id'      => $item['rate_unit_id'],
                    'rate_per_unit'     => $item['rate_per_unit'],
                    'discount_percent'  => $item['discount_percent'] ?? 0,
                    'discount_amount'   => $item['discount_amount'] ?? 0,
                    'line_subtotal'     => $lineSubtotal,
                    'line_total'        => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $discountTotal += $lineDiscount;
            }

            $order->update([
                'subtotal'       => $subtotal,
                'discount_total' => $discountTotal,
                'total_amount'   => $subtotal - $discountTotal,
            ]);

            return $order->load('items');
        });
    }

   public function convertToBill(PurchaseOrder $order): PurchaseBill
    {
        $companyId = Auth::user()->company_id;
        $userId    = Auth::id();

        return DB::transaction(function () use ($order, $companyId, $userId) {

            //----------------------------------
            // 1) CREATE PURCHASE BILL HEADER
            //----------------------------------
            $bill = PurchaseBill::create([
                'company_id'   => $companyId,
                'vendor_id'    => $order->vendor_id,
                'bill_no'      => "PB-" . time(),
                'bill_date'    => now()->toDateString(),
                'due_date'     => now()->addDays(30)->toDateString(),
                'warehouse_id' => null,      // PO usually doesn't contain warehouse
                'notes'        => $order->notes,
                'tax_amount'   => $order->tax_amount,
                'created_by'   => $userId,
            ]);

            //----------------------------------
            // 2) MAP PO ITEMS → BILL ITEMS
            //----------------------------------
            $subtotal = 0;
            $discountTotal = 0;

            foreach ($order->items as $poItem) {

                // Create Bill Item
                $billItem = PurchaseBillItem::create([
                    'purchase_bill_id' => $bill->id,
                    'company_id'       => $companyId,
                    'product_id'       => $poItem->product_id,

                    'qty_unit_id'      => $poItem->qty_unit_id,
                    'qty'              => $poItem->qty,
                    'qty_base'         => $poItem->qty_base,

                    'rate_unit_id'     => $poItem->rate_unit_id,
                    'rate_per_unit'    => $poItem->rate_per_unit,

                    'discount_percent' => $poItem->discount_percent,
                    'discount_amount'  => $poItem->discount_amount,

                    'line_subtotal'    => $poItem->line_subtotal,
                    'line_total'       => $poItem->line_total,

                    'warehouse_id'     => $bill->warehouse_id,
                    'created_by'       => $userId,
                ]);

                //----------------------------------
                // 3) STOCK MOVEMENT (IN)
                //----------------------------------
                StockMovement::create([
                    'company_id'     => $companyId,
                    'product_id'     => $poItem->product_id,
                    'warehouse_id'   => $bill->warehouse_id,
                    'reference_id'   => $bill->id,
                    'reference_type' => 'purchase_bill',
                    'movement_type'  => 'in',
                    'qty'            => $poItem->qty_base,
                    'unit_price'     => $poItem->rate_per_unit,
                    'total_value'    => $poItem->line_total,
                    'created_by'     => $userId,
                ]);

                $subtotal      += $poItem->line_subtotal;
                $discountTotal += $poItem->discount_amount;
            }

            //----------------------------------
            // 4) UPDATE BILL TOTALS
            //----------------------------------
            $bill->update([
                'subtotal'       => $subtotal,
                'discount_total' => $discountTotal,
                'total_amount'   => $subtotal - $discountTotal + $order->tax_amount,
            ]);

            //----------------------------------
            // 5) MARK PO AS "converted"
            //----------------------------------
            $order->update([
                'status' => 'converted',
            ]);

            //----------------------------------
            // 6) RETURN FULL BILL
            //----------------------------------
            return $bill->load(['vendor', 'items.product']);
        });
    }
}
