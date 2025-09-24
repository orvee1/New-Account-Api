<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchase_bills', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('vendor_id')->index();
            $t->string('bill_no')->index();
            $t->date('bill_date');
            $t->date('due_date')->nullable();
            $t->unsignedBigInteger('warehouse_id')->nullable(); // header-level default
            $t->text('notes')->nullable();

            // totals (computed)
            $t->decimal('subtotal', 16, 4)->default(0);
            $t->decimal('discount_total', 16, 4)->default(0);
            $t->decimal('tax_amount', 16, 4)->default(0);
            $t->decimal('total_amount', 16, 4)->default(0);

            $t->unsignedBigInteger('created_by');
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->unique(['company_id','bill_no']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('purchase_bills');
    }
};
