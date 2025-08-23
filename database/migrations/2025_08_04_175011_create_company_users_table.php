<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone_number');
            $table->string('password');
            $table->rememberToken();
            $table->enum('role', ['owner', 'admin', 'accountant','viewer'])->default('viewer');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->json('permissions')->nullable();
            $table->integer('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_users');
    }
};
