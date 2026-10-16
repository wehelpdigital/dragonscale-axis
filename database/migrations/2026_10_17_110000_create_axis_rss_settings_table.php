<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RSS Feeds: the module's own settings (2026-10-17).
 *
 *   cronKey      the secret in the cron address (/rss-cron?key=...)
 *   lastRunAt    when the sources were last read automatically
 *   lastRunBy    what read them: "scheduler" or "cron"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('axis_rss_settings', function (Blueprint $table) {
            $table->id();
            $table->string('settingKey', 100)->unique();
            $table->text('settingValue')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('axis_rss_settings');
    }
};
