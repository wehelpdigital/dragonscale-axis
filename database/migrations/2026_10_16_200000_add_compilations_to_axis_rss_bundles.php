<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RSS Feeds: compilations (2026-10-16).
 *
 * A compilation is a feed made of combined feeds instead of sources, named
 * for what it is used for (usedFor, e.g. "anee.io Latest in Agriculture").
 * It lives in axis_rss_bundles with bundleType = 'compilation' so it has the
 * same public address, switches and preview as a combined feed; its combined
 * feeds are in axis_rss_bundle_members. Each story it serves carries the
 * names of the combined feeds it came from as <category>.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('axis_rss_bundles', function (Blueprint $table) {
            $table->enum('bundleType', ['combined', 'compilation'])->default('combined')->after('bundleToken');
            $table->string('usedFor', 190)->nullable()->after('bundleType');
        });

        Schema::create('axis_rss_bundle_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bundleId');
            $table->unsignedBigInteger('memberBundleId');
            $table->timestamps();

            $table->foreign('bundleId')->references('id')->on('axis_rss_bundles')->cascadeOnDelete();
            $table->foreign('memberBundleId')->references('id')->on('axis_rss_bundles')->cascadeOnDelete();
            $table->unique(['bundleId', 'memberBundleId']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('axis_rss_bundle_members');
        Schema::table('axis_rss_bundles', function (Blueprint $table) {
            $table->dropColumn(['bundleType', 'usedFor']);
        });
    }
};
