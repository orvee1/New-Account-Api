<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('chart_accounts', function (Blueprint $table) {
      $table->id();
      $table->foreignId('company_id')->constrained()->cascadeOnDelete();

      $table->foreignId('parent_id')->nullable()->constrained('chart_accounts')->cascadeOnDelete();

      // group = general ledger (folder), ledger = posting account (leaf)
      $table->enum('type', ['group', 'ledger'])->default('group');

      $table->string('code')->nullable();           // optional: accounting number like 1,1.1,1.1.1
      $table->string('name');
      $table->string('slug')->nullable();

      // materialized path for fast breadcrumbs / unique constraints
      $table->string('path')->index();              // e.g. /1/1.1/1.1.1
      $table->unsignedInteger('depth')->default(0); // root=0, child=1 ...

      $table->unsignedInteger('sort_order')->default(0);
      $table->boolean('is_active')->default(true);

      $table->timestamps();

      // Same name under same parent for same company → unique
      $table->unique(['company_id','parent_id','name']);

      // Optional: prevent children under ledger at DB level (MySQL 8+ supports CHECK)
      // $table->check("NOT (type='ledger' AND EXISTS (SELECT 1 FROM chart_accounts c WHERE c.parent_id = id))");
    });
  }

  public function down(): void {
    Schema::dropIfExists('chart_accounts');
  }
};
