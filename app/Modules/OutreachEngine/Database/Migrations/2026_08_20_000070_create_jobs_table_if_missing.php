<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The stock Laravel `jobs` table, created only if this database does not
 * already have one.
 *
 * This app runs QUEUE_CONNECTION=sync, so nothing writes here today — the
 * scrape/enrich jobs execute inline and the real execution path is the cron
 * (`outreach:*` Artisan commands). The table exists so the admin can flip
 * QUEUE_CONNECTION to `database` and start a worker without a schema change.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }
    }

    public function down(): void
    {
        // Intentionally a no-op. `jobs` is a shared, app-wide table: this
        // migration only ever creates it when it is missing, and by the time
        // anyone rolls back the module the queue may be live and holding real
        // work. Dropping it here would take the whole app's queue with it.
        // Remove the table by hand if it is genuinely unused.
    }
};
