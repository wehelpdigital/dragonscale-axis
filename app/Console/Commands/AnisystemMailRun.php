<?php

namespace App\Console\Commands;

use App\Models\AsCroppingSchedule;
use App\Models\AsEmailTask;
use App\Support\AnisystemResend;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * The daily mail run for AniSystem.
 *
 * Two jobs, in this order.
 *
 * First it WRITES: every cropping schedule whose notification hour has come
 * round gets its morning email queued for the people it is set up to tell.
 * The hour is checked here rather than by a hundred separate crons, which is
 * why this runs often and does nothing most of the time.
 *
 * Then it SENDS: whatever is due in the book goes out, capped, so one bad
 * morning cannot turn into a four-thousand-email request that times out
 * halfway with nobody able to say which half went.
 *
 * The book (`as_email_tasks`) is shared with the farmer app, which writes
 * into it for anything a person is waiting on and sends those itself. What
 * reaches this command is the scheduled work and anything that failed and
 * deserves another try.
 */
class AnisystemMailRun extends Command
{
    protected $signature = 'anisystem:mail-run
        {--limit=50 : How many emails this run may send}
        {--queue-only : Write the morning emails but send nothing}
        {--send-only : Send what is due but write nothing new}
        {--force : Ignore the hour and queue every schedule that is switched on}';

    protected $description = "Queue AniSystem's daily schedule emails and send what is due";

    public function handle(): int
    {
        $limit = max(1, min((int) $this->option('limit'), 500));
        $now = Carbon::now('Asia/Manila');

        $queued = 0;
        if (! $this->option('send-only')) {
            $queued = $this->queueMorningMail($now, (bool) $this->option('force'));
            $this->info("Queued {$queued} morning email(s).");
        }

        if ($this->option('queue-only')) {
            return self::SUCCESS;
        }

        $out = AnisystemResend::drain($limit);
        $this->info("Sent {$out['sent']}, failed {$out['failed']}, of {$out['tried']} tried (cap {$limit}).");

        return self::SUCCESS;
    }

    /**
     * Write today's morning emails for every schedule whose hour has come.
     *
     * A schedule is only ever queued once a day. `notifyLastSentDate` is the
     * guard, and it is written the moment the rows are made rather than after
     * they are sent — a run that dies mid-send must not send everybody a
     * second copy an hour later.
     */
    private function queueMorningMail(Carbon $now, bool $force): int
    {
        $schedules = AsCroppingSchedule::query()
            ->where('deleteStatus', 1)
            ->where(fn ($q) => $q->where('notifyWorkersDaily', 1)->orWhere('notifyOwnerDaily', 1))
            ->when(! $force, fn ($q) => $q
                ->where('notifyHour', '<=', $now->hour)
                ->where(fn ($w) => $w->whereNull('notifyLastSentDate')
                    ->orWhereDate('notifyLastSentDate', '<', $now->toDateString())))
            ->get();

        $made = 0;
        foreach ($schedules as $schedule) {
            try {
                $made += AnisystemResend::queueDayFor($schedule, $now);
                $schedule->forceFill(['notifyLastSentDate' => $now->toDateString()])->save();
            } catch (\Throwable $e) {
                // One bad season must not stop the rest of the farm being told.
                $this->warn("Schedule {$schedule->id}: {$e->getMessage()}");
            }
        }

        return $made;
    }
}
