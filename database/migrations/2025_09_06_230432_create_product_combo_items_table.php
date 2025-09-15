<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('product_combo_items', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('company_id')->index();
      $t->unsignedBigInteger('combo_product_id')->index(); // parent product (type Combo)
      $t->unsignedBigInteger('item_product_id')->index();  // child product (Stock/Service/Non-stock)
      $t->decimal('quantity', 16, 6);
      $t->timestamps();
      $t->unique(['combo_product_id', 'item_product_id']);
    });
  }
  public function down(): void { Schema::dropIfExists('product_combo_items'); }
};