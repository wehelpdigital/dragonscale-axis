<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Support\AnisystemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Opening a client's drawing, and putting the changed one back.
 *
 * A drawing is not a table. It is one entry inside the media list of the note
 * that holds it, and notes live on three shelves — the notebook, a day's own
 * note, and the board's stickies — so a drawing is addressed by shelf, note
 * and position, never by an id of its own.
 *
 * Every drawing the farmer app saves as editable carries its STROKES beside
 * the picture: the shapes it was built from. That is what makes reopening one
 * real editing rather than tracing over a flat PNG, and it is why this hands
 * the strokes over rather than only a URL.
 *
 * The file goes into this app's own storage. That is not a quirk of the
 * console — it is where the client app already puts its pictures, because its
 * container loses its disk on every deploy. The `mm:` prefix is what tells
 * both apps whose /storage a path belongs to.
 */
class DrawController extends BaseScheduleController
{
    /** The three shelves a note, and therefore a drawing, can sit on. */
    private const SHELVES = [
        'note' => ['table' => 'as_schedule_notes', 'title' => 'title', 'body' => 'body'],
        'inline' => ['table' => 'as_inline_notes', 'title' => 'title', 'body' => 'content'],
        'date' => ['table' => 'as_schedule_date_notes', 'title' => null, 'body' => 'noteContent'],
    ];

    /** A drawing this size is a mistake somewhere, not a drawing. */
    private const MAX_BYTES = 12 * 1024 * 1024;

    /**
     * One drawing, with what it was built from.
     */
    public function one(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        [$spec, $note] = $this->holder($request, $schedule->id);
        if (! $note) {
            return $this->jsonFail('That drawing is no longer here.', 404);
        }

        $media = $this->mediaOf($note->media ?? null);
        $i = (int) $request->query('index');
        if (! isset($media[$i])) {
            return $this->jsonFail('That drawing is no longer here.', 404);
        }

        return $this->jsonOk('OK', ['data' => [
            'title' => (string) ($spec['title'] ? ($note->{$spec['title']} ?? '') : ''),
            'note' => trim(strip_tags((string) ($note->{$spec['body']} ?? ''))),
            // Absent when the drawing was filed as a flat picture. The pad
            // opens it as a backdrop then, which is the honest thing to do
            // with something that has no shapes to give back.
            'strokes' => $media[$i]['strokes'] ?? null,
            'editable' => isset($media[$i]['strokes']),
            // Through this app, not straight at wherever the file lives. See
            // picture() — a canvas cannot export what it drew from another
            // origin, so a drawing opened by its own address either refuses
            // to load or loads and cannot be saved.
            'url' => route('anisenso-schedule-manager.records.drawing.image', [
                'scheduleId' => $schedule->id,
                'shelf' => (string) ($request->input('shelf') ?: $request->query('shelf')),
                'noteId' => $note->id,
                'index' => $i,
            ]),
        ]]);
    }

    /**
     * The drawing's own bytes, served from here.
     *
     * A canvas that has drawn an image from another origin is tainted and
     * cannot be exported at all — so the pad asks for these with
     * crossOrigin="anonymous", and a host that answers without the header
     * fails the load outright. That is what a blank pad over an existing
     * drawing means.
     *
     * Both apps' files pass through: one is on this disk, the other is on the
     * client app's, and from the browser's side they are now the same origin
     * either way.
     */
    public function picture(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        [$spec, $note] = $this->holder($request, $schedule->id);
        if (! $note) {
            abort(404);
        }

        $media = $this->mediaOf($note->media ?? null);
        $i = (int) $request->query('index');
        $path = $media[$i]['path'] ?? null;
        if (! is_string($path) || $path === '') {
            abort(404);
        }

        // Ours: read it off the disk rather than asking the web for it.
        if (str_starts_with($path, AnisystemMedia::REMOTE_PREFIX)) {
            $kept = ltrim(substr($path, strlen(AnisystemMedia::REMOTE_PREFIX)), '/');
            if (! Storage::disk('public')->exists($kept)) {
                abort(404);
            }

            return response(Storage::disk('public')->get($kept), 200, [
                'Content-Type' => Storage::disk('public')->mimeType($kept) ?: 'image/png',
                'Cache-Control' => 'private, max-age=300',
            ]);
        }

        // Theirs. Fetched once and passed straight through.
        $url = AnisystemMedia::url($path);
        if (! $url) {
            abort(404);
        }

        try {
            $res = Http::timeout(20)->get($url);
            if (! $res->ok() || ! str_starts_with((string) $res->header('Content-Type'), 'image/')) {
                abort(404);
            }

            return response($res->body(), 200, [
                'Content-Type' => $res->header('Content-Type'),
                'Cache-Control' => 'private, max-age=300',
            ]);
        } catch (\Throwable $e) {
            abort(404);
        }
    }

    /**
     * Put the changed drawing back where it came from.
     *
     * Writes over the entry that was opened — same note, same position — so
     * the drawing the client knows keeps being that drawing rather than
     * becoming a second one beside it. The superseded file is deleted after
     * the note has been written, because nothing else points at it and a
     * season of past versions is dead weight on a disk.
     *
     * Only a notebook note takes words from here. A day's note has no title
     * at all, and a board sticky's words belong to the board's own editor.
     */
    public function save(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        [$spec, $note] = $this->holder($request, $schedule->id);
        if (! $note) {
            return $this->jsonFail('That drawing is no longer here.', 404);
        }

        $media = $this->mediaOf($note->media ?? null);
        $i = (int) $request->input('index');
        if (! isset($media[$i])) {
            return $this->jsonFail('That drawing is no longer here.', 404);
        }

        $binary = $this->pngFrom((string) $request->input('image', ''));
        if ($binary === null) {
            return $this->jsonFail('That did not arrive as a picture.', 422);
        }
        if (strlen($binary) > self::MAX_BYTES) {
            return $this->jsonFail('That drawing is too large to keep.', 413);
        }

        $strokes = $request->input('strokes');
        if (is_string($strokes)) {
            $strokes = json_decode($strokes, true);
        }

        // The same shape and the same place the client app writes: namespaced
        // by season, so one farm's files stay together.
        $path = 'anisystem/drawings/' . $schedule->id . '/' . Str::random(24) . '.png';
        Storage::disk('public')->put($path, $binary);

        $was = $media[$i]['path'] ?? null;
        $media[$i] = array_filter([
            'type' => is_array($strokes) ? 'drawing' : 'image',
            'path' => AnisystemMedia::REMOTE_PREFIX . $path,
            'strokes' => is_array($strokes) ? $strokes : null,
        ], fn ($v) => $v !== null);

        $patch = ['media' => json_encode(array_values($media)), 'updated_at' => now()];
        if ($spec['title'] !== null && $request->filled('title')) {
            $patch[$spec['title']] = mb_substr(trim((string) $request->input('title')), 0, 191);
        }
        if ($spec['title'] !== null && $request->has('note')) {
            $said = trim((string) $request->input('note'));
            $patch[$spec['body']] = $said === '' ? null : '<p>' . str_replace("\n", '<br>', e($said)) . '</p>';
        }

        DB::table($spec['table'])->where('id', $note->id)->update($patch);

        // Only now, and only ours. A path with no `mm:` on it lives on the
        // client app's own disk and is not this app's to delete.
        if (is_string($was) && $was !== '' && str_starts_with($was, AnisystemMedia::REMOTE_PREFIX)
            && $was !== AnisystemMedia::REMOTE_PREFIX . $path) {
            Storage::disk('public')->delete(ltrim(substr($was, strlen(AnisystemMedia::REMOTE_PREFIX)), '/'));
        }

        return $this->jsonOk('Drawing saved.', ['data' => [
            'url' => AnisystemMedia::url(AnisystemMedia::REMOTE_PREFIX . $path),
            'editable' => is_array($strokes),
        ]]);
    }

    /**
     * The note a drawing hangs on, and how that shelf names its columns.
     *
     * @return array{0: array, 1: ?object}
     */
    private function holder(Request $request, int $scheduleId): array
    {
        $shelf = (string) ($request->input('shelf') ?: $request->query('shelf'));
        if (! isset(self::SHELVES[$shelf])) {
            return [[], null];
        }
        $spec = self::SHELVES[$shelf];

        $note = DB::table($spec['table'])
            ->where('id', (int) ($request->input('noteId') ?: $request->query('noteId')))
            ->where('croppingScheduleId', $scheduleId)
            ->where('deleteStatus', 1)
            ->first();

        return [$spec, $note];
    }

    /** A note's media list, whatever the column happens to hold. */
    private function mediaOf($raw): array
    {
        $list = is_array($raw) ? $raw : json_decode((string) $raw, true);

        return is_array($list) ? array_values(array_filter($list, 'is_array')) : [];
    }

    /** The bytes behind a data URL, or null if it is not a PNG one. */
    private function pngFrom(string $dataUrl): ?string
    {
        if (! preg_match('~^data:image/png;base64,~i', $dataUrl)) {
            return null;
        }

        $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);

        return ($binary === false || $binary === '') ? null : $binary;
    }
}
