@extends('layouts.master')

@section('title') Streamr Support — {{ $ticket->ticketNumber }} @endsection

@section('css')
@include('streamr._styles', ['tables' => false])
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Streamr @endslot
        @slot('li_2') Support @endslot
        @slot('li_2_link') {{ route('streamr-support.index') }} @endslot
        @slot('title') {{ $ticket->ticketNumber }} @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bx bx-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bx bx-error-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif

    @php
        $statusBadges = \App\Models\StreamrDeskTicket::STATUS_BADGES;
        $statusLabels = \App\Models\StreamrDeskTicket::STATUSES;
        $priorityBadges = \App\Models\StreamrDeskTicket::PRIORITY_BADGES;
        $priorityLabels = \App\Models\StreamrDeskTicket::PRIORITIES;
    @endphp

    <div id="st">
        <div class="row">
            <div class="col-xl-8">
                <div class="card"><div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">
                        <h4 class="card-title text-dark mb-0">{{ $ticket->subject }}</h4>
                        <span class="badge {{ $statusBadges[$ticket->status] ?? 'bg-secondary' }}">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
                    </div>
                    <p class="text-secondary small mb-4">
                        <span class="st-mono">{{ $ticket->ticketNumber }}</span>
                        · <span class="badge bg-light text-dark">{{ $ticket->categoryLabel() }}</span>
                        · <span class="badge {{ $priorityBadges[$ticket->priority] ?? 'bg-secondary' }}">{{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</span>
                        · opened {{ $ticket->created_at ? $ticket->created_at->format('M j, Y g:i A') : '—' }}
                    </p>

                    <div class="thread">
                        @forelse ($messages as $m)
                            @if ($m->kind === 'status')
                                @php
                                    $changedBy = (string) ($m->meta['changed_by'] ?? '');
                                    $who = $m->origin === 'mother'
                                        ? ($m->authorName ?: 'an admin') . ' (here)'
                                        : (str_contains($changedBy, ':') ? \Illuminate\Support\Str::afterLast($changedBy, ':') : ($changedBy !== '' ? $changedBy : 'Streamr'));
                                @endphp
                                <div class="status-line">
                                    {{ ucfirst((string) ($m->meta['status'] ?? 'changed')) }}
                                    by {{ $who }}
                                    · {{ $m->created_at ? $m->created_at->format('M j g:i A') : '' }}
                                    @if ($m->origin === 'mother')
                                        · {!! $m->pulledAt ? '<span class="ok">Delivered to Streamr ✓</span>' : '<span class="text-secondary">waiting for Streamr</span>' !!}
                                    @endif
                                </div>
                            @else
                                @php
                                    $side = $m->authorType === 'client' ? 'client' : 'admin';
                                    $look = $m->isInternal ? 'internal' : $side;
                                @endphp
                                <div class="msg-row {{ $side }}">
                                    <div class="bubble {{ $look }}">
                                        <div class="meta">
                                            @if ($m->isInternal)<i class="bx bx-lock-alt"></i> Internal note ·@endif
                                            {{ $m->authorName ?: ($side === 'client' ? 'Client' : 'Support team') }}
                                            @if ($m->origin === 'mother') · DS AXIS @elseif ($side === 'admin') · Streamr admin @endif
                                            · {{ $m->created_at ? $m->created_at->format('M j g:i A') : '' }}
                                        </div>
                                        <div class="text">{!! nl2br(e($m->plainBody())) !!}</div>
                                        @if (! empty($m->attachments))
                                            <div class="mt-2 d-flex flex-wrap gap-2">
                                                @foreach ($m->attachments as $a)
                                                    @if (is_array($a) && preg_match('#^https?://#i', (string) ($a['url'] ?? '')))
                                                        <a href="{{ $a['url'] }}" target="_blank" rel="noopener noreferrer" class="small"><i class="bx bx-paperclip"></i> {{ $a['name'] ?? 'attachment' }}</a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                        @if ($m->origin === 'mother')
                                            <div class="mt-1 small">{!! $m->pulledAt ? 'Delivered to Streamr ✓' : 'Waiting for Streamr…' !!}</div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @empty
                            <p class="text-secondary text-center">No messages yet.</p>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('streamr-support.reply', ['id' => $ticket->id]) }}" class="mt-4 border-top pt-3">
                        @csrf
                        <label class="form-label text-dark" for="stReplyBody">Reply</label>
                        <textarea name="body" id="stReplyBody" rows="4" maxlength="8000" class="form-control @error('body') is-invalid @enderror" placeholder="Type your answer…" required>{{ old('body') }}</textarea>
                        @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="internal" value="1" id="stReplyInternal" @checked(old('internal'))>
                                <label class="form-check-label text-dark" for="stReplyInternal">Internal note (not sent to the client)</label>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bx bx-send me-1"></i>Send</button>
                        </div>
                        @if ($ticket->status === 'closed')
                            <div class="form-text">The ticket is closed; a reply opens the conversation again as answered.</div>
                        @endif
                    </form>
                </div></div>
            </div>

            <div class="col-xl-4">
                <div class="card"><div class="card-body">
                    <h5 class="card-title text-dark mb-3">Client</h5>
                    @if ($client)
                        <div class="fw-semibold text-dark">{{ $client->fullName() }}</div>
                        <div class="text-secondary small">{{ $client->email }}</div>
                        @if ($client->storeName)<div class="text-secondary small mt-1"><i class="bx bx-store"></i> {{ $client->storeName }}@if ($client->storeHandle) ({{ '@' . $client->storeHandle }})@endif</div>@endif
                        <div class="mt-2">
                            <span class="badge {{ \App\Models\StreamrClient::STATE_BADGES[$client->computedState()] }}">{{ \App\Models\StreamrClient::STATES[$client->computedState()] }}</span>
                            @if ($client->planName)<span class="text-secondary small ms-1">{{ $client->planName }}</span>@endif
                        </div>
                        <a href="{{ route('streamr-clients.index', ['open' => $client->id]) }}" class="btn btn-sm btn-outline-secondary mt-3">Client details</a>
                    @else
                        <div class="fw-semibold text-dark">{{ $ticket->clientName ?: 'Streamr user #' . $ticket->streamrUserId }}</div>
                        <div class="text-secondary small">{{ $ticket->clientEmail }}</div>
                        @if ($ticket->storeName)<div class="text-secondary small mt-1"><i class="bx bx-store"></i> {{ $ticket->storeName }}</div>@endif
                    @endif
                    <a href="{{ route('streamr-support.index', ['client' => $ticket->streamrUserId, 'status' => 'all']) }}" class="d-block small mt-3">All of this client's tickets</a>
                    @if ($streamrTicketUrl)
                        <a href="{{ $streamrTicketUrl }}" class="d-block small mt-1" target="_blank" rel="noopener">Open in Streamr admin <i class="bx bx-link-external"></i></a>
                    @endif

                    <hr>
                    <h6 class="text-dark">Status</h6>
                    @if ($ticket->status !== 'closed')
                        <form method="POST" action="{{ route('streamr-support.status', ['id' => $ticket->id, 'status' => 'closed']) }}" onsubmit="return confirm('Close this ticket?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="bx bx-x-circle me-1"></i>Close ticket</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('streamr-support.status', ['id' => $ticket->id, 'status' => 'open']) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success w-100"><i class="bx bx-refresh me-1"></i>Reopen ticket</button>
                        </form>
                    @endif
                    @if ($ticket->statusChangedBy)
                        <p class="sub mt-2 mb-0">Last change: {{ $ticket->statusChangedBy }}{{ $ticket->statusChangedAt ? ', ' . $ticket->statusChangedAt->format('M j g:i A') : '' }}</p>
                    @endif
                </div></div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
@endsection
