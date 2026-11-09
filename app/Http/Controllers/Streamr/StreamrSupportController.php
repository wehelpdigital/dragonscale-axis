<?php

namespace App\Http\Controllers\Streamr;

use App\Models\StreamrClient;
use App\Models\StreamrDeskMessage;
use App\Models\StreamrDeskTicket;
use App\Services\Streamr\StreamrBadges;
use App\Services\Streamr\StreamrDesk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Streamr > Support: the tickets Streamr's clients open, answered from here
 * or from Streamr's own admin. A reply here is plain text, kept with origin
 * "mother" and handed to Streamr (doorbell after the response, the feed as
 * the safety net); Streamr emails the client. Every form posts back to
 * streamr-support.index?id={ticket}.
 */
class StreamrSupportController extends StreamrController
{
    private const PER_PAGE = 20;

    public function __construct(private StreamrDesk $desk) {}

    /** The list (?status=, ?q=, ?client=), or one ticket (?id=). */
    public function index(Request $request, $id = null)
    {
        if (! $this->installed()) {
            return $this->notInstalled('Support');
        }

        $ticketId = $this->id($id ?? $request->query('id'));
        if ($ticketId > 0) {
            return $this->ticket($ticketId);
        }

        $status = (string) $request->query('status', 'open');
        $status = in_array($status, ['open', 'answered', 'closed', 'all'], true) ? $status : 'open';
        $q = trim((string) $request->query('q', ''));
        $client = $this->id($request->query('client'));

        $query = StreamrDeskTicket::active()
            ->when($status !== 'all', fn ($w) => $w->where('status', $status))
            ->when($client > 0, fn ($w) => $w->where('streamrUserId', $client))
            ->when($q !== '', function ($w) use ($q) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
                $w->where(fn ($x) => $x->where('subject', 'like', $like)->orWhere('ticketNumber', 'like', $like)
                    ->orWhere('clientName', 'like', $like)->orWhere('clientEmail', 'like', $like));
            })
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'answered' THEN 1 ELSE 2 END")
            ->orderByDesc('lastMessageAt')
            ->orderByDesc('id');

        $counts = StreamrDeskTicket::active()->when($client > 0, fn ($w) => $w->where('streamrUserId', $client))
            ->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status')->all();
        $counts['all'] = array_sum($counts);

        return view('streamr.support.index', [
            'tickets' => $query->paginate(self::PER_PAGE)->withQueryString(),
            'counts' => $counts,
            'status' => $status,
            'q' => $q,
            'client' => $client > 0 ? StreamrClient::active()->where('streamrUserId', $client)->first() : null,
            'clientId' => $client,
        ]);
    }

    /** POST /streamr-support-reply?id= : body (≤ 8,000 characters), internal (a note for admins only). */
    public function reply(Request $request, $id = null)
    {
        if (! $this->installed()) {
            return $this->notInstalled('Support');
        }
        $ticket = StreamrDeskTicket::active()->find($this->id($id ?? $request->query('id')));
        if (! $ticket) {
            return redirect()->route('streamr-support.index')->with('error', 'That ticket was not found.');
        }

        $validator = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:8000'],
            'internal' => ['nullable', 'boolean'],
        ], [
            'body.required' => 'Write the reply first.',
            'body.max' => 'A reply can be up to 8,000 characters.',
        ]);
        if ($validator->fails()) {
            return redirect()->route('streamr-support.index', ['id' => $ticket->id])->withErrors($validator)->withInput();
        }

        $internal = $request->boolean('internal');
        $this->desk->reply($ticket, trim((string) $request->input('body')), $internal, Auth::user());
        StreamrBadges::forget();

        return redirect()->route('streamr-support.index', ['id' => $ticket->id])
            ->with('success', $internal ? 'Internal note saved (the client does not see it).' : 'Reply sent. Streamr emails the client.');
    }

    /** POST /streamr-support-status?id=&status=open|closed */
    public function setStatus(Request $request, $id = null, $status = null)
    {
        if (! $this->installed()) {
            return $this->notInstalled('Support');
        }
        $ticket = StreamrDeskTicket::active()->find($this->id($id ?? $request->query('id')));
        if (! $ticket) {
            return redirect()->route('streamr-support.index')->with('error', 'That ticket was not found.');
        }
        $status = (string) ($status ?? $request->query('status', $request->input('status', '')));
        if (! in_array($status, ['open', 'closed'], true)) {
            return redirect()->route('streamr-support.index', ['id' => $ticket->id])->with('error', 'A ticket can be closed or reopened.');
        }

        $this->desk->setStatus($ticket, $status, Auth::user());
        StreamrBadges::forget();

        return redirect()->route('streamr-support.index', ['id' => $ticket->id])
            ->with('success', $status === 'closed' ? 'Ticket closed. Streamr is told.' : 'Ticket reopened. Streamr is told.');
    }

    private function ticket(int $id)
    {
        $ticket = StreamrDeskTicket::active()->find($id);
        if (! $ticket) {
            return redirect()->route('streamr-support.index')->with('error', 'That ticket was not found.');
        }

        $this->desk->markRead($ticket);
        StreamrBadges::forget();

        $messages = StreamrDeskMessage::active()->where('ticketId', $ticket->id)->orderBy('id')->get();
        $client = StreamrClient::active()->where('streamrUserId', $ticket->streamrUserId)->first();

        return view('streamr.support.show', [
            'ticket' => $ticket,
            'messages' => $messages,
            'client' => $client,
            'streamrTicketUrl' => $this->streamrUrl('/admin/support/' . $ticket->uuid),
        ]);
    }
}
