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
        Schema::create('chart_accounts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id');
            $table->string('account_no', 20);
            $table->string('account_name');
            $table->string('account_type');
            $table->string('detail_type')->nullable();
            $table->bigInteger('parent_account_id')->nullable()->default(0);
            $table->boolean('is_active')->default(true);
            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->date('opening_date')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id','account_no','deleted_at']);
            $table->index(['company_id','parent_account_id']);
            $table->index(['company_id','account_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_accounts');
    }
};
