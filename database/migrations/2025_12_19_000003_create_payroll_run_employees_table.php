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
        Schema::create('payroll_run_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('gross_salary', 12, 2);
            $table->decimal('deductions', 12, 2);
            $table->decimal('net_salary', 12, 2);
            $table->enum('status', ['pending', 'processed', 'undone'])->default('pending');
            $table->timestamps();

            // Indexes and constraints
            $table->unique(['payroll_run_id', 'employee_id']);
            $table->index('payroll_run_id');
            $table->index('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_run_employees');
    }
};
