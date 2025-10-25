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
        Schema::create('sales_invoices', function (Blueprint $t) {
            $t->id();

            // Multi-tenant scope
            $t->unsignedBigInteger('company_id')->index();

            // Cash হলে customer_id null থাকতে পারে
            $t->unsignedBigInteger('customer_id')->nullable()->index();

            $t->enum('sale_type', ['cash','credit'])->default('credit')->index();
            $t->string('invoice_no', 50)->unique();
            $t->date('invoice_date');
            $t->date('due_date')->nullable();

            $t->text('notes')->nullable();
            $t->text('terms')->nullable();

            $t->decimal('subtotal', 18, 2)->default(0);
            $t->decimal('total_discount', 18, 2)->default(0);
            $t->decimal('total_vat', 18, 2)->default(0);
            $t->decimal('shipping_amount', 18, 2)->default(0);
            $t->decimal('grand_total', 18, 2)->default(0);

            $t->string('status', 30)->default('Unpaid')->index(); // Unpaid|Partially Paid|Paid|Cancelled

            $t->timestamps();

            // Optional FKs (keep nullable-friendly)
            $t->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            // company_id intentionally not FK-locked, depends on your companies table design
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
