<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Services\ScheduleManager\ScheduleReadinessService;
use App\Services\ScheduleManager\WeatherService;
use App\Support\CropStages;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The three things the farmer app tells you about a season that this admin
 * never did: what is still missing from the plan, what the crop is doing
 * today, and what the sky is about to do over each lot.
 *
 * All three are readings of data that already exists — nothing here writes.
 * They answer over AJAX so the Activities tab can ask again after an edit
 * instead of carrying a snapshot taken when the page was rendered.
 */
class ScheduleInsightController extends BaseScheduleController
{
    /** Don't resolve more distinct locations than this in one request. */
    private const MAX_LOCATIONS = 12;

    /**
     * What is still missing from this plan. Same checks, same wording as the
     * bell in the farmer's own toolbar, so a client reading their Notice and
     * an admin reading this one are looking at the same list.
     */
    public function notice(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        // Eager, or the checks walk a season one activity at a time: the lots
        // of every card are read twice over, and a busy schedule turned a
        // half-second answer into twenty.
        $schedule->load(['lots', 'workers', 'activities.lots', 'drafts']);

        return $this->jsonOk('Notice', [
            'data' => (new ScheduleReadinessService())->check($schedule),
        ]);
    }

    /**
     * What each lot's crop is doing on a given day (default today), read from
     * the same stage tables the farmer app uses.
     */
    public function growth(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $date = $request->query('date');
        $on = $date ? Carbon::parse($date)->startOfDay() : Carbon::today();

        $anchors = $this->lotAnchors($schedule);
        $rows = [];
        $quiet = [];

        foreach ($schedule->lots as $lot) {
            $crop = trim((string) $lot->crop);
            $table = $crop !== '' ? (CropStages::CROPS[$crop] ?? null) : null;
            $age = $this->lotDayNumberOn($lot, $anchors, $on);

            if (! $table || ! $age) {
                $quiet[] = [
                    'lotId' => $lot->id,
                    'lotName' => $lot->lotName,
                    'crop' => $crop,
                    'cropLabel' => $table['label'] ?? null,
                    // Why this lot cannot be read, in the words of the thing
                    // that would fix it.
                    'why' => $crop === ''
                        ? 'No crop set on this lot'
                        : (! $table
                            ? 'No stage table for "' . $crop . '"'
                            : 'No day 0 yet, or the date is before it'),
                ];
                continue;
            }

            $stage = $this->stageOf($table, $age['day'], $age['counter']);
            if (! $stage) {
                $quiet[] = [
                    'lotId' => $lot->id,
                    'lotName' => $lot->lotName,
                    'crop' => $crop,
                    'cropLabel' => $table['label'],
                    'why' => 'Day ' . $age['day'] . ' is before the first stage',
                ];
                continue;
            }

            $rows[] = [
                'lotId' => $lot->id,
                'lotName' => $lot->lotName,
                'variety' => $lot->variety,
                'crop' => $crop,
                'day' => $age['day'],
                'counter' => $age['counter'],
                'anchorDate' => $age['anchor'],
                'stage' => $stage,
            ];
        }

        return $this->jsonOk('Growth stages', [
            'data' => [
                'date' => $on->toDateString(),
                'prettyDate' => $on->format('D, j M Y'),
                'rows' => $rows,
                'quiet' => $quiet,
                'crops' => collect(CropStages::CROPS)
                    ->map(fn ($c, $k) => ['key' => $k, 'label' => $c['label'], 'icon' => $c['icon']])
                    ->values()->all(),
            ],
        ]);
    }

    /**
     * Six days of forecast for every distinct lot location on this schedule.
     * Identical addresses are resolved once, so a farm with four lots in one
     * town costs one call, not four.
     */
    public function weather(Request $request, WeatherService $weather)
    {
        $schedule = $this->scheduleFromRequest($request);

        $locations = [];   // key => ['query' => ..., 'label' => ..., 'lots' => []]
        $unplaced = [];

        foreach ($schedule->lots as $lot) {
            $query = $lot->geocode_query;
            if (! $query) {
                $unplaced[] = ['lotId' => $lot->id, 'lotName' => $lot->lotName];
                continue;
            }
            $key = $lot->location_key;
            $locations[$key] ??= [
                'query' => $query,
                'label' => $lot->full_address ?: $query,
                'lots' => [],
            ];
            $locations[$key]['lots'][] = ['lotId' => $lot->id, 'lotName' => $lot->lotName];
        }

        // Hour by hour is for the Weather module's own tab. The same endpoint
        // feeds the little chips on a day header, which want six cards and
        // nothing else — asking for hours there would treble the response for
        // nothing anybody is looking at.
        $wantHourly = $request->boolean('hourly');

        $out = [];
        foreach (array_slice($locations, 0, self::MAX_LOCATIONS, true) as $key => $info) {
            $forecast = $weather->forecastForPlace($info['query'], 6);
            $row = [
                'key' => $key,
                'label' => $info['label'],
                'lots' => $info['lots'],
                'ok' => (bool) $forecast,
                'place' => $forecast['place'] ?? $info['label'],
                'days' => $forecast['days'] ?? [],
            ];

            if ($wantHourly && $forecast) {
                // Grouped by date, because the hours belong to the day you
                // opened rather than to a flat rail of the next twenty-four.
                $row['hoursByDay'] = $weather->hourlyByDay(
                    (float) $forecast['lat'],
                    (float) $forecast['lon'],
                    6
                ) ?: [];
            }

            $out[] = $row;
        }

        return $this->jsonOk('Forecast', [
            'data' => [
                'located' => ! empty($out),
                'locations' => $out,
                'unplaced' => $unplaced,
                'dropped' => max(0, count($locations) - self::MAX_LOCATIONS),
            ],
        ]);
    }

    /**
     * What came off the field: yields, moisture, who bought it and for how
     * much. The farmer app writes these; until now nothing here could read
     * them, which made the season's last chapter invisible to the office.
     */
    public function harvest(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $rows = \Illuminate\Support\Facades\DB::table('as_schedule_post_harvests as h')
            ->leftJoin('as_schedule_lots as l', 'l.id', '=', 'h.lotId')
            ->where('h.croppingScheduleId', $schedule->id)
            ->where('h.deleteStatus', 1)
            ->orderByDesc('h.observationDate')
            ->orderBy('h.sortOrder')
            ->select('h.*', 'l.lotName')
            ->get();

        $out = [];
        $totalValue = 0.0;
        foreach ($rows as $r) {
            $value = ($r->yieldAmount && $r->pricePerUnit)
                ? (float) $r->yieldAmount * (float) $r->pricePerUnit
                : null;
            if ($value) {
                $totalValue += $value;
            }
            $out[] = [
                'id' => (int) $r->id,
                'title' => $r->title ?: 'Untitled record',
                'category' => $r->category,
                'lotName' => $r->lotName,
                'when' => $r->observationDate ? Carbon::parse($r->observationDate)->format('M j, Y') : null,
                'yieldAmount' => $r->yieldAmount !== null ? (float) $r->yieldAmount : null,
                'yieldUnit' => $r->yieldUnit,
                'moisturePercent' => $r->moisturePercent !== null ? (float) $r->moisturePercent : null,
                'pricePerUnit' => $r->pricePerUnit !== null ? (float) $r->pricePerUnit : null,
                'buyer' => $r->buyer,
                'notes' => $r->notes,
                'value' => $value,
                'photos' => array_values(array_filter(array_map(
                    fn ($path) => \App\Support\AnisystemMedia::url($path),
                    $this->harvestPhotos($r)
                ))),
            ];
        }

        return $this->jsonOk('Post-harvest', [
            'data' => ['rows' => $out, 'totalValue' => $totalValue],
        ]);
    }

    /** Remove one record the app's own way: hidden, never destroyed. */
    public function harvestDestroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $ok = \Illuminate\Support\Facades\DB::table('as_schedule_post_harvests')
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->where('deleteStatus', 1)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $ok
            ? $this->jsonOk('Record removed.')
            : $this->jsonFail('Already gone.', 404);
    }

    /** One image column or a list of them — the app has written both. */
    private function harvestPhotos($row): array
    {
        $paths = [];
        if (filled($row->imagePath ?? null)) {
            $paths[] = $row->imagePath;
        }
        $many = json_decode((string) ($row->imagePaths ?? ''), true);
        if (is_array($many)) {
            foreach ($many as $m) {
                $paths[] = is_array($m) ? ($m['path'] ?? null) : $m;
            }
        }

        // The single column is usually repeated inside the list, and the same
        // picture twice reads as two harvests of it.
        return array_values(array_unique(array_filter($paths)));
    }

    /**
     * The public link the farmer's own Quick Share hands out. Reading it
     * never creates one: a plan with no token has never been shared, and
     * opening a panel is not a decision to publish.
     */
    public function share(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        return $this->jsonOk('Share link', [
            'data' => [
                'title' => $schedule->title,
                'token' => $schedule->shareToken,
                'url' => $this->shareUrl($schedule->shareToken),
            ],
        ]);
    }

    /** Mint the link — a deliberate press, on the admin's side as on the farmer's. */
    public function shareCreate(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        if (empty($schedule->shareToken)) {
            $schedule->shareToken = \Illuminate\Support\Str::random(32);
            $schedule->save();
        }

        return $this->jsonOk('Share link created.', [
            'data' => [
                'title' => $schedule->title,
                'token' => $schedule->shareToken,
                'url' => $this->shareUrl($schedule->shareToken),
            ],
        ]);
    }

    /** Where the farmer app serves a shared plan from. */
    private function shareUrl(?string $token): ?string
    {
        if (! $token) {
            return null;
        }

        return rtrim((string) config('anisystem.url'), '/') . '/s/' . $token;
    }

    /* ------------------------------------------------------------------ *
     * Below: the same rules the farmer app's board follows, in PHP.
     * ------------------------------------------------------------------ */

    /**
     * Day 0 and transplant dates per lot: the lot's own dates first, then the
     * earliest activity that claims to be one, exactly as the board does when
     * it renumbers the cards.
     *
     * @return array{zero: array<int,string>, transplant: array<int,string>}
     */
    private function lotAnchors($schedule): array
    {
        $zero = [];
        $transplant = [];

        foreach ($schedule->lots as $lot) {
            if ($lot->dayZeroDate) {
                $zero[$lot->id] = $lot->dayZeroDate->toDateString();
            }
            if ($lot->transplantDate) {
                $transplant[$lot->id] = $lot->transplantDate->toDateString();
            }
        }

        foreach ($schedule->activities()->with('lots')->get() as $activity) {
            if (! $activity->targetDate || (! $activity->isDayZero && ! $activity->isTransplant)) {
                continue;
            }
            $date = Carbon::parse($activity->targetDate)->toDateString();
            foreach ($activity->lots as $lot) {
                if ($activity->isDayZero && (! isset($zero[$lot->id]) || $date < $zero[$lot->id])) {
                    $zero[$lot->id] = $date;
                }
                if ($activity->isTransplant && (! isset($transplant[$lot->id]) || $date < $transplant[$lot->id])) {
                    $transplant[$lot->id] = $date;
                }
            }
        }

        return ['zero' => $zero, 'transplant' => $transplant];
    }

    /**
     * How old a lot is on a date, and in which counter.
     *
     * Only a sown-then-transplanted lot flips to a fresh DAT count. A direct
     * seeded lot keeps one count all season even where a transplant activity
     * exists, and a planted lot never had one to flip.
     */
    private function lotDayNumberOn($lot, array $anchors, Carbon $on): ?array
    {
        $dayType = strtoupper((string) ($lot->dayType ?: 'DAT'));
        $dayType = in_array($dayType, ['DAP', 'DAS'], true) ? $dayType : 'DAT';

        if ($dayType === 'DAT' && isset($anchors['transplant'][$lot->id])) {
            $t = Carbon::parse($anchors['transplant'][$lot->id])->startOfDay();
            if ($on->gte($t)) {
                return ['day' => $t->diffInDays($on), 'counter' => 'DAT', 'anchor' => $t->toDateString()];
            }
        }

        if (! isset($anchors['zero'][$lot->id])) {
            return null;
        }
        $a = Carbon::parse($anchors['zero'][$lot->id])->startOfDay();
        if ($on->lt($a)) {
            return null;   // before day zero there is no plant yet
        }

        return [
            'day' => $a->diffInDays($on),
            'counter' => $dayType === 'DAP' ? 'DAP' : 'DAS',
            'anchor' => $a->toDateString(),
        ];
    }

    /**
     * The stage a crop is in on a given day of its own count. Rice read in
     * DAS was direct seeded: it passes the same stages on later days of its
     * own calendar, because DAT starts about three weeks into the plant's
     * life. Every other crop has one table.
     */
    private function stageOf(array $table, int $day, string $counter): ?array
    {
        $stages = (strtoupper($counter) !== 'DAT' && ! empty($table['stagesDirect']))
            ? $table['stagesDirect']
            : $table['stages'];

        $at = -1;
        foreach ($stages as $i => $st) {
            if ($day >= $st[0]) {
                $at = $i;
            }
        }
        if ($at < 0) {
            return null;
        }

        $cur = $stages[$at];
        $next = $stages[$at + 1] ?? null;
        $length = $next ? $next[0] - $cur[0] : null;
        $inStage = $day - $cur[0];

        return [
            'index' => $at,
            'label' => $cur[1],
            'what' => $cur[2],
            'needs' => $cur[3],
            'dayInStage' => $inStage,
            'lengthDays' => $length,
            'progress' => $length ? min(1, max(0, $inStage / $length)) : null,
            'next' => $next ? ['label' => $next[1], 'inDays' => $next[0] - $day] : null,
            'counter' => $table['counter'],
            'icon' => $table['icon'],
            'cropLabel' => $table['label'],
            'steps' => array_map(fn ($st) => ['from' => $st[0], 'label' => $st[1]], $stages),
        ];
    }
}
