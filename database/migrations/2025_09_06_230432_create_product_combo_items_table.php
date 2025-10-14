<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_combo_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');       // combo product id
            $table->unsignedBigInteger('item_product_id');  // component product id
            $table->decimal('quantity', 18, 6)->default(1);
            $table->timestamps();

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            $table->foreign('item_product_id')
                  ->references('id')->on('products')
                  ->restrictOnDelete();

            $table->unique(['product_id','item_product_id']); // prevent duplicate line items
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_combo_items');
    }
};
