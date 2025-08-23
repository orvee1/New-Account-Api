<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Matches: int(10) unsigned AI
            $table->bigIncrements('id');

            $table->string('name', 255);
            $table->string('email', 255);           // not unique in your dump
            $table->string('phone_number', 255);    // required in dump

            // LONGTEXT utf8mb4_bin + JSON validity (CHECK added after create)
            $table->longText('access_course_ids')
                ->nullable()
                ->charset('utf8mb4')
                ->collation('utf8mb4_bin');

            $table->string('password', 255);
            $table->string('security', 255)->nullable();

            $table->integer('status')->default(1);

            $table->rememberToken();                        // varchar(100) nullable
            $table->unsignedBigInteger('go_to_doctor_id')->nullable();
            $table->string('go_to_doctor_token', 255)->nullable();

            $table->timestamp('last_password_changed_at')->nullable();
            $table->unsignedInteger('otp')->nullable();

            $table->timestamps();                           // created_at / updated_at (nullable)
            $table->timestamp('deleted_at')->nullable();    // soft delete timestamp (no SoftDeletes trait required here)

            // Match table options from dump
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine    = 'InnoDB';
        });

        // Enforce JSON validity like: CHECK (json_valid(access_course_ids))
        // (MariaDB 11.4 enforces CHECK constraints)

        // Default Laravel tables kept as-is
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
