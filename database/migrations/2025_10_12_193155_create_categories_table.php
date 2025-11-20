<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name', 150);
            $table->string('slug', 180)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active','inactive'])->default('active')->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['company_id','name']); // scoped unique
            // $table->foreign('parent_id')->references('id')->on('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
