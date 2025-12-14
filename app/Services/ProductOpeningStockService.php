<?php

namespace App\Services;

use App\Models\ChartAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductOpeningStockService
{
    /**
     * Create journal entry for opening stock value.
     * Rules (typical perpetual inventory):
     *  - DR Inventory (Asset)
     *  - CR Opening Balances (Equity)
     *
     * @return JournalEntry|null
     */
    public function createOpeningStockJournal(Product $product, ?float $openingQty = null, ?float $unitCost = null, ?string $entryDate = null)
    {
        // only Stock products
        if (($product->product_type ?? null) !== 'Stock') {
            return null;
        }

        $qty  = (float) ($openingQty ?? 0);
        $cost = (float) ($unitCost ?? $product->costing_price ?? 0);

        if ($qty <= 0 || $cost <= 0) {
            return null;
        }

        $amount = round($qty * $cost, 2);
        if ($amount <= 0) {
            return null;
        }

        $companyId = (int) $product->company_id;

        $inventory = $this->getInventoryAccount($companyId);
        $openingEquity = $this->getOpeningEquity($companyId);

        if (!$inventory || !$openingEquity) {
            throw new \Exception('Required chart accounts are missing (Inventory / Opening Balances). Run ChartAccountSeeder.');
        }

        // idempotency: already created?
        $existing = JournalEntry::query()
            ->where('company_id', $companyId)
            ->where('reference_type', Product::class)
            ->where('reference_id', $product->id)
            ->where('description', 'like', 'Opening stock for product:%')
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($product, $qty, $cost, $amount, $inventory, $openingEquity, $entryDate) {

            $je = JournalEntry::create([
                'entry_date'     => $entryDate ? Carbon::parse($entryDate) : Carbon::today(),
                'company_id'     => $product->company_id,
                'reference_id'   => $product->id,
                'reference_type' => Product::class,
                'description'    => 'Opening stock for product: ' . ($product->name ?? ''),
                'created_by'     => $product->created_by ?? auth()->id(),
            ]);

            // DR Inventory
            JournalLine::create([
                'journal_entry_id' => $je->id,
                'chart_account_id' => $inventory->id,
                'debit'            => $amount,
                'credit'           => 0,
                'narration'        => "Opening stock (Qty: {$qty}, UnitCost: {$cost}) - {$product->name}",
            ]);

            // CR Opening Balances (Equity)
            JournalLine::create([
                'journal_entry_id' => $je->id,
                'chart_account_id' => $openingEquity->id,
                'debit'            => 0,
                'credit'           => $amount,
                'narration'        => 'Offset opening stock (Opening Balances)',
            ]);

            return $je;
        });
    }

    public function getInventoryAccount(int $companyId): ?ChartAccount
    {
        $inventory = ChartAccount::query()
            ->where('slug', 'inventory')
            ->where('company_id', $companyId)
            ->first();

        if ($inventory) return $inventory;

        // try parent group "inventory" first; fallback "current-assets"
        $parent = ChartAccount::query()
            ->where('company_id', $companyId)
            ->where('type', 'group')
            ->whereIn('slug', ['inventory', 'current-assets'])
            ->first();

        if (!$parent) return null;

        $inventory = ChartAccount::create([
            'parent_id'  => $parent->id,
            'type'       => 'ledger',
            'company_id' => $companyId,
            'name'       => 'Inventory',
            'slug'       => 'inventory',
        ]);

        $inventory->path = rtrim($parent->path ?? '', '/') . '/' . $inventory->id;
        $inventory->save();

        return $inventory;
    }

    public function getOpeningEquity(int $companyId): ?ChartAccount
    {
        // NOTE: company_id filter must be applied (আপনার আগের সার্ভিসে এটা miss ছিল)
        $openingEquity = ChartAccount::query()
            ->where('slug', 'opening-balances')
            ->where('company_id', $companyId)
            ->first();

        if ($openingEquity) return $openingEquity;

        $parent = ChartAccount::query()
            ->where([
                'slug'       => 'owners-capital',
                'type'       => 'group',
                'company_id' => $companyId,
            ])->first();

        if (!$parent) return null;

        $openingEquity = ChartAccount::create([
            'parent_id'  => $parent->id,
            'type'       => 'ledger',
            'company_id' => $companyId,
            'name'       => 'Opening Balances',
            'slug'       => 'opening-balances',
        ]);

        $openingEquity->path = rtrim($parent->path ?? '', '/') . '/' . $openingEquity->id;
        $openingEquity->save();

        return $openingEquity;
    }
}
