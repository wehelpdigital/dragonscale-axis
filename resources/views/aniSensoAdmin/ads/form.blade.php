@extends('layouts.master')

@section('title') {{ $mode === 'edit' ? 'Edit ad unit' : 'New ad unit' }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') <a href="{{ route('anisenso-ads.index') }}">Ads</a> @endslot
        @slot('title') {{ $mode === 'edit' ? 'Edit' : 'New' }} @endslot
    @endcomponent

    @php
        $kind = old('kind', $unit->kind ?: 'image');
        $picked = old('placements', array_values(array_filter((array) $unit->placements)));
    @endphp
    <form method="POST"
          action="{{ $mode === 'edit' ? route('anisenso-ads.update', ['id' => $unit->id]) : route('anisenso-ads.store') }}"
          enctype="multipart/form-data" id="adForm">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="row">
            <div class="col-lg-8"><div class="card"><div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Name (yours, never shown)</label>
                    <input type="text" name="name" class="form-control" required maxlength="120" value="{{ old('name', $unit->name) }}">
                    @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Kind</label>
                    <select name="kind" class="form-select" id="adKind">
                        @foreach (\App\Models\AsAdUnit::KINDS as $key => $label)
                            <option value="{{ $key }}" {{ $kind === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- AdSense --}}
                <div data-kind="adsense" class="ad-kind">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Ad slot id</label>
                            <input type="text" name="adSlot" class="form-control" placeholder="1234567890" maxlength="64" value="{{ old('adSlot', $unit->adSlot) }}">
                            <div class="form-text">From AdSense: the data-ad-slot of the unit.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Publisher id (optional)</label>
                            <input type="text" name="adClient" class="form-control" placeholder="ca-pub-… (blank = the one in settings)" maxlength="64" value="{{ old('adClient', $unit->adClient) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Format</label>
                            <select name="adFormat" class="form-select">
                                @foreach (['auto' => 'Auto (responsive)', 'horizontal' => 'Horizontal', 'rectangle' => 'Rectangle', 'vertical' => 'Vertical', 'fluid' => 'Fluid'] as $k => $l)
                                    <option value="{{ $k }}" {{ old('adFormat', $unit->adFormat ?: 'auto') === $k ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Picture --}}
                <div data-kind="image" class="ad-kind">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Upload a picture</label>
                            @if($unit->kind === 'image' && $unit->imageUrl())
                                <img src="{{ $unit->imageUrl() }}" class="img-fluid rounded mb-2" alt="" style="max-height:160px">
                            @endif
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <div class="form-text">Wide works best (about 3:1 on a phone, wider on a desk). Max 4 MB.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">…or a picture's address</label>
                            <input type="url" name="imageUrl" class="form-control" placeholder="https://…" maxlength="500" value="{{ old('imageUrl', preg_match('#^https?://#i', (string) $unit->imagePath) ? $unit->imagePath : '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Where a tap goes</label>
                            <input type="url" name="linkUrl" class="form-control" placeholder="https://…" maxlength="500" value="{{ old('linkUrl', $unit->linkUrl) }}">
                            <div class="form-text">Clicks are counted on the way.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Alt text</label>
                            <input type="text" name="imageAlt" class="form-control" maxlength="191" value="{{ old('imageAlt', $unit->imageAlt) }}">
                        </div>
                    </div>
                </div>

                {{-- Script --}}
                <div data-kind="script" class="ad-kind">
                    <label class="form-label">The network's tag</label>
                    <textarea name="scriptHtml" class="form-control font-monospace" rows="8" placeholder="<script …></script> or any HTML the network gave you">{{ old('scriptHtml', $unit->scriptHtml) }}</textarea>
                    <div class="form-text">Pasted into the slot as it is. Only paste tags from networks you trust: this runs on every free farmer's page.</div>
                </div>
            </div></div></div>

            <div class="col-lg-4"><div class="card"><div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Where it may appear</label>
                    @foreach (\App\Models\AsAdUnit::PLACEMENTS as $key => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="placements[]" value="{{ $key }}" id="pl-{{ $key }}" {{ in_array($key, $picked, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="pl-{{ $key }}">{{ $label }}</label>
                        </div>
                    @endforeach
                    <div class="form-text">Nothing ticked = everywhere.</div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Weight</label>
                        <input type="number" name="weight" class="form-control" min="1" max="100" value="{{ old('weight', $unit->weight ?: 1) }}">
                        <div class="form-text">Odds in the draw.</div>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Sort order</label>
                        <input type="number" name="sortOrder" class="form-control" value="{{ old('sortOrder', $unit->sortOrder ?? 0) }}">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label">Runs from</label>
                        <input type="date" name="startsAt" class="form-control" value="{{ old('startsAt', $unit->startsAt?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Until</label>
                        <input type="date" name="endsAt" class="form-control" value="{{ old('endsAt', $unit->endsAt?->format('Y-m-d')) }}">
                    </div>
                    @error('endsAt')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="isActive" value="1" id="isActive" {{ old('isActive', $unit->isActive ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isActive">Active</label>
                </div>
                <button class="btn btn-primary w-100" type="submit">{{ $mode === 'edit' ? 'Save changes' : 'Create ad unit' }}</button>
                <a href="{{ route('anisenso-ads.index') }}" class="btn btn-light w-100 mt-2">Cancel</a>
            </div></div></div>
        </div>
    </form>
@endsection

@section('script')
<script>
    // Only the chosen kind's fields are shown; the others stay in the form, empty.
    (() => {
        const sel = document.getElementById('adKind');
        const paint = () => document.querySelectorAll('.ad-kind').forEach((el) => { el.style.display = el.dataset.kind === sel.value ? '' : 'none'; });
        sel.addEventListener('change', paint);
        paint();
    })();
</script>
@endsection
