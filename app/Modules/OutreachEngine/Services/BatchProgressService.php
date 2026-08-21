<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachBatch;
use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSearchGrid;
use Illuminate\Support\Facades\Log;

/**
 * Keeps a batch's cached counters and pipeline stage in step with reality.
 *
 * The counters on outreach_batches are a cache of what the grid and lead tables
 * already know. This is the only thing that should write them - two writers
 * would drift, and a progress bar that disagrees with the data behind it is
 * worse than no progress bar.
 *
 * A sweep is Complete when three things are all true: no cell is still waiting,
 * no lead is still hunting for an address, and no address is still queued for
 * verification. Note what is NOT required - that leads were found, or that any
 * address turned out to be valid. A sweep of a province with no matching
 * businesses has genuinely finished; leaving it forever "scraping" would be a
 * lie, and would hide it from the completed list it belongs in.
 */
class BatchProgressService
{
    /**
     * Recompute one batch from its grids and leads.
     *
     * @param  bool  $verificationEnabled  When the account runs no verifier, the
     *                                     verification stage cannot gate completion.
     */
    public function refresh(OutreachBatch $batch, bool $verificationEnabled = true): OutreachBatch
    {
        try {
            $batchId = (string) $batch->batchId;
            $userId = (int) $batch->usersId;

            $cells = OutreachSearchGrid::query()
                ->active()
                ->forUser($userId)
                ->where('batchId', $batchId)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status IN ('pending','processing') THEN 1 ELSE 0 END) as pending")
                ->first();

            $leads = OutreachLead::query()
                ->active()
                ->forUser($userId)
                ->where('batchId', $batchId)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN email IS NOT NULL AND email != '' THEN 1 ELSE 0 END) as withEmail")
                ->selectRaw("SUM(CASE WHEN enrichmentStatus IN ('pending','processing') THEN 1 ELSE 0 END) as enrichPending")
                ->selectRaw("SUM(CASE WHEN email IS NOT NULL AND email != '' AND verificationStatus = 'verified' THEN 1 ELSE 0 END) as verified")
                ->selectRaw('SUM(CASE WHEN isEmailValid = 1 THEN 1 ELSE 0 END) as valid')
                ->selectRaw("SUM(CASE WHEN email IS NOT NULL AND email != '' AND verificationStatus IN ('pending','processing') AND verificationAttempts < " . OutreachLead::MAX_VERIFICATION_ATTEMPTS . " THEN 1 ELSE 0 END) as verifyPending")
                ->first();

            $totalCells = (int) ($cells->total ?? 0);
            $pendingCells = (int) ($cells->pending ?? 0);
            $totalLeads = (int) ($leads->total ?? 0);
            $withEmail = (int) ($leads->withEmail ?? 0);
            $enrichPending = (int) ($leads->enrichPending ?? 0);
            $verified = (int) ($leads->verified ?? 0);
            $valid = (int) ($leads->valid ?? 0);
            $verifyPending = (int) ($leads->verifyPending ?? 0);

            $batch->totalCells = $totalCells;
            $batch->pendingCells = $pendingCells;
            $batch->totalLeads = $totalLeads;
            $batch->leadsWithEmail = $withEmail;
            $batch->leadsVerified = $verified;
            $batch->leadsValid = $valid;
            $batch->countsRefreshedAt = now('Asia/Manila');

            // Cancelled is a decision, not a measurement - never overwrite it.
            if ($batch->status !== OutreachBatch::STATUS_CANCELLED) {
                $batch->status = $this->stageFor(
                    $pendingCells,
                    $enrichPending,
                    $verificationEnabled ? $verifyPending : 0
                );

                if ($batch->status === OutreachBatch::STATUS_COMPLETE && $batch->completedAt === null) {
                    $batch->completedAt = now('Asia/Manila');
                } elseif ($batch->status !== OutreachBatch::STATUS_COMPLETE) {
                    // Work arrived after the fact (a re-scrape, a retry) - the
                    // sweep is running again and the old finish time is wrong.
                    $batch->completedAt = null;
                }
            }

            $batch->save();
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Batch refresh failed: ' . $e->getMessage(), [
                'batchId' => $batch->batchId ?? null,
            ]);
        }

        return $batch;
    }

    /**
     * Refresh every batch belonging to one account that is not already settled.
     *
     * @return int  How many batches were touched.
     */
    public function refreshForUser(int $userId, bool $verificationEnabled = true): int
    {
        $batches = OutreachBatch::query()
            ->active()
            ->forUser($userId)
            ->whereNotIn('status', [OutreachBatch::STATUS_CANCELLED])
            ->get();

        foreach ($batches as $batch) {
            $this->refresh($batch, $verificationEnabled);
        }

        return $batches->count();
    }

    /**
     * Which stage a batch is in, given what is still outstanding.
     *
     * Ordered by the pipeline itself: cells first, then addresses, then
     * verification. The earliest unfinished stage is the one being reported,
     * which is what an admin watching the bar expects to see.
     */
    protected function stageFor(int $pendingCells, int $enrichPending, int $verifyPending): string
    {
        if ($pendingCells > 0) {
            return OutreachBatch::STATUS_SCRAPING;
        }

        if ($enrichPending > 0) {
            return OutreachBatch::STATUS_ENRICHING;
        }

        if ($verifyPending > 0) {
            return OutreachBatch::STATUS_VERIFYING;
        }

        return OutreachBatch::STATUS_COMPLETE;
    }
}
