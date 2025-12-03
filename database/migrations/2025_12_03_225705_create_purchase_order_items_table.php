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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id');

            $table->unsignedBigInteger('qty_unit_id');
            $table->decimal('qty', 18, 4);

            $table->decimal('qty_base', 18, 4);

            $table->unsignedBigInteger('rate_unit_id');
            $table->decimal('rate_per_unit', 18, 4);

            $table->decimal('discount_percent', 10, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);

            $table->decimal('line_subtotal', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
