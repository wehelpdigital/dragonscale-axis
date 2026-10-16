<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RSS Feeds: the order of a compilation's combined feeds (2026-10-17).
 *
 * A compilation is served section by section, one section per combined feed,
 * in this order, both in its RSS and on the page a browser sees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('axis_rss_bundle_members', function (Blueprint $table) {
            $table->unsignedSmallInteger('memberOrder')->default(0)->after('memberBundleId');
        });
    }

    public function down(): void
    {
        Schema::table('axis_rss_bundle_members', function (Blueprint $table) {
            $table->dropColumn('memberOrder');
        });
    }
};
