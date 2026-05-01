<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('receipts')) {
            Schema::create('receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('credit_account_id')->nullable()->constrained('chart_accounts')->nullOnDelete();
                $table->string('receipt_number')->unique();
                $table->date('receipt_date');
                $table->decimal('amount_received', 15, 2);
                $table->string('payment_mode');
                $table->string('reference_number')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->default('completed');
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                $table->foreignId('debit_account_id')->nullable()->constrained('chart_accounts')->nullOnDelete();
                $table->string('payment_number')->unique();
                $table->date('payment_date');
                $table->decimal('amount_paid', 15, 2);
                $table->string('payment_mode');
                $table->string('invoice_reference')->nullable();
                $table->string('cheque_number')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->default('completed');
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('contras')) {
            Schema::create('contras', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('from_account_id')->constrained('chart_accounts');
                $table->foreignId('to_account_id')->constrained('chart_accounts');
                $table->unsignedBigInteger('from_bank_id')->nullable();
                $table->unsignedBigInteger('to_bank_id')->nullable();
                $table->string('contra_number')->unique();
                $table->date('contra_date');
                $table->decimal('amount', 15, 2);
                $table->decimal('debit_amount', 15, 2)->default(0);
                $table->decimal('credit_amount', 15, 2)->default(0);
                $table->string('reference_number')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->default('completed');
                $table->boolean('reconciled')->default(false);
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('debit_notes')) {
            Schema::create('debit_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                $table->foreignId('credit_account_id')->nullable()->constrained('chart_accounts')->nullOnDelete();
                $table->string('debit_note_number')->unique();
                $table->string('invoice_reference')->nullable();
                $table->date('note_date');
                $table->string('reason')->nullable();
                $table->decimal('quantity', 15, 2)->nullable();
                $table->decimal('amount', 15, 2);
                $table->boolean('quality_issue')->default(false);
                $table->decimal('price_adjustment', 15, 2)->default(0);
                $table->decimal('damage_amount', 15, 2)->default(0);
                $table->text('description')->nullable();
                $table->string('status')->default('completed');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->date('approval_date')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('credit_notes')) {
            Schema::create('credit_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('debit_account_id')->nullable()->constrained('chart_accounts')->nullOnDelete();
                $table->string('credit_note_number')->unique();
                $table->string('invoice_reference')->nullable();
                $table->date('note_date');
                $table->string('reason')->nullable();
                $table->decimal('quantity_returned', 15, 2)->nullable();
                $table->decimal('amount', 15, 2);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->string('return_reason')->nullable();
                $table->decimal('claim_amount', 15, 2)->default(0);
                $table->text('description')->nullable();
                $table->string('status')->default('completed');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->date('approval_date')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('manual_journals')) {
            Schema::create('manual_journals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('journal_number')->unique();
                $table->date('journal_date');
                $table->foreignId('debit_account_id')->constrained('chart_accounts');
                $table->foreignId('credit_account_id')->constrained('chart_accounts');
                $table->decimal('debit_amount', 15, 2);
                $table->decimal('credit_amount', 15, 2);
                $table->text('description')->nullable();
                $table->string('reference_number')->nullable();
                $table->text('narration')->nullable();
                $table->string('status')->default('posted');
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->date('posted_date')->nullable();
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('recurring_transactions')) {
            Schema::create('recurring_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('transaction_number')->unique();
                $table->string('type');
                $table->string('frequency')->nullable();
                $table->foreignId('from_account_id')->nullable()->constrained('chart_accounts')->nullOnDelete();
                $table->foreignId('to_account_id')->nullable()->constrained('chart_accounts')->nullOnDelete();
                $table->decimal('amount', 15, 2);
                $table->text('description')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->date('next_date')->nullable();
                $table->string('status')->default('active');
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('transaction_transfers')) {
            Schema::create('transaction_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('transfer_number')->unique();
                $table->foreignId('from_account_id')->constrained('chart_accounts');
                $table->foreignId('to_account_id')->constrained('chart_accounts');
                $table->decimal('amount', 15, 2);
                $table->date('transfer_date');
                $table->string('reference_number')->nullable();
                $table->text('description')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('completed');
                $table->unsignedBigInteger('recorded_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_transfers');
        Schema::dropIfExists('recurring_transactions');
        Schema::dropIfExists('manual_journals');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('debit_notes');
        Schema::dropIfExists('contras');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('receipts');
    }
};
