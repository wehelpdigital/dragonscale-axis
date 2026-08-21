<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Where an address stands with the email verifier.
 *
 * Kept separate from enrichmentStatus because they answer different questions.
 * Enrichment asks "did we find an address at all"; verification asks "will it
 * accept mail". A lead can be enriched and still unusable, and conflating the
 * two would make it impossible to tell a lead nobody could find an address for
 * from one whose address turned out to be dead.
 *
 * verificationResult keeps the verifier's own word for it - valid, catch_all,
 * disposable, role_account and so on - while verificationStatus is reduced to
 * the states the queue cares about. Only 'valid' is promoted for sending; the
 * rest are retained rather than deleted so the same address is never paid for
 * twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outreach_leads')) {
            return;
        }

        Schema::table('outreach_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('outreach_leads', 'verificationStatus')) {
                $table->enum('verificationStatus', ['pending', 'processing', 'verified', 'failed', 'skipped'])
                    ->default('pending')
                    ->after('emailSource');
            }

            if (!Schema::hasColumn('outreach_leads', 'verificationResult')) {
                $table->string('verificationResult', 40)->nullable()->after('verificationStatus');
            }

            if (!Schema::hasColumn('outreach_leads', 'isEmailValid')) {
                // The single flag the send queue reads. Narrower than
                // verificationResult on purpose - only a confirmed deliverable
                // address sets it, because a bounce costs sender reputation.
                $table->boolean('isEmailValid')->default(false)->after('verificationResult');
            }

            if (!Schema::hasColumn('outreach_leads', 'verificationAttempts')) {
                $table->unsignedTinyInteger('verificationAttempts')->default(0)->after('isEmailValid');
            }

            if (!Schema::hasColumn('outreach_leads', 'verifiedAt')) {
                $table->dateTime('verifiedAt')->nullable()->after('verificationAttempts');
            }

            if (!Schema::hasColumn('outreach_leads', 'verificationError')) {
                $table->text('verificationError')->nullable()->after('verifiedAt');
            }
        });

        $exists = collect(DB::select(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            ['outreach_leads', 'outreach_leads_user_verify_idx']
        ))->isNotEmpty();

        if (!$exists) {
            Schema::table('outreach_leads', function (Blueprint $table) {
                $table->index(['usersId', 'verificationStatus'], 'outreach_leads_user_verify_idx');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('outreach_leads')) {
            return;
        }

        Schema::table('outreach_leads', function (Blueprint $table) {
            $table->dropIndex('outreach_leads_user_verify_idx');
            $table->dropColumn([
                'verificationStatus',
                'verificationResult',
                'isEmailValid',
                'verificationAttempts',
                'verifiedAt',
                'verificationError',
            ]);
        });
    }
};
