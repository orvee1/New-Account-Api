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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->string('proprietor_name')->nullable();
            $table->string('customer_number')->nullable(); // optional external code
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('nid')->nullable();
            $table->text('bank_details')->nullable();
            $table->text('notes')->nullable();

            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->date('opening_balance_date')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Unique constraints within a company
            $table->unique(['company_id', 'customer_number']);
            $table->index(['company_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
