<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\AsEmailTask;
use App\Models\AsEmailTemplate;
use App\Models\AsScheduleActivity;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * This side of AniSystem's mail: writing the morning email, and sending.
 *
 * The farmer app owns the same book and sends anything a person is waiting
 * on. What lives here is the part that has to happen when nobody is looking —
 * a cron, at six in the morning, deciding that today's work is worth an email
 * and then putting it in the post.
 *
 * Resend is called over its REST API rather than through the SDK: this app
 * does not have the package and one HTTP call does not need one.
 */
class AnisystemResend
{
    /** How many a single run may send. A cron that times out is worse than a slow one. */
    public const PER_RUN = 50;

    public static function key(): string
    {
        return (string) (env('RESEND_KEY') ?: env('RESEND_API_KEY') ?: '');
    }

    public static function from(): string
    {
        return (string) (env('RESEND_FROM') ?: 'anee.io <onboarding@resend.dev>');
    }

    public static function configured(): bool
    {
        return self::key() !== '';
    }

    /**
     * Empty as much of the book as the caller allows.
     *
     * @return array{tried:int,sent:int,failed:int}
     */
    public static function drain(int $limit = self::PER_RUN): array
    {
        $limit = max(1, min($limit, 500));
        $out = ['tried' => 0, 'sent' => 0, 'failed' => 0];

        if (! self::configured()) {
            Log::warning('AnisystemResend: RESEND_KEY is not set; nothing can be sent.');

            return $out;
        }

        foreach (AsEmailTask::due()->limit($limit)->get() as $task) {
            $out['tried']++;
            self::attempt($task) ? $out['sent']++ : $out['failed']++;
        }

        return $out;
    }

    /** One try at one row. The attempt is counted first, so a crash cannot loop. */
    public static function attempt(AsEmailTask $task): bool
    {
        $task->attempts = (int) $task->attempts + 1;
        $task->save();

        try {
            $res = Http::withToken(self::key())->acceptJson()->timeout(20)
                ->post('https://api.resend.com/emails', [
                    'from' => self::from(),
                    // Bare, with no display name: Resend's sandbox compares the
                    // whole string against the key owner's address, so
                    // "Name <addr>" is refused where the address alone is not.
                    'to' => [$task->toEmail],
                    'subject' => $task->subject,
                    'html' => $task->bodyHtml,
                    // The plain-text twin: the words and every link, no stylesheet.
                    'text' => EmailBlocks::toText((string) $task->bodyHtml),
                ]);

            if ($res->successful() && $res->json('id')) {
                $task->forceFill([
                    'status' => AsEmailTask::SENT,
                    'providerId' => substr((string) $res->json('id'), 0, 120),
                    'sentAt' => now(),
                    'lastError' => null,
                ])->save();

                return true;
            }

            $why = trim(($res->json('name') ?: 'HTTP ' . $res->status())
                . ': ' . ($res->json('message') ?: $res->body()));
        } catch (\Throwable $e) {
            $why = $e->getMessage();
        }

        $task->forceFill([
            'status' => AsEmailTask::FAILED,
            'lastError' => mb_substr($why, 0, 2000),
        ])->save();

        return false;
    }

    /**
     * Write one schedule's morning email for everyone it is meant for.
     *
     * A worker is told only about the work they are on — a list of everyone
     * else's jobs is noise, and noise is what stops people reading these. The
     * owner gets the whole day.
     *
     * @return int how many rows were written
     */
    public static function queueDayFor(AsCroppingSchedule $schedule, ?Carbon $now = null): int
    {
        $today = ($now ?: Carbon::now('Asia/Manila'))->copy()->startOfDay();
        $tomorrow = $today->copy()->addDay();

        $activities = AsScheduleActivity::query()
            ->where('deleteStatus', 1)
            ->where('croppingScheduleId', $schedule->id)
            ->where('isDraft', 0)
            ->where('isHidden', 0)
            ->whereIn('targetDate', [$today->toDateString(), $tomorrow->toDateString()])
            ->with(['lots', 'workers'])
            ->orderBy('targetDate')
            ->orderBy('sequenceOrder')
            ->get();

        // Nothing on either day is worth nobody's inbox.
        if ($activities->isEmpty()) {
            return 0;
        }

        $made = 0;

        if ($schedule->notifyOwnerDaily) {
            $owner = \App\Models\User::find($schedule->anisystemUserId ?: $schedule->usersId);
            if ($owner && filled($owner->email)) {
                $made += self::write($schedule, $owner->email, $owner->name ?? 'there', $activities, $today, $tomorrow) ? 1 : 0;
            }
        }

        if ($schedule->notifyWorkersDaily) {
            $workers = \App\Models\AsScheduleWorker::where('croppingScheduleId', $schedule->id)
                ->where('deleteStatus', 1)->get();

            foreach ($workers as $worker) {
                if (blank($worker->email)) {
                    continue;   // no address on file; not an error
                }
                $theirs = $activities->filter(
                    fn ($a) => $a->workers->contains(fn ($w) => (int) $w->id === (int) $worker->id)
                );
                if ($theirs->isEmpty()) {
                    continue;   // nothing of theirs on either day
                }
                $made += self::write($schedule, $worker->email, $worker->workerName, $theirs, $today, $tomorrow) ? 1 : 0;
            }
        }

        return $made;
    }

    /** One row in the book, rendered from the template the admin owns. */
    private static function write(AsCroppingSchedule $schedule, string $email, string $name, $activities, Carbon $today, Carbon $tomorrow): bool
    {
        $template = AsEmailTemplate::where('groupKey', 'AniSystem')
            ->where('templateKey', 'daily_digest')
            ->where('deleteStatus', 1)
            ->where('isActive', 1)
            ->first();

        if (! $template) {
            Log::warning('AnisystemResend: the daily_digest template is missing or switched off.');

            return false;
        }

        $list = self::list($activities, $today, $tomorrow, $schedule);
        $tags = [
            // What the reader knows the app as (the group key keeps the old name).
            'siteName' => 'anee.io',
            'app_name' => 'anee.io',
            'loginUrl' => 'https://anee.io/login',
            'recipient_name' => $name,
            'workerName' => $name,
            'schedule_title' => (string) $schedule->title,
            'scheduleTitle' => (string) $schedule->title,
            'today_date' => $today->format('l, M j'),
            'tomorrow_date' => $tomorrow->format('l, M j'),
            'dateLabel' => $today->format('l, M j'),
            'today_count' => (string) $activities->where('targetDate', $today->toDateString())->count(),
            'tomorrow_count' => (string) $activities->where('targetDate', $tomorrow->toDateString())->count(),
            'activities_list' => $list,
            'tasksTable' => $list,
            'sentBy' => 'anee.io',
        ];

        $subject = $template->subject;
        $body = $template->bodyHtml;
        foreach ($tags as $k => $v) {
            $subject = str_replace('{{' . $k . '}}', (string) $v, $subject);
            $body = str_replace('{{' . $k . '}}', (string) $v, $body);
        }
        // Nothing the reader should see: a tag this sender has no value for.
        $subject = trim(preg_replace('~\{\{\s*[A-Za-z0-9_]+\s*\}\}~', '', $subject) ?? $subject);
        $body = preg_replace('~\{\{\s*[A-Za-z0-9_]+\s*\}\}~', '', $body) ?? $body;

        AsEmailTask::create([
            'groupKey' => 'AniSystem',
            'templateKey' => 'daily_digest',
            'toEmail' => $email,
            'toName' => $name,
            'subject' => mb_substr($subject, 0, 255),
            'bodyHtml' => $body,
            'status' => AsEmailTask::QUEUED,
            'sendAfter' => now(),
            'relatedType' => 'daily_digest',
            'croppingScheduleId' => $schedule->id,
            'deleteStatus' => 1,
        ]);

        return true;
    }

    /**
     * The work itself, as a table an email client will actually draw — the
     * same rows anee.io's EmailSkin::taskRow() draws (a gold Today / Tomorrow
     * badge, the title, the lots, a short description), and the way to the
     * schedule's public page when it has one.
     */
    private static function list($activities, Carbon $today, Carbon $tomorrow, ?AsCroppingSchedule $schedule = null): string
    {
        $rows = '';
        foreach ($activities as $a) {
            $date = \Illuminate\Support\Carbon::parse($a->targetDate)->toDateString();
            $when = $date === $today->toDateString() ? 'Today'
                : ($date === $tomorrow->toDateString() ? 'Tomorrow' : '');
            $lots = $a->lots->pluck('lotName')->filter()->implode(', ');

            $rows .= '<tr><td class="ae-line ae-task" style="padding:14px 0 14px 14px;border-bottom:1px solid #e3eadb;border-left:3px solid #87B84C;">'
                . ($when !== '' ? '<span style="display:inline-block;margin:0 8px 4px 0;padding:2px 9px;border-radius:999px;background:#E8BE1C;'
                    . 'font-size:11px;line-height:16px;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#2B3A1C;">' . $when . '</span>' : '')
                . '<div class="ae-ink" style="font-size:16px;line-height:1.4;font-weight:700;color:#1f2a17;">' . e((string) $a->activityTitle) . '</div>'
                . ($lots ? '<div class="ae-muted" style="margin-top:4px;font-size:13px;line-height:1.5;color:#5f6b55;">' . e($lots) . '</div>' : '')
                . (filled($a->description)
                    ? '<div style="margin-top:6px;font-size:14px;line-height:1.55;">'
                        . e(\Illuminate\Support\Str::limit(strip_tags((string) $a->description), 220)) . '</div>'
                    : '')
                . '</td></tr>';
        }

        $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 18px;">'
            . $rows . '</table>';

        $token = $schedule ? (string) ($schedule->shareToken ?? '') : '';
        if ($token !== '') {
            $url = e('https://anee.io/s/' . $token);
            $html .= '<table role="presentation" class="ae-btn" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 8px;">'
                . '<tr><td align="center" bgcolor="#E8BE1C" style="background:#E8BE1C;border-radius:999px;">'
                . '<a href="' . $url . '" target="_blank" style="display:inline-block;padding:15px 30px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;'
                . 'font-size:16px;line-height:20px;font-weight:800;color:#2B3A1C;text-decoration:none;border-radius:999px;">'
                . '<span style="color:#2B3A1C;">See the whole plan</span></a></td></tr></table>';
        }

        return $html;
    }
}
