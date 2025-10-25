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
        Schema::create('sales_returns', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('customer_id')->index();
            $t->string('return_no', 50)->unique();
            $t->date('return_date');
            $t->text('notes')->nullable();
            $t->text('terms')->nullable();

            $t->decimal('subtotal', 18, 2)->default(0);
            $t->decimal('total_discount', 18, 2)->default(0);
            $t->decimal('tax_amount', 18, 2)->default(0);
            $t->decimal('grand_total', 18, 2)->default(0);

            $t->string('status', 20)->default('Saved')->index(); // Saved|Posted|Voided
            $t->timestamps();
            $t->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
