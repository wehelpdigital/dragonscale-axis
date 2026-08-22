@extends('layouts.master')

@section('title') {{ $plan->title }} — Community @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Community @endslot
        @slot('li_2_link') {{ route('anisenso-community.plans') }} @endslot
        @slot('title') {{ $plan->title }} @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-8">
            {{-- Plan header --}}
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <h4 class="mb-1 text-dark">{{ $plan->title }}</h4>
                            <div class="text-secondary">
                                @if($plan->cropType)<span class="badge bg-success">{{ $plan->cropType }}</span>@endif
                                @if($plan->cropVariety)<span class="badge bg-light text-dark">{{ $plan->cropVariety }}</span>@endif
                                @if($plan->publicRegion)<span class="badge bg-light text-dark">{{ $plan->publicRegion }}</span>@endif
                            </div>
                            <p class="text-secondary small mb-0 mt-2">
                                Shared by <strong>{{ optional($plan->anisystemUser)->full_name ?: 'a member' }}</strong>
                                @if(optional($plan->anisystemUser)->location) · {{ $plan->anisystemUser->location }}@endif
                                @if($plan->publishedAt) · {{ \Illuminate\Support\Carbon::parse($plan->publishedAt)->format('M j, Y') }}@endif
                            </p>
                        </div>
                        <div class="text-end">
                            <div class="rate-stars text-warning fs-5">{{ $avg ? str_repeat('★', (int) round($avg)) : '—' }}</div>
                            <div class="text-secondary small">{{ $avg ? $avg . ' from ' . $ratings->count() : 'No ratings' }}</div>
                            <button type="button" class="btn btn-sm btn-soft-danger mt-2" id="unpublishBtn" data-id="{{ $plan->id }}">Unpublish</button>
                        </div>
                    </div>
                    @if($plan->publicSummary)<p class="text-dark mt-3 mb-0">{{ $plan->publicSummary }}</p>@endif
                </div>
            </div>

            {{-- Comments --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-dark mb-3">Comments &amp; questions ({{ $thread->count() }})</h5>
                    @forelse($thread as $comment)
                        <div class="border rounded p-3 mb-2" data-comment="{{ $comment->id }}">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong class="text-dark">{{ optional($comment->author)->full_name ?: 'Member' }}</strong>
                                    @if($comment->isQuestion)<span class="badge bg-info ms-1">Question</span>@endif
                                    <span class="text-secondary small ms-1">{{ $comment->created_at?->diffForHumans() }}</span>
                                </div>
                                <button type="button" class="btn btn-sm text-danger btn-del-comment" data-id="{{ $comment->id }}" title="Remove comment"><i class="bx bx-trash"></i></button>
                            </div>
                            <p class="mb-0 mt-1 text-dark">{{ $comment->body }}</p>
                            @foreach($comment->replies as $reply)
                                <div class="border-start ps-3 ms-2 mt-2" data-comment="{{ $reply->id }}">
                                    <div class="d-flex justify-content-between">
                                        <div><strong class="text-dark">{{ optional($reply->author)->full_name ?: 'Member' }}</strong>
                                            <span class="text-secondary small ms-1">{{ $reply->created_at?->diffForHumans() }}</span></div>
                                        <button type="button" class="btn btn-sm text-danger btn-del-comment" data-id="{{ $reply->id }}" title="Remove reply"><i class="bx bx-trash"></i></button>
                                    </div>
                                    <p class="mb-0 mt-1 text-dark">{{ $reply->body }}</p>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-secondary mb-0">No comments yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            {{-- Lots --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-dark">Lots ({{ $plan->lots->count() }})</h5>
                    @forelse($plan->lots as $lot)
                        <div class="d-flex justify-content-between small border-bottom py-1">
                            <span class="text-dark">{{ $lot->lotName }}@if($lot->variety) · {{ $lot->variety }}@endif</span>
                            @if($lot->lotSize)<span class="text-secondary">{{ rtrim(rtrim(number_format((float) $lot->lotSize, 2, '.', ''), '0'), '.') }} {{ $lot->lotSizeUnit }}</span>@endif
                        </div>
                    @empty <p class="text-secondary small mb-0">No lots.</p>@endforelse
                </div>
            </div>
            {{-- Workers --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-dark">Workers ({{ $plan->workers->count() }})</h5>
                    @forelse($plan->workers as $worker)
                        <span class="badge bg-light text-dark mb-1">{{ $worker->workerName }}</span>
                    @empty <p class="text-secondary small mb-0">No workers.</p>@endforelse
                </div>
            </div>
            {{-- Ratings --}}
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-dark">Ratings ({{ $ratings->count() }})</h5>
                    @forelse($ratings as $rating)
                        <div class="border-bottom py-2">
                            <div class="text-warning">{{ str_repeat('★', $rating->rating) }}<span class="text-secondary">{{ str_repeat('☆', 5 - $rating->rating) }}</span></div>
                            <div class="small text-dark">{{ optional($rating->author)->full_name ?: 'Member' }}</div>
                            @if($rating->review)<div class="small text-secondary">{{ $rating->review }}</div>@endif
                        </div>
                    @empty <p class="text-secondary small mb-0">No ratings.</p>@endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    const CSRF = "{{ csrf_token() }}";
    document.getElementById('unpublishBtn')?.addEventListener('click', async (e) => {
        if (!confirm('Remove this plan from the Community?')) return;
        const res = await fetch('/anisenso-community-plans?id=' + e.currentTarget.getAttribute('data-id') + '/unpublish', { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
        const data = await res.json();
        if (data.success) { toastr.success(data.message); setTimeout(() => window.location = '{{ route('anisenso-community.plans') }}', 800); }
        else toastr.error(data.message || 'Could not unpublish.');
    });
    document.querySelectorAll('.btn-del-comment').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Remove this comment?')) return;
            const res = await fetch('/anisenso-community-comments?id=' + btn.getAttribute('data-id'), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
            const data = await res.json();
            if (data.success) { toastr.success(data.message); document.querySelector('[data-comment="' + btn.getAttribute('data-id') + '"]')?.remove(); }
            else toastr.error(data.message || 'Could not remove.');
        });
    });
</script>
@endsection
