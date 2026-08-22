<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The do-not-send list: addresses that bounced, complained, or opted out.
 *
 * Suppression is keyed on the ADDRESS, not the lead. One address can sit on
 * several leads - a chain with a single head-office inbox - and a bounce
 * belongs to the mailbox rather than to whichever record happened to trigger
 * it. Keyed on leadId, the next lead sharing that address would be mailed
 * again, which is precisely how a sending domain gets itself blocked.
 *
 * Rows are never cleaned up by the pipeline. A suppression is a permanent fact
 * about a mailbox, and re-mailing something that already bounced or complained
 * costs far more than a row in a table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('outreach_suppressions')) {
            return;
        }

        Schema::create('outreach_suppressions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('usersId')->index();
            $table->string('email', 255);

            $table->enum('reason', ['bounced', 'spam', 'unsubscribed', 'manual'])->default('bounced');

            // 'hard' never retries; 'soft' is recorded but may be released later -
            // a full mailbox is a temporary condition, a dead address is not.
            $table->enum('bounceType', ['hard', 'soft', 'unknown'])->default('unknown');

            $table->string('source', 190)->nullable();
            $table->text('detail')->nullable();
            $table->unsignedBigInteger('leadId')->nullable()->index();
            $table->unsignedBigInteger('emailLogId')->nullable();
            $table->dateTime('suppressedAt')->nullable();

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->unique(['usersId', 'email'], 'outreach_suppressions_user_email_unique');
            $table->index(['usersId', 'reason'], 'outreach_suppressions_user_reason_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_suppressions');
    }
};
