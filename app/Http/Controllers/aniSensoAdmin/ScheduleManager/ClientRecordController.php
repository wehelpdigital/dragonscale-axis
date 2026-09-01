<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Support\AneeEmoji;
use App\Support\AnisystemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The season's own records, for the three modules that are mostly a shelf:
 * the maps that were drawn on, the drawings that were made, and the threads
 * the client had with the technician.
 *
 * These are read live from the farmer app's tables, as everything on this
 * page is. What can be written is what is genuinely the admin's to write: a
 * title, and a removal. A drawing has no title of its own — it is an entry
 * inside the media list of the note that holds it, and the note's title is
 * the drawing's — so renaming one happens in Notes, and this says so instead
 * of offering a box that writes nowhere.
 */
class ClientRecordController extends BaseScheduleController
{
    // ---------------------------------------------------------------- maps --

    public function maps(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $rows = DB::table('as_schedule_map_saves')
            ->where('scheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->orderByDesc('updated_at')
            ->limit(300)
            ->get()
            ->map(function ($m) {
                $objects = json_decode((string) $m->objects, true);
                $objects = is_array($objects) ? $objects : [];
                $kinds = [];
                foreach ($objects as $o) {
                    $k = (string) ($o['kind'] ?? $o['type'] ?? 'shape');
                    $kinds[$k] = ($kinds[$k] ?? 0) + 1;
                }

                return [
                    'id' => (int) $m->id,
                    'title' => (string) ($m->title ?: 'Untitled map'),
                    'source' => (string) ($m->source ?? ''),
                    'noteId' => $m->noteId ? (int) $m->noteId : 0,
                    'shapes' => count($objects),
                    'kinds' => $kinds,
                    'labels' => collect($objects)->pluck('label')->filter()->take(24)->values(),
                    'when' => (string) ($m->updated_at ?? ''),
                ];
            })->values();

        return $this->jsonOk('OK', ['data' => $rows]);
    }

    public function mapRename(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            return $this->jsonFail('A map needs a name.', 422);
        }

        $hit = DB::table('as_schedule_map_saves')
            ->where('id', $this->queryId($request))
            ->where('scheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->update(['title' => mb_substr($title, 0, 191), 'updated_at' => now()]);

        return $hit ? $this->jsonOk('Map renamed.') : $this->jsonFail('That map is gone.', 404);
    }

    public function mapDestroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $hit = DB::table('as_schedule_map_saves')
            ->where('id', $this->queryId($request))
            ->where('scheduleId', $schedule->id)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $hit ? $this->jsonOk('Map removed.') : $this->jsonFail('That map is gone.', 404);
    }

    // ------------------------------------------------------------ drawings --

    /** Which tables hold notes, and what the columns are called on each. */
    private const NOTE_SHELVES = [
        'note' => ['table' => 'as_schedule_notes', 'title' => 'title'],
        'inline' => ['table' => 'as_inline_notes', 'title' => 'title'],
        'date' => ['table' => 'as_schedule_date_notes', 'title' => null],
    ];

    private function mediaOf($raw): array
    {
        $list = is_array($raw) ? $raw : json_decode((string) $raw, true);

        return is_array($list) ? $list : [];
    }

    /**
     * Every drawing on the season.
     *
     * Assembled rather than queried: a drawing is one entry inside a note's
     * JSON media list, so there is nothing to join against and no way to ask
     * the database to count them.
     */
    public function drawings(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $rows = [];

        foreach (self::NOTE_SHELVES as $key => $spec) {
            $notes = DB::table($spec['table'])
                ->where('deleteStatus', 1)
                ->where('croppingScheduleId', $schedule->id)
                ->whereNotNull('media')
                ->where('media', '!=', '')
                ->orderByDesc('id')
                ->limit(300)
                ->get();

            foreach ($notes as $note) {
                foreach ($this->mediaOf($note->media) as $i => $m) {
                    $path = (string) ($m['path'] ?? '');
                    $isTeam = (bool) preg_match('~/board-[A-Za-z0-9]+\.png$~', $path);
                    if ($path === '' || (($m['type'] ?? '') !== 'drawing' && ! $isTeam)) {
                        continue;
                    }
                    $rows[] = [
                        'shelf' => $key,
                        'noteId' => (int) $note->id,
                        'index' => (int) $i,
                        'noteTitle' => $spec['title'] ? trim((string) ($note->{$spec['title']} ?? '')) : '',
                        'team' => $isTeam,
                        'url' => AnisystemMedia::url($path),
                        'when' => (string) ($note->updated_at ?? ''),
                        'sortKey' => isset($note->updated_at) ? strtotime((string) $note->updated_at) : 0,
                    ];
                }
            }
        }

        usort($rows, fn ($a, $b) => $b['sortKey'] <=> $a['sortKey']);

        return $this->jsonOk('OK', ['data' => array_values($rows)]);
    }

    /**
     * Take one drawing out of the note that holds it.
     *
     * The entry leaves the media list; the note stays. A note is words with
     * pictures in it, and the words are not anybody's to delete because one
     * of the pictures was.
     */
    public function drawingDestroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $shelf = (string) $request->query('shelf', '');
        if (! isset(self::NOTE_SHELVES[$shelf])) {
            return $this->jsonFail('Unknown note shelf.', 422);
        }
        $table = self::NOTE_SHELVES[$shelf]['table'];
        $index = (int) $request->query('index', -1);

        $note = DB::table($table)
            ->where('id', (int) $request->query('noteId'))
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->first();
        if (! $note) {
            return $this->jsonFail('That note is gone.', 404);
        }

        $media = $this->mediaOf($note->media);
        if (! isset($media[$index])) {
            return $this->jsonFail('That drawing is gone already.', 404);
        }
        unset($media[$index]);

        DB::table($table)->where('id', $note->id)->update([
            'media' => json_encode(array_values($media)),
            'updated_at' => now(),
        ]);

        return $this->jsonOk('Drawing removed.');
    }

    // ------------------------------------------------------------------ ai --

    /**
     * The threads on this season: the client's own with the technician, and
     * the team sessions inside the Collab Room. One list, because to an admin
     * reading a season they are the same thing.
     */
    public function ai(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $personal = DB::table('anisystem_ai_conversations as c')
            ->leftJoin('anisystem_users as u', 'u.id', '=', 'c.userId')
            ->where('c.deleteStatus', 1)
            ->where('c.croppingScheduleId', $schedule->id)
            ->selectRaw("'personal' as kind, c.id as id, c.title as title,
                TRIM(CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,''))) as who,
                (SELECT COUNT(*) FROM anisystem_ai_messages m WHERE m.conversationId = c.id AND m.deleteStatus = 1) as messageCount,
                COALESCE((SELECT MAX(m.created_at) FROM anisystem_ai_messages m WHERE m.conversationId = c.id AND m.deleteStatus = 1), c.updated_at) as lastAt");

        $team = DB::table('as_schedule_ai_sessions as t')
            ->leftJoin('anisystem_users as u', 'u.id', '=', 't.startedByUserId')
            ->where('t.deleteStatus', 1)
            ->where('t.scheduleId', $schedule->id)
            ->selectRaw("'team' as kind, t.id as id, t.title as title,
                TRIM(CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,''))) as who,
                (SELECT COUNT(*) FROM as_schedule_ai_messages m WHERE m.sessionId = t.id AND m.deleteStatus = 1) as messageCount,
                COALESCE(t.lastMessageAt, t.updated_at) as lastAt");

        $rows = DB::query()->fromSub($personal->unionAll($team), 'x')
            ->orderByDesc('x.lastAt')
            ->limit(300)
            ->get()
            ->map(fn ($r) => [
                'kind' => (string) $r->kind,
                'id' => (int) $r->id,
                'title' => (string) ($r->title ?: 'Untitled thread'),
                'who' => trim((string) $r->who) ?: null,
                'messageCount' => (int) $r->messageCount,
                'when' => (string) ($r->lastAt ?? ''),
            ])->values();

        return $this->jsonOk('OK', ['data' => $rows]);
    }

    /** One thread, turn by turn. */
    public function aiShow(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $kind = (string) $request->query('kind');
        $id = $this->queryId($request);

        if ($kind === 'team') {
            $head = DB::table('as_schedule_ai_sessions')
                ->where('id', $id)->where('scheduleId', $schedule->id)->where('deleteStatus', 1)->first();
            $turns = $head ? DB::table('as_schedule_ai_messages')
                ->where('sessionId', $id)->where('deleteStatus', 1)
                ->orderBy('id')->limit(400)->get(['role', 'content', 'created_at']) : collect();
        } elseif ($kind === 'personal') {
            $head = DB::table('anisystem_ai_conversations')
                ->where('id', $id)->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->first();
            $turns = $head ? DB::table('anisystem_ai_messages')
                ->where('conversationId', $id)->where('deleteStatus', 1)
                ->orderBy('id')->limit(400)->get(['role', 'content', 'created_at']) : collect();
        } else {
            return $this->jsonFail('Unknown thread kind.', 422);
        }

        if (! $head) {
            return $this->jsonFail('That thread is gone.', 404);
        }

        return $this->jsonOk('OK', ['data' => [
            'title' => (string) ($head->title ?: 'Untitled thread'),
            'turns' => $turns->map(fn ($t) => [
                'role' => (string) $t->role,
                'body' => (string) $t->content,
                // What the client actually saw. Anee writes her faces as
                // `:anee-thinking:` and the farmer app swaps each one for a
                // drawing; reading the row raw shows the shortcode sitting in
                // the sentence instead. Escaped here, not in the browser, so
                // the page can insert it as markup safely.
                'bodyHtml' => AneeEmoji::body((string) $t->content),
                'when' => (string) $t->created_at,
            ])->values(),
        ]]);
    }

    public function aiRename(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            return $this->jsonFail('A thread needs a name.', 422);
        }

        [$table, $key] = $this->aiTable((string) $request->query('kind'));
        if (! $table) {
            return $this->jsonFail('Unknown thread kind.', 422);
        }

        $hit = DB::table($table)
            ->where('id', $this->queryId($request))
            ->where($key, $schedule->id)
            ->where('deleteStatus', 1)
            ->update(['title' => mb_substr($title, 0, 191), 'updated_at' => now()]);

        return $hit ? $this->jsonOk('Thread renamed.') : $this->jsonFail('That thread is gone.', 404);
    }

    public function aiDestroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        [$table, $key] = $this->aiTable((string) $request->query('kind'));
        if (! $table) {
            return $this->jsonFail('Unknown thread kind.', 422);
        }

        $hit = DB::table($table)
            ->where('id', $this->queryId($request))
            ->where($key, $schedule->id)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $hit ? $this->jsonOk('Thread removed.') : $this->jsonFail('That thread is gone.', 404);
    }

    /** @return array{0: ?string, 1: string} the table, and the column that names the season */
    private function aiTable(string $kind): array
    {
        if ($kind === 'team') {
            return ['as_schedule_ai_sessions', 'scheduleId'];
        }
        if ($kind === 'personal') {
            return ['anisystem_ai_conversations', 'croppingScheduleId'];
        }

        return [null, ''];
    }
}
