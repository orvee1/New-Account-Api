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
        Schema::create('admin_sms_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('admin_id');
            $table->string('job_id')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('delivery_status');
            $table->string('event');
            $table->unsignedInteger('event_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_sms_logs');
    }
};
