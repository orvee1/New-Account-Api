<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupSeededAccountingData extends Command
{
    protected $signature = 'accounting:cleanup-seeded-data
        {--company=1 : Company ID to clean}
        {--masters : Also remove exact seeded customers, vendors, products, brand, category, warehouse}
        {--dry-run : Show rows that would be deleted without deleting}
        {--force : Run cleanup without confirmation}';

    protected $description = 'Remove exact seeder-created accounting/demo data while keeping auth, company, menu, permissions, and user-entered rows intact.';

    private array $productSkus = [
        'PROD-001', 'PROD-002', 'PROD-003', 'PROD-004', 'PROD-005',
        'PROD-006', 'PROD-007', 'PROD-008', 'PROD-009', 'PROD-010',
        'PROD-NS-001', 'PROD-SVC-001', 'PROD-CMB-001',
    ];

    private array $customerEmails = [
        'contact@acme.com', 'info@globaltech.com', 'sales@digitalinno.com',
        'contact@premium.com', 'info@sunrise.com', 'contact@bluesky.com',
        'support@nextgen.com', 'hello@stellar.com', 'contact@quantum.com',
        'sales@phoenix.com',
    ];

    private array $vendorEmails = [
        'vendor1@techworld.com', 'vendor2@digital.com', 'vendor3@hardwarehub.com',
        'vendor4@wholesale.com', 'vendor5@premiumit.com', 'vendor6@globalsupply.com',
        'vendor7@metrotech.com', 'vendor8@easterntraders.com',
        'vendor9@centurytech.com', 'vendor10@advancedsupply.com',
    ];

    public function handle(): int
    {
        $companyId = (int) $this->option('company');
        $ids = $this->seededIds($companyId);
        $journalIds = $this->seededJournalIds($companyId, $ids);
        $counts = $this->counts($companyId, $ids, $journalIds);

        $this->table(['Exact Seeded Data', 'Rows'], collect($counts)->map(fn ($count, $name) => [$name, $count])->all());

        if ($this->option('dry-run')) {
            $this->info('Dry run only. Nothing was deleted.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Delete only these exact seeded rows?')) {
            $this->warn('Cleanup cancelled.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($companyId, $ids, $journalIds) {
            if ($journalIds->isNotEmpty()) {
                DB::table('journal_lines')->whereIn('journal_entry_id', $journalIds)->delete();
                DB::table('journal_entries')->whereIn('id', $journalIds)->delete();
            }

            if ($ids['products']->isNotEmpty()) {
                DB::table('product_combo_items')
                    ->whereIn('product_id', $ids['products'])
                    ->orWhereIn('item_product_id', $ids['products'])
                    ->delete();
                $this->deleteWhereIn('product_stocks', 'product_id', $ids['products']);
                $this->deleteWhereIn('stock_movements', 'product_id', $ids['products']);
            }

            if ($this->option('masters')) {
                $this->deleteWhereIn('products', 'id', $ids['products']);
                $this->deleteWhereIn('customers', 'id', $ids['customers']);
                $this->deleteWhereIn('vendors', 'id', $ids['vendors']);

                DB::table('warehouses')->where('company_id', $companyId)->where('name', 'Main Warehouse')->delete();
                DB::table('brands')->where('company_id', $companyId)->where('name', 'Generic Brand')->delete();
                DB::table('categories')->where('company_id', $companyId)->where('name', 'General')->delete();
            }
        });

        $this->info('Exact seeded/demo accounting data cleanup complete.');
        return self::SUCCESS;
    }

    private function seededIds(int $companyId): array
    {
        return [
            'products' => Product::query()->where('company_id', $companyId)->whereIn('sku', $this->productSkus)->pluck('id'),
            'customers' => Customer::query()->where('company_id', $companyId)->whereIn('email', $this->customerEmails)->pluck('id'),
            'vendors' => Vendor::query()->where('company_id', $companyId)->whereIn('email', $this->vendorEmails)->pluck('id'),
        ];
    }

    private function seededJournalIds(int $companyId, array $ids)
    {
        return DB::table('journal_entries')
            ->where('company_id', $companyId)
            ->where(function ($query) use ($ids) {
                $query->where(function ($q) {
                    $q->whereIn('reference_type', ['Opening Balance', 'Sales', 'Expense', 'Transfer'])
                        ->whereNull('reference_id')
                        ->whereIn('description', [
                            'Opening Balance - Cash',
                            'Cash Sale - Product A',
                            'Office Supplies Purchase',
                            'Cash deposited to Bank',
                            'Cash Sale - Product B',
                        ]);
                });

                if ($ids['products']->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($ids) {
                        $q->where('reference_type', Product::class)
                            ->whereIn('reference_id', $ids['products'])
                            ->where('description', 'like', 'Opening stock for product:%');
                    });
                }

                if ($ids['customers']->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($ids) {
                        $q->where('reference_type', Customer::class)
                            ->whereIn('reference_id', $ids['customers'])
                            ->where('description', 'like', 'Opening balance for customer%');
                    });
                }

                if ($ids['vendors']->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($ids) {
                        $q->where('reference_type', Vendor::class)
                            ->whereIn('reference_id', $ids['vendors'])
                            ->where('description', 'like', 'Opening Balance for Vendor:%');
                    });
                }
            })
            ->pluck('id');
    }

    private function counts(int $companyId, array $ids, $journalIds): array
    {
        return [
            'journal_entries' => $journalIds->count(),
            'journal_lines' => DB::table('journal_lines')->whereIn('journal_entry_id', $journalIds)->count(),
            'product_stocks' => $this->countWhereIn('product_stocks', 'product_id', $ids['products']),
            'stock_movements' => $this->countWhereIn('stock_movements', 'product_id', $ids['products']),
            'products' => $this->option('masters') ? $ids['products']->count() : 0,
            'customers' => $this->option('masters') ? $ids['customers']->count() : 0,
            'vendors' => $this->option('masters') ? $ids['vendors']->count() : 0,
            'warehouses' => $this->option('masters') ? DB::table('warehouses')->where('company_id', $companyId)->where('name', 'Main Warehouse')->count() : 0,
            'brands' => $this->option('masters') ? DB::table('brands')->where('company_id', $companyId)->where('name', 'Generic Brand')->count() : 0,
            'categories' => $this->option('masters') ? DB::table('categories')->where('company_id', $companyId)->where('name', 'General')->count() : 0,
        ];
    }

    private function countWhereIn(string $table, string $column, $ids): int
    {
        if ($ids->isEmpty() || ! DB::getSchemaBuilder()->hasTable($table) || ! DB::getSchemaBuilder()->hasColumn($table, $column)) {
            return 0;
        }

        return DB::table($table)->whereIn($column, $ids)->count();
    }

    private function deleteWhereIn(string $table, string $column, $ids): void
    {
        if ($ids->isEmpty() || ! DB::getSchemaBuilder()->hasTable($table) || ! DB::getSchemaBuilder()->hasColumn($table, $column)) {
            return;
        }

        DB::table($table)->whereIn($column, $ids)->delete();
    }
}
