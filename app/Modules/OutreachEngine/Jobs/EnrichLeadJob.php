<?php

namespace App\Modules\OutreachEngine\Jobs;

use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Services\LeadEnrichmentService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Finds the contact email for one lead.
 *
 * READ THIS BEFORE DISPATCHING: this app runs QUEUE_CONNECTION=sync, so dispatch()
 * executes the job INLINE and blocks the caller - enrichment fetches several web pages
 * and may call an LLM, which no HTTP request should be made to wait on. The supported
 * path is the cron command `outreach:enrich-leads`. This class exists so the module is
 * ready the day the operator switches QUEUE_CONNECTION to `database` (the jobs table
 * already ships with the module) and runs `php artisan queue:work`; only then does
 * dispatching this beat the command.
 */
class EnrichLeadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Attempts before the job is handed to failed().
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Seconds the job may run. Enough for a search call plus a handful of page fetches
     * at 15s each, and an LLM round trip.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The lead to enrich.
     *
     * The id, not the model: enrich() mutates and saves the row, and a serialised copy
     * from minutes ago could write back a stale attempt counter.
     *
     * @var int
     */
    public $leadId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $leadId)
    {
        $this->leadId = $leadId;
    }

    /**
     * Execute the job.
     */
    public function handle(SettingsResolver $resolver): void
    {
        $lead = OutreachLead::query()->active()->find($this->leadId);

        if (!$lead) {
            Log::warning('[OutreachEngine] EnrichLeadJob: lead ' . $this->leadId . ' is gone or deleted.');

            return;
        }

        if ($lead->hasValidEmail()) {
            Log::info('[OutreachEngine] EnrichLeadJob: lead ' . $this->leadId . ' already has an email - skipping.');

            return;
        }

        if ((int) $lead->enrichmentAttempts >= OutreachLead::MAX_ENRICHMENT_ATTEMPTS) {
            Log::info('[OutreachEngine] EnrichLeadJob: lead ' . $this->leadId . ' has used all enrichment attempts - skipping.');

            return;
        }

        // requireForUser() throws when the account has no saved settings; letting that
        // escape is deliberate, so the failure lands in failed() with a real reason.
        $settings = $resolver->requireForUser((int) $lead->usersId);

        $result = (new LeadEnrichmentService($settings))->enrich($lead);

        if (!empty($result['email'])) {
            Log::info(
                '[OutreachEngine] EnrichLeadJob: lead ' . $this->leadId . ' resolved to '
                . $result['email'] . ' via ' . ($result['source'] ?: 'unknown') . '.'
            );

            return;
        }

        Log::info('[OutreachEngine] EnrichLeadJob: lead ' . $this->leadId . ' produced no email - ' . ($result['error'] ?: 'nothing found'));
    }

    /**
     * The job exhausted its tries or threw outside enrich().
     *
     * enrich() records its own failures, so this covers what happened before it and,
     * more importantly, clears the 'processing' status it set on the way in - a row
     * left processing is invisible to needsEnrichment() and would never be retried.
     */
    public function failed(\Throwable $e): void
    {
        try {
            OutreachLead::query()
                ->where('id', $this->leadId)
                ->update([
                    'enrichmentStatus' => OutreachLead::ENRICHMENT_FAILED,
                    'enrichmentError' => Str::limit($e->getMessage(), 480),
                ]);
        } catch (\Throwable $inner) {
            Log::error('[OutreachEngine] EnrichLeadJob: could not mark lead ' . $this->leadId . ' failed - ' . $inner->getMessage());
        }

        Log::error('[OutreachEngine] EnrichLeadJob failed for lead ' . $this->leadId . ': ' . $e->getMessage());
    }
}
