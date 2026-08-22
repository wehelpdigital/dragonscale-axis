<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flow guard rails, tracking switches, and where a contact stands on interest.
 *
 * The AI timing node advises; these columns are the walls it advises inside.
 * That split is deliberate. A model asked "when should I send the next one"
 * will occasionally answer with a number that empties the queue in an hour, and
 * the cost of that answer is the sending domain - which cannot be bought back
 * at any price. So the model proposes a gap and a volume, and the values here
 * clamp both before anything is sent. A model outage or a nonsense reply then
 * degrades to the defaults rather than to an incident.
 *
 * Defaults are conservative on purpose: 15 minutes between sends, no more than
 * 30 a day, a follow-up no sooner than 3 days, and silence called at 14 days -
 * which is the "more than 2 weeks" rule the flow's no-reply branch needs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outreach_settings')) {
            return;
        }

        Schema::table('outreach_settings', function (Blueprint $table) {
            // --- AI timing node: the model advises inside these walls ---
            if (!Schema::hasColumn('outreach_settings', 'aiTimingEnabled')) {
                $table->boolean('aiTimingEnabled')->default(true)->after('aiRephraseEnabled');
            }

            if (!Schema::hasColumn('outreach_settings', 'aiMinGapMinutes')) {
                $table->unsignedSmallInteger('aiMinGapMinutes')->default(15)->after('aiTimingEnabled');
            }

            if (!Schema::hasColumn('outreach_settings', 'aiMaxGapMinutes')) {
                $table->unsignedSmallInteger('aiMaxGapMinutes')->default(240)->after('aiMinGapMinutes');
            }

            if (!Schema::hasColumn('outreach_settings', 'aiMaxPerDay')) {
                // Advisory ceiling for the model. effectiveDailyCap() still has
                // the final word, so this can never raise the real limit.
                $table->unsignedSmallInteger('aiMaxPerDay')->default(30)->after('aiMaxGapMinutes');
            }

            // --- Flow timings ---
            if (!Schema::hasColumn('outreach_settings', 'followUpDelayDays')) {
                $table->unsignedSmallInteger('followUpDelayDays')->default(3)->after('aiMaxPerDay');
            }

            if (!Schema::hasColumn('outreach_settings', 'maxFollowUps')) {
                $table->unsignedTinyInteger('maxFollowUps')->default(2)->after('followUpDelayDays');
            }

            if (!Schema::hasColumn('outreach_settings', 'noReplyAfterDays')) {
                $table->unsignedSmallInteger('noReplyAfterDays')->default(14)->after('maxFollowUps');
            }

            if (!Schema::hasColumn('outreach_settings', 'replyDelayMinMinutes')) {
                $table->unsignedSmallInteger('replyDelayMinMinutes')->default(30)->after('noReplyAfterDays');
            }

            if (!Schema::hasColumn('outreach_settings', 'replyDelayMaxMinutes')) {
                $table->unsignedSmallInteger('replyDelayMaxMinutes')->default(480)->after('replyDelayMinMinutes');
            }

            // --- Tracking ---
            if (!Schema::hasColumn('outreach_settings', 'trackOpens')) {
                $table->boolean('trackOpens')->default(true)->after('replyDelayMaxMinutes');
            }

            if (!Schema::hasColumn('outreach_settings', 'trackingDomain')) {
                // Blank falls back to APP_URL. A separate host is better practice
                // for deliverability but must be a deliberate choice.
                $table->string('trackingDomain', 255)->nullable()->after('trackOpens');
            }

            if (!Schema::hasColumn('outreach_settings', 'autoSuppressBounced')) {
                $table->boolean('autoSuppressBounced')->default(true)->after('trackingDomain');
            }

            if (!Schema::hasColumn('outreach_settings', 'autoSuppressComplaints')) {
                $table->boolean('autoSuppressComplaints')->default(true)->after('autoSuppressBounced');
            }

            // --- The global cron switch for the flow engine ---
            if (!Schema::hasColumn('outreach_settings', 'flowCronEnabled')) {
                $table->boolean('flowCronEnabled')->default(false)->after('autoSuppressComplaints');
            }
        });

        if (!Schema::hasTable('outreach_leads')) {
            return;
        }

        Schema::table('outreach_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('outreach_leads', 'interestStatus')) {
                $table->enum('interestStatus', ['unknown', 'interested', 'not_interested'])
                    ->default('unknown')
                    ->after('outreachStatus');
            }

            if (!Schema::hasColumn('outreach_leads', 'interestedAt')) {
                $table->dateTime('interestedAt')->nullable()->after('interestStatus');
            }

            if (!Schema::hasColumn('outreach_leads', 'interestNote')) {
                // Why the classifier decided, kept so a human reviewing the
                // inbox can see the reasoning rather than just the verdict.
                $table->string('interestNote', 500)->nullable()->after('interestedAt');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('outreach_settings')) {
            Schema::table('outreach_settings', function (Blueprint $table) {
                $table->dropColumn([
                    'aiTimingEnabled', 'aiMinGapMinutes', 'aiMaxGapMinutes', 'aiMaxPerDay',
                    'followUpDelayDays', 'maxFollowUps', 'noReplyAfterDays',
                    'replyDelayMinMinutes', 'replyDelayMaxMinutes',
                    'trackOpens', 'trackingDomain', 'autoSuppressBounced',
                    'autoSuppressComplaints', 'flowCronEnabled',
                ]);
            });
        }

        if (Schema::hasTable('outreach_leads')) {
            Schema::table('outreach_leads', function (Blueprint $table) {
                $table->dropColumn(['interestStatus', 'interestedAt', 'interestNote']);
            });
        }
    }
};
