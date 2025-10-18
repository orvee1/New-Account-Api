<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->index();

            // Types: Stock | Non-stock | Service | Combo
            $table->enum('product_type', ['Stock','Non-stock','Service','Combo'])->index();

            $table->string('name', 255);
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 255)->nullable();

            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedBigInteger('brand_id')->nullable()->index();

            $table->string('unit', 50)->nullable();

            $table->decimal('costing_price', 18, 4)->nullable();
            $table->decimal('sales_price', 18, 4)->nullable();
            $table->decimal('tax_percent', 6, 2)->nullable();

            $table->boolean('has_warranty')->default(false);
            $table->unsignedInteger('warranty_days')->nullable();
            $table->date('manufactured_at')->nullable();
            $table->date('expired_at')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active','inactive'])->default('active')->index();

            $table->json('meta')->nullable();

            $table->timestamps();

            // Scoped uniques
            $table->unique(['company_id', 'sku']);
            $table->unique(['company_id', 'barcode']);

            // FK hints (optional, keep nullable safe)
            // $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            // $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
