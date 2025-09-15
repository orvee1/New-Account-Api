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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();

            // tenancy & audit
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            // core fields
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->string('proprietor_name')->nullable();
            $table->string('vendor_number');
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->string('nid')->nullable();
            $table->string('email')->nullable();
            $table->text('bank_details')->nullable();
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->text('notes')->nullable();

            // opening balance (immutable after create)
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->date('opening_balance_date')->nullable();

            // dynamic custom fields
            $table->json('custom_fields')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'vendor_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
