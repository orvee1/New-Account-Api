<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('journal_entries', 'status')) {
                $table->string('status')->default('posted')->after('description')->index();
            }
            if (! Schema::hasColumn('journal_entries', 'entry_type')) {
                $table->string('entry_type')->default('normal')->after('status')->index();
            }
            if (! Schema::hasColumn('journal_entries', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('entry_type');
            }
            if (! Schema::hasColumn('journal_entries', 'posted_by')) {
                $table->unsignedBigInteger('posted_by')->nullable()->after('posted_at');
            }
            if (! Schema::hasColumn('journal_entries', 'reversed_entry_id')) {
                $table->unsignedBigInteger('reversed_entry_id')->nullable()->after('posted_by')->index();
            }
            if (! Schema::hasColumn('journal_entries', 'reversal_entry_id')) {
                $table->unsignedBigInteger('reversal_entry_id')->nullable()->after('reversed_entry_id')->index();
            }
        });

        Schema::table('manual_journals', function (Blueprint $table) {
            if (! Schema::hasColumn('manual_journals', 'journal_entry_id')) {
                $table->unsignedBigInteger('journal_entry_id')->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('manual_journals', 'entry_type')) {
                $table->string('entry_type')->default('normal')->after('status')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('manual_journals', function (Blueprint $table) {
            if (Schema::hasColumn('manual_journals', 'entry_type')) {
                $table->dropColumn('entry_type');
            }
            if (Schema::hasColumn('manual_journals', 'journal_entry_id')) {
                $table->dropColumn('journal_entry_id');
            }
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            foreach (['reversal_entry_id', 'reversed_entry_id', 'posted_by', 'posted_at', 'entry_type', 'status'] as $column) {
                if (Schema::hasColumn('journal_entries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
