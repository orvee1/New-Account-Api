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
        Schema::create('account_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('reconciliation_date');
            $table->decimal('beginning_balance', 15, 2);
            $table->decimal('ending_balance', 15, 2);
            $table->decimal('cleared_deposits', 15, 2)->default(0);
            $table->decimal('cleared_payments', 15, 2)->default(0);
            $table->decimal('difference', 15, 2)->default(0);
            $table->json('cleared_transactions')->nullable(); // Store IDs of cleared transactions
            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'account_id']);
            $table->index(['user_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_reconciliations');
    }
};
