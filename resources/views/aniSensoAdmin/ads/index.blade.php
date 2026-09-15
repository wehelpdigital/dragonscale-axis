@extends('layouts.master')

@section('title') Ads @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') AniSystem @endslot
        @slot('title') Ads @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- The switch and the settings --}}
    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h4 class="card-title mb-1 text-dark">Advertising on the free plan</h4>
                <p class="text-secondary mb-0">anee.io shows a slot to a Libre account on its own farm — the dashboard, the schedules page, the activities board, every module, the community, the upgrade pages — and to anybody not paying on the public pricing page. Paid plans never see one. Workers standing in somebody else's farm never see one either.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('anisenso-ads.settings') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="form-check form-switch mt-md-4">
                        <input class="form-check-input" type="checkbox" name="isEnabled" value="1" id="isEnabled" {{ $settings->isEnabled ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="isEnabled">Ads are on</label>
                    </div>
                    <div class="form-text">Off, and no slot is drawn anywhere, whatever the units say.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Google AdSense publisher id</label>
                    <input type="text" name="adsenseClient" class="form-control" placeholder="ca-pub-1234567890123456" maxlength="64" value="{{ old('adsenseClient', $settings->adsenseClient) }}">
                    <div class="form-text">Used by AdSense units that do not carry their own.</div>
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch mt-md-4">
                        <input class="form-check-input" type="checkbox" name="autoAds" value="1" id="autoAds" {{ $settings->autoAds ? 'checked' : '' }}>
                        <label class="form-check-label" for="autoAds">AdSense auto ads</label>
                    </div>
                    <div class="form-text">Let Google place more on its own.</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Slot label</label>
                    <input type="text" name="label" class="form-control" maxlength="60" required value="{{ old('label', $settings->label) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Feed: one every … posts</label>
                    <input type="number" name="feedEvery" class="form-control" min="3" max="30" required value="{{ old('feedEvery', $settings->feedEvery ?: 6) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">The line beside the label (leads to the paid plans)</label>
                    <input type="text" name="upsell" class="form-control" maxlength="191" required value="{{ old('upsell', $settings->upsell) }}">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-primary" type="submit"><i class="bx bx-save"></i> Save settings</button>
                </div>
            </div>
        </form>
    </div></div></div></div>

    {{-- The units --}}
    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h4 class="card-title mb-1 text-dark">Ad units</h4>
                <p class="text-secondary mb-0">What a slot can carry. Each slot draws one unit at random among those that fit the page, by weight.</p>
            </div>
            <a href="{{ route('anisenso-ads.create') }}" class="btn btn-primary"><i class="bx bx-plus"></i> New ad unit</a>
        </div>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th></th><th>Name</th><th>Kind</th><th>Where</th><th>Weight</th><th>Runs</th><th>Seen / clicked</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($units as $u)
                    <tr data-row="{{ $u->id }}">
                        <td style="width:96px;">
                            @if($u->kind === 'image' && $u->imageUrl())
                                <img src="{{ $u->imageUrl() }}" alt="" style="width:80px;height:45px;object-fit:cover;border-radius:6px;">
                            @elseif($u->kind === 'adsense')
                                <span class="badge bg-light text-dark border">AdSense</span>
                            @else
                                <span class="badge bg-light text-dark border">Script</span>
                            @endif
                        </td>
                        <td class="text-dark fw-semibold">{{ $u->name }}</td>
                        <td class="text-secondary">{{ \App\Models\AsAdUnit::KINDS[$u->kind] ?? $u->kind }}</td>
                        <td class="text-secondary small">
                            @php $where = array_values(array_filter((array) $u->placements)); @endphp
                            {{ $where ? implode(', ', array_map(fn ($p) => \App\Models\AsAdUnit::PLACEMENTS[$p] ?? $p, $where)) : 'Everywhere' }}
                        </td>
                        <td>{{ $u->weight }}</td>
                        <td class="text-secondary small">
                            @if($u->startsAt || $u->endsAt)
                                {{ $u->startsAt?->format('M j') ?? '…' }} → {{ $u->endsAt?->format('M j, Y') ?? '…' }}
                            @else always @endif
                        </td>
                        <td class="text-secondary small">{{ number_format($u->impressions) }} / {{ number_format($u->clicks) }}</td>
                        <td>@if($u->isActive)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Paused</span>@endif</td>
                        <td class="text-end">
                            <a href="{{ route('anisenso-ads.edit', ['id' => $u->id]) }}" class="btn btn-sm btn-soft-primary"><i class="bx bx-edit"></i></a>
                            <button type="button" class="btn btn-sm btn-soft-danger btn-del" data-id="{{ $u->id }}"><i class="bx bx-trash"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-secondary py-4">No ad units yet — nothing is shown until there is one.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div></div></div></div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    const CSRF = "{{ csrf_token() }}";
    document.querySelectorAll('.btn-del').forEach((b) => b.addEventListener('click', async () => {
        if (!confirm('Remove this ad unit?')) return;
        const res = await fetch('{{ route('anisenso-ads.destroy') }}?id=' + b.getAttribute('data-id'), { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' } });
        const data = await res.json();
        if (data.success) { toastr.success(data.message); document.querySelector('[data-row="' + b.getAttribute('data-id') + '"]')?.remove(); }
        else toastr.error(data.message || 'Could not remove.');
    }));
</script>
@endsection
