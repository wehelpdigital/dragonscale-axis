<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Support\AnisystemMedia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

/**
 * What the members have drawn, photographed and mapped, and what their teams
 * have been doing in the Collab Room.
 *
 * Four screens over four different shapes of the same idea:
 *
 *  - Gallery: rows in as_gallery_images, an album and a season each.
 *  - Drawings: NOT a table. A drawing is an entry inside a note's media list,
 *    and notes live on three shelves (the notebook, the board's stickies, a
 *    day's note), so the list is assembled from all three and paged in PHP.
 *    There is no way to ask the database for it and there never was.
 *  - Maps: rows in as_schedule_map_saves, each holding a bag of objects.
 *  - Collab Room: one row per schedule that has a room, with what is in it --
 *    board pages, recordings, chat -- counted alongside.
 *
 * Removal everywhere is deleteStatus = 0, the way the whole app removes
 * things: the row stays, the app stops showing it, and nothing is destroyed
 * by an operator's mis-click.
 */
class AnisystemMediaController extends Controller
{
    /** The three shelves a note (and therefore a drawing) can sit on. */
    private const NOTE_SHELVES = [
        'note' => ['table' => 'as_schedule_notes', 'title' => 'title', 'body' => 'body', 'schedule' => 'croppingScheduleId'],
        'inline' => ['table' => 'as_inline_notes', 'title' => 'title', 'body' => 'content', 'schedule' => 'croppingScheduleId'],
        'date' => ['table' => 'as_schedule_date_notes', 'title' => null, 'body' => 'noteContent', 'schedule' => 'croppingScheduleId'],
    ];

    // =====================================================================
    // Gallery
    // =====================================================================

    public function gallery()
    {
        return view('aniSensoAdmin.media.gallery');
    }

    public function galleryData(Request $request)
    {
        try {
            $query = DB::table('as_gallery_images as g')
                ->leftJoin('anisystem_users as u', 'u.id', '=', 'g.userId')
                ->leftJoin('as_cropping_schedules as s', 's.id', '=', 'g.croppingScheduleId')
                ->leftJoin('as_gallery_albums as a', 'a.id', '=', 'g.albumId')
                ->where('g.deleteStatus', 1)
                ->selectRaw("
                    g.id, g.path, g.caption, g.isTeam, g.created_at,
                    TRIM(CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,''))) as clientName,
                    u.email as clientEmail, s.title as scheduleTitle, a.title as albumTitle
                ");

            $this->applyCommonFilters($query, $request, 'g.created_at', [
                'g.caption', 'u.firstName', 'u.lastName', 'u.email', 's.title', 'a.title',
            ]);

            if ($request->query('kind') === 'team') {
                $query->where('g.isTeam', 1);
            } elseif ($request->query('kind') === 'personal') {
                $query->where(fn ($w) => $w->where('g.isTeam', 0)->orWhereNull('g.isTeam'));
            }

            return DataTables::query($query)
                ->addColumn('url', fn ($row) => AnisystemMedia::url($row->path))
                ->addColumn('fileName', fn ($row) => AnisystemMedia::basename($row->path))
                ->editColumn('created_at', fn ($row) => $this->when($row->created_at))
                ->orderColumn('created_at', 'g.created_at $1')
                ->make(true);
        } catch (\Exception $e) {
            Log::error('AniSystem gallery list failed: ' . $e->getMessage());

            return response()->json(['error' => 'Could not load the gallery.'], 500);
        }
    }

    public function galleryDestroy(Request $request)
    {
        return $this->softRemove('as_gallery_images', (int) $request->query('id'), 'Photo removed.');
    }

    // =====================================================================
    // Drawings
    // =====================================================================

    public function drawings()
    {
        return view('aniSensoAdmin.media.drawings');
    }

    /**
     * Every drawing on every shelf.
     *
     * Assembled rather than queried: a drawing is one entry inside a note's
     * JSON media list, so there is nothing to join against and no way to make
     * the database count them. The shelves are read newest-first with a cap,
     * flattened, filtered, and paged here — which is honest about what it is,
     * and fast enough for the thing it lists.
     */
    public function drawingsData(Request $request)
    {
        try {
            $search = mb_strtolower(trim((string) $request->query('searchFilter')));
            $shelf = (string) $request->query('shelf', '');
            $rows = [];

            foreach (self::NOTE_SHELVES as $key => $spec) {
                if ($shelf !== '' && $shelf !== $key) {
                    continue;
                }
                $notes = DB::table($spec['table'] . ' as n')
                    ->leftJoin('as_cropping_schedules as s', 's.id', '=', 'n.' . $spec['schedule'])
                    ->leftJoin('anisystem_users as u', 'u.id', '=', 's.anisystemUserId')
                    ->where('n.deleteStatus', 1)
                    ->whereNotNull('n.media')
                    ->where('n.media', '!=', '')
                    ->orderByDesc('n.id')
                    ->limit(400)
                    ->selectRaw(sprintf(
                        "n.id, n.media, n.updated_at, %s as noteTitle, n.%s as noteBody, s.title as scheduleTitle,
                         TRIM(CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,''))) as clientName, u.email as clientEmail",
                        $spec['title'] ? 'n.' . $spec['title'] : "''",
                        $spec['body']
                    ))
                    ->get();

                foreach ($notes as $note) {
                    foreach ($this->mediaOf($note->media) as $i => $m) {
                        $path = (string) ($m['path'] ?? '');
                        $isDrawing = ($m['type'] ?? '') === 'drawing'
                            || (bool) preg_match('~/board-[A-Za-z0-9]+\.png$~', $path);
                        if (! $isDrawing || $path === '') {
                            continue;
                        }
                        $rows[] = [
                            'shelf' => $key,
                            'noteId' => (int) $note->id,
                            'index' => (int) $i,
                            'title' => trim((string) $note->noteTitle) ?: 'Untitled note',
                            'words' => mb_substr(trim(strip_tags((string) $note->noteBody)), 0, 120),
                            'clientName' => trim((string) $note->clientName) ?: null,
                            'clientEmail' => $note->clientEmail,
                            'scheduleTitle' => $note->scheduleTitle,
                            'team' => (bool) preg_match('~/board-[A-Za-z0-9]+\.png$~', $path),
                            'url' => AnisystemMedia::url($path),
                            'when' => $this->when($note->updated_at),
                            'sortKey' => $note->updated_at ? strtotime($note->updated_at) : 0,
                        ];
                    }
                }
            }

            if ($search !== '') {
                $rows = array_values(array_filter($rows, function ($r) use ($search) {
                    return str_contains(mb_strtolower(implode(' ', [
                        $r['title'], $r['words'], $r['clientName'], $r['clientEmail'], $r['scheduleTitle'],
                    ])), $search);
                }));
            }

            usort($rows, fn ($a, $b) => $b['sortKey'] <=> $a['sortKey']);

            $start = max(0, (int) $request->query('start', 0));
            $length = (int) $request->query('length', 25);
            $page = $length < 0 ? $rows : array_slice($rows, $start, $length);

            return response()->json([
                'draw' => (int) $request->query('draw', 1),
                'recordsTotal' => count($rows),
                'recordsFiltered' => count($rows),
                'data' => $page,
            ]);
        } catch (\Exception $e) {
            Log::error('AniSystem drawings list failed: ' . $e->getMessage());

            return response()->json(['error' => 'Could not load the drawings.'], 500);
        }
    }

    /**
     * Take one drawing out of the note that holds it.
     *
     * The entry is removed from the media list rather than the note: a note
     * is words with pictures in it, and the words are not the operator's to
     * delete because one of the pictures was.
     */
    public function drawingDestroy(Request $request)
    {
        try {
            $shelf = (string) $request->query('shelf');
            $spec = self::NOTE_SHELVES[$shelf] ?? null;
            if (! $spec) {
                return response()->json(['success' => false, 'message' => 'Unknown note shelf.'], 422);
            }

            $noteId = (int) $request->query('noteId');
            $index = (int) $request->query('index');
            $note = DB::table($spec['table'])->where('id', $noteId)->where('deleteStatus', 1)->first();
            if (! $note) {
                return response()->json(['success' => false, 'message' => 'That note is gone.'], 404);
            }

            $media = $this->mediaOf($note->media);
            if (! array_key_exists($index, $media)) {
                return response()->json(['success' => false, 'message' => 'That drawing is no longer there.'], 404);
            }

            unset($media[$index]);
            DB::table($spec['table'])->where('id', $noteId)
                ->update(['media' => json_encode(array_values($media)), 'updated_at' => now()]);

            return response()->json(['success' => true, 'message' => 'Drawing removed from the note.']);
        } catch (\Exception $e) {
            Log::error('AniSystem drawing removal failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not remove that drawing.'], 500);
        }
    }

    // =====================================================================
    // Notes
    // =====================================================================

    public function notes()
    {
        return view('aniSensoAdmin.media.notes');
    }

    /**
     * Every note on every shelf.
     *
     * Same assembly as the drawings, and for the same reason: three tables
     * with the same job and no key between them. A note's pictures come along
     * so the row can say how much is attached without a second trip.
     */
    public function notesData(Request $request)
    {
        try {
            $search = mb_strtolower(trim((string) $request->query('searchFilter')));
            $shelf = (string) $request->query('shelf', '');
            $withMedia = $request->query('media') === 'yes';
            $rows = [];

            foreach (self::NOTE_SHELVES as $key => $spec) {
                if ($shelf !== '' && $shelf !== $key) {
                    continue;
                }
                $notes = DB::table($spec['table'] . ' as n')
                    ->leftJoin('as_cropping_schedules as s', 's.id', '=', 'n.' . $spec['schedule'])
                    ->leftJoin('anisystem_users as u', 'u.id', '=', 's.anisystemUserId')
                    ->where('n.deleteStatus', 1)
                    ->orderByDesc('n.id')
                    ->limit(500)
                    ->selectRaw(sprintf(
                        "n.id, n.media, n.updated_at, n.created_at, %s as noteTitle, n.%s as noteBody,
                         s.title as scheduleTitle,
                         TRIM(CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,''))) as clientName,
                         u.email as clientEmail",
                        $spec['title'] ? 'n.' . $spec['title'] : "''",
                        $spec['body']
                    ))
                    ->get();

                foreach ($notes as $note) {
                    $media = $this->mediaOf($note->media);
                    if ($withMedia && ! $media) {
                        continue;
                    }
                    $rows[] = [
                        'shelf' => $key,
                        'id' => (int) $note->id,
                        'title' => trim((string) $note->noteTitle) ?: 'Untitled note',
                        'words' => mb_substr(trim(strip_tags((string) $note->noteBody)), 0, 160),
                        'attachments' => count($media),
                        'clientName' => trim((string) $note->clientName) ?: null,
                        'clientEmail' => $note->clientEmail,
                        'scheduleTitle' => $note->scheduleTitle,
                        'when' => $this->when($note->updated_at),
                        'sortKey' => $note->updated_at ? strtotime($note->updated_at) : 0,
                    ];
                }
            }

            if ($search !== '') {
                $rows = array_values(array_filter($rows, fn ($r) => str_contains(mb_strtolower(implode(' ', [
                    $r['title'], $r['words'], $r['clientName'], $r['clientEmail'], $r['scheduleTitle'],
                ])), $search)));
            }

            usort($rows, fn ($a, $b) => $b['sortKey'] <=> $a['sortKey']);
            $start = max(0, (int) $request->query('start', 0));
            $length = (int) $request->query('length', 25);

            return response()->json([
                'draw' => (int) $request->query('draw', 1),
                'recordsTotal' => count($rows),
                'recordsFiltered' => count($rows),
                'data' => $length < 0 ? $rows : array_slice($rows, $start, $length),
            ]);
        } catch (\Exception $e) {
            Log::error('AniSystem notes list failed: ' . $e->getMessage());

            return response()->json(['error' => 'Could not load the notes.'], 500);
        }
    }

    /** One note, with its words and everything attached to it. */
    public function noteShow(Request $request)
    {
        $spec = self::NOTE_SHELVES[(string) $request->query('shelf')] ?? null;
        if (! $spec) {
            return response()->json(['success' => false, 'message' => 'Unknown note shelf.'], 422);
        }

        $note = DB::table($spec['table'] . ' as n')
            ->leftJoin('as_cropping_schedules as s', 's.id', '=', 'n.' . $spec['schedule'])
            ->leftJoin('anisystem_users as u', 'u.id', '=', 's.anisystemUserId')
            ->where('n.id', (int) $request->query('id'))->where('n.deleteStatus', 1)
            ->selectRaw(sprintf(
                "n.id, n.media, n.updated_at, %s as noteTitle, n.%s as noteBody, s.title as scheduleTitle,
                 TRIM(CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,''))) as clientName, u.email as clientEmail",
                $spec['title'] ? 'n.' . $spec['title'] : "''",
                $spec['body']
            ))
            ->first();

        if (! $note) {
            return response()->json(['success' => false, 'message' => 'That note is gone.'], 404);
        }

        return response()->json(['success' => true, 'data' => [
            'title' => trim((string) $note->noteTitle) ?: 'Untitled note',
            'body' => trim(strip_tags((string) $note->noteBody)),
            'clientName' => trim((string) $note->clientName) ?: null,
            'clientEmail' => $note->clientEmail,
            'scheduleTitle' => $note->scheduleTitle,
            'when' => $this->when($note->updated_at),
            'media' => collect($this->mediaOf($note->media))->map(fn ($m) => [
                'type' => (string) ($m['type'] ?? 'image'),
                'url' => AnisystemMedia::url((string) ($m['path'] ?? '')),
                'name' => AnisystemMedia::basename((string) ($m['path'] ?? '')),
            ])->values(),
        ]]);
    }

    public function noteDestroy(Request $request)
    {
        $spec = self::NOTE_SHELVES[(string) $request->query('shelf')] ?? null;
        if (! $spec) {
            return response()->json(['success' => false, 'message' => 'Unknown note shelf.'], 422);
        }

        return $this->softRemove($spec['table'], (int) $request->query('id'), 'Note removed.');
    }

    // =====================================================================
    // Reels and stories
    // =====================================================================

    public function reels()
    {
        return view('aniSensoAdmin.media.reels');
    }

    public function reelsData(Request $request)
    {
        try {
            $query = DB::table('as_community_wall_posts as p')
                ->leftJoin('anisystem_users as u', 'u.id', '=', 'p.authorUserId')
                ->where('p.deleteStatus', 1)
                ->where('p.isReel', 1)
                ->selectRaw("
                    p.id, p.body, p.videoPath, p.videoPoster, p.imagePath, p.durationSec, p.audioTitle,
                    p.isRestricted, p.created_at,
                    TRIM(CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,''))) as clientName,
                    u.email as clientEmail,
                    (SELECT COUNT(*) FROM as_community_wall_comments c WHERE c.wallPostId = p.id AND c.deleteStatus = 1) as comments,
                    (SELECT COUNT(*) FROM as_community_reactions r WHERE r.targetType = 'wallpost' AND r.targetId = p.id) as reactions
                ");

            $this->applyCommonFilters($query, $request, 'p.created_at', [
                'p.body', 'u.firstName', 'u.lastName', 'u.email', 'p.audioTitle',
            ]);

            if ($request->query('restricted') === 'yes') {
                $query->where('p.isRestricted', 1);
            }

            return DataTables::query($query)
                ->addColumn('videoUrl', fn ($row) => AnisystemMedia::url($row->videoPath))
                ->addColumn('posterUrl', fn ($row) => AnisystemMedia::url($row->videoPoster ?: $row->imagePath))
                ->editColumn('created_at', fn ($row) => $this->when($row->created_at))
                ->orderColumn('created_at', 'p.created_at $1')
                ->make(true);
        } catch (\Exception $e) {
            Log::error('AniSystem reels list failed: ' . $e->getMessage());

            return response()->json(['error' => 'Could not load the reels.'], 500);
        }
    }

    /** Take a reel down, and its comments with it. */
    public function reelDestroy(Request $request)
    {
        try {
            $id = (int) $request->query('id');
            $post = DB::table('as_community_wall_posts')->where('id', $id)->where('deleteStatus', 1)->first();
            if (! $post) {
                return response()->json(['success' => false, 'message' => 'Already gone.'], 404);
            }
            DB::transaction(function () use ($id) {
                DB::table('as_community_wall_comments')->where('wallPostId', $id)->update(['deleteStatus' => 0]);
                DB::table('as_community_wall_posts')->where('id', $id)->update(['deleteStatus' => 0]);
            });

            return response()->json(['success' => true, 'message' => 'Reel removed.']);
        } catch (\Exception $e) {
            Log::error('AniSystem reel removal failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not remove that reel.'], 500);
        }
    }

    // =====================================================================
    // Maps
    // =====================================================================

    public function maps()
    {
        return view('aniSensoAdmin.media.maps');
    }

    public function mapsData(Request $request)
    {
        try {
            $query = DB::table('as_schedule_map_saves as m')
                ->leftJoin('anisystem_users as u', 'u.id', '=', 'm.userId')
                ->leftJoin('as_cropping_schedules as s', 's.id', '=', 'm.scheduleId')
                ->where('m.deleteStatus', 1)
                ->selectRaw("
                    m.id, m.title, m.source, m.objects, m.noteId, m.created_at, m.updated_at,
                    TRIM(CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,''))) as clientName,
                    u.email as clientEmail, s.title as scheduleTitle
                ");

            $this->applyCommonFilters($query, $request, 'm.updated_at', [
                'm.title', 'u.firstName', 'u.lastName', 'u.email', 's.title',
            ]);

            if ($request->filled('source')) {
                $query->where('m.source', $request->query('source'));
            }

            return DataTables::query($query)
                ->addColumn('shapes', function ($row) {
                    $objects = json_decode((string) $row->objects, true);

                    return is_array($objects) ? count($objects) : 0;
                })
                ->editColumn('updated_at', fn ($row) => $this->when($row->updated_at))
                ->orderColumn('updated_at', 'm.updated_at $1')
                ->make(true);
        } catch (\Exception $e) {
            Log::error('AniSystem maps list failed: ' . $e->getMessage());

            return response()->json(['error' => 'Could not load the maps.'], 500);
        }
    }

    /** One saved map, with what it actually holds. */
    public function mapShow(Request $request)
    {
        $map = DB::table('as_schedule_map_saves as m')
            ->leftJoin('anisystem_users as u', 'u.id', '=', 'm.userId')
            ->leftJoin('as_cropping_schedules as s', 's.id', '=', 'm.scheduleId')
            ->where('m.id', (int) $request->query('id'))->where('m.deleteStatus', 1)
            ->selectRaw("m.*, TRIM(CONCAT(COALESCE(u.firstName,''),' ',COALESCE(u.lastName,''))) as clientName, u.email as clientEmail, s.title as scheduleTitle")
            ->first();

        if (! $map) {
            return response()->json(['success' => false, 'message' => 'That map is gone.'], 404);
        }

        $objects = json_decode((string) $map->objects, true);
        $objects = is_array($objects) ? $objects : [];
        $kinds = [];
        foreach ($objects as $o) {
            $kind = (string) ($o['kind'] ?? $o['type'] ?? 'shape');
            $kinds[$kind] = ($kinds[$kind] ?? 0) + 1;
        }

        return response()->json(['success' => true, 'data' => [
            'title' => $map->title,
            'source' => $map->source,
            'clientName' => trim((string) $map->clientName) ?: null,
            'clientEmail' => $map->clientEmail,
            'scheduleTitle' => $map->scheduleTitle,
            'when' => $this->when($map->updated_at),
            'shapes' => count($objects),
            'kinds' => $kinds,
            'labels' => collect($objects)->pluck('label')->filter()->take(24)->values(),
        ]]);
    }

    public function mapDestroy(Request $request)
    {
        return $this->softRemove('as_schedule_map_saves', (int) $request->query('id'), 'Map removed.');
    }

    // =====================================================================
    // Collab Room
    // =====================================================================

    public function rooms()
    {
        return view('aniSensoAdmin.media.rooms');
    }

    public function roomsData(Request $request)
    {
        try {
            $query = DB::table('as_cropping_schedules as s')
                ->leftJoin('anisystem_users as u', 'u.id', '=', 's.anisystemUserId')
                ->where('s.deleteStatus', 1)
                ->selectRaw("
                    s.id, s.title as scheduleTitle, s.updated_at,
                    TRIM(CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,''))) as clientName,
                    u.email as clientEmail,
                    (SELECT COUNT(*) FROM as_schedule_messages m WHERE m.scheduleId = s.id AND m.deleteStatus = 1) as chatCount,
                    (SELECT COUNT(*) FROM as_schedule_board_pages p WHERE p.scheduleId = s.id) as boardPages,
                    (SELECT COUNT(*) FROM as_team_recordings r WHERE r.scheduleId = s.id AND r.deleteStatus = 1) as recordings,
                    (SELECT COUNT(*) FROM as_schedule_ai_sessions a WHERE a.scheduleId = s.id AND a.deleteStatus = 1) as aiSessions,
                    (SELECT MAX(m.created_at) FROM as_schedule_messages m WHERE m.scheduleId = s.id AND m.deleteStatus = 1) as lastChatAt
                ");

            if ($request->filled('searchFilter')) {
                $s = trim((string) $request->searchFilter);
                $query->where(function ($w) use ($s) {
                    $w->where('s.title', 'like', "%{$s}%")
                        ->orWhere('u.firstName', 'like', "%{$s}%")
                        ->orWhere('u.lastName', 'like', "%{$s}%")
                        ->orWhere('u.email', 'like', "%{$s}%");
                });
            }

            // A schedule with nothing in its room is not a room; the default
            // hides them so the list is the rooms that exist.
            if ($request->query('empty') !== 'show') {
                $query->havingRaw('(chatCount + boardPages + recordings + aiSessions) > 0');
            }

            return DataTables::query($query)
                ->editColumn('lastChatAt', fn ($row) => $this->when($row->lastChatAt))
                ->orderColumn('chatCount', 'chatCount $1')
                ->make(true);
        } catch (\Exception $e) {
            Log::error('AniSystem rooms list failed: ' . $e->getMessage());

            return response()->json(['error' => 'Could not load the rooms.'], 500);
        }
    }

    /** One room: who said what, what was recorded, what the board holds. */
    public function roomShow(Request $request)
    {
        try {
            $id = (int) $request->query('id');
            $schedule = DB::table('as_cropping_schedules as s')
                ->leftJoin('anisystem_users as u', 'u.id', '=', 's.anisystemUserId')
                ->where('s.id', $id)->where('s.deleteStatus', 1)
                ->selectRaw("s.id, s.title, TRIM(CONCAT(COALESCE(u.firstName,''),' ',COALESCE(u.lastName,''))) as clientName, u.email as clientEmail")
                ->first();
            if (! $schedule) {
                return response()->json(['success' => false, 'message' => 'That season is gone.'], 404);
            }

            $chat = DB::table('as_schedule_messages as m')
                ->leftJoin('anisystem_users as u', 'u.id', '=', 'm.userId')
                ->where('m.scheduleId', $id)->where('m.deleteStatus', 1)
                ->orderByDesc('m.id')->limit(60)
                ->selectRaw("m.id, m.body, m.imagePath, m.created_at, TRIM(CONCAT(COALESCE(u.firstName,''),' ',COALESCE(u.lastName,''))) as who")
                ->get()->reverse()->values();

            $recordings = DB::table('as_team_recordings as r')
                ->leftJoin('anisystem_users as u', 'u.id', '=', 'r.userId')
                ->where('r.scheduleId', $id)->where('r.deleteStatus', 1)
                ->orderByDesc('r.id')->limit(30)
                ->selectRaw("r.id, r.kind, r.title, r.path, r.poster, r.seconds, r.created_at, TRIM(CONCAT(COALESCE(u.firstName,''),' ',COALESCE(u.lastName,''))) as who")
                ->get();

            $pages = DB::table('as_schedule_board_pages')->where('scheduleId', $id)
                ->orderBy('page')->get(['id', 'page', 'orientation', 'updated_at']);

            return response()->json(['success' => true, 'data' => [
                'title' => $schedule->title,
                'clientName' => trim((string) $schedule->clientName) ?: null,
                'clientEmail' => $schedule->clientEmail,
                'chat' => $chat->map(fn ($m) => [
                    'id' => (int) $m->id,
                    'who' => trim((string) $m->who) ?: 'Someone',
                    'body' => (string) $m->body,
                    'photo' => AnisystemMedia::url($m->imagePath),
                    'at' => $this->when($m->created_at, 'M j, g:i A'),
                ]),
                'recordings' => $recordings->map(fn ($r) => [
                    'id' => (int) $r->id,
                    'kind' => (string) $r->kind,
                    'title' => (string) ($r->title ?: 'Untitled'),
                    'who' => trim((string) $r->who) ?: null,
                    'url' => AnisystemMedia::url($r->path),
                    'poster' => AnisystemMedia::url($r->poster),
                    'seconds' => (int) $r->seconds,
                    'at' => $this->when($r->created_at),
                ]),
                'pages' => $pages->map(fn ($p) => [
                    'page' => (int) $p->page,
                    'orientation' => (string) $p->orientation,
                    'at' => $this->when($p->updated_at),
                ]),
            ]]);
        } catch (\Exception $e) {
            Log::error('AniSystem room read failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not read that room.'], 500);
        }
    }

    public function roomMessageDestroy(Request $request)
    {
        return $this->softRemove('as_schedule_messages', (int) $request->query('id'), 'Message removed.');
    }

    public function roomRecordingDestroy(Request $request)
    {
        return $this->softRemove('as_team_recordings', (int) $request->query('id'), 'Recording removed.');
    }

    // =====================================================================
    // Shared
    // =====================================================================

    /** Search, a date range and a season filter: the same three, everywhere. */
    private function applyCommonFilters($query, Request $request, string $dateColumn, array $searchable): void
    {
        if ($request->filled('searchFilter')) {
            $s = trim((string) $request->searchFilter);
            $query->where(function ($w) use ($s, $searchable) {
                foreach ($searchable as $i => $col) {
                    $i === 0 ? $w->where($col, 'like', "%{$s}%") : $w->orWhere($col, 'like', "%{$s}%");
                }
            });
        }
        if ($request->filled('from')) {
            $query->where($dateColumn, '>=', Carbon::parse($request->from, 'Asia/Manila')->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where($dateColumn, '<=', Carbon::parse($request->to, 'Asia/Manila')->endOfDay());
        }
    }

    /** A note's media list, whatever shape it was stored in. */
    private function mediaOf($raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function when($value, string $format = 'M j, Y g:i A'): string
    {
        return $value ? Carbon::parse($value)->timezone('Asia/Manila')->format($format) : '—';
    }

    /** The app's own way of removing something: hidden, not destroyed. */
    private function softRemove(string $table, int $id, string $message)
    {
        try {
            $row = DB::table($table)->where('id', $id)->where('deleteStatus', 1)->first();
            if (! $row) {
                return response()->json(['success' => false, 'message' => 'Already gone.'], 404);
            }
            DB::table($table)->where('id', $id)->update(['deleteStatus' => 0]);

            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            Log::error('AniSystem media removal failed (' . $table . '): ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not remove that.'], 500);
        }
    }
}
