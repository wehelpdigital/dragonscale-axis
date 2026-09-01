@extends('layouts.master')

@section('title') {{ $group->name }} — Groups @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

{{-- The mode layer, last in the head so it answers after the rules
     above it. --}}
@include('aniSensoAdmin.partials.dark')

@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Groups @endslot
        @slot('li_2_link') {{ route('anisenso-community.groups') }} @endslot
        @slot('title') {{ $group->name }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-8 offset-xl-2">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h4 class="mb-1 text-dark">{{ $group->name }}</h4>
                        @if($group->description)<p class="text-secondary mb-1">{{ $group->description }}</p>@endif
                        <p class="text-secondary small mb-0">Owner: {{ optional($group->creator)->full_name ?: '—' }} · {{ $memberCount }} members</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-soft-danger" id="delGroupBtn" data-id="{{ $group->id }}">Delete group</button>
                </div>
            </div>

            {{-- THE DOOR.
                 Whether the room is listed at all, and how somebody gets in:
                 walk in, know the password, or be let in. The password is
                 kept encrypted rather than hashed, because the organiser has
                 to be able to read it back to tell the next person — so it
                 can be shown here, and a blank box means "leave it alone"
                 rather than "erase it". --}}
            <div class="card"><div class="card-body">
                <h6 class="text-dark mb-2"><i class="bx bx-lock-alt me-1"></i>The door</h6>
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-dark mb-1">Listed</label>
                        <select class="form-select form-select-sm" id="grpPrivacy">
                            <option value="public" @selected(($group->privacy ?: 'public') === 'public')>Public — anyone can find it</option>
                            <option value="private" @selected($group->privacy === 'private')>Private — only members see it</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-dark mb-1">Getting in</label>
                        <select class="form-select form-select-sm" id="grpJoinMode">
                            <option value="open" @selected(($group->joinMode ?: 'open') === 'open')>Open — walk in</option>
                            <option value="password" @selected($group->joinMode === 'password')>Password</option>
                            <option value="approval" @selected($group->joinMode === 'approval')>Somebody says yes</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="grpPasswordWrap" style="{{ $group->joinMode === 'password' ? '' : 'display:none;' }}">
                        <label class="form-label text-dark mb-1">Password</label>
                        <input type="text" class="form-control form-control-sm" id="grpPassword"
                               value="{{ $group->joinMode === 'password' ? ($group->joinPassword ?? '') : '' }}"
                               placeholder="Leave blank to keep it">
                    </div>
                    <div class="col-md-3 text-end">
                        <button type="button" class="btn btn-primary btn-sm" id="grpDoorSave"><i class="bx bx-save me-1"></i>Save the door</button>
                    </div>
                </div>
            </div>

            {{-- Standing at the door. Only a room that asks for approval has
                 a queue, and an answered one stays on the list so it is clear
                 what was decided. --}}
            @if($requests->count())
            <div class="card"><div class="card-body">
                <h6 class="text-dark mb-2"><i class="bx bx-user-check me-1"></i>At the door</h6>
                @foreach($requests as $r)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 gap-2" data-req="{{ $r->id }}">
                        <div>
                            <a href="{{ route('anisenso-community.members', ['id' => $r->userId]) }}" class="text-dark fw-semibold">
                                {{ trim($r->firstName . ' ' . $r->lastName) ?: 'Someone' }}
                            </a>
                            <div class="text-secondary" style="font-size:11.5px;">asked {{ $r->created_at }}</div>
                        </div>
                        @if($r->status === 'pending')
                            <span class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-success js-req" data-id="{{ $r->id }}" data-verdict="approved">Let in</button>
                                <button class="btn btn-sm btn-outline-secondary js-req" data-id="{{ $r->id }}" data-verdict="declined">Turn away</button>
                            </span>
                        @else
                            <span class="badge bg-light text-dark border">{{ $r->status }}</span>
                        @endif
                    </div>
                @endforeach
            </div></div>
            @endif

            {{-- Who is in. A removed member keeps their row with the reason on
                 it, because "why is she not in here any more" is a question
                 somebody eventually asks. --}}
            <div class="card"><div class="card-body">
                <h6 class="text-dark mb-2"><i class="bx bx-group me-1"></i>In the room <span class="text-secondary fw-normal">{{ $memberCount }}</span></h6>
                @forelse($roster as $m)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-1 gap-2" data-member="{{ $m->id }}">
                        <div class="{{ $m->deleteStatus ? '' : 'text-secondary' }}">
                            <a href="{{ route('anisenso-community.members', ['id' => $m->userId]) }}" class="{{ $m->deleteStatus ? 'text-dark' : 'text-secondary' }}">
                                {{ trim($m->firstName . ' ' . $m->lastName) ?: 'Someone' }}
                            </a>
                            @if($m->role && $m->role !== 'member')
                                <span class="badge bg-primary ms-1" style="font-size:10px;">{{ $m->role }}</span>
                            @endif
                            @unless($m->deleteStatus)
                                <div style="font-size:11px;">out since {{ $m->removedAt }} — {{ $m->removedReason ?: 'no reason written' }}</div>
                            @endunless
                        </div>
                        @if($m->deleteStatus)
                            <button class="btn btn-sm btn-link text-danger p-0 js-member-out" data-id="{{ $m->id }}" title="Take out of the room">
                                <i class="bx bx-user-x"></i>
                            </button>
                        @endif
                    </div>
                @empty
                    <p class="text-secondary small mb-0">Nobody in this room.</p>
                @endforelse
            </div></div>

            {{-- The room's chat, which is not the same thing as its posts. --}}
            @if($chat->count())
            <div class="card"><div class="card-body">
                <h6 class="text-dark mb-2"><i class="bx bx-message-square-dots me-1"></i>Room chat</h6>
                @foreach($chat as $m)
                    <div class="d-flex justify-content-between align-items-start border-bottom py-1 gap-2" data-msg="{{ $m->id }}">
                        <div class="min-w-0">
                            <span class="fw-semibold text-dark" style="font-size:12.5px;">{{ trim($m->firstName . ' ' . $m->lastName) ?: 'Someone' }}</span>
                            <span class="text-secondary" style="font-size:11px;">{{ $m->created_at }}</span>
                            @if($m->body)<div style="font-size:12.5px; white-space:pre-wrap;">{{ $m->body }}</div>@endif
                            @if($m->imagePath)
                                <a href="{{ \App\Support\AnisystemMedia::url($m->imagePath) }}" target="_blank" class="small">a picture</a>
                            @endif
                        </div>
                        <button class="btn btn-sm btn-link text-danger p-0 js-msg-del" data-id="{{ $m->id }}"><i class="bx bx-trash"></i></button>
                    </div>
                @endforeach
            </div></div>
            @endif

            @forelse($posts as $post)
                <div class="card" data-post="{{ $post->id }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <strong class="text-dark">{{ optional($post->author)->full_name ?: 'Member' }}</strong>
                                <span class="text-secondary small ms-1">{{ $post->created_at?->diffForHumans() }}</span>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm {{ $post->isRestricted ? 'text-warning' : 'text-secondary' }} btn-restrict" data-type="post" data-id="{{ $post->id }}" data-on="{{ $post->isRestricted ? 1 : 0 }}" title="{{ $post->isRestricted ? 'Lift restriction' : 'Restrict this post' }}"><i class="bx {{ $post->isRestricted ? 'bx-lock-alt' : 'bx-lock-open-alt' }}"></i></button>
                                <button type="button" class="btn btn-sm text-danger btn-del-post" data-id="{{ $post->id }}" title="Remove post"><i class="bx bx-trash"></i></button>
                            </div>
                        </div>
                        @if($post->isRestricted)<div class="badge bg-warning text-dark mb-1">Restricted in community</div>@endif
                        @if($post->title)<h5 class="text-dark mt-1 mb-1">{{ $post->title }}</h5>@endif
                        <p class="text-dark mb-2" style="white-space:pre-line;">{{ $post->body }}</p>
                        @if($post->imagePath)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->imagePath) }}" class="img-fluid rounded mb-2" style="max-height:280px;" alt="attachment">
                        @endif

                        @if($post->replies->count())
                            <div class="border-top pt-2 mt-2">
                                @foreach($post->replies->sortBy('id') as $reply)
                                    <div class="d-flex justify-content-between align-items-start mb-1" data-reply="{{ $reply->id }}">
                                        <div class="small"><strong class="text-dark">{{ optional($reply->author)->full_name ?: 'Member' }}</strong>
                                            <span class="text-secondary">· {{ $reply->created_at?->diffForHumans() }}</span>
                                            <div class="text-dark" style="white-space:pre-line;">{{ $reply->body }}</div>
                                        </div>
                                        <span class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm {{ $reply->isRestricted ? 'text-warning' : 'text-secondary' }} btn-restrict" data-type="reply" data-id="{{ $reply->id }}" data-on="{{ $reply->isRestricted ? 1 : 0 }}" title="{{ $reply->isRestricted ? 'Lift restriction' : 'Restrict this reply' }}"><i class="bx {{ $reply->isRestricted ? 'bx-lock-alt' : 'bx-lock-open-alt' }}"></i></button>
                                            <button type="button" class="btn btn-sm text-danger btn-del-reply" data-id="{{ $reply->id }}" title="Remove reply"><i class="bx bx-x"></i></button>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="card"><div class="card-body text-secondary text-center">No posts in this group.</div></div>
            @endforelse

            <div>{{ $posts->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
@endsection

@section('script')
<script>
const GRP_TOKEN = '{{ csrf_token() }}';
const GRP_ID = {{ $group->id }};

// A password box only means anything on a room that asks for one.
$('#grpJoinMode').on('change', function () {
    $('#grpPasswordWrap').toggle($(this).val() === 'password');
});

$('#grpDoorSave').on('click', function () {
    const $b = $(this).prop('disabled', true);
    $.post('{{ url('/anisenso-community-groups-door') }}?id=' + GRP_ID, {
        _token: GRP_TOKEN,
        privacy: $('#grpPrivacy').val(),
        joinMode: $('#grpJoinMode').val(),
        joinPassword: $('#grpPassword').val(),
    }).done((res) => {
        res.success ? toastr.success(res.message) : toastr.error(res.message);
    }).fail((xhr) => toastr.error(xhr.responseJSON?.message || 'That did not save.'))
      .always(() => $b.prop('disabled', false));
});

$(document).on('click', '.js-req', function () {
    const row = $(this).closest('[data-req]');
    $.post('{{ url('/anisenso-community-groups-request') }}?id=' + $(this).data('id'),
        { _token: GRP_TOKEN, verdict: $(this).data('verdict') })
        .done((res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            setTimeout(() => window.location.reload(), 600);
        })
        .fail((xhr) => toastr.error(xhr.responseJSON?.message || 'That did not work.'));
});

function grpCut(url, ask, row) {
    if (!confirm(ask)) return;
    $.ajax({ url, type: 'DELETE', data: { _token: GRP_TOKEN } })
        .done((res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            $(row).fadeOut(180, function () { $(this).remove(); });
        })
        .fail((xhr) => toastr.error(xhr.responseJSON?.message || 'That did not work.'));
}
$(document).on('click', '.js-member-out', function () {
    grpCut('{{ url('/anisenso-community-groups-member') }}?id=' + $(this).data('id'),
        'Take them out of this room?', $(this).closest('[data-member]'));
});
$(document).on('click', '.js-msg-del', function () {
    grpCut('{{ url('/anisenso-community-groups-message') }}?id=' + $(this).data('id'),
        'Remove this message?', $(this).closest('[data-msg]'));
});
</script>
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    const CSRF = "{{ csrf_token() }}";
    async function del(url, okMsg, onOk) {
        const res = await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
        const data = await res.json();
        if (data.success) { toastr.success(data.message); onOk(); } else toastr.error(data.message || 'Could not remove.');
    }
    document.getElementById('delGroupBtn')?.addEventListener('click', (e) => {
        if (!confirm('Delete this group and all its posts?')) return;
        del('/anisenso-community-groups?id=' + e.currentTarget.getAttribute('data-id'), '', () => setTimeout(() => window.location = '{{ route('anisenso-community.groups') }}', 800));
    });
    document.querySelectorAll('.btn-del-post').forEach((btn) => btn.addEventListener('click', () => {
        if (!confirm('Remove this post and its replies?')) return;
        del('/anisenso-community-posts?id=' + btn.getAttribute('data-id'), '', () => document.querySelector('[data-post="' + btn.getAttribute('data-id') + '"]')?.remove());
    }));
    document.querySelectorAll('.btn-del-reply').forEach((btn) => btn.addEventListener('click', () => {
        if (!confirm('Remove this reply?')) return;
        del('/anisenso-community-replies?id=' + btn.getAttribute('data-id'), '', () => document.querySelector('[data-reply="' + btn.getAttribute('data-id') + '"]')?.remove());
    }));
    // Restrict / un-restrict (soft moderation, shows a notice in AniSystem).
    document.querySelectorAll('.btn-restrict').forEach((btn) => btn.addEventListener('click', async () => {
        const on = btn.getAttribute('data-on') === '1';
        const type = btn.getAttribute('data-type'), id = btn.getAttribute('data-id');
        let reason = '';
        if (!on) { reason = prompt('Restrict this content across the community?\nOptional reason shown to moderators:', '') ; if (reason === null) return; }
        const body = new URLSearchParams({ restricted: on ? '0' : '1', reason });
        const res = await fetch('/anisenso-community-restrict?type=' + type + '&id=' + id, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const data = await res.json();
        if (data.success) { toastr.success(data.message); setTimeout(() => window.location.reload(), 700); }
        else toastr.error(data.message || 'Could not update.');
    }));
</script>
@endsection
