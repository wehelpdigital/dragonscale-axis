@extends('layouts.master')

@section('title') Community — Reports @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    #reportsTable td { vertical-align: top; }
    .rp-said { background: #f8f9fa; border-left: 3px solid #dee2e6; padding: .5rem .65rem;
        border-radius: .35rem; font-size: .8125rem; color: #495057; white-space: pre-wrap; }
    .rp-said.rp-now { border-left-color: #0ab39c; }
    .rp-gone { color: #f06548; font-size: .8125rem; font-style: italic; }
    .rp-why { font-weight: 600; color: #212529; }
    .rp-note { font-size: .8125rem; color: #6c757d; margin-top: .25rem; }
    .rp-tabs .btn { text-transform: none; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Community @endslot
        @slot('title') Reports @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h4 class="card-title mb-1 text-dark">Reports</h4>
                            <p class="text-secondary mb-0">
                                What members have flagged in the community. Nothing was hidden when a report
                                arrived — that decision is here.
                            </p>
                        </div>
                        <form method="GET" action="{{ route('anisenso-community.reports') }}" class="d-flex gap-2">
                            <input type="hidden" name="status" value="{{ $status }}">
                            <input type="text" name="q" value="{{ $search }}" class="form-control"
                                   placeholder="Search what was said…" style="min-width:240px;">
                            <button class="btn btn-primary" type="submit"><i class="bx bx-search"></i></button>
                            @if($search)<a href="{{ route('anisenso-community.reports', ['status' => $status]) }}" class="btn btn-light">Clear</a>@endif
                        </form>
                    </div>

                    {{-- Open first, because open is the only one that is work. --}}
                    <div class="rp-tabs btn-group mb-3" role="group">
                        @foreach (['open' => 'Open', 'reviewed' => 'Reviewed', 'dismissed' => 'Dismissed', 'actioned' => 'Removed', 'all' => 'All'] as $key => $label)
                            <a href="{{ route('anisenso-community.reports', ['status' => $key, 'q' => $search]) }}"
                               class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-outline-primary' }}">
                                {{ $label }}
                                @if ($key !== 'all' && ($counts[$key] ?? 0) > 0)
                                    <span class="badge bg-light text-dark ms-1">{{ $counts[$key] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="reportsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:15%">Reported</th>
                                    <th style="width:20%">Why</th>
                                    <th>What was said</th>
                                    <th style="width:16%">People</th>
                                    <th class="text-end" style="width:16%">Decide</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports as $report)
                                    <tr data-report-row="{{ $report->id }}">
                                        <td>
                                            <span class="badge bg-soft-primary text-primary">{{ $report->targetLabel() }}</span>
                                            <div class="text-secondary small mt-1">#{{ $report->targetId }}</div>
                                            <div class="text-secondary small">{{ $report->created_at?->format('M j, Y g:ia') }}</div>
                                            @if ($report->status !== 'open')
                                                <div class="mt-1"><span class="badge bg-soft-secondary text-secondary">{{ ucfirst($report->status) }}</span></div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="rp-why">{{ $report->reasonLabel() }}</div>
                                            @if ($report->details)
                                                <div class="rp-note">“{{ $report->details }}”</div>
                                            @endif
                                            @if ($report->note)
                                                <div class="rp-note"><i class="bx bx-check-shield"></i> {{ $report->note }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            {{-- What it said when it was reported, and what it says now.
                                                 The two differing is itself worth seeing. --}}
                                            @if ($report->snapshot)
                                                <div class="rp-said">{{ $report->snapshot }}</div>
                                            @else
                                                <div class="text-secondary small">No words — a photo or a clip.</div>
                                            @endif
                                            @if ($report->liveText === null)
                                                <div class="rp-gone mt-1">Already gone from the app.</div>
                                            @elseif ($report->snapshot && trim($report->liveText) !== trim($report->snapshot))
                                                <div class="rp-said rp-now mt-1"><strong>Now reads:</strong> {{ $report->liveText }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small">
                                                <span class="text-secondary">Reported by</span><br>
                                                <span class="fw-semibold text-dark">{{ optional($report->reporter)->full_name ?: 'Member #' . $report->reporterUserId }}</span>
                                            </div>
                                            @if ($report->targetUserId)
                                                <div class="small mt-1">
                                                    <span class="text-secondary">Posted by</span><br>
                                                    <a href="{{ route('anisenso-community.members.show', $report->targetUserId) }}" class="fw-semibold text-dark">
                                                        {{ optional($report->reportedUser)->full_name ?: 'Member #' . $report->targetUserId }}
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($report->status === 'open')
                                                <button type="button" class="btn btn-sm btn-soft-success rp-act" data-act="review" data-id="{{ $report->id }}">Reviewed</button>
                                                <button type="button" class="btn btn-sm btn-soft-secondary rp-act" data-act="dismiss" data-id="{{ $report->id }}">Dismiss</button>
                                            @endif
                                            @if ($report->liveText !== null)
                                                <button type="button" class="btn btn-sm btn-soft-danger rp-act mt-1" data-act="remove" data-id="{{ $report->id }}">Remove content</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary py-5">
                                            <i class="bx bx-shield-quarter" style="font-size:2rem;"></i>
                                            <div class="mt-2">Nothing here — no {{ $status === 'all' ? '' : $status . ' ' }}reports.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $reports->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.rp-act');
    if (!btn) return;

    var act = btn.getAttribute('data-act');
    var id = btn.getAttribute('data-id');
    if (act === 'remove' && !confirm('Remove this content from the community? Every open report about it closes too.')) return;

    var urls = {
        review: '{{ url('anisenso-community/reports') }}/' + id + '/review',
        dismiss: '{{ url('anisenso-community/reports') }}/' + id + '/dismiss',
        remove: '{{ url('anisenso-community/reports') }}/' + id + '/remove'
    };

    btn.disabled = true;
    fetch(urls[act], {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (window.toastr) { d.success ? toastr.success(d.message) : toastr.error(d.message); }
            if (d.success) { setTimeout(function () { location.reload(); }, 600); }
            else { btn.disabled = false; }
        })
        .catch(function () {
            if (window.toastr) toastr.error('Could not save that — try again.');
            btn.disabled = false;
        });
});
</script>
@endsection
