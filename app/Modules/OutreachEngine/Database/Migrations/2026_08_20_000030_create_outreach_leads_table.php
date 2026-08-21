<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Businesses discovered by the grid scraper.
 *
 * placeId is globally unique on purpose: overlapping grid circles WILL return
 * the same business more than once, and child cells re-cover their parent's
 * area. The unique index is the last line of defence behind the code-level
 * dedupe check, so two concurrent grid workers cannot double-insert.
 *
 * Each lead carries two independent state machines — enrichmentStatus (do we
 * have an email yet?) and outreachStatus (where is it in the campaign?) —
 * because a lead can be fully enriched but never contacted, or contacted and
 * later bounced.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('outreach_leads')) {
            return;
        }

        Schema::create('outreach_leads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('usersId')->index();
            $table->char('batchId', 36)->nullable()->index();
            $table->unsignedBigInteger('gridId')->nullable()->index();

            $table->string('placeId', 255);
            $table->string('businessName', 255);
            $table->string('category', 190)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('city', 190)->nullable();
            $table->string('province', 190)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('phone', 60)->nullable();
            $table->string('website', 500)->nullable();
            $table->string('facebookUrl', 500)->nullable();
            $table->string('email', 255)->nullable()->index();
            // 'places' | 'website' | 'facebook' | 'llm' | 'manual'
            $table->string('emailSource', 60)->nullable();

            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('userRatingsTotal')->nullable();

            $table->enum('enrichmentStatus', ['pending', 'processing', 'enriched', 'failed', 'skipped'])
                ->default('pending');
            $table->unsignedTinyInteger('enrichmentAttempts')->default(0);
            $table->text('enrichmentError')->nullable();
            $table->dateTime('enrichedAt')->nullable();

            $table->enum('outreachStatus', ['uncontacted', 'queued', 'contacted', 'replied', 'unsubscribed', 'bounced', 'failed'])
                ->default('uncontacted');
            $table->dateTime('lastContactedAt')->nullable();
            $table->dateTime('repliedAt')->nullable();
            $table->unsignedTinyInteger('contactAttempts')->default(0);
            $table->text('notes')->nullable();

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->unique('placeId', 'outreach_leads_placeid_unique');
            $table->index(['usersId', 'outreachStatus'], 'outreach_leads_user_outreach_idx');
            $table->index(['usersId', 'enrichmentStatus'], 'outreach_leads_user_enrich_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_leads');
    }
};
