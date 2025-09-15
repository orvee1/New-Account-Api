<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->string('product_type'); // Stock | Non-stock | Service | Combo
            $t->string('name');
            $t->string('code')->nullable()->index(); // SKU
            $t->text('description')->nullable();

            // For non-combo (optional batch meta on product-level; real batch rows live in product_batches)
            $t->string('default_batch_no')->nullable();
            $t->date('default_manufactured_at')->nullable();
            $t->date('default_expired_at')->nullable();

            // Extra fields (simple; for flexible K/V see product_attributes)
            $t->string('extra_field1_name')->nullable();
            $t->string('extra_field1_value')->nullable();
            $t->string('extra_field2_name')->nullable();
            $t->string('extra_field2_value')->nullable();

            $t->string('category')->nullable();

            // Prices (base-unit)
            $t->decimal('costing_price', 16, 4)->default(0);
            $t->decimal('sales_price', 16, 4)->default(0);

            // Warranty
            $t->boolean('has_warranty')->default(false);
            $t->unsignedInteger('warranty_days')->default(0);

            // Base unit cache (for quick reads)
            $t->string('base_unit_name')->nullable();

            $t->unsignedBigInteger('created_by');
            $t->unsignedBigInteger('updated_by')->nullable();

            $t->softDeletes();
            $t->timestamps();

            $t->unique(['company_id', 'code']);
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};

