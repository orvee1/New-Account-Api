<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_invoices') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_invoices MODIFY status ENUM('draft', 'sent', 'unpaid', 'paid', 'partially_paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_invoices') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_invoices MODIFY status ENUM('draft', 'sent', 'paid', 'partially_paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'draft'");
        }
    }
};
