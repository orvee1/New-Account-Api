<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            if (! Schema::hasColumn('fixed_assets', 'journal_entry_id')) {
                $table->unsignedBigInteger('journal_entry_id')->nullable()->after('id')->index();
            }
        });

        Schema::table('asset_depreciations', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_depreciations', 'journal_entry_id')) {
                $table->unsignedBigInteger('journal_entry_id')->nullable()->after('id')->index();
            }
        });

        Schema::table('asset_disposals', function (Blueprint $table) {
            if (! Schema::hasColumn('asset_disposals', 'journal_entry_id')) {
                $table->unsignedBigInteger('journal_entry_id')->nullable()->after('id')->index();
            }
        });

        $this->ensureLedgerAccounts();
    }

    public function down(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            if (Schema::hasColumn('fixed_assets', 'journal_entry_id')) {
                $table->dropColumn('journal_entry_id');
            }
        });

        Schema::table('asset_disposals', function (Blueprint $table) {
            if (Schema::hasColumn('asset_disposals', 'journal_entry_id')) {
                $table->dropColumn('journal_entry_id');
            }
        });

        Schema::table('asset_depreciations', function (Blueprint $table) {
            if (Schema::hasColumn('asset_depreciations', 'journal_entry_id')) {
                $table->dropColumn('journal_entry_id');
            }
        });
    }

    private function ensureLedgerAccounts(): void
    {
        $companies = DB::table('companies')->pluck('id');

        foreach ($companies as $companyId) {
            $this->ensureLedger($companyId, '1.2.1.11', 'Fixed Asset Ledger', '1.2.1', 'asset', 'debit');
            $this->ensureLedger($companyId, '1.2.1.10.1', 'Accumulated Depreciation Ledger', '1.2.1.10', 'asset', 'credit');
            $this->ensureLedger($companyId, '5.2.9.1', 'Depreciation Expense', '5.2.9', 'expense', 'debit');
            $this->ensureLedger($companyId, '4.2.3.1', 'Gain on Asset Disposal', '4.2.3', 'income', 'credit');
            $this->ensureLedger($companyId, '5.2.18.1', 'Loss on Asset Disposal', '5.2.18', 'expense', 'debit');
        }
    }

    private function ensureLedger(int $companyId, string $code, string $name, string $parentCode, string $baseType, string $normalBalance): void
    {
        if (DB::table('chart_accounts')->where('company_id', $companyId)->where('code', $code)->exists()) {
            return;
        }

        $parent = DB::table('chart_accounts')
            ->where('company_id', $companyId)
            ->where('code', $parentCode)
            ->first();

        if (! $parent) {
            return;
        }

        $id = DB::table('chart_accounts')->insertGetId([
            'company_id' => $companyId,
            'parent_id' => $parent->id,
            'type' => 'ledger',
            'base_type' => $baseType,
            'normal_balance' => $normalBalance,
            'code' => $code,
            'name' => $name,
            'slug' => strtolower(str_replace([' ', '/', '&'], '-', $name)),
            'path' => '/',
            'depth' => ((int) ($parent->depth ?? 0)) + 1,
            'sort_order' => 0,
            'is_active' => true,
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $parentPath = trim((string) ($parent->path ?? ''), '/');
        $path = $parentPath ? "/{$parentPath}/{$id}" : "/{$id}";

        DB::table('chart_accounts')->where('id', $id)->update(['path' => $path]);
    }
};
