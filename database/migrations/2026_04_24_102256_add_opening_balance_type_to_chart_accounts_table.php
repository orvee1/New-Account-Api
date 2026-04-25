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
            if (!Schema::hasColumn('chart_accounts', 'opening_balance')) {
                $table->decimal('opening_balance', 16, 2)->default(0)->after('is_active');
            }
            if (!Schema::hasColumn('chart_accounts', 'opening_balance_type')) {
                $table->enum('opening_balance_type', ['debit', 'credit'])->nullable()->after('opening_balance');
            }
            if (!Schema::hasColumn('chart_accounts', 'opening_date')) {
                $table->date('opening_date')->nullable()->after('opening_balance_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->dropColumn(['opening_balance', 'opening_balance_type', 'opening_date']);
        });
    }
};
