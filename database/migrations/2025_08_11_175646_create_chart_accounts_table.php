<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();

            $table->string('account_no', 20)->nullable(); // কোড নাও থাকলে null
            $table->string('account_name');               // দেখানোর নাম

            // নোড টাইপ + রিপোর্ট গ্রুপিং
            $table->enum('node_type', ['group', 'ledger'])->default('group');
            $table->enum('major_type', ['asset', 'liability', 'equity', 'income', 'expense'])->nullable();

            // আগের টাইপ/ডিটেইল ফিল্ড (চাইলে ডিপ্রিকেট করতে পারো)
            $table->string('account_type')->nullable();
            $table->string('detail_type')->nullable();

            // প্যারেন্ট
            $table->unsignedBigInteger('parent_account_id')->nullable()->index();

            // ট্রি ন্যাভিগেশনের জন্য
            $table->string('path', 1024)
                ->nullable()
                ->charset('ascii')              
                ->collation('ascii_general_ci');
            $table->unsignedTinyInteger('depth')->default(0)->index();

            $table->boolean('is_active')->default(true);
            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->date('opening_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // একই কোম্পানিতে account_no ইউনিক (soft delete-aware)
            $table->unique(['company_id', 'account_no', 'deleted_at']);

            // দ্রুত কোয়েরির জন্য
            $table->index(['company_id', 'parent_account_id']);
            $table->index(['company_id', 'account_type']);
        });

        // ফরেন কি আলাদা করলে MySQL ভার্সন ইস্যু কম হয়
        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->foreign('parent_account_id')
                ->references('id')->on('chart_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chart_accounts', function (Blueprint $table) {
            $table->dropForeign(['parent_account_id']);
        });
        Schema::dropIfExists('chart_accounts');
    }
};
