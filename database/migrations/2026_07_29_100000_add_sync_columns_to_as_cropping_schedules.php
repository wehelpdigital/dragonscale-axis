<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            // Live-sync change counter: bumped by TouchScheduleSync middleware
            // on every successful mutation so other open setup pages can
            // detect the change by polling a single indexed row.
            $table->unsignedBigInteger('syncVersion')->default(0)->after('deleteStatus');
            // Which browser tab made the last change (X-Sync-Client header) —
            // lets the acting tab recognise its own edits and skip refreshing.
            $table->string('lastEditClientId', 40)->nullable()->after('syncVersion');
            // Display name for the "Updated with changes by …" toast.
            $table->string('lastEditedByName')->nullable()->after('lastEditClientId');
        });
    }

    public function down(): void
    {
        Schema::table('as_cropping_schedules', function (Blueprint $table) {
            $table->dropColumn(['syncVersion', 'lastEditClientId', 'lastEditedByName']);
        });
    }
};
