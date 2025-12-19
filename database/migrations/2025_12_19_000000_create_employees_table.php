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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('nid_number')->nullable();
            $table->text('address')->nullable();
            $table->string('department');
            $table->string('designation');
            $table->date('date_of_joining');
            $table->enum('employment_type', ['permanent', 'contract', 'temporary', 'casual'])->default('permanent');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('company_id');
            $table->index('department');
            $table->index('status');
            $table->index('employment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
