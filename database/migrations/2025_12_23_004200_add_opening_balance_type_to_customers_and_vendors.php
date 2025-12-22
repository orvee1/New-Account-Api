<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'opening_balance_type')) {
                $table->enum('opening_balance_type', ['debit', 'credit'])
                    ->nullable()
                    ->after('opening_balance');
            }
        });

        Schema::table('vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('vendors', 'opening_balance_type')) {
                $table->enum('opening_balance_type', ['debit', 'credit'])
                    ->nullable()
                    ->after('opening_balance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'opening_balance_type')) {
                $table->dropColumn('opening_balance_type');
            }
        });

        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'opening_balance_type')) {
                $table->dropColumn('opening_balance_type');
            }
        });
    }
};
