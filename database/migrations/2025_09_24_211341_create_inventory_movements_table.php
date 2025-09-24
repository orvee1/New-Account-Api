<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_movements', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('product_id')->index();
            $t->unsignedBigInteger('warehouse_id')->nullable()->index();
            $t->unsignedBigInteger('batch_id')->nullable()->index();

            // signed quantity in base unit (+ for in, - for out)
            $t->decimal('quantity_base', 16, 6);
            $t->decimal('unit_cost_base', 16, 6)->default(0); // cost per base unit

            // document link
            $t->string('document_type'); // purchase_bill | purchase_return | ...
            $t->unsignedBigInteger('document_id')->index();

            $t->json('meta')->nullable();
            $t->unsignedBigInteger('created_by');
            $t->timestamps();

            $t->index(['company_id','document_type','document_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('inventory_movements');
    }
};
