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
        Schema::create('inventory_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->bigInteger('reference_id');
            $table->string('reference_type'); // 'sale', 'purchase', 'adjustment'
            $table->decimal('qty_in', 15, 4)->default(0);
            $table->decimal('qty_out', 15, 4)->default(0);
            $table->decimal('qty_balance', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->decimal('new_weighted_avg_cost', 15, 4)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_ledger');
    }
};
