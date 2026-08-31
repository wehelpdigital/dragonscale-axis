@extends('layouts.master')

@section('title') {{ $member->full_name }} — Members @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style> .avatar-lg { width:64px;height:64px;border-radius:50%;background:#556ee6;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:22px; } </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Members @endslot
        @slot('li_2_link') {{ route('anisenso-community.members') }} @endslot
        @slot('title') {{ $member->full_name }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="avatar-lg">{{ $member->initials ?: '?' }}</span>
                        <div>
                            <h5 class="mb-0 text-dark">{{ $member->full_name }}</h5>
                            <div class="text-secondary small">{{ $member->email }}</div>
                            @if($member->location)<div class="text-secondary small"><i class="bx bx-map"></i> {{ $member->location }}</div>@endif
                        </div>
                    </div>
                    @if($member->bio)<p class="text-dark small mb-2" style="white-space:pre-line;">{{ $member->bio }}</p>@endif
                    <div class="d-flex gap-3 small text-secondary">
                        <span>{{ $plans->count() }} shared plans</span>
                        <span>{{ collect($people['connections'])->where('status', 'accepted')->count() }} co-farmers</span>
                        <span>{{ $groupList->count() }} groups</span>
                    </div>
                </div>
            </div>

            {{-- Shared plans --}}
            <div class="card"><div class="card-body">
                <h6 class="text-dark">Shared plans</h6>
                @forelse($plans as $plan)
                    <a href="{{ route('anisenso-community.plans', ['id' => $plan->id]) }}" class="d-block small border-bottom py-1 text-dark">{{ $plan->title }}</a>
                @empty <p class="text-secondary small mb-0">None.</p>@endforelse
            </div></div>

            {{-- Groups --}}
            <div class="card"><div class="card-body">
                <h6 class="text-dark">Groups</h6>
                @forelse($groupList as $g)
                    <a href="{{ route('anisenso-community.groups', ['id' => $g->id]) }}" class="badge bg-light text-dark mb-1 d-inline-block">{{ $g->name }}</a>
                @empty <p class="text-secondary small mb-0">None.</p>@endforelse
            </div></div>

            {{-- Co-farmers. A handshake has a direction and a state: who
                 asked, and whether the other has answered. A list of accepted
                 names hid both, and hid the requests nobody had replied to. --}}
            <div class="card"><div class="card-body">
                <h6 class="text-dark">Co-farmers</h6>
                @forelse($people['connections'] as $c)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-1 gap-2">
                        <a href="{{ route('anisenso-community.members', ['id' => $c['whoId']]) }}" class="small text-dark">
                            {{ $c['who'] }}
                        </a>
                        <span class="d-flex align-items-center gap-2">
                            @if($c['status'] === 'pending')
                                <span class="badge bg-warning text-dark" style="font-size:10px;">
                                    {{ $c['theyAsked'] ? 'they asked' : 'waiting on them' }}
                                </span>
                            @endif
                            <button class="btn btn-sm btn-link text-danger p-0 js-cf-cut" data-id="{{ $c['id'] }}"
                                    title="Sever this link"><i class="bx bx-unlink"></i></button>
                        </span>
                    </div>
                @empty <p class="text-secondary small mb-0">Nobody yet.</p>@endforelse
            </div></div>

            {{-- Follows are one-sided, so they are counted rather than paired. --}}
            <div class="card"><div class="card-body">
                <h6 class="text-dark">Follows</h6>
                <p class="text-secondary small mb-1">
                    Following {{ count($people['following']) }} · followed by {{ count($people['followers']) }}
                </p>
                @foreach(array_slice($people['following'], 0, 8) as $f)
                    <span class="badge bg-light text-dark border me-1 mb-1">{{ $f['who'] }}</span>
                @endforeach
            </div></div>
        </div>

        {{-- Wall --}}
        <div class="col-xl-8">
            <div class="card"><div class="card-body">
                <h5 class="text-dark mb-3">{{ $member->firstName }}'s wall</h5>
                @forelse($wallPosts as $post)
                    <div class="border rounded p-3 mb-2" data-wall-post="{{ $post->id }}">
                        <div class="d-flex justify-content-between">
                            <div><strong class="text-dark">{{ optional($post->author)->full_name ?: 'Member' }}</strong>
                                <span class="text-secondary small ms-1">{{ $post->created_at?->diffForHumans() }}</span></div>
                            <span class="d-flex gap-1">
                                <button type="button" class="btn btn-sm {{ $post->isRestricted ? 'text-warning' : 'text-secondary' }} btn-restrict" data-type="wall-post" data-id="{{ $post->id }}" data-on="{{ $post->isRestricted ? 1 : 0 }}" title="{{ $post->isRestricted ? 'Lift restriction' : 'Restrict this post' }}"><i class="bx {{ $post->isRestricted ? 'bx-lock-alt' : 'bx-lock-open-alt' }}"></i></button>
                                <button type="button" class="btn btn-sm text-danger btn-del-wall-post" data-id="{{ $post->id }}" title="Remove post"><i class="bx bx-trash"></i></button>
                            </span>
                        </div>
                        @if($post->isRestricted)<span class="badge bg-warning text-dark mb-1">Restricted</span>@endif
                        @if($post->body)<p class="text-dark mb-2 mt-1" style="white-space:pre-line;">{{ $post->body }}</p>@endif
                        @if($post->imagePath)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->imagePath) }}" class="img-fluid rounded mb-2" style="max-height:260px;" alt="photo">@endif
                        @foreach($post->comments->sortBy('id') as $comment)
                            <div class="d-flex justify-content-between align-items-start ms-3 mt-1" data-wall-comment="{{ $comment->id }}">
                                <div class="small"><strong class="text-dark">{{ optional($comment->author)->full_name ?: 'Member' }}</strong>
                                    <span class="text-secondary">· {{ $comment->created_at?->diffForHumans() }}</span>
                                    <div class="text-dark" style="white-space:pre-line;">{{ $comment->body }}</div></div>
                                <span class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm {{ $comment->isRestricted ? 'text-warning' : 'text-secondary' }} btn-restrict" data-type="wall-comment" data-id="{{ $comment->id }}" data-on="{{ $comment->isRestricted ? 1 : 0 }}" title="{{ $comment->isRestricted ? 'Lift restriction' : 'Restrict this comment' }}"><i class="bx {{ $comment->isRestricted ? 'bx-lock-alt' : 'bx-lock-open-alt' }}"></i></button>
                                    <button type="button" class="btn btn-sm text-danger btn-del-wall-comment" data-id="{{ $comment->id }}" title="Remove comment"><i class="bx bx-x"></i></button>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="text-secondary mb-0">No wall posts.</p>
                @endforelse
                <div>{{ $wallPosts->links('pagination::bootstrap-4') }}</div>
            </div></div>
        </div>
    </div>
@endsection

@section('script')
<script>
/* Severing from a member's page is the same act as severing from the
   co-farmer list, so it is the same endpoint and the same question. */
$(document).on('click', '.js-cf-cut', function () {
    if (!confirm('Sever this co-farmer link? Both sides lose it.')) return;
    const row = $(this).closest('.d-flex');
    $.ajax({
        url: '{{ url('/anisenso-community-cofarmers') }}?id=' + $(this).data('id'),
        type: 'DELETE', data: { _token: '{{ csrf_token() }}' },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            row.fadeOut(180, function () { $(this).remove(); });
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'That did not work.')
    });
});
</script>
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    const CSRF = "{{ csrf_token() }}";
    async function del(url, sel, id) {
        if (!confirm('Remove this?')) return;
        const res = await fetch(url, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
        const data = await res.json();
        if (data.success) { toastr.success(data.message); document.querySelector('[' + sel + '="' + id + '"]')?.remove(); }
        else toastr.error(data.message || 'Could not remove.');
    }
    document.querySelectorAll('.btn-del-wall-post').forEach((b) => b.addEventListener('click', () => del('/anisenso-community-wall-posts?id=' + b.getAttribute('data-id'), 'data-wall-post', b.getAttribute('data-id'))));
    document.querySelectorAll('.btn-del-wall-comment').forEach((b) => b.addEventListener('click', () => del('/anisenso-community-wall-comments?id=' + b.getAttribute('data-id'), 'data-wall-comment', b.getAttribute('data-id'))));
    document.querySelectorAll('.btn-restrict').forEach((btn) => btn.addEventListener('click', async () => {
        const on = btn.getAttribute('data-on') === '1';
        let reason = '';
        if (!on) { reason = prompt('Restrict this content across the community?\nOptional reason:', ''); if (reason === null) return; }
        const body = new URLSearchParams({ restricted: on ? '0' : '1', reason });
        const res = await fetch('/anisenso-community-restrict?type=' + btn.getAttribute('data-type') + '&id=' + btn.getAttribute('data-id'), { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const data = await res.json();
        if (data.success) { toastr.success(data.message); setTimeout(() => window.location.reload(), 700); }
        else toastr.error(data.message || 'Could not update.');
    }));
</script>
@endsection
