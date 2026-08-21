<?php

namespace App\Modules\OutreachEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OutreachEngine\Models\OutreachEmailLog;
use App\Modules\OutreachEngine\Models\OutreachInboundMessage;
use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Models\OutreachSearchGrid;
use App\Modules\OutreachEngine\Services\OutreachDecisionService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Lead Finder overview: the headline numbers, the 30-day trend, and whether the
 * campaign is allowed to send right now.
 *
 * The page renders as a shell and pulls everything from data() over AJAX, so a slow
 * aggregate never holds up the layout.
 */
class DashboardController extends Controller
{
    /** How many days the trend chart covers, today included. */
    const TREND_DAYS = 30;

    /**
     * Display the dashboard shell.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $userId = (int) Auth::id();
        $settings = (new SettingsResolver())->forUser($userId);

        return view('outreach::dashboard', [
            'settings' => $settings,
            // forUserOrNew() hands back an unsaved instance when nothing was ever
            // saved, so ->exists is how the view tells "configured" from "defaults".
            'isConfigured' => $settings->exists,
            'trendDays' => self::TREND_DAYS,
        ]);
    }

    /**
     * Every dashboard number in one payload.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {
        try {
            $userId = (int) Auth::id();

            $settings = (new SettingsResolver())->forUser($userId);
            $window = (new OutreachDecisionService($settings))->evaluateWindow();

            $leads = OutreachLead::query()->active()->forUser($userId);
            $totalLeads = (clone $leads)->count();
            $enrichedEmails = (clone $leads)->hasEmail()->count();
            $contactedLeads = (clone $leads)->whereNotNull('lastContactedAt')->count();
            $repliedLeads = (clone $leads)->where('outreachStatus', OutreachLead::OUTREACH_REPLIED)->count();

            $logs = OutreachEmailLog::query()->active()->forUser($userId);
            $totalSent = (clone $logs)->where('status', OutreachEmailLog::STATUS_SENT)->count();
            $totalBounced = (clone $logs)->where('status', OutreachEmailLog::STATUS_BOUNCED)->count();

            // A bounced log has already been flipped out of 'sent' by the inbound
            // processor, so neither rate may divide by $totalSent alone - the
            // denominator has to be every message that actually left.
            $delivered = $totalSent + $totalBounced;

            // Counted as DISTINCT leads, not messages: a chatty prospect who sends
            // four emails is still one reply, and the rate stays under 100%.
            $repliesReceived = OutreachInboundMessage::query()
                ->active()
                ->forUser($userId)
                ->inbound()
                ->where('isBounce', false)
                ->whereNotNull('leadId')
                ->distinct()
                ->count('leadId');

            $pendingGrids = OutreachSearchGrid::query()
                ->active()
                ->forUser($userId)
                ->where('status', OutreachSearchGrid::STATUS_PENDING)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard loaded.',
                'data' => [
                    'stats' => [
                        'totalLeads' => $totalLeads,
                        'enrichedEmails' => $enrichedEmails,
                        'totalSent' => $totalSent,
                        'repliesReceived' => $repliesReceived,
                        'replyRate' => $delivered > 0 ? round(($repliesReceived / $delivered) * 100, 1) : 0.0,
                        'bounceRate' => $delivered > 0 ? round(($totalBounced / $delivered) * 100, 1) : 0.0,
                        'sentToday' => (int) $window['sentToday'],
                        'dailyCap' => (int) $window['cap'],
                        'pendingGrids' => $pendingGrids,
                    ],
                    'daily' => $this->dailySeries($userId),
                    'pipeline' => [
                        'scraped' => $totalLeads,
                        'enriched' => $enrichedEmails,
                        'contacted' => $contactedLeads,
                        'replied' => $repliedLeads,
                    ],
                    'window' => [
                        'allowed' => (bool) $window['allowed'],
                        'reason' => (string) $window['reason'],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[OutreachEngine] Dashboard data failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading the dashboard.',
            ], 500);
        }
    }

    /**
     * Sends and replies per day for the trend chart.
     *
     * The date spine is built first and the grouped counts are poured into it, so a
     * day with no activity still shows up as a zero. Charting the sparse result of
     * the group-by instead would slide every later point onto the wrong date.
     *
     * @return array ['labels'=>string[],'sent'=>int[],'replies'=>int[]]
     */
    private function dailySeries(int $userId): array
    {
        $end = Carbon::now('Asia/Manila')->endOfDay();
        $start = $end->copy()->startOfDay()->subDays(self::TREND_DAYS - 1);

        $spine = [];
        for ($cursor = $start->copy(); $cursor->lessThanOrEqualTo($end); $cursor->addDay()) {
            $spine[$cursor->toDateString()] = 0;
        }

        $rangeStart = $start->toDateTimeString();
        $rangeEnd = $end->toDateTimeString();

        // Timestamps are already written in Asia/Manila (BaseModel::freshTimestamp),
        // so DATE() buckets them on the right business day with no conversion.
        // get() rather than pluck(): pluck rewrites the select list on a query that has
        // none, and leaning on that subtlety with a raw aliased select invites a
        // "column not found" the day the helper changes.
        $sentByDay = $this->bucketMap(
            OutreachEmailLog::query()
                ->active()
                ->forUser($userId)
                ->where('status', OutreachEmailLog::STATUS_SENT)
                ->whereNotNull('sentAt')
                ->whereBetween('sentAt', [$rangeStart, $rangeEnd])
                ->selectRaw('DATE(sentAt) as bucketDate, COUNT(*) as total')
                ->groupBy('bucketDate')
                ->get()
        );

        // receivedAt is read off the mail header and can be missing on a malformed
        // message; created_at is when we stored it, which is never null.
        $repliesByDay = $this->bucketMap(
            OutreachInboundMessage::query()
                ->active()
                ->forUser($userId)
                ->inbound()
                ->where('isBounce', false)
                ->whereRaw('COALESCE(receivedAt, created_at) BETWEEN ? AND ?', [$rangeStart, $rangeEnd])
                ->selectRaw('DATE(COALESCE(receivedAt, created_at)) as bucketDate, COUNT(*) as total')
                ->groupBy('bucketDate')
                ->get()
        );

        $labels = [];
        $sent = [];
        $replies = [];

        foreach ($spine as $date => $zero) {
            $labels[] = $date;
            $sent[] = (int) ($sentByDay[$date] ?? $zero);
            $replies[] = (int) ($repliesByDay[$date] ?? $zero);
        }

        return [
            'labels' => $labels,
            'sent' => $sent,
            'replies' => $replies,
        ];
    }

    /**
     * Turn a grouped result set into ['YYYY-MM-DD' => count].
     *
     * @param  \Illuminate\Support\Collection  $rows
     */
    private function bucketMap($rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $map[(string) $row->bucketDate] = (int) $row->total;
        }

        return $map;
    }
}
