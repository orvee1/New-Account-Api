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
        Schema::create('sales_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('sales_invoices')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_accounts');
            $table->enum('dr_cr', ['dr', 'cr']);
            $table->decimal('amount', 15, 4);
            $table->string('narration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_journal_entries');
    }
};
