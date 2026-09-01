<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Models\AsAiSetting;
use App\Services\AniSensoAiClient;
use App\Support\AneeEmoji;
use App\Support\AniSensoTechnician;
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
    /**
     * What stands in front of a line an admin wrote into a client's thread.
     *
     * The conversation has two roles and no third one, so an admin's message
     * has to go in as a user turn. Without a mark the client would open their
     * app and find words in their own voice that they never said. With it,
     * both apps can see whose line it is: this one renders it as a label, and
     * the farmer app shows the mark until it is taught the same trick.
     *
     * Plain text on purpose — it lives in the same column as everything else,
     * and it has to survive being read by anything that reads that column.
     */
    public const ADMIN_MARK = '[technician] ';

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

    /**
     * One map, with everything drawn on it.
     *
     * The list can say a map has nine shapes on it; it cannot say whether the
     * north field is the big one. Handing the objects over lets the console
     * draw them, which is the difference between administering a map and
     * administering a row that mentions one.
     */
    public function mapShow(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $map = DB::table('as_schedule_map_saves')
            ->where('id', $this->queryId($request))
            ->where('scheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->first();

        if (! $map) {
            return $this->jsonFail('That map is gone.', 404);
        }

        return $this->jsonOk('OK', ['data' => [
            'id' => (int) $map->id,
            'title' => (string) ($map->title ?: 'Untitled map'),
            'source' => (string) ($map->source ?? ''),
            'noteId' => $map->noteId ? (int) $map->noteId : 0,
            'description' => self::mapWords($map->noteId),
            'objects' => self::mapObjects($map->objects),
            'when' => (string) ($map->updated_at ?? ''),
        ]]);
    }

    /**
     * Write a map's shapes back.
     *
     * Rebuilt field by field rather than stored as it arrived. The console
     * edits what a shape is CALLED and what colour it is drawn in, and it can
     * take one off the map — it does not move points about, and a season's
     * map is not the place to discover that a request body could. So the
     * geometry is carried across from what is already stored, by position,
     * and only the parts an admin can actually edit are read from the wire.
     */
    public function mapSave(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $map = DB::table('as_schedule_map_saves')
            ->where('id', $id)->where('scheduleId', $schedule->id)->where('deleteStatus', 1)->first();
        if (! $map) {
            return $this->jsonFail('That map is gone.', 404);
        }

        $sent = json_decode((string) $request->input('objects', ''), true);
        if (! is_array($sent)) {
            return $this->jsonFail('That did not arrive as a list of shapes.', 422);
        }

        $existing = self::mapObjects($map->objects);
        $kept = [];

        foreach ($sent as $row) {
            $at = (int) ($row['at'] ?? -1);
            if (! isset($existing[$at])) {
                // A shape the console invented, or one that has moved under
                // it because the map was edited in their app while this was
                // open. Either way it is not ours to write.
                continue;
            }

            $shape = $existing[$at];
            $label = trim((string) ($row['label'] ?? ''));
            $shape['label'] = $label === '' ? null : mb_substr($label, 0, 120);

            $colour = strtolower(trim((string) ($row['color'] ?? '')));
            if (preg_match('/^#[0-9a-f]{6}$/', $colour)) {
                $shape['color'] = $colour;
            }

            $kept[] = $shape;
        }

        DB::table('as_schedule_map_saves')->where('id', $id)->update([
            'objects' => json_encode(array_values($kept)),
            'updated_at' => now(),
        ]);

        return $this->jsonOk('Map saved.', ['data' => ['objects' => $kept]]);
    }

    /**
     * The shapes on a map, as a list, whatever the column happens to hold.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function mapObjects($raw): array
    {
        $objects = json_decode((string) $raw, true);

        return is_array($objects) ? array_values(array_filter($objects, 'is_array')) : [];
    }

    /**
     * What a saved map is called, and what it is for.
     *
     * The name is the save's own; the description belongs to the note filed
     * with it, which is where the farmer app puts it and therefore where the
     * client reads it. Both move together, because a map renamed in one place
     * and not the other is two maps as far as anybody looking is concerned.
     */
    public function mapRename(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            return $this->jsonFail('A map needs a name.', 422);
        }
        if (mb_strlen($title) > 180) {
            return $this->jsonFail('That name is too long.', 422);
        }

        $said = trim((string) $request->input('description', ''));
        if (mb_strlen($said) > 2000) {
            return $this->jsonFail('That description is too long.', 422);
        }

        $map = DB::table('as_schedule_map_saves')
            ->where('id', $this->queryId($request))
            ->where('scheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->first();
        if (! $map) {
            return $this->jsonFail('That map is gone.', 404);
        }

        DB::table('as_schedule_map_saves')->where('id', $map->id)->update([
            'title' => mb_substr($title, 0, 180),
            'updated_at' => now(),
        ]);

        // The note carries the same name and holds the words. A map with no
        // note has nowhere to keep a description; the name still lands, and
        // the browser is told so rather than being left to wonder.
        $kept = true;
        if ($map->noteId) {
            $note = DB::table('as_schedule_notes')
                ->where('id', $map->noteId)->where('deleteStatus', 1)->first();
            if ($note) {
                DB::table('as_schedule_notes')->where('id', $note->id)->update([
                    'title' => mb_substr($title, 0, 180),
                    'body' => self::mapNoteBody($said, (string) $note->body),
                    'updated_at' => now(),
                ]);
            } else {
                $kept = false;
            }
        } else {
            $kept = false;
        }

        return $this->jsonOk(
            ($kept || $said === '') ? 'Map saved.' : 'Name saved. This map has no note, so the description had nowhere to go.',
            ['data' => ['title' => $title, 'descriptionKept' => $kept]]
        );
    }

    /**
     * The words on a map's note, without the sentence the app wrote itself.
     *
     * The farmer app ends the body with a line telling the reader how to open
     * the map. Showing that in an edit box invites somebody to delete it, and
     * they did not write it.
     */
    private static function mapWords($noteId): string
    {
        if (! $noteId) {
            return '';
        }

        $body = (string) DB::table('as_schedule_notes')
            ->where('id', $noteId)->where('deleteStatus', 1)->value('body');

        $text = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $body)), ENT_QUOTES));

        return trim((string) preg_replace('/\s*Saved team map[^\n]*$/u', '', $text));
    }

    /**
     * A note body from what somebody typed, keeping the app's own last line.
     */
    private static function mapNoteBody(string $said, string $was): string
    {
        preg_match('/(Saved team map[^\n<]*)/u', strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $was)), $m);
        $tail = trim($m[1] ?? 'Saved team map — tap View map to open it.');

        $text = trim($said === '' ? $tail : $said . "\n\n" . $tail);

        // The newline is REPLACED, not decorated. nl2br inserts a <br> and
        // keeps the newline beside it, and the reader above counts both — so
        // a description written with single line breaks came back with double
        // ones, and gained another pair every time it was saved.
        return '<p>' . str_replace("\n", '<br>', e($text)) . '</p>';
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

    /**
     * Say something into a thread, and let Anee answer it.
     *
     * The thread belongs to the client — this writes into the same rows their
     * app reads, so whatever is said here appears in their app the next time
     * they open it. That is the point of it: an admin reading a season can
     * answer a question in the place the question was asked, instead of
     * somewhere the client will never look.
     *
     * The admin's turn is stored as a user turn, because that is the only
     * role the conversation has for "not the assistant", and Anee has to see
     * it as the thing she is replying to. It is marked so the client can tell
     * it apart from their own words.
     *
     * Nothing here is charged. Credits are the client's, spent when the
     * client asks; an admin answering on their season should not empty a
     * wallet the client did not reach for.
     */
    public function aiReply(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $kind = (string) $request->query('kind');
        $id = $this->queryId($request);

        $said = trim((string) $request->input('body', ''));
        if ($said === '') {
            return $this->jsonFail('Nothing was written.', 422);
        }
        if (mb_strlen($said) > 4000) {
            return $this->jsonFail('That is too long for one message.', 422);
        }

        $settings = AsAiSetting::current();
        if (! $settings->isUsable()) {
            return $this->jsonFail('The AI is switched off, or has no key. Set it up under AniSystem AI.', 422);
        }

        [$head, $messages, $link] = $this->aiThreadFor($kind, $id, $schedule->id);
        if (! $head) {
            return $this->jsonFail('That thread is gone.', 404);
        }

        // What Anee has already said and been told, oldest first. Bounded:
        // a season's thread can run long, and the whole of it is neither
        // affordable nor useful — the recent turns are what a reply is about.
        $history = DB::table($messages)
            ->where($link['col'], $id)->where('deleteStatus', 1)
            ->orderByDesc('id')->limit(30)->get(['role', 'content'])
            ->reverse()
            ->map(fn ($t) => [
                'role' => $t->role === 'assistant' ? 'assistant' : 'user',
                'text' => (string) $t->content,
            ])->values()->all();

        $mine = self::ADMIN_MARK . $said;

        // Ask before writing anything. If Anee cannot be reached the thread
        // is left exactly as it was, rather than holding a question the
        // client will open and find unanswered — and the admin can press send
        // again without wondering whether the first one went in.
        $answer = app(AniSensoAiClient::class)->ask($settings, $history, $mine);

        if (! ($answer['ok'] ?? false)) {
            return $this->jsonFail((string) ($answer['error'] ?: 'Anee could not be reached.'), 502);
        }

        $reply = (string) $answer['text'];
        $now = now();

        DB::transaction(function () use ($messages, $link, $mine, $reply, $now) {
            DB::table($messages)->insert(array_merge($link['stamp'], [
                'role' => 'user',
                'content' => $mine,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
            DB::table($messages)->insert(array_merge($link['stamp'], [
                'role' => 'assistant',
                'content' => $reply,
                'deleteStatus' => 1,
                'created_at' => $now->copy()->addSecond(),
                'updated_at' => $now,
            ]));
        });

        // The thread's own clock, where it keeps one, so the client's list
        // sorts this to the top the way a real reply would.
        if ($link['touch']) {
            DB::table($link['head'])->where('id', $id)->update([$link['touch'] => now(), 'updated_at' => now()]);
        }

        return $this->jsonOk('Sent.', ['data' => [
            'turns' => [
                ['role' => 'user', 'body' => $mine, 'bodyHtml' => AneeEmoji::body($mine)],
                ['role' => 'assistant', 'body' => $reply, 'bodyHtml' => AneeEmoji::body($reply)],
            ],
        ]]);
    }

    /**
     * The head row of a thread, and where its turns live.
     *
     * The two kinds are stored differently — a Collab Room session and a
     * client's own conversation were built at different times — so everything
     * that differs is answered once, here, rather than at each use.
     *
     * @return array{0: ?object, 1: string, 2: array}
     */
    private function aiThreadFor(string $kind, int $id, int $scheduleId): array
    {
        if ($kind === 'team') {
            $head = DB::table('as_schedule_ai_sessions')
                ->where('id', $id)->where('scheduleId', $scheduleId)->where('deleteStatus', 1)->first();

            // A Collab Room message must name a farmer-app user, and an admin
            // is not one — the two apps keep separate people. The console has
            // a person of its own for exactly this, so the client's app shows
            // a name that is true instead of their own over words they never
            // wrote.
            return [$head, 'as_schedule_ai_messages', [
                'head' => 'as_schedule_ai_sessions',
                'col' => 'sessionId',
                'touch' => 'lastMessageAt',
                'stamp' => [
                    'sessionId' => $id,
                    'scheduleId' => $scheduleId,
                    'userId' => AniSensoTechnician::id(),
                ],
            ]];
        }

        if ($kind === 'personal') {
            $head = DB::table('anisystem_ai_conversations')
                ->where('id', $id)->where('croppingScheduleId', $scheduleId)->where('deleteStatus', 1)->first();

            return [$head, 'anisystem_ai_messages', [
                'head' => 'anisystem_ai_conversations',
                'col' => 'conversationId',
                'touch' => null,
                'stamp' => ['conversationId' => $id],
            ]];
        }

        return [null, '', []];
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
