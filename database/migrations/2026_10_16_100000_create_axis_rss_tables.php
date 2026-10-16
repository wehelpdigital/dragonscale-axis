<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RSS Feeds (2026-10-16): DS AXIS's own RSS.app.
 *
 *   axis_rss_sources         where stories come from: an RSS feed, a Google News
 *                            search, a news page's list of articles, or a page
 *                            watched for changes (PAGASA's bulletins)
 *   axis_rss_items           every story a source gave, once each
 *   axis_rss_bundles         a combined feed: several sources, filtered, served
 *                            as one RSS address (/rss/{bundleToken}.xml)
 *   axis_rss_bundle_sources  which sources a combined feed reads
 *
 * The feeds are shared by every admin, like the AniSystem news feeds they
 * replace, so usersId records who added a row rather than who may see it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('axis_rss_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId')->nullable();
            $table->string('sourceName', 190);
            $table->enum('sourceType', ['rss', 'google_news', 'webpage', 'page_watch'])->default('rss');
            $table->string('sourceUrl', 1000)->nullable();
            $table->string('searchQuery', 500)->nullable();
            $table->json('sourceSettings')->nullable();
            $table->text('includeKeywords')->nullable();
            $table->text('excludeKeywords')->nullable();
            $table->boolean('fetchDetails')->default(true);
            $table->unsignedSmallInteger('refreshMinutes')->default(30);
            $table->boolean('isActive')->default(true);
            $table->timestamp('lastFetchedAt')->nullable();
            $table->enum('lastStatus', ['ok', 'failed'])->nullable();
            $table->string('lastMessage', 500)->nullable();
            $table->unsignedInteger('lastItemCount')->default(0);
            $table->unsignedInteger('failCount')->default(0);
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('usersId')->references('id')->on('users')->nullOnDelete();
            $table->index(['delete_status', 'isActive']);
        });

        Schema::create('axis_rss_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sourceId');
            $table->char('guidHash', 40);
            $table->char('linkHash', 40);
            $table->string('itemTitle', 500);
            $table->string('itemLink', 1000);
            $table->text('itemSummary')->nullable();
            $table->string('itemImage', 1000)->nullable();
            $table->string('itemPublisher', 190)->nullable();
            $table->timestamp('publishedAt')->nullable();
            $table->timestamp('detailsFetchedAt')->nullable();
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('sourceId')->references('id')->on('axis_rss_sources')->cascadeOnDelete();
            $table->unique(['sourceId', 'guidHash']);
            $table->index(['sourceId', 'delete_status', 'publishedAt']);
            $table->index(['sourceId', 'detailsFetchedAt']);
            $table->index('linkHash');
        });

        Schema::create('axis_rss_bundles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId')->nullable();
            $table->string('bundleName', 190);
            $table->string('bundleDescription', 500)->nullable();
            $table->string('bundleToken', 32)->unique();
            $table->text('includeKeywords')->nullable();
            $table->text('excludeKeywords')->nullable();
            $table->unsignedSmallInteger('maxItems')->default(50);
            $table->unsignedSmallInteger('maxAgeDays')->default(7);
            $table->boolean('isActive')->default(true);
            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->foreign('usersId')->references('id')->on('users')->nullOnDelete();
            $table->index(['delete_status', 'isActive']);
        });

        Schema::create('axis_rss_bundle_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bundleId');
            $table->unsignedBigInteger('sourceId');
            $table->timestamps();

            $table->foreign('bundleId')->references('id')->on('axis_rss_bundles')->cascadeOnDelete();
            $table->foreign('sourceId')->references('id')->on('axis_rss_sources')->cascadeOnDelete();
            $table->unique(['bundleId', 'sourceId']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('axis_rss_bundle_sources');
        Schema::dropIfExists('axis_rss_bundles');
        Schema::dropIfExists('axis_rss_items');
        Schema::dropIfExists('axis_rss_sources');
    }
};
