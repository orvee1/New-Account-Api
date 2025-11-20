<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('stock_movements', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('company_id')->index();
      $t->unsignedBigInteger('product_id')->index();
      $t->unsignedBigInteger('warehouse_id')->index();
      $t->unsignedBigInteger('product_batch_id')->nullable()->index();

      $t->enum('type', [
        'OPENING','PURCHASE','SALE','ADJUSTMENT','TRANSFER',
        'ASSEMBLY','DISASSEMBLY'
      ])->index();

      // Positive for inbound, negative for outbound; always in BASE UNIT
      $t->decimal('quantity', 24, 6);
      $t->string('unit_name')->nullable();           // original unit (for audit)
      $t->decimal('unit_factor_to_base', 16, 6)->nullable();

      $t->unsignedBigInteger('created_by');
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('stock_movements'); }
};
