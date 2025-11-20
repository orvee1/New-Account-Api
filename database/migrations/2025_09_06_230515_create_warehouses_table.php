<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('warehouses', function (Blueprint $t) {
      $t->id();
      $t->unsignedBigInteger('company_id')->index();
      $t->string('name');
      $t->boolean('is_default')->default(false);
      $t->timestamps();
      $t->unique(['company_id','name']);
    });
  }
  public function down(): void { Schema::dropIfExists('warehouses'); }
};