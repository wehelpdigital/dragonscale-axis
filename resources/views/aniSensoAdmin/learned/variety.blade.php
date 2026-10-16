@extends('layouts.master')

@section('title') Learned variety @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AniSystem @endslot
        @slot('li_2') Learned by Anee @endslot
        @slot('title') {{ $r->name }} @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @php
        $stale = ! $r->reviewed && ! $r->aliasOf && $r->staleAt && \Illuminate\Support\Carbon::parse($r->staleAt)->isPast();
        $val = fn ($k, $d = '') => old($k, $d);
    @endphp

    <div class="row"><div class="col-lg-8"><div class="card"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h4 class="card-title mb-1 text-dark">{{ $r->name }} <span class="text-secondary fw-normal">({{ $r->crop }})</span></h4>
                <p class="text-secondary mb-0">Found by {{ $r->foundBy ?: 'a module' }} on {{ $r->created_at ? \Illuminate\Support\Carbon::parse($r->created_at)->format('M j, Y') : 'an unknown day' }} · read {{ (int) $r->hits }} times since
                    · {{ $r->reviewed ? 'reviewed' : ($stale ? 'stale, looked up again by the next module' : 'stale on ' . ($r->staleAt ? \Illuminate\Support\Carbon::parse($r->staleAt)->format('M j, Y') : 'never')) }}
                    @if ((int) $r->status === 0) · <b>retired</b>@endif</p>
            </div>
            <a class="btn btn-light" href="{{ route('anisenso-learned.index', ['tab' => 'varieties']) }}"><i class="bx bx-arrow-back"></i> All learned varieties</a>
        </div>

        @if ($r->aliasOf)
            <div class="alert alert-info">This row only says that the name <b>{{ $r->name }}</b> is the registered variety <b>{{ $r->aliasOf }}</b>: the record's facts are used. Clear "Is the registered variety" below to make it a variety of its own.</div>
        @endif

        <form method="POST" action="{{ route('anisenso-learned.variety.save') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $r->id }}">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="{{ $val('name', $r->name) }}" required maxlength="120"></div>
                <div class="col-md-6"><label class="form-label">Is the registered variety (leave empty for its own row)</label><input class="form-control" name="aliasOf" value="{{ $val('aliasOf', $r->aliasOf) }}" maxlength="120"></div>
                <div class="col-12"><label class="form-label">Other names it is sold or typed under (comma separated)</label><input class="form-control" name="aliases" value="{{ $val('aliases', implode(', ', $aliases)) }}"></div>
                <div class="col-md-4"><label class="form-label">NSIC number</label><input class="form-control" name="nsic" value="{{ $val('nsic', $r->nsic) }}" maxlength="60"></div>
                <div class="col-md-4"><label class="form-label">Company or breeder</label><input class="form-control" name="company" value="{{ $val('company', $r->company) }}" maxlength="120"></div>
                <div class="col-md-4"><label class="form-label">Type</label>
                    <select class="form-select" name="type">
                        @foreach (['' => 'Not known', 'hybrid' => 'Hybrid', 'inbred' => 'Inbred', 'open' => 'Open pollinated', 'other' => 'Other'] as $k => $l)
                            <option value="{{ $k }}" {{ (string) $val('type', $r->type) === $k ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-3"><label class="form-label">Released (year)</label><input class="form-control" type="number" name="year" value="{{ $val('year', $r->year) }}"></div>
                <div class="col-md-3"><label class="form-label">Days to harvest</label><input class="form-control" type="number" name="maturityDays" value="{{ $val('maturityDays', $r->maturityDays) }}"></div>
                <div class="col-md-3"><label class="form-label">Average yield (t/ha)</label><input class="form-control" type="number" step="0.01" name="yieldAvg" value="{{ $val('yieldAvg', $r->yieldAvg) }}"></div>
                <div class="col-md-3"><label class="form-label">Top yield (t/ha)</label><input class="form-control" type="number" step="0.01" name="yieldTop" value="{{ $val('yieldTop', $r->yieldTop) }}"></div>
                <div class="col-md-3"><label class="form-label">Height (cm)</label><input class="form-control" type="number" name="heightCm" value="{{ $val('heightCm', $r->heightCm) }}"></div>
                <div class="col-md-9"><label class="form-label">Season or growing environment</label><input class="form-control" name="season" value="{{ $val('season', $r->season) }}" maxlength="160"></div>
                <div class="col-md-6"><label class="form-label">Ratings (pests, diseases)</label><textarea class="form-control" rows="2" name="reactions" maxlength="400">{{ $val('reactions', $r->reactions) }}</textarea></div>
                <div class="col-md-6"><label class="form-label">Tolerance (drought, flood, salt, lodging)</label><textarea class="form-control" rows="2" name="tolerances" maxlength="400">{{ $val('tolerances', $r->tolerances) }}</textarea></div>
                <div class="col-md-6"><label class="form-label">Grain or fruit</label><input class="form-control" name="grain" value="{{ $val('grain', $r->grain) }}" maxlength="300"></div>
                <div class="col-md-6"><label class="form-label">Its known weakness</label><input class="form-control" name="caution" value="{{ $val('caution', $r->caution) }}" maxlength="300"></div>
                <div class="col-12"><h6 class="text-dark mb-0 mt-2">Seed (the Land Prep Planner times the soak with these)</h6></div>
                <div class="col-md-2"><label class="form-label">Soak from (h)</label><input class="form-control" type="number" name="soakLo" value="{{ $val('soakLo', $seed['soak'][0] ?? '') }}"></div>
                <div class="col-md-2"><label class="form-label">to (h)</label><input class="form-control" type="number" name="soakHi" value="{{ $val('soakHi', $seed['soak'][1] ?? '') }}"></div>
                <div class="col-md-2"><label class="form-label">Change water every (h)</label><input class="form-control" type="number" name="refresh" value="{{ $val('refresh', $seed['refresh'] ?? '') }}"></div>
                <div class="col-md-2"><label class="form-label">Incubate from (h)</label><input class="form-control" type="number" name="incLo" value="{{ $val('incLo', $seed['incubate'][0] ?? '') }}"></div>
                <div class="col-md-2"><label class="form-label">to (h)</label><input class="form-control" type="number" name="incHi" value="{{ $val('incHi', $seed['incubate'][1] ?? '') }}"></div>
                <div class="col-md-1"><label class="form-label">kg/ha from</label><input class="form-control" type="number" step="0.1" name="rateLo" value="{{ $val('rateLo', $seed['rate'][0] ?? '') }}"></div>
                <div class="col-md-1"><label class="form-label">to</label><input class="form-control" type="number" step="0.1" name="rateHi" value="{{ $val('rateHi', $seed['rate'][1] ?? '') }}"></div>
                <div class="col-12"><label class="form-label">The seller's seed instructions</label><input class="form-control" name="seedNotes" value="{{ $val('seedNotes', $seed['notes'] ?? '') }}" maxlength="320"></div>
                <div class="col-md-4"><label class="form-label">How sure</label>
                    <select class="form-select" name="confidence">
                        @foreach (['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $k => $l)
                            <option value="{{ $k }}" {{ $val('confidence', $r->confidence) === $k ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-8 d-flex align-items-end">
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="reviewed" value="1" id="lvReviewed" {{ old('reviewed', $r->reviewed) ? 'checked' : '' }}>
                        <label class="form-check-label" for="lvReviewed">Reviewed (never goes stale; Anee only adds names to it)</label></div>
                </div>
            </div>
            <div class="mt-3 d-flex gap-2"><button class="btn btn-primary" type="submit"><i class="bx bx-save"></i> Save</button></div>
        </form>
    </div></div></div>

    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <h5 class="card-title text-dark">Actions</h5>
            <div class="d-grid gap-2">
                @foreach ((int) $r->status === 1 ? ['refresh' => 'Look it up again next time', 'retire' => 'Retire (no module reads it)'] : ['restore' => 'Restore'] as $act => $label)
                    <form method="POST" action="{{ route('anisenso-learned.variety.act') }}">@csrf<input type="hidden" name="id" value="{{ $r->id }}"><input type="hidden" name="act" value="{{ $act }}">
                        <button class="btn {{ $act === 'retire' ? 'btn-outline-danger' : 'btn-light' }} w-100" type="submit">{{ $label }}</button></form>
                @endforeach
            </div>
        </div></div>
        <div class="card"><div class="card-body">
            <h5 class="card-title text-dark">What Anee read (never shown to farmers)</h5>
            @if (! empty($facts['published']))<p class="small mb-1"><b>Published as:</b> {{ $facts['published'] }}</p>@endif
            @if (! empty($facts['sourceNote']))<p class="small mb-2"><b>Her note:</b> {{ $facts['sourceNote'] }}</p>@endif
            <ul class="small mb-0 ps-3">
                @forelse ($sources as $s)
                    <li>{{ $s['title'] ?? '' }} @if (! empty($s['url']))<a href="{{ $s['url'] }}" target="_blank" rel="noopener noreferrer">open</a>@endif</li>
                @empty
                    <li class="text-secondary">No pages kept.</li>
                @endforelse
            </ul>
        </div></div>
    </div></div>
@endsection
