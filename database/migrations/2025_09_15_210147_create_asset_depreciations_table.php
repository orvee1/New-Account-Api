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
        Schema::create('asset_depreciations', function (Blueprint $t) {
            $t->id();

            // Multi-tenant
            $t->unsignedBigInteger('company_id')->index();

            // FK to fixed_assets
            $t->unsignedBigInteger('fixed_asset_id')->index();

            // Creator/Updater (company_users id রাখলে int যথেষ্ট)
            $t->integer('created_by')->nullable();
            $t->integer('updated_by')->nullable();

            // Rule/Entry fields
            $t->string('method')->default('Straight Line');      // Straight Line | Reducing Balance
            $t->string('frequency')->default('Monthly');          // Monthly | Yearly
            $t->string('time_of_entry')->default('Last day of the period'); // UI default

            $t->decimal('amount', 12, 2)->default(0);             // per-period depreciation amount

            $t->string('debit_acc_name')->default('Depreciation Expense');
            $t->string('credit_acc_name')->default('Accumulated Depreciation');

            $t->date('start_date');
            $t->date('end_date')->nullable();

            $t->boolean('is_active')->default(true);

            $t->timestamps();
            $t->softDeletes();

            // ফাস্ট লুকআপ
            $t->index(['company_id', 'fixed_asset_id', 'is_active']);

            // রিলেশন কনস্ট্রেন্ট (companies টেবিল থাকলে আপনি কাস্টম FK দিতে পারেন)
            $t->foreign('fixed_asset_id')->references('id')->on('fixed_assets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
    }
};
