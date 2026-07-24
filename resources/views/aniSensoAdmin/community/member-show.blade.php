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
                        <span>{{ $connections->count() }} connections</span>
                        <span>{{ $groupList->count() }} groups</span>
                    </div>
                </div>
            </div>

            {{-- Shared plans --}}
            <div class="card"><div class="card-body">
                <h6 class="text-dark">Shared plans</h6>
                @forelse($plans as $plan)
                    <a href="{{ route('anisenso-community.plans.show', $plan->id) }}" class="d-block small border-bottom py-1 text-dark">{{ $plan->title }}</a>
                @empty <p class="text-secondary small mb-0">None.</p>@endforelse
            </div></div>

            {{-- Groups --}}
            <div class="card"><div class="card-body">
                <h6 class="text-dark">Groups</h6>
                @forelse($groupList as $g)
                    <a href="{{ route('anisenso-community.groups.show', $g->id) }}" class="badge bg-light text-dark mb-1 d-inline-block">{{ $g->name }}</a>
                @empty <p class="text-secondary small mb-0">None.</p>@endforelse
            </div></div>

            {{-- Connections --}}
            <div class="card"><div class="card-body">
                <h6 class="text-dark">Connections</h6>
                @forelse($connections as $conn)
                    <a href="{{ route('anisenso-community.members.show', $conn->id) }}" class="d-block small border-bottom py-1 text-dark">{{ $conn->full_name }}@if($conn->location)<span class="text-secondary"> · {{ $conn->location }}</span>@endif</a>
                @empty <p class="text-secondary small mb-0">None.</p>@endforelse
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
                            <button type="button" class="btn btn-sm text-danger btn-del-wall-post" data-id="{{ $post->id }}" title="Remove post"><i class="bx bx-trash"></i></button>
                        </div>
                        @if($post->body)<p class="text-dark mb-2 mt-1" style="white-space:pre-line;">{{ $post->body }}</p>@endif
                        @if($post->imagePath)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->imagePath) }}" class="img-fluid rounded mb-2" style="max-height:260px;" alt="photo">@endif
                        @foreach($post->comments->sortBy('id') as $comment)
                            <div class="d-flex justify-content-between align-items-start ms-3 mt-1" data-wall-comment="{{ $comment->id }}">
                                <div class="small"><strong class="text-dark">{{ optional($comment->author)->full_name ?: 'Member' }}</strong>
                                    <span class="text-secondary">· {{ $comment->created_at?->diffForHumans() }}</span>
                                    <div class="text-dark" style="white-space:pre-line;">{{ $comment->body }}</div></div>
                                <button type="button" class="btn btn-sm text-danger btn-del-wall-comment" data-id="{{ $comment->id }}" title="Remove comment"><i class="bx bx-x"></i></button>
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
    document.querySelectorAll('.btn-del-wall-post').forEach((b) => b.addEventListener('click', () => del('/anisenso-community/wall-posts/' + b.getAttribute('data-id'), 'data-wall-post', b.getAttribute('data-id'))));
    document.querySelectorAll('.btn-del-wall-comment').forEach((b) => b.addEventListener('click', () => del('/anisenso-community/wall-comments/' + b.getAttribute('data-id'), 'data-wall-comment', b.getAttribute('data-id'))));
</script>
@endsection
