<?php

namespace App\Services;

use App\Models\InventoryLedger;
use App\Models\Product;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Warehouse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class SalesReturnService
{
    public function __construct(
        private AccountingPostingService $postingService,
        private StockService $stockService
    ) {
    }

    public function createReturn(array $payload, int $userId): SalesReturn
    {
        $companyId = Auth::guard('sanctum')->user()?->company_id ?? Auth::user()?->company_id;

        if (! $companyId) {
            throw new Exception('Authenticated company context is required.');
        }

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

            $this->postReturnJournal($return->refresh()->load(['customer', 'items.salesInvoiceItem', 'items.product']));

            return $return->load(['customer', 'items.product']);
        });
    }

    public function deleteReturn(SalesReturn $return): void
    {
        DB::transaction(function () use ($return) {
            $return->loadMissing(['items.salesInvoiceItem', 'items.product', 'salesInvoice']);

            foreach ($return->items as $item) {
                $product = Product::query()
                    ->where('company_id', $return->company_id)
                    ->lockForUpdate()
                    ->find($item->product_id);

                $invoiceItem = $item->salesInvoiceItem;

                if (! $product || ! $invoiceItem || ! in_array($product->product_type, ['Stock', 'Combo'], true)) {
                    continue;
                }

                $soldQty = max((float) $invoiceItem->quantity, 0.000001);
                $qtyBase = round((float) $item->quantity * ((float) $invoiceItem->quantity_in_base_uom / $soldQty), 6);
                $unitCost = (float) $invoiceItem->weighted_avg_cost;
                $removedValue = $qtyBase * $unitCost;
                $oldQty = (float) $product->current_stock_in_base_uom;
                $oldAvg = (float) $product->weighted_avg_cost;
                $oldValue = $oldQty * $oldAvg;
                $newQty = round($oldQty - $qtyBase, 4);
                $newAvg = $oldAvg;

                if ($newQty > 0) {
                    $newAvg = ($oldValue - $removedValue) / $newQty;
                } elseif ($newQty == 0.0) {
                    $newAvg = 0;
                }

                $product->update([
                    'current_stock_in_base_uom' => $newQty,
                    'weighted_avg_cost' => $newAvg,
                ]);

                InventoryLedger::create([
                    'product_id' => $product->id,
                    'reference_id' => $return->id,
                    'reference_type' => 'sales_return_reversal',
                    'qty_in' => 0,
                    'qty_out' => $qtyBase,
                    'qty_balance' => $newQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $removedValue,
                    'new_weighted_avg_cost' => $newAvg,
                ]);

                $warehouseId = $this->resolveWarehouseId($return, $product);
                if ($warehouseId) {
                    $this->stockService->removeSalesReturnStock(
                        $product,
                        $warehouseId,
                        $qtyBase,
                        $unitCost,
                        $return,
                        $return->return_date?->toDateString(),
                        Auth::id() ?? $return->created_by
                    );
                }
            }

            $this->postingService->deleteForReference($return->company_id, SalesReturn::class, $return->id);
            $return->delete();
        });
    }

    private function attachReturnItems(SalesReturn $return, array $items): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $invoiceItem = SalesInvoiceItem::query()
                ->whereHas('salesInvoice', function ($query) use ($return) {
                    $query->where('company_id', $return->company_id);

                    if ($return->sales_invoice_id) {
                        $query->where('id', $return->sales_invoice_id);
                    }
                })
                ->with('salesInvoice')
                ->findOrFail($item['sales_invoice_item_id']);

            if ((int) $invoiceItem->product_id !== (int) $item['product_id']) {
                throw new Exception('Returned product does not match the original invoice item.');
            }

            if ((int) $invoiceItem->salesInvoice->customer_id !== (int) $return->customer_id) {
                throw new Exception('Returned item does not belong to the selected customer.');
            }

            $quantity = (float) $item['quantity'];
            $alreadyReturned = (float) SalesReturnItem::query()
                ->where('sales_invoice_item_id', $invoiceItem->id)
                ->whereHas('salesReturn', fn ($query) => $query->whereNull('deleted_at'))
                ->sum('quantity');

            if ($alreadyReturned + $quantity > (float) $invoiceItem->quantity) {
                throw new Exception('Return quantity cannot exceed the original sold quantity.');
            }

            $lineTotal = $quantity * (float)$item['unit_price'];
            $discountAmount = (float)Arr::get($item, 'discount_amount', 0);
            $taxAmount = (float)Arr::get($item, 'tax_amount', 0);
            $lineNet = $lineTotal - $discountAmount + $taxAmount;

            SalesReturnItem::create([
                'sales_return_id'      => $return->id,
                'sales_invoice_item_id' => $invoiceItem->id,
                'product_id'           => $item['product_id'],
                'quantity'             => $quantity,
                'unit_price'           => $item['unit_price'],
                'discount_amount'      => $discountAmount,
                'tax_amount'           => $taxAmount,
                'line_total'           => $lineNet,
            ]);

            $this->restoreReturnedStock($return, $invoiceItem, $quantity);

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

    private function restoreReturnedStock(SalesReturn $return, SalesInvoiceItem $invoiceItem, float $quantity): void
    {
        $product = Product::query()
            ->where('company_id', $return->company_id)
            ->lockForUpdate()
            ->findOrFail($invoiceItem->product_id);

        if (! in_array($product->product_type, ['Stock', 'Combo'], true)) {
            return;
        }

        $soldQty = max((float) $invoiceItem->quantity, 0.000001);
        $qtyBase = round($quantity * ((float) $invoiceItem->quantity_in_base_uom / $soldQty), 6);
        $unitCost = (float) $invoiceItem->weighted_avg_cost;
        $returnCost = round($qtyBase * $unitCost, 4);

        $oldQty = (float) $product->current_stock_in_base_uom;
        $oldAvg = (float) $product->weighted_avg_cost;
        $newQty = round($oldQty + $qtyBase, 4);
        $newAvg = $newQty > 0
            ? (($oldQty * $oldAvg) + $returnCost) / $newQty
            : 0;

        $product->update([
            'current_stock_in_base_uom' => $newQty,
            'weighted_avg_cost' => $newAvg,
        ]);

        InventoryLedger::create([
            'product_id' => $product->id,
            'reference_id' => $return->id,
            'reference_type' => 'sales_return',
            'qty_in' => $qtyBase,
            'qty_out' => 0,
            'qty_balance' => $newQty,
            'unit_cost' => $unitCost,
            'total_cost' => $returnCost,
            'new_weighted_avg_cost' => $newAvg,
        ]);

        $warehouseId = $this->resolveWarehouseId($return, $product);
        if ($warehouseId) {
            $this->stockService->addSalesReturnStock(
                $product,
                $warehouseId,
                $qtyBase,
                $unitCost,
                $return,
                $return->return_date?->toDateString(),
                $return->created_by
            );
        }
    }

    private function postReturnJournal(SalesReturn $return): void
    {
        $return->loadMissing(['customer', 'items.salesInvoiceItem']);

        $salesReturnDebit = round((float) $return->subtotal - (float) $return->discount_total, 2);
        $taxDebit = round((float) $return->tax_amount, 2);
        $receivableCredit = round((float) $return->total_amount, 2);
        $cogsCredit = 0.0;

        foreach ($return->items as $item) {
            $invoiceItem = $item->salesInvoiceItem;
            if (! $invoiceItem) {
                continue;
            }

            $soldQty = max((float) $invoiceItem->quantity, 0.000001);
            $qtyBase = (float) $item->quantity * ((float) $invoiceItem->quantity_in_base_uom / $soldQty);
            $cogsCredit += $qtyBase * (float) $invoiceItem->weighted_avg_cost;
        }

        $lines = [];

        if ($salesReturnDebit > 0) {
            $lines[] = [
                'key' => 'sales_revenue',
                'debit' => $salesReturnDebit,
                'credit' => 0,
                'narration' => "Sales return {$return->return_no}",
            ];
        }

        if ($taxDebit > 0) {
            $lines[] = [
                'key' => 'tax_payable',
                'debit' => $taxDebit,
                'credit' => 0,
                'narration' => "VAT reversed {$return->return_no}",
            ];
        }

        if ($receivableCredit > 0) {
            $receivableLine = [
                'debit' => 0,
                'credit' => $receivableCredit,
                'narration' => "Receivable reduced {$return->return_no}",
            ];

            if ($return->customer?->chart_account_id) {
                $receivableLine['account_id'] = $return->customer->chart_account_id;
            } else {
                $receivableLine['key'] = 'accounts_receivable';
            }

            $lines[] = $receivableLine;
        }

        if (round($cogsCredit, 2) > 0) {
            $lines[] = [
                'key' => 'inventory',
                'debit' => round($cogsCredit, 2),
                'credit' => 0,
                'narration' => "Inventory restored {$return->return_no}",
            ];
            $lines[] = [
                'key' => 'cogs',
                'debit' => 0,
                'credit' => round($cogsCredit, 2),
                'narration' => "COGS reversed {$return->return_no}",
            ];
        }

        if (count($lines) >= 2) {
            $this->postingService->post([
                'company_id' => $return->company_id,
                'reference_type' => SalesReturn::class,
                'reference_id' => $return->id,
                'entry_date' => $return->return_date?->toDateString() ?? now()->toDateString(),
                'description' => "Sales Return #{$return->return_no}",
                'created_by' => Auth::id() ?? $return->created_by,
                'lines' => $lines,
            ]);
        }
    }

    private function resolveWarehouseId(SalesReturn $return, Product $product): ?int
    {
        $warehouseId = $return->salesInvoice?->warehouse_id ?? $product->warehouse_id;

        if ($warehouseId) {
            return (int) $warehouseId;
        }

        return Warehouse::query()
            ->where('company_id', $return->company_id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->value('id');
    }
}
