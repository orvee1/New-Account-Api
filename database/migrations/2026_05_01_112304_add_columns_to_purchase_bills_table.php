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
        Schema::table('purchase_bills', function (Blueprint $table) {
            $table->string('supplier_ref_no', 100)->nullable()->after('bill_no');
            $table->enum('vat_mode', ['exclusive', 'inclusive'])->default('exclusive')->after('notes');
            $table->decimal('trade_discount_amt', 15, 4)->default(0)->after('subtotal');
            $table->decimal('line_discount_amt', 15, 4)->default(0)->after('trade_discount_amt');
            $table->decimal('taxable_amount', 15, 4)->default(0)->after('line_discount_amt');
            $table->decimal('vat_amount', 15, 4)->default(0)->after('taxable_amount');
            $table->decimal('ait_amount', 15, 4)->default(0)->after('vat_amount');
            $table->decimal('bill_discount_amt', 15, 4)->default(0)->after('ait_amount');
            $table->unsignedBigInteger('bill_discount_account_id')->nullable()->after('bill_discount_amt');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('bill_discount_account_id');
            
            $table->foreign('bill_discount_account_id')->references('id')->on('chart_accounts');
            
            $table->index('bill_date');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_bills', function (Blueprint $table) {
            $table->dropForeign(['bill_discount_account_id']);
            $table->dropIndex(['bill_date']);
            $table->dropIndex(['payment_status']);
            
            $table->dropColumn([
                'supplier_ref_no',
                'vat_mode',
                'trade_discount_amt',
                'line_discount_amt',
                'taxable_amount',
                'vat_amount',
                'ait_amount',
                'bill_discount_amt',
                'bill_discount_account_id',
                'payment_status',
            ]);
        });
    }
};
