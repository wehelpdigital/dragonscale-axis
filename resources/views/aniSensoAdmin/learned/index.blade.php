@extends('layouts.master')

@section('title') Learned by Anee @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') AniSystem @endslot
        @slot('title') Learned by Anee @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
        <h4 class="card-title mb-1 text-dark">What Anee looked up, kept</h4>
        <p class="text-secondary mb-3">When Anee researches a variety or a product online for a farmer, anee.io keeps what she found, and every module reads it here first, so nothing is searched twice. Correct a row, mark it reviewed (a reviewed variety never goes stale and keeps its facts), or retire a wrong one (no module reads it and the next farmer gets it looked up again). Learned varieties go stale after a year and are looked up again then. Sources show only here, never to farmers.</p>

        <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
            <li class="nav-item"><a class="nav-link {{ $tab === 'varieties' ? 'active' : '' }}" href="{{ route('anisenso-learned.index', ['tab' => 'varieties']) }}"><i class="bx bx-leaf"></i> Varieties</a></li>
            <li class="nav-item"><a class="nav-link {{ $tab === 'products' ? 'active' : '' }}" href="{{ route('anisenso-learned.index', ['tab' => 'products']) }}"><i class="bx bx-spray-can"></i> Products</a></li>
        </ul>

        @if (! $ready[$tab])
            <div class="alert alert-warning">The table is not in the database yet: anee.io has to deploy its migration first.</div>
        @else
            <form method="GET" action="{{ route('anisenso-learned.index') }}" class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="search" name="q" value="{{ $q }}" class="form-control" style="max-width:20rem" placeholder="{{ $tab === 'varieties' ? 'Name, other name, NSIC number, company or crop' : 'Brand, company or ingredient' }}">
                <button class="btn btn-primary" type="submit"><i class="bx bx-search"></i> Search</button>
                <div class="btn-group flex-wrap" role="group">
                    @foreach (($tab === 'varieties' ? ['live' => 'Live', 'unreviewed' => 'Not reviewed', 'stale' => 'Stale', 'retired' => 'Retired', 'all' => 'All'] : ['live' => 'Live', 'unreviewed' => 'Not reviewed', 'retired' => 'Retired', 'all' => 'All']) as $k => $label)
                        <a class="btn btn-sm {{ $show === $k ? 'btn-dark' : 'btn-light' }}" href="{{ route('anisenso-learned.index', ['tab' => $tab, 'show' => $k, 'q' => $q]) }}">{{ $label }} <span class="badge bg-secondary">{{ $counts[$k] ?? 0 }}</span></a>
                    @endforeach
                </div>
            </form>

            @if (! $rows || ! $rows->count())
                <p class="text-secondary">Nothing here yet. A row appears the first time Anee finds a {{ $tab === 'varieties' ? 'variety' : 'product' }} online for a farmer.</p>
            @elseif ($tab === 'varieties')
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Variety</th><th>Crop</th><th>What Anee found</th><th>How sure</th><th>Found by</th><th>Read</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($rows as $r)
                            @php
                                $al = json_decode((string) $r->aliases, true) ?: [];
                                $stale = ! $r->reviewed && ! $r->aliasOf && $r->staleAt && \Illuminate\Support\Carbon::parse($r->staleAt)->isPast();
                            @endphp
                            <tr class="{{ (int) $r->status === 0 ? 'opacity-50' : '' }}">
                                <td>
                                    <b class="text-dark">{{ $r->name }}</b>
                                    @if ($al)<div class="small text-secondary">Also: {{ implode(', ', array_slice($al, 0, 5)) }}</div>@endif
                                    @if ($r->nsic)<div class="small text-secondary">{{ $r->nsic }}</div>@endif
                                </td>
                                <td>{{ $r->crop }}</td>
                                <td class="small">
                                    @if ($r->aliasOf)
                                        A name for the registered <b>{{ $r->aliasOf }}</b> (the record speaks for it)
                                    @else
                                        {{ implode(' · ', array_filter([$r->type, $r->company, $r->maturityDays ? $r->maturityDays . ' days' : null,
                                            $r->yieldAvg !== null ? rtrim(rtrim((string) $r->yieldAvg, '0'), '.') . ' t/ha avg' : null, $r->yieldTop !== null ? rtrim(rtrim((string) $r->yieldTop, '0'), '.') . ' t/ha top' : null, $r->year ? 'released ' . $r->year : null])) }}
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $r->confidence === 'high' ? 'bg-success' : ($r->confidence === 'medium' ? 'bg-info' : 'bg-warning') }}">{{ $r->confidence }}</span>
                                    @if ($r->reviewed)<span class="badge bg-dark">reviewed</span>@endif
                                    @if ($stale)<span class="badge bg-warning text-dark">stale</span>@endif
                                    @if ((int) $r->status === 0)<span class="badge bg-secondary">retired</span>@endif
                                </td>
                                <td class="small">{{ $r->foundBy }}<div class="text-secondary">{{ $r->created_at ? \Illuminate\Support\Carbon::parse($r->created_at)->format('M j, Y') : '' }}</div></td>
                                <td class="small">{{ (int) $r->hits }}×</td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-light" href="{{ route('anisenso-learned.variety', ['id' => $r->id]) }}"><i class="bx bx-edit"></i> Open</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">{{ $rows->links('pagination::bootstrap-4') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Product</th><th>Type</th><th>What Anee found</th><th>How sure</th><th>Found by</th><th></th></tr></thead>
                        <tbody>
                        @foreach ($rows as $r)
                            @php
                                $info = json_decode((string) $r->info, true) ?: [];
                                $ai = json_decode((string) $r->ai, true) ?: [];
                                $nut = json_decode((string) $r->nutrients, true) ?: [];
                            @endphp
                            <tr class="{{ (int) $r->status === 0 ? 'opacity-50' : '' }}">
                                <td><b class="text-dark">{{ $r->brand }}</b><div class="small text-secondary">{{ $r->company }}</div>
                                    @if (! empty($r->aliasList))<div class="small text-secondary">Also: {{ implode(', ', array_slice($r->aliasList, 0, 5)) }}</div>@endif</td>
                                <td class="small">{{ \App\Http\Controllers\aniSensoAdmin\AnisystemLearnedController::TYPES[$r->type] ?? $r->type }}{{ $r->form ? ' · ' . $r->form : '' }}</td>
                                <td class="small">
                                    {{ implode(' + ', array_map(fn ($a) => trim(($a['n'] ?? '') . (isset($a['a']) ? ' ' . $a['a'] . ' ' . ($a['u'] ?? '') : '')), $ai)) }}
                                    @if ($nut)<div>{{ implode(', ', array_map(fn ($k, $v) => $k . ' ' . $v . '%', array_keys($nut), $nut)) }}</div>@endif
                                </td>
                                <td>
                                    <span class="badge {{ ($info['confidence'] ?? '') === 'high' ? 'bg-success' : 'bg-info' }}">{{ $info['confidence'] ?? 'medium' }}</span>
                                    @if (! empty($info['reviewed']))<span class="badge bg-dark">reviewed</span>@endif
                                    @if ((int) $r->status === 0)<span class="badge bg-secondary">retired</span>@endif
                                </td>
                                <td class="small">{{ implode(', ', (array) ($info['via'] ?? [])) }}<div class="text-secondary">{{ $info['learnedAt'] ?? '' }}</div></td>
                                <td class="text-end text-nowrap"><a class="btn btn-sm btn-light" href="{{ route('anisenso-learned.product', ['id' => $r->id]) }}"><i class="bx bx-edit"></i> Open</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-2">{{ $rows->links('pagination::bootstrap-4') }}</div>
            @endif
        @endif
    </div></div></div></div>
@endsection
