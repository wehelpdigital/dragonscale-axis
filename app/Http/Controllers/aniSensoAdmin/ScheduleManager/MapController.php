<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Models\AsScheduleMapObject;
use App\Support\AniSensoTechnician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Support\AnisystemMedia;

/**
 * The season's map, as something to draw on rather than something to list.
 *
 * A season has two map things and they are not the same. The LIVE CANVAS is
 * `as_schedule_map_objects` — the shapes currently on the map, one row each,
 * which is what the client sees when they open Maps in their app. A SAVE is a
 * row in `as_schedule_map_saves` holding a frozen copy of that canvas under a
 * name. The console could list saves and nothing else, so an admin could read
 * that a map had nine shapes and never see the ground.
 *
 * These are the same doors the farmer app's ScheduleMapController opens, over
 * the same rows, so a shape drawn here is a shape on the client's map. What
 * differs is who is asked at each one: there it is "are you on this team",
 * here it is "is this season yours to administer", which the base controller
 * has already answered by the time any of this runs.
 *
 * Three of that controller's doors are deliberately not here. `trace` relays a
 * half-drawn shape to other people watching live; `loc` shares a member's GPS
 * position with their team. Neither has a meaning for one admin working alone,
 * and nothing in this console broadcasts.
 */
class MapController extends BaseScheduleController
{
    /** Every shape kind the client can draw. */
    private const KINDS = 'pen,line,path,rect,area,text,arrow,pin';

    /** The lettering a text shape may be set in. */
    private const FONTS = 'sans,serif,mono,round,slab';

    /** A canvas holds this many shapes; past it the map stops being a map. */
    private const MAX_SHAPES = 2000;

    public function objects(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $rows = AsScheduleMapObject::active()
            ->where('scheduleId', $schedule->id)
            ->orderBy('id')
            ->limit(self::MAX_SHAPES)
            ->get();

        return $this->jsonOk('OK', ['data' => ['objects' => $rows->map(fn ($o) => $o->shaped())->all()]]);
    }

    public function push(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'kind' => 'required|in:' . self::KINDS,
            'color' => 'nullable|string|max:16',
            // A stroke's thickness and a label's type size share this column,
            // and lettering wants to go bigger than any line would.
            'width' => 'nullable|integer|min:1|max:64',
            'points' => 'required|array|min:1|max:' . self::MAX_SHAPES,
            'points.*' => 'array|size:2',
            'label' => 'nullable|string|max:500',
            'font' => 'nullable|in:' . self::FONTS,
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $object = AsScheduleMapObject::create([
            'scheduleId' => $schedule->id,
            // Signed by the console's own technician, the same as everything
            // else it writes into the farmer app. The column will not take
            // null, and it must not take the client's id for a line the
            // client did not draw.
            'userId' => AniSensoTechnician::id(),
            'kind' => $request->input('kind'),
            'color' => $request->input('color'),
            'width' => (int) $request->input('width', 3),
            'font' => $request->input('font'),
            'points' => json_encode($request->input('points')),
            'label' => $request->input('label'),
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('OK', ['data' => ['object' => $object->shaped()]]);
    }

    /**
     * Move a shape, reshape it, or change what it says.
     *
     * Everything but the id is optional and only what was sent is written —
     * dragging a boundary and renaming a label are the same edit to the same
     * row, and giving each its own door would be three endpoints saying the
     * same thing.
     */
    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'points' => 'sometimes|array|min:1|max:' . self::MAX_SHAPES,
            'points.*' => 'array|size:2',
            'label' => 'sometimes|nullable|string|max:500',
            'font' => 'sometimes|nullable|in:' . self::FONTS,
            'width' => 'sometimes|integer|min:1|max:64',
            'color' => 'sometimes|nullable|string|max:16',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $object = AsScheduleMapObject::active()
            ->where('scheduleId', $schedule->id)
            ->find($request->input('id'));
        if (! $object) {
            return $this->jsonFail('That shape no longer exists.', 404);
        }

        $patch = [];
        if ($request->has('points')) {
            $patch['points'] = json_encode($request->input('points'));
        }
        foreach (['label', 'font', 'color'] as $field) {
            if ($request->has($field)) {
                $patch[$field] = $request->input($field);
            }
        }
        if ($request->has('width')) {
            $patch['width'] = (int) $request->input('width');
        }
        if (empty($patch)) {
            return $this->jsonFail('Nothing to change on that shape.', 422);
        }

        $object->update($patch);

        return $this->jsonOk('OK', ['data' => ['object' => $object->fresh()->shaped()]]);
    }

    public function remove(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $object = AsScheduleMapObject::active()
            ->where('scheduleId', $schedule->id)
            ->find($request->input('id'));
        if (! $object) {
            return $this->jsonFail('That shape is already gone.', 404);
        }

        $object->update(['deleteStatus' => 0]);

        return $this->jsonOk('Removed.');
    }

    /**
     * Wipe the canvas.
     *
     * The rows stay and stop being shown, the way everything is removed here,
     * so a save made earlier still holds what was on it.
     */
    public function clear(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        AsScheduleMapObject::active()->where('scheduleId', $schedule->id)->update(['deleteStatus' => 0]);

        return $this->jsonOk('The map is clear.');
    }

    /** The saved maps, and how much is on the canvas right now. */
    public function saves(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        // A season's maps are linked to it now, and the grower's own maps
        // count too (anee moved Maps to Global and Quick Tools).
        $saves = \App\Support\AnisystemMaps::forSeason($schedule)
            ->orderByDesc('updated_at')
            ->limit(120)
            ->get(['id', 'title', 'source', 'objects', 'noteId', 'updated_at'])
            ->map(function ($row) {
                $objects = json_decode((string) $row->objects, true);
                $objects = is_array($objects) ? $objects : [];

                return [
                    'id' => (int) $row->id,
                    'title' => (string) ($row->title ?: 'Untitled map'),
                    'source' => (string) ($row->source ?? ''),
                    'shapes' => count($objects),
                    'when' => (string) ($row->updated_at ?? ''),
                ];
            })->values();

        return $this->jsonOk('OK', ['data' => [
            'saves' => $saves,
            'liveCount' => AsScheduleMapObject::active()->where('scheduleId', $schedule->id)->count(),
        ]]);
    }

    /**
     * File what is on the canvas, the three ways anee offers (2026-09-30):
     *
     *   plain  just the map, named, reopenable from Maps. Nothing in Notes.
     *   map    the map, and its picture filed as a note in the grower's own
     *          notes (their Global Notes), tied to the map by noteId.
     *   image  only the picture, as a note in this season's notebook.
     *
     * The picture is the one the browser composed (imagery, shapes and
     * measurements); it is kept on this app's public disk under the "mm:"
     * prefix anee reads its files by.
     */
    public function saveMap(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'mode' => 'nullable|in:plain,map,image',
            'saveId' => 'nullable|integer',
            'title' => 'nullable|string|max:180',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $objects = AsScheduleMapObject::active()
            ->where('scheduleId', $schedule->id)
            ->orderBy('id')
            ->limit(self::MAX_SHAPES)
            ->get()
            ->map(fn ($o) => $o->shaped())
            ->all();

        if (empty($objects)) {
            return $this->jsonFail('There is nothing on the map to save yet.', 422);
        }

        $title = trim((string) $request->input('title')) ?: 'Map';
        $mode = (string) ($request->input('mode') ?: 'plain');
        // The autosave writes the shapes back into the open map and files
        // nothing: a note per autosave would bury the notebook.
        if ($request->boolean('quiet')) {
            $mode = 'plain';
        }
        $owner = (int) ($schedule->anisystemUserId ?? 0);

        // The picture, when this save files one.
        $picture = null;
        if ($mode !== 'plain') {
            $binary = $this->pngFrom((string) $request->input('image', ''));
            if ($binary !== null && strlen($binary) <= 12_000_000) {
                $picture = 'anisystem/maps/' . $schedule->id . '/' . ($mode === 'map' ? 'map-' : 'mapimg-') . Str::random(24) . '.png';
                Storage::disk('public')->put($picture, $binary);
            }
            if ($mode === 'image' && $picture === null) {
                return $this->jsonFail('The picture of the map did not arrive, so there is nothing to file. Try again.', 422);
            }
        }
        $description = trim((string) $request->input('description'));

        // "Save as image note": a picture in the season's notebook, no map file.
        if ($mode === 'image') {
            $noteId = $this->fileNote($schedule->id, $owner ?: null, $title, $description !== '' ? $description : null, 'image', $picture);

            return $this->jsonOk('Saved to the season\'s notebook as an image note.', ['data' => ['noteId' => $noteId]]);
        }

        $payload = [
            'title' => mb_substr($title, 0, 180),
            'objects' => json_encode($objects),
            'updated_at' => now(),
        ];

        $existing = $request->filled('saveId')
            ? \App\Support\AnisystemMaps::forSeason($schedule)
                ->where('id', (int) $request->input('saveId'))
                ->first()
            : null;

        if ($existing) {
            DB::table('as_schedule_map_saves')->where('id', $existing->id)->update($payload);
            $id = (int) $existing->id;
        } else {
            // A map drawn here is the GROWER's, like every map on anee now:
            // it lands in their Maps (scheduleId 0, their userId), remembers
            // it was drawn in this season, and is linked to it.
            $owner = (int) ($schedule->anisystemUserId ?? 0);
            $id = (int) DB::table('as_schedule_map_saves')->insertGetId($payload + [
                'scheduleId' => $owner > 0 ? 0 : $schedule->id,
                'originScheduleId' => $schedule->id,
                'userId' => $owner > 0 ? $owner : AniSensoTechnician::id(),
                'source' => 'solo',
                'deleteStatus' => 1,
                'created_at' => now(),
            ]);
        }
        \App\Support\AnisystemMaps::link($id, (int) $schedule->id);

        // "Save map to notes": its picture goes to the grower's own notes (the
        // season's notebook on an admin-owned schedule), and the map knows
        // which note is its picture, as anee's do.
        if ($mode === 'map') {
            $noteId = $owner > 0
                ? $this->fileNote(0, $owner, $title, $description, 'map', $picture)
                : $this->fileNote($schedule->id, null, $title, $description, 'map', $picture);
            DB::table('as_schedule_map_saves')->where('id', $id)->update(['noteId' => $noteId]);
        }

        return $this->jsonOk(
            $existing ? 'Saved over “' . mb_substr($title, 0, 60) . '”.'
                : ($mode === 'map' ? 'Map saved, and its picture is in the notes.' : 'Map saved.'),
            ['data' => ['id' => $id, 'saveId' => $id, 'title' => $title, 'shapes' => count($objects)]]
        );
    }

    /** A note carrying a map's picture. Returns its id. */
    private function fileNote(int $bucket, ?int $userId, string $title, ?string $words, string $type, ?string $picture): int
    {
        $body = $type === 'map'
            ? trim(($words ? $words . "\n\n" : '') . 'Saved map, tap View map to open it.')
            : $words;

        return (int) DB::table('as_schedule_notes')->insertGetId([
            'croppingScheduleId' => $bucket,
            'userId' => $userId,
            'title' => mb_substr($title, 0, 180),
            'body' => $body !== null && $body !== '' ? '<p>' . nl2br(e($body)) . '</p>' : null,
            'media' => json_encode($picture ? [['type' => $type, 'path' => AnisystemMedia::REMOTE_PREFIX . $picture, 'poster' => null]] : []),
            'sortOrder' => 0,
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    /**
     * Put a saved map back on the canvas.
     *
     * Everything currently drawn comes off first — a save is a picture of a
     * whole canvas, not a layer to stack on one.
     */
    public function loadSave(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $save = \App\Support\AnisystemMaps::forSeason($schedule)
            ->where('id', (int) $request->input('id'))
            ->first();
        if (! $save) {
            return $this->jsonFail('That saved map no longer exists.', 404);
        }

        $objects = json_decode((string) $save->objects, true) ?: [];

        DB::transaction(function () use ($schedule, $objects) {
            AsScheduleMapObject::active()->where('scheduleId', $schedule->id)->update(['deleteStatus' => 0]);

            foreach (array_slice($objects, 0, self::MAX_SHAPES) as $o) {
                if (! is_array($o['points'] ?? null) || empty($o['points'])) {
                    continue;
                }
                AsScheduleMapObject::create([
                    'scheduleId' => $schedule->id,
                    'userId' => AniSensoTechnician::id(),
                    'kind' => $o['kind'] ?? 'pen',
                    'color' => $o['color'] ?? null,
                    'width' => (int) ($o['width'] ?? 3),
                    // A save made before lettering existed has no font, and
                    // null is right for it: the client reads that as "an old
                    // label" and draws it the way it always drew.
                    'font' => $o['font'] ?? null,
                    'points' => json_encode($o['points']),
                    'label' => $o['label'] ?? null,
                    'deleteStatus' => 1,
                ]);
            }
        });

        return $this->jsonOk('Map opened.', ['data' => [
            'saveId' => (int) $save->id,
            'title' => (string) $save->title,
        ]]);
    }

    /**
     * A flat picture of the ground under a view, for saving a thumbnail.
     *
     * Fetched here rather than in the browser so the key stays on the server;
     * a static-map URL carries it in the query string, and a key on a page is
     * a key anybody can spend.
     */
    public function basemap(Request $request)
    {
        $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'zoom' => 'required|numeric|between:1,22',
            'maptype' => 'nullable|in:roadmap,hybrid',
            'size' => 'nullable|integer|min:256|max:640',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $key = (string) config('services.google_maps.key');
        if ($key === '') {
            return $this->jsonFail('No map key configured.', 404);
        }

        $url = 'https://maps.googleapis.com/maps/api/staticmap'
            . '?size=' . (int) $request->input('size', 640) . 'x' . (int) $request->input('size', 640)
            . '&scale=2'
            . '&maptype=' . ($request->input('maptype') === 'roadmap' ? 'roadmap' : 'hybrid')
            . '&center=' . round((float) $request->input('lat'), 6) . ',' . round((float) $request->input('lng'), 6)
            . '&zoom=' . (int) round((float) $request->input('zoom'))
            . '&key=' . rawurlencode($key);

        try {
            $res = Http::timeout(20)->get($url);
            if (! $res->ok() || ! str_starts_with((string) $res->header('Content-Type'), 'image/')) {
                return $this->jsonFail('Could not fetch the map imagery.', 502);
            }

            return response($res->body(), 200, [
                'Content-Type' => $res->header('Content-Type'),
                'Cache-Control' => 'private, max-age=120',
            ]);
        } catch (\Throwable $e) {
            return $this->jsonFail('Could not fetch the map imagery.', 502);
        }
    }
}
