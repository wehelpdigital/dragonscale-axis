<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds session_id column to users table for single-session enforcement.
     * Only one active session is allowed per user at a time.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('session_id', 100)->nullable()->after('remember_token');
            $table->timestamp('last_login_at')->nullable()->after('session_id');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['session_id', 'last_login_at', 'last_login_ip']);
        });
    }
};
