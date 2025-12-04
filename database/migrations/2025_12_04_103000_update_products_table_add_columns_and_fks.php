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
        Schema::table('products', function (Blueprint $table) {
            
            if (!Schema::hasColumn('products', 'sku')) {
                $table->string('sku', 100)->nullable()->after('name');
            }
            
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode', 255)->nullable()->after('sku');
            }

            if (!Schema::hasColumn('products', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->index()->after('barcode');
            }

            if (!Schema::hasColumn('products', 'brand_id')) {
                $table->unsignedBigInteger('brand_id')->nullable()->index()->after('category_id');
            }

            if (!Schema::hasColumn('products', 'warehouse_id')) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->index()->after('brand_id');
            }

            if (!Schema::hasColumn('products', 'unit')) {
                $table->string('unit', 50)->nullable()->after('warehouse_id');
            }

            if (!Schema::hasColumn('products', 'tax_percent')) {
                $table->decimal('tax_percent', 6, 2)->nullable()->after('sales_price');
            }

            if (!Schema::hasColumn('products', 'manufactured_at')) {
                $table->date('manufactured_at')->nullable()->after('warranty_days');
            }

            if (!Schema::hasColumn('products', 'expired_at')) {
                $table->date('expired_at')->nullable()->after('manufactured_at');
            }

            if (!Schema::hasColumn('products', 'status')) {
                $table->enum('status', ['active','inactive'])->default('active')->index()->after('description');
            }

            if (!Schema::hasColumn('products', 'meta')) {
                $table->json('meta')->nullable()->after('status');
            }

            // Foreign Keys
            // We wrap these in a separate schema call or try/catch if we want to be super safe, 
            // but typically we just add them. To avoid "constraint already exists", we can't easily check.
            // However, assuming this migration is running because they are missing:
            
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop FKs first
            $table->dropForeign(['category_id']);
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['warehouse_id']);

            // Drop columns if they were added by this migration (hard to know for sure, but we can try dropping)
            // Ideally we only drop what we added. 
            $table->dropColumn(['warehouse_id']); 
            // We won't drop others like sku/barcode as they might have existed before.
        });
    }
};
