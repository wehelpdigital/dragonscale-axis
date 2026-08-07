@extends('layouts.master')

@section('title') {{ $group->name }} — Groups @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
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
        del('/anisenso-community/groups/' + e.currentTarget.getAttribute('data-id'), '', () => setTimeout(() => window.location = '{{ route('anisenso-community.groups') }}', 800));
    });
    document.querySelectorAll('.btn-del-post').forEach((btn) => btn.addEventListener('click', () => {
        if (!confirm('Remove this post and its replies?')) return;
        del('/anisenso-community/posts/' + btn.getAttribute('data-id'), '', () => document.querySelector('[data-post="' + btn.getAttribute('data-id') + '"]')?.remove());
    }));
    document.querySelectorAll('.btn-del-reply').forEach((btn) => btn.addEventListener('click', () => {
        if (!confirm('Remove this reply?')) return;
        del('/anisenso-community/replies/' + btn.getAttribute('data-id'), '', () => document.querySelector('[data-reply="' + btn.getAttribute('data-id') + '"]')?.remove());
    }));
    // Restrict / un-restrict (soft moderation, shows a notice in AniSystem).
    document.querySelectorAll('.btn-restrict').forEach((btn) => btn.addEventListener('click', async () => {
        const on = btn.getAttribute('data-on') === '1';
        const type = btn.getAttribute('data-type'), id = btn.getAttribute('data-id');
        let reason = '';
        if (!on) { reason = prompt('Restrict this content across the community?\nOptional reason shown to moderators:', '') ; if (reason === null) return; }
        const body = new URLSearchParams({ restricted: on ? '0' : '1', reason });
        const res = await fetch('/anisenso-community/restrict/' + type + '/' + id, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const data = await res.json();
        if (data.success) { toastr.success(data.message); setTimeout(() => window.location.reload(), 700); }
        else toastr.error(data.message || 'Could not update.');
    }));
</script>
@endsection
