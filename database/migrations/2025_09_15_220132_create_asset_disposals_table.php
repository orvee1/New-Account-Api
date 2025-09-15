<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_disposals', function (Blueprint $t) {
            $t->id();

            // Multi-tenant
            $t->unsignedBigInteger('company_id')->index();

            // FK -> fixed_assets
            $t->unsignedBigInteger('fixed_asset_id')->index();
            $t->foreign('fixed_asset_id')
              ->references('id')->on('fixed_assets')
              ->onDelete('cascade');

            // Who did it
            $t->integer('created_by')->nullable();
            $t->integer('updated_by')->nullable();

            // Business fields (UI অনুযায়ী)
            $t->string('disposal_type');                 // 'Sold' | 'Scrapped'
            $t->decimal('disposal_value', 12, 2)->default(0);
            $t->string('tag_no')->nullable();            // snapshot of asset tag at disposal time
            $t->date('disposed_at')->nullable();         // UI-তে নেই; আমরা আজকের দিন সেট করবো

            $t->text('remarks')->nullable();

            $t->timestamps();
            $t->softDeletes();

            $t->index(['company_id','fixed_asset_id','disposal_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
