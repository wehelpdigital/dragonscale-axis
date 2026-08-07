<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated sessions table for the mother (DS Axis) admin app.
 *
 * The admin app and the AniSystem client app share one database. When both
 * used the default `sessions` table, each app's session garbage-collection
 * pruned the OTHER app's rows by its own (shorter) lifetime — logging admins
 * out at random. Isolating the admin sessions here stops that cross-eviction.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dsaxis_sessions')) {
            return;
        }
        Schema::create('dsaxis_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsaxis_sessions');
    }
};
