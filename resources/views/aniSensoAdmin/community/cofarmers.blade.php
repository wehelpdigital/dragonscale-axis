@extends('layouts.master')

@section('title') Community — Co-farmers @endsection

@section('css')
<style>
    .cf-pair { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .cf-name { font-weight: 600; color: #343a40; }
    .cf-arrow { color: #98a4b6; }
    .cf-state { font-size: 10.5px; font-weight: 700; border-radius: 999px; padding: .1rem .5rem; }
    .cf-state.is-accepted { background: #e9f7ef; color: #0f8a5f; }
    .cf-state.is-pending { background: #fff4e5; color: #b26b00; }
    .cf-state.is-other { background: #eef1f6; color: #74788d; }
    .cf-empty { text-align: center; padding: 2.2rem 1rem; color: #98a4b6; }
    .cf-fig { border: 1px solid #e6e8ec; border-radius: 10px; padding: .55rem .85rem; background: #fff; min-width: 9rem; }
    .cf-fig b { display: block; font-size: 17px; font-weight: 700; line-height: 1.2; color: #556ee6; }
    .cf-fig span { font-size: 11.5px; color: #98a4b6; }
</style>

{{-- The mode layer, last in the head so it answers after the rules
     above it. --}}
@include('aniSensoAdmin.partials.dark')

@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Community @endslot
        @slot('title') Co-farmers @endslot
    @endcomponent

    @include('aniSensoAdmin.community.partials.shelf', ['cmHere' => 'cofarmers'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <h4 class="card-title mb-1 text-dark">Co-farmers</h4>
                            <p class="text-secondary mb-0">
                                Who farms alongside whom. A co-farmer is a handshake — one side asked and the other
                                agreed — so it can still be waiting. A follow is one-sided and needs nobody's permission;
                                the two are listed apart because they are not the same thing.
                            </p>
                        </div>
                        <form method="GET" action="{{ route('anisenso-community.cofarmers') }}" class="d-flex gap-2">
                            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search a name or email…" style="min-width:220px;">
                            <select name="status" class="form-select" style="max-width:9rem;">
                                <option value="">Any state</option>
                                <option value="accepted" @selected($status === 'accepted')>Accepted</option>
                                <option value="pending" @selected($status === 'pending')>Waiting</option>
                            </select>
                            <button class="btn btn-primary" type="submit"><i class="bx bx-search"></i></button>
                            @if($search || $status)<a href="{{ route('anisenso-community.cofarmers') }}" class="btn btn-light">Clear</a>@endif
                        </form>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <div class="cf-fig"><b>{{ $counts['accepted'] }}</b><span>Handshakes made</span></div>
                        <div class="cf-fig"><b>{{ $counts['pending'] }}</b><span>Still waiting</span></div>
                        <div class="cf-fig"><b>{{ $counts['follows'] }}</b><span>Follows</span></div>
                    </div>

                    <h6 class="text-dark mb-2"><i class="bx bx-link me-1"></i>Co-farmer links</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Who asked</th><th>Who was asked</th><th>State</th><th>When</th><th class="text-end">—</th></tr>
                            </thead>
                            <tbody>
                            @forelse ($rows as $r)
                                <tr>
                                    <td>
                                        <a href="{{ route('anisenso-community.members', ['id' => $r->userId]) }}" class="cf-name">
                                            {{ trim($r->aFirst . ' ' . $r->aLast) ?: 'Someone' }}
                                        </a>
                                        <div class="text-secondary" style="font-size:11.5px;">{{ $r->aEmail }}</div>
                                    </td>
                                    <td>
                                        <a href="{{ route('anisenso-community.members', ['id' => $r->friendUserId]) }}" class="cf-name">
                                            {{ trim($r->bFirst . ' ' . $r->bLast) ?: 'Someone' }}
                                        </a>
                                        <div class="text-secondary" style="font-size:11.5px;">{{ $r->bEmail }}</div>
                                    </td>
                                    <td>
                                        <span class="cf-state {{ $r->status === 'accepted' ? 'is-accepted' : ($r->status === 'pending' ? 'is-pending' : 'is-other') }}">
                                            {{ $r->status === 'pending' ? 'waiting' : $r->status }}
                                        </span>
                                    </td>
                                    <td class="text-secondary" style="font-size:12px;">{{ $r->respondedAt ?: $r->created_at }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-danger js-cf-cut" data-id="{{ $r->id }}"
                                                title="Both sides lose this link"><i class="bx bx-unlink"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="cf-empty"><i class="bx bx-link"></i> Nobody has paired up yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6 class="text-dark mb-2"><i class="bx bx-user-plus me-1"></i>Follows</h6>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Follower</th><th></th><th>Following</th><th>When</th><th class="text-end">—</th></tr>
                            </thead>
                            <tbody>
                            @forelse ($follows as $f)
                                <tr>
                                    <td><a href="{{ route('anisenso-community.members', ['id' => $f->followerUserId]) }}" class="cf-name">{{ trim($f->aFirst . ' ' . $f->aLast) ?: 'Someone' }}</a></td>
                                    <td class="cf-arrow"><i class="bx bx-right-arrow-alt"></i></td>
                                    <td><a href="{{ route('anisenso-community.members', ['id' => $f->followedUserId]) }}" class="cf-name">{{ trim($f->bFirst . ' ' . $f->bLast) ?: 'Someone' }}</a></td>
                                    <td class="text-secondary" style="font-size:12px;">{{ $f->created_at }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-danger js-cf-unfollow" data-id="{{ $f->id }}"><i class="bx bx-x"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="cf-empty"><i class="bx bx-user-plus"></i> Nobody is following anybody yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
const CF_TOKEN = '{{ csrf_token() }}';
function cfCut(url, ask, btn) {
    if (!confirm(ask)) return;
    $.ajax({
        url, type: 'DELETE', data: { _token: CF_TOKEN },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            $(btn).closest('tr').fadeOut(180, function () { $(this).remove(); });
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'That did not work.')
    });
}
$(document).on('click', '.js-cf-cut', function () {
    cfCut('{{ url('/anisenso-community-cofarmers') }}?id=' + $(this).data('id'),
        'Sever this co-farmer link? Both sides lose it.', this);
});
$(document).on('click', '.js-cf-unfollow', function () {
    cfCut('{{ url('/anisenso-community-follows') }}?id=' + $(this).data('id'),
        'Remove this follow?', this);
});
</script>
@endsection
