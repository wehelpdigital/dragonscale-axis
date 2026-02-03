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
        Schema::create('ai_websites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usersId');
            $table->string('websiteName', 255);
            $table->string('websiteUrl', 1000);
            $table->text('description')->nullable();

            // Scraping configuration
            $table->enum('scrapeType', [
                'full_page',        // Scrape entire page content
                'specific_selector', // Scrape specific CSS selector
                'sitemap',          // Follow sitemap links
                'api_endpoint'      // Treat as API endpoint (JSON)
            ])->default('full_page');
            $table->string('cssSelector', 500)->nullable(); // For specific_selector type
            $table->text('allowedPaths')->nullable(); // JSON array of allowed URL paths
            $table->text('excludedPaths')->nullable(); // JSON array of excluded URL paths

            // Scheduling
            $table->enum('scrapeFrequency', [
                'manual',    // Only scrape when manually triggered
                'hourly',    // Every hour
                'daily',     // Once a day
                'weekly',    // Once a week
                'monthly'    // Once a month
            ])->default('manual');

            // Status tracking
            $table->timestamp('lastScrapedAt')->nullable();
            $table->enum('lastScrapeStatus', ['pending', 'success', 'failed', 'in_progress'])->default('pending');
            $table->text('lastScrapeError')->nullable();
            $table->integer('scrapeCount')->default(0);

            // Priority and status
            $table->integer('priority')->default(0); // Higher = checked first
            $table->boolean('isActive')->default(true);

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index('usersId');
            $table->index('isActive');
            $table->index('scrapeFrequency');
            $table->index('delete_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_websites');
    }
};
