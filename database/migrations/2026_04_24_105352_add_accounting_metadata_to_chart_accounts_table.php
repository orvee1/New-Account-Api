<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chart_accounts', function (Blueprint $table) {
            // base_type helps in generating Balance Sheet vs Income Statement
            $table->enum('base_type', ['asset', 'liability', 'equity', 'income', 'expense'])
                  ->after('type')
                  ->nullable();

            // normal_balance helps in calculating the 'actual' balance
            $table->enum('normal_balance', ['debit', 'credit'])
                  ->after('base_type')
                  ->nullable();

            // is_system prevents users from deleting core accounts (Retained Earnings, A/R, etc.)
            $table->boolean('is_system')->default(false)->after('is_active');

            // Unique code per company
            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'code']);
            $table->dropColumn(['base_type', 'normal_balance', 'is_system']);
        });
    }
};
