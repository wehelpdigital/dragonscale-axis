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
        Schema::create('ai_website_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('websiteId');
            $table->unsignedBigInteger('usersId');

            // Page information
            $table->string('url', 2000);
            $table->string('title', 500)->nullable();
            $table->text('metaDescription')->nullable();
            $table->string('metaKeywords', 1000)->nullable();

            // Content storage
            $table->longText('rawHtml')->nullable();
            $table->longText('cleanContent')->nullable(); // Extracted text content
            $table->longText('structuredData')->nullable(); // JSON-LD, microdata, etc.
            $table->text('headings')->nullable(); // JSON array of h1-h6 headings
            $table->text('links')->nullable(); // JSON array of links found on page
            $table->text('images')->nullable(); // JSON array of images with alt text

            // Content analysis
            $table->integer('wordCount')->default(0);
            $table->integer('contentLength')->default(0); // Character count
            $table->string('contentHash', 64)->nullable(); // SHA-256 hash to detect changes
            $table->string('language', 10)->nullable();

            // HTTP response info
            $table->integer('httpStatus')->nullable();
            $table->string('contentType', 100)->nullable();
            $table->integer('responseTime')->nullable(); // in milliseconds
            $table->integer('pageSize')->nullable(); // in bytes

            // Crawl metadata
            $table->integer('depth')->default(0); // 0 = root, 1 = first level, etc.
            $table->string('parentUrl', 2000)->nullable();
            $table->boolean('isIndexable')->default(true);
            $table->boolean('hasChanges')->default(false); // True if content changed from last scrape

            // Timestamps
            $table->timestamp('firstScrapedAt')->nullable();
            $table->timestamp('lastScrapedAt')->nullable();
            $table->timestamp('contentChangedAt')->nullable();

            $table->enum('scrapeStatus', ['pending', 'in_progress', 'completed', 'failed', 'skipped'])->default('pending');
            $table->text('scrapeError')->nullable();

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            // URL hash for indexing (URLs are too long for direct index)
            $table->string('urlHash', 64)->nullable();

            // Indexes
            $table->index('websiteId');
            $table->index('usersId');
            $table->index('scrapeStatus');
            $table->index('contentHash');
            $table->index(['websiteId', 'urlHash'], 'idx_website_url');
            $table->index(['websiteId', 'delete_status'], 'idx_website_active');

            // Foreign key
            $table->foreign('websiteId')->references('id')->on('ai_websites')->onDelete('cascade');
        });

        // Add new scrape type to ai_websites table
        Schema::table('ai_websites', function (Blueprint $table) {
            // Modify enum to include 'whole_site' option
            $table->integer('maxPages')->default(100)->after('scrapeFrequency');
            $table->integer('maxDepth')->default(3)->after('maxPages');
            $table->integer('pagesScraped')->default(0)->after('scrapeCount');
            $table->text('crawlQueue')->nullable()->after('pagesScraped'); // JSON array of URLs to crawl
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_websites', function (Blueprint $table) {
            $table->dropColumn(['maxPages', 'maxDepth', 'pagesScraped', 'crawlQueue']);
        });

        Schema::dropIfExists('ai_website_pages');
    }
};
