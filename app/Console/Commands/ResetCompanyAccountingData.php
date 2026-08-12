<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetCompanyAccountingData extends Command
{
    protected $signature = 'accounting:reset-company-data
        {--company=1 : Company ID to reset}
        {--dry-run : Show rows that would be deleted without deleting}
        {--force : Run reset without confirmation}';

    protected $description = 'Delete company business/accounting data while keeping Chart of Accounts and system/auth setup intact.';

    private array $preserveTables = [
        'migrations',
        'users',
        'companies',
        'company_users',
        'chart_accounts',
        'account_types',
        'company_settings',
        'menus',
        'permissions',
        'roles',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'personal_access_tokens',
        'settings',
        'site_setups',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
        'password_reset_tokens',
    ];

    private array $deleteFirstTables = [
        'journal_lines',
        'product_combo_items',
        'sales_invoice_items',
        'sales_order_items',
        'sales_return_items',
        'purchase_bill_items',
        'purchase_order_items',
        'purchase_return_items',
        'payroll_run_employees',
    ];

    public function handle(): int
    {
        $companyId = (int) $this->option('company');
        $tables = $this->companyTables();
        $counts = $this->counts($companyId, $tables);

        $this->warn('This keeps chart_accounts, company/login/system setup, menu/permissions, and settings.');
        $this->table(['Data', 'Rows'], collect($counts)->map(fn ($count, $name) => [$name, $count])->all());

        if ($this->option('dry-run')) {
            $this->info('Dry run only. Nothing was deleted.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete company {$companyId} data except Chart of Accounts?")) {
            $this->warn('Reset cancelled.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($companyId, $tables) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($this->deleteFirstTables as $table) {
                $this->deleteChildTable($table, $companyId);
            }

            foreach ($tables as $table) {
                DB::table($table)->where('company_id', $companyId)->delete();
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        });

        $this->info('Company data reset complete. Chart of Accounts and system setup were kept.');
        return self::SUCCESS;
    }

    private function companyTables(): Collection
    {
        return collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->filter(fn ($table) => ! in_array($table, $this->preserveTables, true))
            ->filter(fn ($table) => Schema::hasColumn($table, 'company_id'))
            ->values();
    }

    private function counts(int $companyId, Collection $tables): array
    {
        $counts = [];

        foreach ($this->deleteFirstTables as $table) {
            $count = $this->countChildTable($table, $companyId);
            if ($count > 0) {
                $counts[$table] = $count;
            }
        }

        foreach ($tables as $table) {
            $count = DB::table($table)->where('company_id', $companyId)->count();
            if ($count > 0) {
                $counts[$table] = $count;
            }
        }

        return $counts;
    }

    private function countChildTable(string $table, int $companyId): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        if (Schema::hasColumn($table, 'company_id')) {
            return DB::table($table)->where('company_id', $companyId)->count();
        }

        if ($table === 'journal_lines') {
            return DB::table($table)
                ->whereIn('journal_entry_id', DB::table('journal_entries')->select('id')->where('company_id', $companyId))
                ->count();
        }

        if ($table === 'product_combo_items') {
            return DB::table($table)
                ->whereIn('product_id', DB::table('products')->select('id')->where('company_id', $companyId))
                ->orWhereIn('item_product_id', DB::table('products')->select('id')->where('company_id', $companyId))
                ->count();
        }

        return 0;
    }

    private function deleteChildTable(string $table, int $companyId): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasColumn($table, 'company_id')) {
            DB::table($table)->where('company_id', $companyId)->delete();
            return;
        }

        if ($table === 'journal_lines') {
            DB::table($table)
                ->whereIn('journal_entry_id', DB::table('journal_entries')->select('id')->where('company_id', $companyId))
                ->delete();
            return;
        }

        if ($table === 'product_combo_items') {
            DB::table($table)
                ->whereIn('product_id', DB::table('products')->select('id')->where('company_id', $companyId))
                ->orWhereIn('item_product_id', DB::table('products')->select('id')->where('company_id', $companyId))
                ->delete();
        }
    }
}
