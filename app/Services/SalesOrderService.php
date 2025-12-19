<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class SalesOrderService
{
    public function createOrder(array $payload, int $userId): SalesOrder
    {
        $companyId = Auth::user()->company_id;

        return DB::transaction(function () use ($payload, $userId, $companyId) {
            // Create Sales Order
            $order = SalesOrder::create([
                'company_id'     => $companyId,
                'customer_id'    => $payload['customer_id'],
                'order_no'       => $payload['order_no'] ?? 'SO-' . now()->timestamp,
                'order_date'     => $payload['order_date'],
                'expected_delivery_date' => Arr::get($payload, 'expected_delivery_date'),
                'notes'          => Arr::get($payload, 'notes'),
                'status'         => 'pending',
                'created_by'     => $userId,
            ]);

            // Add Items
            $totals = $this->attachOrderItems($order, $payload['items'] ?? []);

            // Update totals
            $order->update([
                'subtotal'       => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_amount'     => $totals['tax_amount'],
                'total_amount'   => $totals['total_amount'],
            ]);

            return $order;
        });
    }

    public function updateOrder(SalesOrder $order, array $payload, int $userId): SalesOrder
    {
        return DB::transaction(function () use ($order, $payload, $userId) {
            // Delete existing items
            $order->items()->delete();

            // Update order
            $order->update([
                'customer_id'    => $payload['customer_id'],
                'order_date'     => $payload['order_date'],
                'expected_delivery_date' => Arr::get($payload, 'expected_delivery_date'),
                'notes'          => Arr::get($payload, 'notes'),
                'updated_by'     => $userId,
            ]);

            // Add new items
            $totals = $this->attachOrderItems($order, $payload['items'] ?? []);

            // Update totals
            $order->update([
                'subtotal'       => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_amount'     => $totals['tax_amount'],
                'total_amount'   => $totals['total_amount'],
            ]);

            return $order;
        });
    }

    public function convertToInvoice(SalesOrder $order): SalesInvoice
    {
        return DB::transaction(function () use ($order) {
            $companyId = $order->company_id;
            $userId = auth()->id();

            // Create Invoice from Order
            $invoice = SalesInvoice::create([
                'company_id'      => $companyId,
                'customer_id'     => $order->customer_id,
                'sales_order_id'  => $order->id,
                'invoice_no'      => 'INV-' . now()->timestamp,
                'invoice_date'    => now()->toDateString(),
                'due_date'        => now()->addDays(30)->toDateString(),
                'notes'           => $order->notes,
                'status'          => 'sent',
                'subtotal'        => $order->subtotal,
                'discount_total'  => $order->discount_total,
                'tax_amount'      => $order->tax_amount,
                'total_amount'    => $order->total_amount,
                'created_by'      => $userId,
            ]);

            // Copy items from order
            foreach ($order->items as $item) {
                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id'       => $item->product_id,
                    'quantity'         => $item->quantity,
                    'unit_price'       => $item->unit_price,
                    'discount_amount'  => $item->discount_amount,
                    'tax_amount'       => $item->tax_amount,
                    'line_total'       => $item->line_total,
                    'description'      => $item->description,
                ]);
            }

            // Update order status
            $order->update(['status' => 'confirmed']);

            return $invoice;
        });
    }

    private function attachOrderItems(SalesOrder $order, array $items): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $lineTotal = (int)$item['quantity'] * (float)$item['unit_price'];
            $discountAmount = (float)Arr::get($item, 'discount_amount', 0);
            $taxAmount = (float)Arr::get($item, 'tax_amount', 0);

            SalesOrderItem::create([
                'sales_order_id'  => $order->id,
                'product_id'      => $item['product_id'],
                'quantity'        => $item['quantity'],
                'unit_price'      => $item['unit_price'],
                'discount_amount' => $discountAmount,
                'tax_amount'      => $taxAmount,
                'line_total'      => $lineTotal - $discountAmount + $taxAmount,
                'description'     => Arr::get($item, 'description'),
            ]);

            $subtotal += $lineTotal;
            $discountTotal += $discountAmount;
            $taxTotal += $taxAmount;
        }

        $totalAmount = $subtotal - $discountTotal + $taxTotal;

        return compact('subtotal', 'discountTotal', 'taxTotal', 'totalAmount');
    }
}
