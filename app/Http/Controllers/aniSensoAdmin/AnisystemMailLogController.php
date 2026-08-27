<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsEmailTask;
use App\Support\AnisystemResend;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The mail book: every email AniSystem has meant to send.
 *
 * "Did Nena get her schedule?" used to have no answer at all — mail either
 * arrived or it did not, and the only trace was a line in a log file on a
 * container that gets wiped every deploy. Every message is a row now, written
 * before the attempt, and this is where they are read.
 *
 * The screen also runs the thing. Waiting until six tomorrow morning to find
 * out whether the cron works is not a way to build anything, so "Run now"
 * does exactly what the scheduler does, and says what happened.
 */
class AnisystemMailLogController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request)
    {
        // ?id= opens one message; without it, the list.
        if ($request->filled('id')) {
            return $this->show((int) $request->query('id'));
        }

        $status = (string) $request->query('status', '');
        $find = trim((string) $request->query('q', ''));

        $tasks = AsEmailTask::query()
            ->where('deleteStatus', 1)
            ->when($status !== '', function ($q) use ($status) {
                // "Given up" is a failure that has run out of tries — worth
                // its own filter, because it is the only one nothing will fix
                // on its own.
                $status === 'given_up'
                    ? $q->where('status', AsEmailTask::FAILED)->where('attempts', '>=', AsEmailTask::MAX_ATTEMPTS)
                    : $q->where('status', $status);
            })
            ->when($find !== '', fn ($q) => $q->where(function ($w) use ($find) {
                $w->where('toEmail', 'like', "%{$find}%")
                    ->orWhere('subject', 'like', "%{$find}%")
                    ->orWhere('templateKey', 'like', "%{$find}%");
            }))
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $counts = [
            'all' => AsEmailTask::where('deleteStatus', 1)->count(),
            'queued' => AsEmailTask::where('deleteStatus', 1)->where('status', AsEmailTask::QUEUED)->count(),
            'sent' => AsEmailTask::where('deleteStatus', 1)->where('status', AsEmailTask::SENT)->count(),
            'failed' => AsEmailTask::where('deleteStatus', 1)->where('status', AsEmailTask::FAILED)->count(),
        ];

        return view('aniSensoAdmin.mailLog.index', [
            'tasks' => $tasks,
            'counts' => $counts,
            'status' => $status,
            'find' => $find,
            'configured' => AnisystemResend::configured(),
            'from' => AnisystemResend::from(),
            'due' => AsEmailTask::due()->count(),
        ]);
    }

    /** One message, as it will arrive. */
    public function show(int $id)
    {
        $task = AsEmailTask::where('deleteStatus', 1)->findOrFail($id);

        return view('aniSensoAdmin.mailLog.show', compact('task'));
    }

    /**
     * Do now what the cron does at ten past.
     *
     * Nobody should have to wait until tomorrow morning to learn whether the
     * daily email works.
     */
    public function run(Request $request)
    {
        $limit = max(1, min((int) $request->input('limit', 50), 200));
        $force = $request->boolean('force');

        $queued = 0;
        foreach (\App\Models\AsCroppingSchedule::where('deleteStatus', 1)
            ->where(fn ($q) => $q->where('notifyWorkersDaily', 1)->orWhere('notifyOwnerDaily', 1))
            ->when(! $force, fn ($q) => $q
                ->where('notifyHour', '<=', Carbon::now('Asia/Manila')->hour)
                ->where(fn ($w) => $w->whereNull('notifyLastSentDate')
                    ->orWhereDate('notifyLastSentDate', '<', Carbon::now('Asia/Manila')->toDateString())))
            ->get() as $schedule) {
            $queued += AnisystemResend::queueDayFor($schedule);
            $schedule->forceFill(['notifyLastSentDate' => Carbon::now('Asia/Manila')->toDateString()])->save();
        }

        $out = AnisystemResend::drain($limit);

        return back()->with('success', "Queued {$queued}. Sent {$out['sent']}, failed {$out['failed']}, of {$out['tried']} tried.");
    }

    /** Put a given-up message back in the queue for one more go. */
    public function retry(Request $request)
    {
        $task = AsEmailTask::where('deleteStatus', 1)->findOrFail((int) $request->input('id'));
        $task->forceFill([
            'status' => AsEmailTask::QUEUED,
            'attempts' => 0,
            'lastError' => null,
            'sendAfter' => now(),
        ])->save();

        $ok = AnisystemResend::attempt($task);

        return back()->with($ok ? 'success' : 'error',
            $ok ? 'Sent.' : ('Still refused: ' . $task->fresh()->lastError));
    }
}
