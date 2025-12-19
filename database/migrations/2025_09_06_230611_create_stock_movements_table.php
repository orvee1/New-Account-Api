<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();

            // opening, purchase_in, sale_out, adjustment_in, adjustment_out, transfer_in, transfer_out
            $table->string('movement_type', 50)->index();

            $table->decimal('qty_in', 18, 4)->default(0);
            $table->decimal('qty_out', 18, 4)->default(0);

            $table->decimal('unit_cost', 18, 4)->nullable();
            $table->decimal('total_cost', 18, 4)->nullable();

            $table->nullableMorphs('reference'); // reference_type + reference_id
            $table->timestamp('occurred_at')->index();

            $table->foreignId('created_by')->nullable()->index();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'product_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
