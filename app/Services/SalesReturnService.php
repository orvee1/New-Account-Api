<?php

namespace App\Services;

use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class SalesReturnService
{
    public function createReturn(array $payload, int $userId): SalesReturn
    {
        $companyId = Auth::user()->company_id;

        return DB::transaction(function () use ($payload, $userId, $companyId) {
            // Create Return
            $return = SalesReturn::create([
                'company_id'       => $companyId,
                'customer_id'      => $payload['customer_id'],
                'sales_invoice_id' => Arr::get($payload, 'sales_invoice_id'),
                'return_no'        => $payload['return_no'] ?? 'RET-' . now()->timestamp,
                'return_date'      => $payload['return_date'],
                'reason'           => Arr::get($payload, 'reason'),
                'notes'            => Arr::get($payload, 'notes'),
                'created_by'       => $userId,
            ]);

            // Add Items
            $totals = $this->attachReturnItems($return, $payload['items'] ?? []);

            // Update totals
            $return->update([
                'subtotal'        => $totals['subtotal'],
                'discount_total'  => $totals['discount_total'],
                'tax_amount'      => $totals['tax_amount'],
                'total_amount'    => $totals['total_amount'],
            ]);

            return $return;
        });
    }

    private function attachReturnItems(SalesReturn $return, array $items): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $lineTotal = (int)$item['quantity'] * (float)$item['unit_price'];
            $discountAmount = (float)Arr::get($item, 'discount_amount', 0);
            $taxAmount = (float)Arr::get($item, 'tax_amount', 0);

            SalesReturnItem::create([
                'sales_return_id'      => $return->id,
                'sales_invoice_item_id' => Arr::get($item, 'sales_invoice_item_id'),
                'product_id'           => $item['product_id'],
                'quantity'             => $item['quantity'],
                'unit_price'           => $item['unit_price'],
                'discount_amount'      => $discountAmount,
                'tax_amount'           => $taxAmount,
                'line_total'           => $lineTotal - $discountAmount + $taxAmount,
            ]);

            $subtotal += $lineTotal;
            $discountTotal += $discountAmount;
            $taxTotal += $taxAmount;
        }

        $totalAmount = $subtotal - $discountTotal + $taxTotal;

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_amount' => $taxTotal,
            'total_amount' => $totalAmount,
        ];
    }
}
