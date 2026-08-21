<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Work queue for the geographic grid scraper.
 *
 * A "batch" is one region sweep: the region's bounding box is tiled into
 * overlapping circles, one row per circle. Google Places caps a Nearby Search
 * at 60 results, so a cell that comes back saturated is marked 'split' and
 * spawns four half-radius children (depth + 1) — that recursion is why the
 * table is self-referencing through parentId.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('outreach_search_grids')) {
            return;
        }

        Schema::create('outreach_search_grids', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('usersId')->index();
            $table->char('batchId', 36)->index();          // uuid — one scrape run
            $table->string('businessType', 190);
            $table->string('regionLabel', 190);            // e.g. "La Union"

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('radiusKm', 8, 3);
            $table->unsignedTinyInteger('depth')->default(0);
            $table->unsignedBigInteger('parentId')->nullable()->index();

            $table->enum('status', ['pending', 'processing', 'completed', 'split', 'failed'])
                ->default('pending');
            $table->unsignedInteger('resultsCount')->default(0);
            $table->unsignedInteger('newLeadsCount')->default(0);
            // Places hands back an opaque continuation token; it can be long.
            $table->string('pageToken', 500)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('lastError')->nullable();
            $table->dateTime('processedAt')->nullable();

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            // The two shapes the cron claims work with.
            $table->index(['batchId', 'status'], 'outreach_grids_batch_status_idx');
            $table->index(['usersId', 'status'], 'outreach_grids_user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_search_grids');
    }
};
