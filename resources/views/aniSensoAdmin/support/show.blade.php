@extends('layouts.master')

@section('title') Support — {{ $ticket->subject }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .support-thread { max-width: 760px; }
    .support-bubble { max-width: 82%; border-radius: 1rem; padding: .7rem 1rem; }
    .support-bubble .meta { font-size: .72rem; margin-bottom: .2rem; font-weight: 600; }
    .support-bubble .text { white-space: pre-line; word-break: break-word; }
    .support-row { display: flex; margin-bottom: .75rem; }
    .support-row.admin { justify-content: flex-end; }
    .support-row.client { justify-content: flex-start; }
    .support-bubble.admin { background: #556ee6; color: #fff; }
    .support-bubble.admin .meta { color: #dfe4fb; }
    .support-bubble.client { background: #f3f4f6; color: #1f2937; border: 1px solid #e5e7eb; }
    .support-bubble.client .meta { color: #6b7280; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') <a href="{{ route('anisenso-support.index') }}">Support</a> @endslot
        @slot('title') Ticket #{{ $ticket->id }} @endslot
    @endcomponent

    @php $badge = ['open' => 'bg-warning', 'answered' => 'bg-success', 'closed' => 'bg-secondary']; @endphp

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-1">
                        <h4 class="card-title text-dark mb-0">{{ $ticket->subject }}</h4>
                        <span class="badge {{ $badge[$ticket->status] ?? 'bg-secondary' }}">{{ ucfirst($ticket->status) }}</span>
                    </div>
                    <p class="text-secondary small mb-4">
                        <span class="badge bg-light text-dark">{{ \App\Models\SupportTicket::CATEGORIES[$ticket->category] ?? 'General' }}</span>
                        · opened {{ optional($ticket->created_at)->format('M j, Y g:i A') }}
                    </p>

                    <div class="support-thread mx-auto">
                        @foreach($messages as $m)
                            @php $isAdmin = $m->authorType === 'admin'; @endphp
                            <div class="support-row {{ $isAdmin ? 'admin' : 'client' }}">
                                <div class="support-bubble {{ $isAdmin ? 'admin' : 'client' }}">
                                    <div class="meta">
                                        {{ $isAdmin ? '🛟 ' . ($m->authorName ?: 'Support team') : ($m->authorName ?: 'Client') }}
                                        · {{ optional($m->created_at)->diffForHumans() }}
                                    </div>
                                    <div class="text">{{ $m->body }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($ticket->status !== 'closed')
                        <form method="POST" action="{{ route('anisenso-support.reply', ['id' => $ticket->id]) }}" class="mt-4 border-top pt-3">
                            @csrf
                            <label class="form-label text-dark">Reply to the client</label>
                            <textarea name="body" rows="4" maxlength="8000" class="form-control @error('body') is-invalid @enderror" placeholder="Type your answer…" required>{{ old('body') }}</textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="text-secondary small">The client is notified in their AniSystem bell.</span>
                                <button type="submit" class="btn btn-primary"><i class="bx bx-send"></i> Send reply</button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-secondary mt-4 mb-0">This ticket is closed. Reopen it to reply.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-dark mb-3">Client</h5>
                    @if($ticket->user)
                        <div class="fw-semibold text-dark">{{ $ticket->user->full_name }}</div>
                        <div class="text-secondary small">{{ $ticket->user->email }}</div>
                        @if($ticket->user->city || $ticket->user->province)
                            <div class="text-secondary small mt-1"><i class="bx bx-map"></i> {{ trim(($ticket->user->city ?? '') . ', ' . ($ticket->user->province ?? ''), ', ') }}</div>
                        @endif
                        <a href="{{ route('anisenso-community.members', ['id' => $ticket->user->id]) }}" class="btn btn-sm btn-outline-secondary mt-3">View member</a>
                    @else
                        <p class="text-secondary mb-0">Unknown client (#{{ $ticket->userId }})</p>
                    @endif

                    <hr>
                    <h6 class="text-dark">Actions</h6>
                    @if($ticket->status !== 'closed')
                        <form method="POST" action="{{ route('anisenso-support.close', ['id' => $ticket->id]) }}" onsubmit="return confirm('Close this ticket?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="bx bx-x-circle"></i> Close ticket</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('anisenso-support.reopen', ['id' => $ticket->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success w-100"><i class="bx bx-refresh"></i> Reopen ticket</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    @if(session('success')) toastr.success(@json(session('success'))); @endif
</script>
@endsection
