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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('doctor_id')->nullable();
            $table->string('job_id');
            $table->string('mobile_no')->nullable();
            $table->string('delivery_status');
            $table->string('event');
            $table->timestamps();
            $table->integer('event_type')->nullable();
            $table->integer('admin_id')->nullable();
            $table->string('identifier')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
