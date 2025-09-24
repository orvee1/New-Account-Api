<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchase_return_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('purchase_return_id')->index();
            $t->unsignedBigInteger('product_id')->index();

            $t->unsignedBigInteger('qty_unit_id');
            $t->decimal('qty', 16, 6);
            $t->decimal('qty_base', 16, 6);

            $t->unsignedBigInteger('rate_unit_id');
            $t->decimal('rate_per_unit', 16, 6);
            $t->decimal('rate_per_base', 16, 6);

            $t->decimal('discount_percent', 16, 6)->default(0);
            $t->decimal('discount_amount', 16, 6)->default(0);

            $t->decimal('line_subtotal', 16, 4);
            $t->decimal('line_total', 16, 4);

            $t->unsignedBigInteger('warehouse_id')->nullable();
            $t->unsignedBigInteger('batch_id')->nullable();
            $t->string('batch_no')->nullable();
            $t->date('manufactured_at')->nullable();
            $t->date('expired_at')->nullable();

            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('purchase_return_items');
    }
};
