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
        Schema::create('activities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('guard', 255)->default('guest');
            $table->unsignedInteger('user_id')->nullable()->index('activities_user_id_index');
            $table->string('ip', 255)->nullable()->index();
            $table->string('user_agent', 255)->nullable();
            $table->string('uuid', 255)->nullable()->index();
            $table->string('method', 255)->default('any');
            $table->string('url', 255)->nullable()->index();
            // match utf8mb4_bin for JSON-like request column
            $table->longText('request')->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
            $table->longText('files')->nullable();

            $table->timestamps(); // nullable by default

            $table->index(['uuid', 'guard', 'user_id', 'user_agent'], 'uuid_guard_user_id_user_agent_index');
            $table->index('created_at', 'created_at_index');

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
