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
        Schema::create('admin_device_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_device_id')->index();
            $table->foreignId('user_id')->index();
            $table->text('reason')->nullable();
            $table->unsignedTinyInteger('type')->default(1);
            $table->text('note')->nullable();
            $table->timestamp('accept_at')->nullable();
            $table->foreignId('accept_by')->index()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_device_requests');
    }
};
