<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchase_bill_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('purchase_bill_id')->index();
            $t->unsignedBigInteger('product_id')->index();

            // qty
            $t->unsignedBigInteger('qty_unit_id');  // in this unit qty entered
            $t->decimal('qty', 16, 6);
            $t->decimal('qty_base', 16, 6);         // server computed

            // rate
            $t->unsignedBigInteger('rate_unit_id'); // unit of rate
            $t->decimal('rate_per_unit', 16, 6);
            $t->decimal('rate_per_base', 16, 6);    // server computed

            // discount
            $t->decimal('discount_percent', 16, 6)->default(0);
            $t->decimal('discount_amount', 16, 6)->default(0);

            // line totals
            $t->decimal('line_subtotal', 16, 4);    // qty_base * rate_per_base
            $t->decimal('line_total', 16, 4);       // after discount

            // stock locus
            $t->unsignedBigInteger('warehouse_id')->nullable();
            $t->unsignedBigInteger('batch_id')->nullable();
            $t->string('batch_no')->nullable();     // for quick view
            $t->date('manufactured_at')->nullable();
            $t->date('expired_at')->nullable();

            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('purchase_bill_items');
    }
};
