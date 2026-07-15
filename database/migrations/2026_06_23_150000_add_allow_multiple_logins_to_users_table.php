<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user opt-out for the single-session enforcement added in
     * 2026_02_17_100000_add_session_id_to_users_table. When set to 1,
     * SingleSession middleware skips the session_id/current-session
     * check and LoginController stops writing session_id on login —
     * so the same account can be logged in on multiple devices at
     * once. Default 0 keeps existing behavior for every user.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('allow_multiple_logins')->default(0)->after('last_login_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('allow_multiple_logins');
        });
    }
};
