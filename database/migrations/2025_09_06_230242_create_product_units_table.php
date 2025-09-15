<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_units', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('product_id')->index();
            $t->string('name');            // e.g., pcs, box
            $t->decimal('factor', 16, 6);  // to base unit
            $t->boolean('is_base')->default(false);
            $t->timestamps();

            $t->unique(['product_id', 'name']);
        });
    }
    public function down(): void { Schema::dropIfExists('product_units'); }
};
