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
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->decimal('trade_discount_amt', 15, 2)->default(0)->after('subtotal');
            $table->decimal('line_discount_amt', 15, 2)->default(0)->after('trade_discount_amt');
            $table->decimal('taxable_amount', 15, 2)->default(0)->after('line_discount_amt');
            $table->decimal('vat_amount', 15, 2)->default(0)->after('taxable_amount');
            $table->decimal('ait_amount', 15, 2)->default(0)->after('vat_amount');
            $table->decimal('invoice_discount_amt', 15, 2)->default(0)->after('ait_amount');
            $table->foreignId('invoice_discount_account_id')->nullable()->after('invoice_discount_amt')->constrained('chart_accounts');
            $table->decimal('grand_total', 15, 2)->default(0)->after('invoice_discount_account_id');
            $table->enum('vat_mode', ['exclusive', 'inclusive'])->default('exclusive')->after('grand_total');
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->foreignId('sale_uom_id')->nullable()->after('product_id')->constrained('product_uoms');
            $table->foreignId('price_uom_id')->nullable()->after('sale_uom_id')->constrained('product_uoms');
            $table->decimal('quantity_in_sale_uom', 15, 4)->default(0)->after('price_uom_id');
            $table->decimal('quantity_in_base_uom', 15, 4)->default(0)->after('quantity_in_sale_uom');
            $table->decimal('unit_price_original', 15, 4)->default(0)->after('quantity_in_base_uom');
            $table->decimal('trade_discount_pct', 5, 2)->default(0)->after('unit_price_original');
            $table->decimal('trade_discount_amt', 15, 4)->default(0)->after('trade_discount_pct');
            $table->decimal('net_unit_price', 15, 4)->default(0)->after('trade_discount_amt');
            $table->decimal('line_gross_amount', 15, 4)->default(0)->after('net_unit_price');
            $table->decimal('line_discount_pct', 5, 2)->default(0)->after('line_gross_amount');
            $table->decimal('line_discount_amt', 15, 4)->default(0)->after('line_discount_pct');
            $table->decimal('line_subtotal', 15, 4)->default(0)->after('line_discount_amt');
            $table->decimal('vat_rate', 5, 2)->default(0)->after('line_subtotal');
            $table->decimal('vat_amount', 15, 4)->default(0)->after('vat_rate');
            $table->decimal('ait_rate', 5, 2)->default(0)->after('vat_amount');
            $table->decimal('ait_amount', 15, 4)->default(0)->after('ait_rate');
            $table->decimal('weighted_avg_cost', 15, 4)->default(0)->after('ait_amount');
            $table->decimal('cogs', 15, 4)->default(0)->after('weighted_avg_cost');
            $table->decimal('gross_profit', 15, 4)->default(0)->after('cogs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['invoice_discount_account_id']);
            $table->dropColumn([
                'trade_discount_amt',
                'line_discount_amt',
                'taxable_amount',
                'vat_amount',
                'ait_amount',
                'invoice_discount_amt',
                'invoice_discount_account_id',
                'grand_total',
                'vat_mode'
            ]);
        });

        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['sale_uom_id']);
            $table->dropForeign(['price_uom_id']);
            $table->dropColumn([
                'sale_uom_id',
                'price_uom_id',
                'quantity_in_sale_uom',
                'quantity_in_base_uom',
                'unit_price_original',
                'trade_discount_pct',
                'trade_discount_amt',
                'net_unit_price',
                'line_gross_amount',
                'line_discount_pct',
                'line_discount_amt',
                'line_subtotal',
                'vat_rate',
                'vat_amount',
                'ait_rate',
                'ait_amount',
                'weighted_avg_cost',
                'cogs',
                'gross_profit'
            ]);
        });
    }
};
