<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Open, bounce and complaint tracking on each sent message.
 *
 * trackingId is the token embedded in the tracking pixel. It is a random 32
 * characters rather than the row id, because the pixel URL is handed to a
 * stranger: a sequential id would let anyone walk the whole send history by
 * counting, and would leak how much mail this account sends.
 *
 * ONE HONEST CAVEAT, recorded here because the number will be read by someone
 * who did not build it: openCount is inflated. Apple Mail Privacy Protection
 * pre-fetches every image in every message regardless of whether a human ever
 * looked at it, and Gmail proxies images through its own cache. Opens are
 * directionally useful for comparing templates against each other; they are not
 * a true count of people. The UI labels them as such.
 *
 * complainedAt can only ever be partially populated. A recipient pressing "mark
 * as spam" sends no signal to the sender. The only per-message complaint data
 * that exists comes from ISP feedback loops - Outlook and Yahoo mail an ARF
 * report to an enrolled address, which the IMAP poller can parse. Gmail offers
 * no per-message equivalent at any price, only domain-level aggregates in
 * Postmaster Tools. A zero here does not mean nobody complained.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outreach_email_logs')) {
            return;
        }

        Schema::table('outreach_email_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('outreach_email_logs', 'trackingId')) {
                $table->char('trackingId', 32)->nullable()->after('messageId');
            }

            if (!Schema::hasColumn('outreach_email_logs', 'taskId')) {
                $table->unsignedBigInteger('taskId')->nullable()->after('templateId');
            }

            if (!Schema::hasColumn('outreach_email_logs', 'openedAt')) {
                $table->dateTime('openedAt')->nullable()->after('sentAt');
            }

            if (!Schema::hasColumn('outreach_email_logs', 'lastOpenedAt')) {
                $table->dateTime('lastOpenedAt')->nullable()->after('openedAt');
            }

            if (!Schema::hasColumn('outreach_email_logs', 'openCount')) {
                $table->unsignedInteger('openCount')->default(0)->after('lastOpenedAt');
            }

            if (!Schema::hasColumn('outreach_email_logs', 'bouncedAt')) {
                $table->dateTime('bouncedAt')->nullable()->after('openCount');
            }

            if (!Schema::hasColumn('outreach_email_logs', 'bounceType')) {
                $table->enum('bounceType', ['hard', 'soft', 'unknown'])->nullable()->after('bouncedAt');
            }

            if (!Schema::hasColumn('outreach_email_logs', 'complainedAt')) {
                $table->dateTime('complainedAt')->nullable()->after('bounceType');
            }

            if (!Schema::hasColumn('outreach_email_logs', 'isFollowUp')) {
                $table->boolean('isFollowUp')->default(false)->after('aiRephrased');
            }
        });

        foreach ([
            ['outreach_logs_tracking_idx', ['trackingId']],
            ['outreach_logs_task_idx', ['taskId', 'status']],
        ] as $spec) {
            [$name, $columns] = $spec;

            $exists = collect(DB::select(
                'SELECT INDEX_NAME FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
                ['outreach_email_logs', $name]
            ))->isNotEmpty();

            if (!$exists) {
                Schema::table('outreach_email_logs', function (Blueprint $table) use ($name, $columns) {
                    $table->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('outreach_email_logs')) {
            return;
        }

        Schema::table('outreach_email_logs', function (Blueprint $table) {
            $table->dropIndex('outreach_logs_tracking_idx');
            $table->dropIndex('outreach_logs_task_idx');
            $table->dropColumn([
                'trackingId', 'taskId', 'openedAt', 'lastOpenedAt', 'openCount',
                'bouncedAt', 'bounceType', 'complainedAt', 'isFollowUp',
            ]);
        });
    }
};
