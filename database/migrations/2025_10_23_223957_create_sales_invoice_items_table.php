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
       Schema::create('sales_invoice_items', function (Blueprint $t) {
            $t->id();

            $t->unsignedBigInteger('sales_invoice_id')->index();
            $t->unsignedBigInteger('product_id')->index();

            // Quantity as typed by user + unit normalization snapshot
            $t->decimal('qty_input', 18, 6);
            $t->unsignedBigInteger('qty_unit_id')->nullable()->index();
            $t->decimal('qty_unit_factor', 18, 6)->default(1);
            $t->decimal('base_qty', 18, 6);

            // Billing unit & rate snapshot
            $t->unsignedBigInteger('billing_unit_id')->nullable()->index();
            $t->decimal('billing_unit_factor', 18, 6)->default(1);
            $t->decimal('rate_per_billing_unit', 18, 6)->default(0);
            $t->decimal('unit_price_base', 18, 6)->default(0); // rate / billing_factor

            // Money totals
            $t->decimal('line_subtotal', 18, 2)->default(0);
            $t->decimal('discount_percent', 10, 4)->default(0);
            $t->decimal('discount_amount', 18, 2)->default(0);
            $t->decimal('vat_percent', 10, 4)->default(0);
            $t->decimal('vat_amount', 18, 2)->default(0);
            $t->decimal('line_total', 18, 2)->default(0);

            $t->json('snap')->nullable(); // optional: product/unit labels snapshot

            $t->timestamps();

            // FKs
            $t->foreign('sales_invoice_id')
              ->references('id')->on('sales_invoices')
              ->onDelete('cascade');

            $t->foreign('product_id')
              ->references('id')->on('products')
              ->restrictOnDelete();

            $t->foreign('qty_unit_id')
              ->references('id')->on('product_units')
              ->nullOnDelete();

            $t->foreign('billing_unit_id')
              ->references('id')->on('product_units')
              ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
    }
};
