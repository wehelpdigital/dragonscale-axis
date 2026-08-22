<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

/**
 * Every AI conversation a client has had, read-only.
 *
 * Two kinds, and the operator cares about both: the personal threads with the
 * technician (anisystem_ai_conversations) and the team sessions inside a
 * schedule's Collab Room (as_schedule_ai_sessions). They are listed as one
 * table with a Kind column rather than two screens, because the question
 * being asked is "what has the AI been answering, and what did it cost" and
 * that question does not care which door the client came through.
 *
 * A union, normalised to the same columns, wrapped as a subquery: DataTables
 * then paginates, searches and sorts it in the database rather than in PHP —
 * which matters the day this table is not five rows long.
 */
class AnisystemAiConversationsController extends Controller
{
    /** One page of the conversation list, for DataTables. */
    public function data(Request $request)
    {
        try {
            $personal = DB::table('anisystem_ai_conversations as c')
                ->leftJoin('anisystem_users as u', 'u.id', '=', 'c.userId')
                ->leftJoin('as_cropping_schedules as s', 's.id', '=', 'c.croppingScheduleId')
                ->where('c.deleteStatus', 1)
                ->selectRaw("
                    'personal' as kind,
                    c.id as id,
                    c.title as title,
                    c.userId as userId,
                    TRIM(CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,''))) as clientName,
                    u.email as clientEmail,
                    s.title as scheduleTitle,
                    (SELECT COUNT(*) FROM anisystem_ai_messages m
                       WHERE m.conversationId = c.id AND m.deleteStatus = 1) as messageCount,
                    (SELECT COALESCE(SUM(m.creditsCharged),0) FROM anisystem_ai_messages m
                       WHERE m.conversationId = c.id AND m.deleteStatus = 1) as credits,
                    COALESCE((SELECT MAX(m.created_at) FROM anisystem_ai_messages m
                       WHERE m.conversationId = c.id AND m.deleteStatus = 1), c.updated_at) as lastAt,
                    c.created_at as startedAt
                ");

            $team = DB::table('as_schedule_ai_sessions as t')
                ->leftJoin('anisystem_users as u', 'u.id', '=', 't.startedByUserId')
                ->leftJoin('as_cropping_schedules as s', 's.id', '=', 't.scheduleId')
                ->where('t.deleteStatus', 1)
                ->selectRaw("
                    'team' as kind,
                    t.id as id,
                    t.title as title,
                    t.startedByUserId as userId,
                    TRIM(CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,''))) as clientName,
                    u.email as clientEmail,
                    s.title as scheduleTitle,
                    (SELECT COUNT(*) FROM as_schedule_ai_messages m
                       WHERE m.sessionId = t.id AND m.deleteStatus = 1) as messageCount,
                    (SELECT COALESCE(SUM(m.creditsCharged),0) FROM as_schedule_ai_messages m
                       WHERE m.sessionId = t.id AND m.deleteStatus = 1) as credits,
                    COALESCE(t.lastMessageAt, t.updated_at) as lastAt,
                    t.created_at as startedAt
                ");

            // The kind filter narrows before the union, so the database never
            // builds the half nobody asked for.
            $kind = (string) $request->query('kind', '');
            if ($kind === 'personal') {
                $union = $personal;
            } elseif ($kind === 'team') {
                $union = $team;
            } else {
                $union = $personal->unionAll($team);
            }

            $query = DB::query()->fromSub($union, 'x');

            if ($request->filled('searchFilter')) {
                $s = trim((string) $request->searchFilter);
                $query->where(function ($w) use ($s) {
                    $w->where('x.title', 'like', "%{$s}%")
                        ->orWhere('x.clientName', 'like', "%{$s}%")
                        ->orWhere('x.clientEmail', 'like', "%{$s}%")
                        ->orWhere('x.scheduleTitle', 'like', "%{$s}%");
                });
            }

            // A day is a day in Manila, which is where the farms are.
            if ($request->filled('from')) {
                $query->where('x.lastAt', '>=', Carbon::parse($request->from, 'Asia/Manila')->startOfDay());
            }
            if ($request->filled('to')) {
                $query->where('x.lastAt', '<=', Carbon::parse($request->to, 'Asia/Manila')->endOfDay());
            }
            if ($request->query('linked') === 'yes') {
                $query->whereNotNull('x.scheduleTitle');
            } elseif ($request->query('linked') === 'no') {
                $query->whereNull('x.scheduleTitle');
            }
            if ($request->query('empty') === 'hide') {
                $query->where('x.messageCount', '>', 0);
            }

            return DataTables::query($query)
                ->addIndexColumn()
                ->editColumn('lastAt', fn ($row) => $row->lastAt
                    ? Carbon::parse($row->lastAt)->timezone('Asia/Manila')->format('M j, Y g:i A') : '—')
                ->editColumn('startedAt', fn ($row) => $row->startedAt
                    ? Carbon::parse($row->startedAt)->timezone('Asia/Manila')->format('M j, Y') : '—')
                ->orderColumn('lastAt', 'x.lastAt $1')
                ->orderColumn('messageCount', 'x.messageCount $1')
                ->orderColumn('credits', 'x.credits $1')
                ->orderColumn('clientName', 'x.clientName $1')
                ->make(true);
        } catch (\Exception $e) {
            Log::error('AniSystem AI conversations list failed: ' . $e->getMessage());

            return response()->json(['error' => 'Could not load conversations.'], 500);
        }
    }

    /**
     * One conversation, turn by turn.
     *
     * Read-only on purpose: this is a window onto what the client asked and
     * what the model said, not a place to edit either.
     */
    public function show(Request $request, $id)
    {
        try {
            $kind = $request->query('kind') === 'team' ? 'team' : 'personal';

            if ($kind === 'team') {
                $head = DB::table('as_schedule_ai_sessions as t')
                    ->leftJoin('anisystem_users as u', 'u.id', '=', 't.startedByUserId')
                    ->leftJoin('as_cropping_schedules as s', 's.id', '=', 't.scheduleId')
                    ->where('t.id', (int) $id)->where('t.deleteStatus', 1)
                    ->selectRaw("t.id, t.title, t.created_at, TRIM(CONCAT(COALESCE(u.firstName,''),' ',COALESCE(u.lastName,''))) as clientName, u.email as clientEmail, s.title as scheduleTitle")
                    ->first();
                $turns = DB::table('as_schedule_ai_messages as m')
                    ->leftJoin('anisystem_users as u', 'u.id', '=', 'm.userId')
                    ->where('m.sessionId', (int) $id)->where('m.deleteStatus', 1)
                    ->orderBy('m.id')
                    ->selectRaw("m.role, m.content, m.imagePath, m.creditsCharged, m.created_at, TRIM(CONCAT(COALESCE(u.firstName,''),' ',COALESCE(u.lastName,''))) as who")
                    ->get();
            } else {
                $head = DB::table('anisystem_ai_conversations as c')
                    ->leftJoin('anisystem_users as u', 'u.id', '=', 'c.userId')
                    ->leftJoin('as_cropping_schedules as s', 's.id', '=', 'c.croppingScheduleId')
                    ->where('c.id', (int) $id)->where('c.deleteStatus', 1)
                    ->selectRaw("c.id, c.title, c.created_at, TRIM(CONCAT(COALESCE(u.firstName,''),' ',COALESCE(u.lastName,''))) as clientName, u.email as clientEmail, s.title as scheduleTitle")
                    ->first();
                $turns = DB::table('anisystem_ai_messages as m')
                    ->where('m.conversationId', (int) $id)->where('m.deleteStatus', 1)
                    ->orderBy('m.id')
                    ->selectRaw('m.role, m.content, m.imagePath, m.imagePaths, m.creditsCharged, m.tokensIn, m.tokensOut, m.isRefusal, m.created_at, NULL as who')
                    ->get();
            }

            if (! $head) {
                return response()->json(['success' => false, 'message' => 'Conversation not found.'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'head' => [
                        'id' => (int) $head->id,
                        'kind' => $kind,
                        'title' => $head->title,
                        'clientName' => trim((string) $head->clientName) ?: null,
                        'clientEmail' => $head->clientEmail,
                        'scheduleTitle' => $head->scheduleTitle,
                        'startedAt' => $head->created_at
                            ? Carbon::parse($head->created_at)->timezone('Asia/Manila')->format('M j, Y g:i A') : null,
                    ],
                    'turns' => $turns->map(fn ($t) => [
                        'role' => $t->role,
                        'who' => trim((string) ($t->who ?? '')) ?: null,
                        'content' => (string) $t->content,
                        'hasPhoto' => filled($t->imagePath ?? null) || filled($t->imagePaths ?? null),
                        'credits' => (float) ($t->creditsCharged ?? 0),
                        'at' => $t->created_at
                            ? Carbon::parse($t->created_at)->timezone('Asia/Manila')->format('M j, g:i A') : null,
                    ])->values(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AniSystem AI conversation read failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Could not read that conversation.'], 500);
        }
    }
}
