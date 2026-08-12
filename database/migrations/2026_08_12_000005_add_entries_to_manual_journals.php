<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_journals', function (Blueprint $table) {
            if (! Schema::hasColumn('manual_journals', 'entries')) {
                $table->json('entries')->nullable()->after('credit_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('manual_journals', function (Blueprint $table) {
            if (Schema::hasColumn('manual_journals', 'entries')) {
                $table->dropColumn('entries');
            }
        });
    }
};
