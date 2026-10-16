@extends('layouts.master')

@section('title') Learned product @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AniSystem @endslot
        @slot('li_2') Learned by Anee @endslot
        @slot('title') {{ $r->brand }} @endslot
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
        $aiText = implode('; ', array_map(fn ($a) => trim(($a['n'] ?? '') . (isset($a['a']) ? ' ' . $a['a'] . ' ' . ($a['u'] ?? '') : '')), $ai));
        $nutText = implode(', ', array_map(fn ($k, $v) => $k . ' ' . $v, array_keys($nut), $nut));
    @endphp

    <div class="row"><div class="col-lg-8"><div class="card"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h4 class="card-title mb-1 text-dark">{{ $r->brand }}</h4>
                <p class="text-secondary mb-0">Found by {{ implode(', ', (array) ($info['via'] ?? [])) ?: 'a module' }} on {{ $info['learnedAt'] ?? 'an unknown day' }}
                    @if (! empty($info['reviewed'])) · reviewed @endif @if ((int) $r->status === 0) · <b>retired</b>@endif</p>
            </div>
            <a class="btn btn-light" href="{{ route('anisenso-learned.index', ['tab' => 'products']) }}"><i class="bx bx-arrow-back"></i> All learned products</a>
        </div>
        <form method="POST" action="{{ route('anisenso-learned.product.save') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $r->id }}">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Brand</label><input class="form-control" name="brand" value="{{ old('brand', $r->brand) }}" required maxlength="190"></div>
                <div class="col-md-6"><label class="form-label">Company</label><input class="form-control" name="company" value="{{ old('company', $r->company) }}" maxlength="120"></div>
                <div class="col-md-6"><label class="form-label">Type</label>
                    <select class="form-select" name="type">
                        @foreach ($types as $k => $l)
                            <option value="{{ $k }}" {{ old('type', $r->type) === $k ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-6"><label class="form-label">Formulation (EC, WP, SC...) or form (granular, soluble, liquid)</label><input class="form-control" name="form" value="{{ old('form', $r->form) }}" maxlength="16"></div>
                <div class="col-12"><label class="form-label">Active ingredients with strength, separated by ; (like Cypermethrin 50 g/L; Chlorpyrifos 500 g/L)</label><textarea class="form-control" rows="2" name="ai">{{ old('ai', $aiText) }}</textarea></div>
                <div class="col-12"><label class="form-label">Guaranteed analysis in percent (like N 21, S 24, or N 15, P2O5 9, K2O 20, Mg 1)</label><input class="form-control" name="nutrients" value="{{ old('nutrients', $nutText) }}"></div>
                <div class="col-12"><label class="form-label">Other names it is asked by (comma separated)</label><input class="form-control" name="aka" value="{{ old('aka', implode(', ', (array) ($info['aka'] ?? []))) }}"></div>
                <div class="col-md-4"><label class="form-label">How sure</label>
                    <select class="form-select" name="confidence">
                        @foreach (['high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $k => $l)
                            <option value="{{ $k }}" {{ old('confidence', $info['confidence'] ?? 'medium') === $k ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-8 d-flex align-items-end">
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="reviewed" value="1" id="lpReviewed" {{ old('reviewed', ! empty($info['reviewed'])) ? 'checked' : '' }}>
                        <label class="form-check-label" for="lpReviewed">Reviewed</label></div>
                </div>
            </div>
            <div class="mt-3"><button class="btn btn-primary" type="submit"><i class="bx bx-save"></i> Save</button></div>
        </form>
        @if (! empty($info['mix']))
            <hr><h6 class="text-dark">What its label says about mixing (as Anee found it)</h6>
            <ul class="small mb-0">@foreach ((array) $info['mix'] as $m)<li>{{ $m }}</li>@endforeach</ul>
        @endif
    </div></div></div>

    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            <h5 class="card-title text-dark">Actions</h5>
            <form method="POST" action="{{ route('anisenso-learned.product.act') }}">@csrf<input type="hidden" name="id" value="{{ $r->id }}">
                <input type="hidden" name="act" value="{{ (int) $r->status === 1 ? 'retire' : 'restore' }}">
                <button class="btn {{ (int) $r->status === 1 ? 'btn-outline-danger' : 'btn-light' }} w-100" type="submit">{{ (int) $r->status === 1 ? 'Retire (no module reads it)' : 'Restore' }}</button></form>
        </div></div>
        <div class="card"><div class="card-body">
            <h5 class="card-title text-dark">Names that find it</h5>
            <ul class="list-unstyled small mb-0">
                @forelse ($aliases as $a)
                    <li class="d-flex justify-content-between align-items-center gap-2 mb-1"><span>{{ $a->alias }} <span class="text-secondary">({{ $a->via ?: 'book' }})</span></span>
                        <form method="POST" action="{{ route('anisenso-learned.product.act') }}" class="m-0">@csrf<input type="hidden" name="id" value="{{ $r->id }}"><input type="hidden" name="act" value="unalias"><input type="hidden" name="aliasId" value="{{ $a->id }}">
                            <button class="btn btn-sm btn-link text-danger p-0" type="submit">Remove</button></form></li>
                @empty
                    <li class="text-secondary">Only its brand.</li>
                @endforelse
            </ul>
        </div></div>
        <div class="card"><div class="card-body">
            <h5 class="card-title text-dark">What Anee read (never shown to farmers)</h5>
            <p class="small mb-0 text-break">{{ $r->sourceRef ?: 'No pages kept.' }}</p>
        </div></div>
    </div></div>
@endsection
