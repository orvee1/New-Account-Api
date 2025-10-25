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
        Schema::create('estimates', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('customer_id')->index();
            $t->string('estimate_no', 50)->unique();
            $t->date('estimate_date');
            $t->date('expiry_date')->nullable();
            $t->boolean('is_draft')->default(true)->index();
            $t->text('notes')->nullable();
            $t->decimal('subtotal', 18, 2)->default(0);
            $t->decimal('total_discount', 18, 2)->default(0);
            $t->decimal('grand_total', 18, 2)->default(0);
            $t->timestamps();
            $t->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
