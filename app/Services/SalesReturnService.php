<?php

namespace App\Services;

use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Services\AccountMappingService;
use App\Services\JournalPostingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class SalesReturnService
{
    public function __construct(
        private AccountMappingService $accountMapping,
        private JournalPostingService $posting
    ) {}

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

            $this->postSalesReturnJournal($return, $userId);

            return $return;
        });
    }

    private function postSalesReturnJournal(SalesReturn $return, int $userId): void
    {
        $companyId = $return->company_id;
        $accountsReceivable = $this->accountMapping->accountsReceivable($companyId);
        $salesReturn = $this->accountMapping->salesReturn($companyId);

        if (!$accountsReceivable || !$salesReturn) {
            throw new \Exception('Required chart accounts are missing. Run ChartAccountSeeder.');
        }

        $amount = (float) $return->total_amount;

        $this->posting->postEntry(
            companyId: $companyId,
            entryDate: $return->return_date,
            description: "Sales Return #{$return->return_no}",
            referenceType: SalesReturn::class,
            referenceId: $return->id,
            createdBy: $userId,
            lines: [
                [
                    'account_id' => $salesReturn->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'narration' => 'Sales Return',
                ],
                [
                    'account_id' => $accountsReceivable->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'narration' => 'Accounts Receivable',
                ],
            ]
        );
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
