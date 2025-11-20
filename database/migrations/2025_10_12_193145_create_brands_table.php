<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name', 150);
            $table->string('slug', 180)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active','inactive'])->default('active')->index();
            $table->timestamps();

            $table->unique(['company_id','name']); // scoped unique
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
