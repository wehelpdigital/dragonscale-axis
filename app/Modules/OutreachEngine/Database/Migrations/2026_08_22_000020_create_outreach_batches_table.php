<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One row per sweep, so a batch is a thing you can name rather than a bare uuid.
 *
 * Until now a "batch" existed only as a char(36) stamped on grid cells and
 * leads, and the scraper screen rebuilt the list with a GROUP BY every time it
 * loaded. That is fine for a recent-activity strip and useless for anything
 * else: there is nowhere to put a name, nowhere to record that the pipeline
 * actually finished, and the aggregate has to be recomputed on every request.
 *
 * The counters here are denormalised on purpose. A sweep can hold tens of
 * thousands of leads, and the batch list would otherwise run several COUNT(*)
 * with a WHERE on status for every row on the page.
 *
 * Existing sweeps are back-filled from the grid cells that already carry a
 * batchId, so nothing scraped before this migration disappears from the screen.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('outreach_batches')) {
            return;
        }

        Schema::create('outreach_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('usersId')->index();
            $table->char('batchId', 36)->unique('outreach_batches_batchid_unique');

            // Null means "never renamed" - the screen then shows the generated
            // label (business type + region) instead of an empty cell.
            $table->string('name', 190)->nullable();
            $table->string('businessType', 190);
            $table->string('regionLabel', 190);
            $table->decimal('radiusKm', 8, 3)->default(30.000);

            $table->enum('status', ['scraping', 'enriching', 'verifying', 'complete', 'cancelled'])
                ->default('scraping');

            $table->unsignedInteger('totalCells')->default(0);
            $table->unsignedInteger('pendingCells')->default(0);
            $table->unsignedInteger('totalLeads')->default(0);
            $table->unsignedInteger('leadsWithEmail')->default(0);
            $table->unsignedInteger('leadsVerified')->default(0);
            $table->unsignedInteger('leadsValid')->default(0);

            $table->dateTime('startedAt')->nullable();
            $table->dateTime('completedAt')->nullable();
            $table->dateTime('countsRefreshedAt')->nullable();

            $table->enum('delete_status', ['active', 'deleted'])->default('active');
            $table->timestamps();

            $table->index(['usersId', 'status'], 'outreach_batches_user_status_idx');
        });

        $this->backfill();
    }

    /**
     * Rebuild a batch row for every sweep that already exists.
     */
    private function backfill(): void
    {
        if (!Schema::hasTable('outreach_search_grids')) {
            return;
        }

        $rows = DB::table('outreach_search_grids')
            ->select('batchId', 'usersId')
            ->selectRaw('MAX(businessType) as businessType')
            ->selectRaw('MAX(regionLabel) as regionLabel')
            ->selectRaw('MAX(radiusKm) as radiusKm')
            ->selectRaw('COUNT(*) as totalCells')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pendingCells")
            ->selectRaw('MIN(created_at) as startedAt')
            ->where('delete_status', 'active')
            ->groupBy('batchId', 'usersId')
            ->get();

        foreach ($rows as $row) {
            if (empty($row->batchId)) {
                continue;
            }

            DB::table('outreach_batches')->insertOrIgnore([
                'usersId' => $row->usersId,
                'batchId' => $row->batchId,
                'name' => null,
                'businessType' => (string) $row->businessType,
                'regionLabel' => (string) $row->regionLabel,
                'radiusKm' => (float) $row->radiusKm,
                // Left as scraping deliberately: BatchProgressService recomputes
                // the real state on its next pass rather than guessing here.
                'status' => 'scraping',
                'totalCells' => (int) $row->totalCells,
                'pendingCells' => (int) $row->pendingCells,
                'startedAt' => $row->startedAt,
                'created_at' => $row->startedAt,
                'updated_at' => $row->startedAt,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('outreach_batches');
    }
};
