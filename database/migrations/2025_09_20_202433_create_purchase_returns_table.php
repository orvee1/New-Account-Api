<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchase_returns', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('vendor_id')->index();
            $t->string('return_no')->index();
            $t->date('return_date');
            $t->unsignedBigInteger('warehouse_id')->nullable();
            $t->text('notes')->nullable();

            $t->decimal('subtotal', 16, 4)->default(0);
            $t->decimal('discount_total', 16, 4)->default(0);
            $t->decimal('tax_amount', 16, 4)->default(0);
            $t->decimal('total_amount', 16, 4)->default(0);

            $t->unsignedBigInteger('created_by');
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->unique(['company_id','return_no']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('purchase_returns');
    }
};
