<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An optional ceiling on how many businesses one sweep may collect.
 *
 * Mainly a testing control: a province sweep can return thousands of leads, and
 * trying the pipeline end to end should not mean waiting for all of them or
 * paying to enrich them. Setting a cap of 10 gives a complete, real run in a
 * couple of minutes.
 *
 * Null means no limit, which is the behaviour every existing sweep already had.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outreach_batches')) {
            return;
        }

        Schema::table('outreach_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('outreach_batches', 'maxLeads')) {
                $table->unsignedInteger('maxLeads')->nullable()->after('radiusKm');
            }

            if (!Schema::hasColumn('outreach_batches', 'stoppedReason')) {
                // Set when a sweep ends for a reason other than running out of
                // cells, so the batch screen can say "hit its 10 lead limit"
                // rather than showing a province that mysteriously finished early.
                $table->string('stoppedReason', 190)->nullable()->after('maxLeads');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('outreach_batches')) {
            return;
        }

        Schema::table('outreach_batches', function (Blueprint $table) {
            $table->dropColumn(['maxLeads', 'stoppedReason']);
        });
    }
};
