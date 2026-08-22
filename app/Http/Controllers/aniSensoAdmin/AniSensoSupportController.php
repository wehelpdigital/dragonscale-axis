<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * AniSenso admin — Support desk. Staff answer the tickets AniSystem clients
 * raise from their app. Replies land in the client's notification bell.
 *
 * Data lives in the shared `as_support_tickets` / `as_support_messages` tables.
 */
class AniSensoSupportController extends Controller
{
    /** All tickets, open ones first, with owner + message counts. */
    public function index(Request $request)
    {
        // ?id= asks for one ticket; without it this is the list.
        if ($request->filled('id')) {
            return $this->show((int) $request->query('id'));
        }
        $search = trim((string) $request->query('q'));
        $status = $request->query('status');

        $tickets = SupportTicket::active()
            ->with('user')
            ->withCount('messages')
            ->when(in_array($status, ['open', 'answered', 'closed'], true), fn ($w) => $w->where('status', $status))
            ->when($search !== '', function ($w) use ($search) {
                $w->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('firstName', 'like', "%{$search}%")
                                ->orWhere('lastName', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'answered' THEN 1 ELSE 2 END")
            ->orderByDesc('lastReplyAt')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $counts = SupportTicket::active()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('aniSensoAdmin.support.index', compact('tickets', 'search', 'status', 'counts'));
    }

    /** One ticket with its full thread. */
    public function show($id)
    {
        $ticket = SupportTicket::active()->with('user')->where('id', $id)->firstOrFail();
        $messages = $ticket->messages()->active()->orderBy('id')->get();

        return view('aniSensoAdmin.support.show', compact('ticket', 'messages'));
    }

    /** Post an admin reply, flag the ticket answered, and notify the client. */
    public function reply(Request $request, $id)
    {
        $ticket = SupportTicket::active()->where('id', $id)->firstOrFail();
        $data = $request->validate(['body' => 'required|string|max:8000']);

        $admin = Auth::user();
        $adminName = trim((string) ($admin->name ?? '')) ?: 'Support team';

        DB::transaction(function () use ($ticket, $data, $admin, $adminName) {
            SupportMessage::create([
                'ticketId' => $ticket->id,
                'authorType' => 'admin',
                'authorId' => (int) ($admin->id ?? 0),
                'authorName' => $adminName,
                'body' => $data['body'],
                'deleteStatus' => 1,
            ]);
            $ticket->update(['status' => 'answered', 'lastReplyAt' => now()]);

            // Ping the client's AniSystem notification bell (deep-links to the ticket).
            DB::table('anisystem_notifications')->insert([
                'userId' => $ticket->userId,
                'type' => 'support',
                'title' => 'Support replied to your ticket',
                'body' => Str::limit($ticket->subject, 90),
                'url' => '/app/support/' . $ticket->id,
                'actorUserId' => null,
                'croppingScheduleId' => null,
                'readAt' => null,
                'deleteStatus' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('anisenso-support.show', $ticket->id)
            ->with('success', 'Reply sent — the client has been notified.');
    }

    public function close($id)
    {
        $ticket = SupportTicket::active()->where('id', $id)->firstOrFail();
        $ticket->update(['status' => 'closed']);

        return redirect()->route('anisenso-support.show', $ticket->id)->with('success', 'Ticket closed.');
    }

    public function reopen($id)
    {
        $ticket = SupportTicket::active()->where('id', $id)->firstOrFail();
        $ticket->update(['status' => 'open']);

        return redirect()->route('anisenso-support.show', $ticket->id)->with('success', 'Ticket reopened.');
    }
}
