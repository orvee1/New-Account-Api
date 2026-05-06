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
        Schema::table('purchase_bill_items', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_uom_id')->nullable()->after('product_id');
            $table->unsignedBigInteger('price_uom_id')->nullable()->after('purchase_uom_id');
            $table->decimal('quantity_in_purchase_uom', 15, 4)->default(0)->after('price_uom_id');
            $table->decimal('quantity_in_base_uom', 15, 4)->default(0)->after('quantity_in_purchase_uom');
            $table->decimal('unit_price_original', 15, 4)->default(0)->after('quantity_in_base_uom');
            $table->decimal('trade_discount_pct', 5, 2)->default(0)->after('unit_price_original');
            $table->decimal('trade_discount_amt', 15, 4)->default(0)->after('trade_discount_pct');
            $table->decimal('net_unit_price', 15, 4)->default(0)->after('trade_discount_amt');
            $table->decimal('line_gross_amount', 15, 4)->default(0)->after('net_unit_price');
            $table->decimal('line_discount_pct', 5, 2)->default(0)->after('line_gross_amount');
            $table->decimal('line_discount_amt', 15, 4)->default(0)->after('line_discount_pct');
            // line_subtotal already exists
            $table->decimal('vat_rate', 5, 2)->default(0)->after('line_subtotal');
            $table->decimal('vat_amount', 15, 4)->default(0)->after('vat_rate');
            $table->decimal('ait_rate', 5, 2)->default(0)->after('vat_amount');
            $table->decimal('ait_amount', 15, 4)->default(0)->after('ait_rate');
            $table->decimal('net_unit_cost', 15, 4)->default(0)->after('ait_amount');
            $table->decimal('weighted_avg_cost_before', 15, 4)->default(0)->after('net_unit_cost');
            $table->decimal('weighted_avg_cost_after', 15, 4)->default(0)->after('weighted_avg_cost_before');

            $table->foreign('purchase_uom_id')->references('id')->on('product_uoms');
            $table->foreign('price_uom_id')->references('id')->on('product_uoms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_bill_items', function (Blueprint $table) {
            $table->dropForeign(['purchase_uom_id']);
            $table->dropForeign(['price_uom_id']);
            
            $table->dropColumn([
                'purchase_uom_id',
                'price_uom_id',
                'quantity_in_purchase_uom',
                'quantity_in_base_uom',
                'unit_price_original',
                'trade_discount_pct',
                'trade_discount_amt',
                'net_unit_price',
                'line_gross_amount',
                'line_discount_pct',
                'line_discount_amt',
                'vat_rate',
                'vat_amount',
                'ait_rate',
                'ait_amount',
                'net_unit_cost',
                'weighted_avg_cost_before',
                'weighted_avg_cost_after',
            ]);
        });
    }
};
