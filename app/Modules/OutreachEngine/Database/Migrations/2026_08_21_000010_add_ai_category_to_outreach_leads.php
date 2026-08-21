<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An AI-assigned business category, kept apart from Google's own answer.
 *
 * The existing `category` column holds types[0] straight from the Places
 * response, which is frequently useless - "point_of_interest", "establishment"
 * - because Google orders that array by its own taxonomy, not by usefulness.
 * Rather than overwrite it (and lose the provenance of what Google actually
 * said), the model's answer lands in its own column beside it.
 *
 * categoryStatus drives the work queue the same way enrichmentStatus does:
 * 'pending' rows are what outreach:categorize-leads picks up.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outreach_leads')) {
            return;
        }

        Schema::table('outreach_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('outreach_leads', 'aiCategory')) {
                $table->string('aiCategory', 120)->nullable()->after('category');
            }

            if (!Schema::hasColumn('outreach_leads', 'categoryStatus')) {
                $table->enum('categoryStatus', ['pending', 'processing', 'categorized', 'failed', 'skipped'])
                    ->default('pending')
                    ->after('aiCategory');
            }

            if (!Schema::hasColumn('outreach_leads', 'categoryAttempts')) {
                $table->unsignedTinyInteger('categoryAttempts')->default(0)->after('categoryStatus');
            }

            if (!Schema::hasColumn('outreach_leads', 'categorizedAt')) {
                $table->dateTime('categorizedAt')->nullable()->after('categoryAttempts');
            }
        });

        // Separate pass: adding an index inside the same closure that creates the
        // column fails on some MySQL versions, which plan the whole ALTER at once.
        //
        // The index is checked against information_schema rather than
        // Schema::getIndexes()/Doctrine - doctrine/dbal is not installed here, and
        // a re-run of this migration must not die on a duplicate key name.
        $exists = collect(DB::select(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND INDEX_NAME = ?
              LIMIT 1',
            ['outreach_leads', 'outreach_leads_user_category_idx']
        ))->isNotEmpty();

        if (!$exists) {
            Schema::table('outreach_leads', function (Blueprint $table) {
                $table->index(['usersId', 'categoryStatus'], 'outreach_leads_user_category_idx');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('outreach_leads')) {
            return;
        }

        Schema::table('outreach_leads', function (Blueprint $table) {
            $table->dropIndex('outreach_leads_user_category_idx');
            $table->dropColumn(['aiCategory', 'categoryStatus', 'categoryAttempts', 'categorizedAt']);
        });
    }
};
