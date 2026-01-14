<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'chart_account_id')) {
                $table->foreignId('chart_account_id')
                    ->nullable()
                    ->constrained('chart_accounts')
                    ->nullOnDelete()
                    ->after('opening_balance_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'chart_account_id')) {
                $table->dropConstrainedForeignId('chart_account_id');
            }
        });
    }
};
