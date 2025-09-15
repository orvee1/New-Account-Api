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
        Schema::create('fixed_assets', function (Blueprint $t) {
            $t->id();

            $t->unsignedBigInteger('company_id')->index(); // ->constrained('companies')->cascadeOnDelete(); // থাকলে আনকমেন্ট

            // Creator/Updater (optional)
            $t->integer('created_by')->nullable();
            $t->integer('updated_by')->nullable();

            // Core fields
            $t->string('name');
            $t->string('category')->nullable();
            $t->date('purchase_date')->nullable();

            $t->decimal('amount', 12, 2)->default(0);
            $t->string('vendor_name')->nullable();

            $t->string('purchase_mode')->nullable();   // 'Cash Purchase' | 'Bank Purchase' | 'On Credit'
            $t->string('payment_mode')->nullable();    // e.g. Cash A/C name or Bank A/C name

            $t->unsignedInteger('useful_life')->nullable(); // years
            $t->decimal('salvage_value', 12, 2)->nullable()->default(0);

            $t->string('depreciation_method')->default('Straight Line'); // Straight Line | Reducing Balance
            $t->string('frequency')->nullable(); // Monthly | Yearly
            $t->decimal('depreciation_rate', 7, 4)->nullable(); // in %

            $t->string('asset_location')->nullable();
            $t->string('tag_serial_number')->nullable();

            $t->timestamps();
            $t->softDeletes();

            // Per-company unique tag
            $t->unique(['company_id', 'tag_serial_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
