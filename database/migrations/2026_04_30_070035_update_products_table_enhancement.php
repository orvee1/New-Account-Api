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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'base_uom_id')) {
                $table->foreignId('base_uom_id')->nullable()->constrained('units_of_measure');
            }
            if (!Schema::hasColumn('products', 'weighted_avg_cost')) {
                $table->decimal('weighted_avg_cost', 15, 4)->default(0);
            }
            if (!Schema::hasColumn('products', 'current_stock_in_base_uom')) {
                $table->decimal('current_stock_in_base_uom', 15, 4)->default(0);
            }
            if (!Schema::hasColumn('products', 'vat_rate')) {
                $table->decimal('vat_rate', 5, 2)->default(0);
            }
            if (!Schema::hasColumn('products', 'vat_inclusive')) {
                $table->boolean('vat_inclusive')->default(false);
            }
            if (!Schema::hasColumn('products', 'ait_rate')) {
                $table->decimal('ait_rate', 5, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['base_uom_id']);
            $table->dropColumn([
                'base_uom_id',
                'weighted_avg_cost',
                'current_stock_in_base_uom',
                'vat_rate',
                'vat_inclusive',
                'ait_rate'
            ]);
        });
    }
};
