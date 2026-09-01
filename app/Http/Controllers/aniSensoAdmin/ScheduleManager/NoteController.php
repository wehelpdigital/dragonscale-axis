<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Support\AnisystemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The client's Notes module, from this side.
 *
 * Notes are words, and in the farmer app they sit on three shelves that have
 * the same job and no key between them: a note of its own, a note pinned to a
 * day in the plan, and a note written inline against a lot or an activity.
 * The Member Media screens already read all three; this reads the same rows
 * for one season and can write them back, because the mother app is the admin
 * for the client app and a note the admin cannot fix is a note nobody can.
 *
 * Pictures attached to a note are shown but not added here — they belong to
 * the phone that took them.
 */
class NoteController extends BaseScheduleController
{
    /**
     * Which column holds what, per shelf. `title => null` is a shelf whose
     * notes have no title at all: a day note IS its words.
     */
    private const SHELVES = [
        'note' => [
            'table' => 'as_schedule_notes',
            'title' => 'title',
            'body' => 'body',
            'label' => 'Note',
        ],
        'date' => [
            'table' => 'as_schedule_date_notes',
            'title' => null,
            'body' => 'noteContent',
            'label' => 'Day note',
        ],
        'inline' => [
            'table' => 'as_inline_notes',
            'title' => 'title',
            'body' => 'content',
            'label' => 'Inline note',
        ],
    ];

    private function shelf(Request $request, string $key = 'shelf'): array
    {
        $shelf = (string) $request->query($key, $request->input($key, ''));
        if (! isset(self::SHELVES[$shelf])) {
            abort(response()->json(['success' => false, 'message' => 'Unknown note shelf.'], 422));
        }

        return self::SHELVES[$shelf] + ['key' => $shelf];
    }

    /** Attachments are stored as a JSON list of {type, path}. */
    private function mediaOf($raw): array
    {
        $list = is_array($raw) ? $raw : json_decode((string) $raw, true);

        return is_array($list) ? array_values(array_filter($list, 'is_array')) : [];
    }

    /** Every note on this season, newest first. */
    public function data(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $search = mb_strtolower(trim((string) $request->query('search', '')));
        $rows = [];

        // Which saved map belongs to which note, read once. A map chip should
        // open the map, and the map is a save — the picture on the note is
        // only its likeness.
        $mapSaves = DB::table('as_schedule_map_saves')
            ->where('scheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->whereNotNull('noteId')
            ->pluck('id', 'noteId')
            ->all();

        try {
            foreach (self::SHELVES as $key => $spec) {
                $notes = DB::table($spec['table'])
                    ->where('deleteStatus', 1)
                    ->where('croppingScheduleId', $schedule->id)
                    ->orderByDesc('id')
                    ->limit(500)
                    ->get();

                foreach ($notes as $n) {
                    $media = $this->mediaOf($n->media ?? null);
                    // The notebook keeps one picture in a column of its own,
                    // from before a note could hold several. It is an
                    // attachment like any other and the card should say so.
                    if (filled($n->imagePath ?? null)) {
                        $media[] = ['type' => 'image', 'path' => (string) $n->imagePath];
                    }
                    $body = trim(strip_tags((string) ($n->{$spec['body']} ?? '')));
                    $rows[] = [
                        'shelf' => $key,
                        'shelfLabel' => $spec['label'],
                        'id' => (int) $n->id,
                        'hasTitle' => $spec['title'] !== null,
                        'title' => $spec['title'] ? trim((string) ($n->{$spec['title']} ?? '')) : '',
                        'words' => mb_substr($body, 0, 220),
                        'attachments' => count($media),
                        // The attachments themselves, not a tally of them: a
                        // chip has to be pressable, and pressing one has to
                        // know which entry on which note it is opening.
                        'atts' => self::attsOf($media, $key, (int) $n->id, $mapSaves),
                        'noteDate' => isset($n->noteDate) ? (string) $n->noteDate : null,
                        'when' => (string) ($n->updated_at ?? $n->created_at ?? ''),
                        'sortKey' => isset($n->updated_at) ? strtotime((string) $n->updated_at) : 0,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error('Schedule notes list failed: ' . $e->getMessage());

            return $this->jsonFail('Could not load the notes.', 500);
        }

        if ($search !== '') {
            $rows = array_values(array_filter(
                $rows,
                fn ($r) => str_contains(mb_strtolower($r['title'] . ' ' . $r['words']), $search)
            ));
        }

        usort($rows, fn ($a, $b) => $b['sortKey'] <=> $a['sortKey']);

        return $this->jsonOk('OK', ['data' => $rows]);
    }

    /**
     * What is attached to a note, counted by kind.
     *
     * The same reading the farmer app does, including how it recognises the
     * things that predate their own labels: a map saved before there was a
     * 'map' type is known by the filename the map save writes, and a team
     * whiteboard by the board's. Without those, half a season's maps would
     * be listed as loose photographs.
     *
     * @return array<string, int> in the order a note should read them
     */
    /**
     * A note's attachments, in a form a chip can be built from and pressed.
     *
     * `index` is the position in the note's own media list, which is how a
     * drawing is addressed for editing — there is no id on one. `saveId` is
     * the map filed with this note, when there is one: without it a map chip
     * could only show a picture of the ground, which is not the thing.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function attsOf(array $media, string $shelf, int $noteId, array $mapSaves): array
    {
        $out = [];

        foreach ($media as $i => $m) {
            $kind = self::kindOf($m);
            $url = AnisystemMedia::url((string) ($m['path'] ?? ''));
            if ($url === null && $kind !== 'map') {
                continue;
            }

            $out[] = [
                'type' => $kind,
                'index' => (int) $i,
                'shelf' => $shelf,
                'noteId' => $noteId,
                'url' => $url,
                'name' => (string) ($m['title'] ?? AnisystemMedia::basename((string) ($m['path'] ?? ''))),
                'saveId' => $kind === 'map' ? (int) ($mapSaves[$noteId] ?? 0) : 0,
            ];
        }

        return $out;
    }

    /**
     * What one attachment is.
     *
     * The type it was filed under, or — for the ones that predate their own
     * labels — the filename. A map save and the team board each write a name
     * with their mark in it, and reading that is the difference between a
     * season's maps being findable and being listed as photographs.
     */
    private static function kindOf(array $m): string
    {
        $path = (string) ($m['path'] ?? '');
        $type = (string) ($m['type'] ?? '');

        if ($type === 'map' || preg_match('~/map-[A-Za-z0-9]+\.png$~', $path)) {
            return 'map';
        }
        if ($type === 'drawing' || preg_match('~/board-[A-Za-z0-9]+\.png$~', $path)) {
            return 'drawing';
        }

        return $type === 'video' ? 'video' : 'image';
    }

    /** One note, with its words as written and everything attached to it. */
    public function show(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $spec = $this->shelf($request);

        $note = DB::table($spec['table'])
            ->where('id', $this->queryId($request))
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->first();

        if (! $note) {
            return $this->jsonFail('That note is gone.', 404);
        }

        return $this->jsonOk('OK', ['data' => [
            'shelf' => $spec['key'],
            'shelfLabel' => $spec['label'],
            'id' => (int) $note->id,
            'hasTitle' => $spec['title'] !== null,
            'title' => $spec['title'] ? (string) ($note->{$spec['title']} ?? '') : '',
            'body' => (string) ($note->{$spec['body']} ?? ''),
            'noteDate' => isset($note->noteDate) ? (string) $note->noteDate : null,
            'media' => collect($this->mediaOf($note->media ?? null))->map(fn ($m) => [
                // The same reading the shelf does, so a note does not call
                // something a photograph that its own card called a map.
                'type' => self::kindOf($m),
                'url' => AnisystemMedia::url((string) ($m['path'] ?? '')),
                'name' => AnisystemMedia::basename((string) ($m['path'] ?? '')),
            ])->values(),
        ]]);
    }

    /**
     * A new note goes on the client's own shelf — the one their Notes module
     * lists. The other two are written by the screens they belong to (a day
     * in the plan, a lot) and would be orphans if made from here.
     */
    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $body = trim((string) $request->input('body', ''));
        $title = trim((string) $request->input('title', ''));
        if ($body === '' && $title === '') {
            return $this->jsonFail('A note needs a title or some words.', 422);
        }

        $id = DB::table('as_schedule_notes')->insertGetId([
            'croppingScheduleId' => $schedule->id,
            // Whose note it is: the client who owns the season. Null for an
            // admin-owned schedule, which is what that column already holds
            // for everything else on it.
            'userId' => $schedule->anisystemUserId,
            'title' => mb_substr($title, 0, 191),
            'body' => $body,
            'sortOrder' => 0,
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->jsonOk('Note added.', ['id' => $id]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $spec = $this->shelf($request);

        $payload = [
            $spec['body'] => (string) $request->input('body', ''),
            'updated_at' => now(),
        ];
        if ($spec['title'] !== null) {
            $payload[$spec['title']] = mb_substr(trim((string) $request->input('title', '')), 0, 191);
        }

        $hit = DB::table($spec['table'])
            ->where('id', $this->queryId($request))
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->update($payload);

        return $hit
            ? $this->jsonOk('Note saved.')
            : $this->jsonFail('That note is gone.', 404);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $spec = $this->shelf($request);

        $hit = DB::table($spec['table'])
            ->where('id', $this->queryId($request))
            ->where('croppingScheduleId', $schedule->id)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $hit
            ? $this->jsonOk('Note removed.')
            : $this->jsonFail('That note is gone.', 404);
    }
}
