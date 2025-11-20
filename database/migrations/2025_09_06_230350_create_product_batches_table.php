<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('product_batches', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('company_id')->index();
      $t->unsignedBigInteger('product_id')->index();
      $t->string('batch_no')->nullable()->index();
      $t->date('manufactured_at')->nullable();
      $t->date('expired_at')->nullable();
      $t->timestamps();
      $t->unique(['company_id','product_id','batch_no']);
    });
  }
  public function down(): void { Schema::dropIfExists('product_batches'); }
};

