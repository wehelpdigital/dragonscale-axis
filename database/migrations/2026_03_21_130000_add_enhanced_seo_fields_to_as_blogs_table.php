<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('as_blogs', function (Blueprint $table) {
            // Open Graph fields
            $table->string('ogTitle')->nullable()->after('metaKeywords');
            $table->text('ogDescription')->nullable()->after('ogTitle');
            $table->string('ogImage')->nullable()->after('ogDescription');

            // Twitter Card fields
            $table->string('twitterTitle')->nullable()->after('ogImage');
            $table->text('twitterDescription')->nullable()->after('twitterTitle');
            $table->string('twitterImage')->nullable()->after('twitterDescription');

            // Additional SEO fields
            $table->string('canonicalUrl')->nullable()->after('twitterImage');
            $table->string('focusKeyword')->nullable()->after('canonicalUrl');
            $table->integer('seoScore')->default(0)->after('focusKeyword');
            $table->json('seoAnalysis')->nullable()->after('seoScore');

            // Schema markup
            $table->string('schemaType')->default('Article')->after('seoAnalysis');

            // Builder content (JSON format for drag-drop builder)
            $table->longText('builderContent')->nullable()->after('blogContent');
            $table->boolean('useBuilder')->default(false)->after('builderContent');

            // Reading time estimate
            $table->integer('readingTime')->default(0)->after('viewCount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('as_blogs', function (Blueprint $table) {
            $table->dropColumn([
                'ogTitle',
                'ogDescription',
                'ogImage',
                'twitterTitle',
                'twitterDescription',
                'twitterImage',
                'canonicalUrl',
                'focusKeyword',
                'seoScore',
                'seoAnalysis',
                'schemaType',
                'builderContent',
                'useBuilder',
                'readingTime',
            ]);
        });
    }
};
