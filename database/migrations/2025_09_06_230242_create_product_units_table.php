<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->string('name', 50);
            $table->decimal('factor', 18, 6)->default(1);
            $table->boolean('is_base')->default(false);
            $table->timestamps();

            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            // one product cannot have two base units
            $table->unique(['product_id','name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
