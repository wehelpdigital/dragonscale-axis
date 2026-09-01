@extends('layouts.master')

@section('title') Community — Shared Plans @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    #plansTable td { vertical-align: middle; }
    .rate-stars { color: #f1b44c; letter-spacing: 1px; }
</style>

{{-- The mode layer, last in the head so it answers after the rules
     above it. --}}
@include('aniSensoAdmin.partials.dark')

@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Community @endslot
        @slot('title') Shared Plans @endslot
    @endcomponent

    @include('aniSensoAdmin.community.partials.shelf', ['cmHere' => 'plans'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h4 class="card-title mb-1 text-dark">Shared Plans</h4>
                            <p class="text-secondary mb-0">Cropping plans AniSystem members have published to the Community. Review them and unpublish or remove abusive comments.</p>
                        </div>
                        <form method="GET" action="{{ route('anisenso-community.plans') }}" class="d-flex gap-2">
                            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search title, crop or region…" style="min-width:240px;">
                            <button class="btn btn-primary" type="submit"><i class="bx bx-search"></i></button>
                            @if($search)<a href="{{ route('anisenso-community.plans') }}" class="btn btn-light">Clear</a>@endif
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="plansTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Plan</th>
                                    <th>Owner</th>
                                    <th>Region</th>
                                    <th class="text-center">Rating</th>
                                    <th class="text-center">Comments</th>
                                    <th>Published</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plans as $plan)
                                    <tr data-plan-row="{{ $plan->id }}">
                                        <td>
                                            <a href="{{ route('anisenso-community.plans', ['id' => $plan->id]) }}" class="fw-semibold text-dark">{{ $plan->title }}</a>
                                            @if($plan->cropType)<div class="text-secondary small">{{ $plan->cropType }}@if($plan->cropVariety) · {{ $plan->cropVariety }}@endif</div>@endif
                                        </td>
                                        <td>{{ optional($plan->anisystemUser)->full_name ?: '—' }}
                                            @if(optional($plan->anisystemUser)->location)<div class="text-secondary small">{{ $plan->anisystemUser->location }}</div>@endif
                                        </td>
                                        <td>{{ $plan->publicRegion ?: '—' }}</td>
                                        <td class="text-center">
                                            @if($plan->ratingCount)
                                                <span class="rate-stars">{{ str_repeat('★', (int) round($plan->avgRating)) }}</span>
                                                <div class="text-secondary small">{{ number_format($plan->avgRating, 1) }} ({{ $plan->ratingCount }})</div>
                                            @else <span class="text-secondary">—</span>@endif
                                        </td>
                                        <td class="text-center">{{ (int) $plan->commentCount }}</td>
                                        <td>{{ $plan->publishedAt ? \Illuminate\Support\Carbon::parse($plan->publishedAt)->format('M j, Y') : '—' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('anisenso-community.plans', ['id' => $plan->id]) }}" class="btn btn-sm btn-soft-primary">View</a>
                                            <button type="button" class="btn btn-sm btn-soft-danger btn-unpublish" data-id="{{ $plan->id }}" data-title="{{ $plan->title }}">Unpublish</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-secondary py-4">{{ $search ? 'No plans match your search.' : 'No plans have been shared yet.' }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $plans->links('pagination::bootstrap-4') }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    const CSRF = "{{ csrf_token() }}";
    document.querySelectorAll('.btn-unpublish').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Remove "' + btn.getAttribute('data-title') + '" from the Community?')) return;
            btn.disabled = true;
            try {
                const res = await fetch('/anisenso-community-plans?id=' + btn.getAttribute('data-id') + '/unpublish', {
                    method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    toastr.success(data.message);
                    document.querySelector('[data-plan-row="' + btn.getAttribute('data-id') + '"]')?.remove();
                } else { toastr.error(data.message || 'Could not unpublish.'); btn.disabled = false; }
            } catch (_) { toastr.error('Network error — try again.'); btn.disabled = false; }
        });
    });
</script>
@endsection
