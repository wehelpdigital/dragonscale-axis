@extends('layouts.master')

@section('title') Website: {{ $game->exists ? 'Edit ' . $game->name : 'Add a game' }} @endsection

@section('css')
@include('games.salpakan._styles', ['tables' => false])
<style>
    #gs .shot { position: relative; border-radius: .6rem; overflow: hidden; background: var(--bs-secondary-bg); }
    #gs .shot img { width: 100%; aspect-ratio: 16 / 10; object-fit: cover; display: block; }
    #gs .shot .form-control { border-radius: 0; border-width: 1px 0 0; font-size: .78rem; }
    #gs .shot .remove { position: absolute; top: .4rem; right: .4rem; }
    #gs .repeat-row { display: grid; gap: .5rem; grid-template-columns: 1fr 2fr auto; align-items: start; margin-bottom: .5rem; }
    #gs .platform-row { display: grid; gap: .5rem; grid-template-columns: 1.2fr .8fr 2fr auto; align-items: center; margin-bottom: .5rem; }
    @media (max-width: 767px) { #gs .repeat-row, #gs .platform-row { grid-template-columns: 1fr; } }
</style>
@endsection

@php
    $platforms = old('platforms', $game->platforms ?? []);
    $features = old('features', $game->features ?? []);
    $shots = old('screenshots', $game->screenshots ?? []);
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Games @endslot
        @slot('li_2') Website @endslot
        @slot('title') {{ $game->exists ? $game->name : 'Add a game' }} @endslot
    @endcomponent

    <div id="gs">
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('games-site.save') }}" enctype="multipart/form-data">
            @csrf
            @if ($game->exists)<input type="hidden" name="id" value="{{ $game->id }}">@endif
            <div class="row">
                <div class="col-xl-8">
                    <div class="card"><div class="card-body">
                        <h4 class="card-title text-dark mb-3"><i class="bx bx-joystick me-1"></i>The game</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-dark" for="name">Name</label>
                                <input class="form-control" id="name" name="name" value="{{ old('name', $game->name) }}" maxlength="120" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-dark" for="gameKey">Key</label>
                                <input class="form-control mono" id="gameKey" name="gameKey" value="{{ old('gameKey', $game->gameKey) }}" maxlength="40" required placeholder="salpakan">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-dark" for="slug">Page address</label>
                                <input class="form-control mono" id="slug" name="slug" value="{{ old('slug', $game->slug) }}" maxlength="80" required placeholder="salpakan-tactics">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label text-dark" for="tagline">Tagline</label>
                                <input class="form-control" id="tagline" name="tagline" value="{{ old('tagline', $game->tagline) }}" maxlength="200" placeholder="One line that sells it">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-dark" for="genre">Genre</label>
                                <input class="form-control" id="genre" name="genre" value="{{ old('genre', $game->genre) }}" maxlength="80" placeholder="Strategy · 2 players">
                            </div>
                            <div class="col-12">
                                <label class="form-label text-dark" for="summary">Summary</label>
                                <textarea class="form-control" id="summary" name="summary" rows="2" maxlength="1000">{{ old('summary', $game->summary) }}</textarea>
                                <div class="form-text">On the home page's card. Two or three sentences.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-dark" for="description">About the game</label>
                                <textarea class="form-control" id="description" name="description" rows="7" maxlength="20000">{{ old('description', $game->description) }}</textarea>
                                <div class="form-text">On the game's own page. A blank line starts a new paragraph.</div>
                            </div>
                        </div>
                    </div></div>

                    <div class="card"><div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="card-title text-dark mb-0"><i class="bx bx-star me-1"></i>Features</h4>
                            <button type="button" class="btn btn-light btn-sm" id="addFeature"><i class="bx bx-plus"></i> Add</button>
                        </div>
                        <div id="features">
                            @foreach ($features as $i => $f)
                                <div class="repeat-row">
                                    <input class="form-control" name="features[{{ $i }}][title]" value="{{ $f['title'] ?? '' }}" maxlength="80" placeholder="Title">
                                    <input class="form-control" name="features[{{ $i }}][text]" value="{{ $f['text'] ?? '' }}" maxlength="400" placeholder="A sentence">
                                    <button type="button" class="btn btn-light text-danger" data-remove-row><i class="bx bx-x"></i></button>
                                </div>
                            @endforeach
                        </div>
                    </div></div>

                    <div class="card"><div class="card-body">
                        <h4 class="card-title text-dark mb-1"><i class="bx bx-images me-1"></i>Screenshots</h4>
                        <p class="small text-secondary">In this order on the game's page. Landscape pictures look best (16:10 or 16:9).</p>
                        <div class="row g-3 mb-3">
                            @foreach ($shots as $i => $s)
                                <div class="col-sm-6 col-lg-4">
                                    <div class="shot">
                                        <img src="{{ \App\Http\Controllers\Games\GamesSiteController::imageUrl($s['url'] ?? '') }}" alt="">
                                        <input type="hidden" name="screenshots[{{ $i }}][url]" value="{{ $s['url'] ?? '' }}">
                                        <input class="form-control" name="screenshots[{{ $i }}][caption]" value="{{ $s['caption'] ?? '' }}" maxlength="160" placeholder="Caption">
                                        <label class="remove btn btn-sm btn-light"><input type="checkbox" name="screenshots[{{ $i }}][remove]" value="1" class="form-check-input me-1">Remove</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <label class="form-label text-dark" for="newScreenshots">Add screenshots</label>
                        <input type="file" class="form-control" id="newScreenshots" name="newScreenshots[]" accept="image/*" multiple>
                    </div></div>
                </div>

                <div class="col-xl-4">
                    <div class="card"><div class="card-body">
                        <h4 class="card-title text-dark mb-3"><i class="bx bx-show me-1"></i>On the website</h4>
                        <div class="mb-3">
                            <label class="form-label text-dark" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                @foreach (\App\Models\GameSiteGame::STATUSES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $game->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark" for="playUrl">Play it here (web game)</label>
                            <input type="url" class="form-control" id="playUrl" name="playUrl" value="{{ old('playUrl', $game->playUrl) }}" maxlength="255" placeholder="https://...">
                            <div class="form-text">The website's Play button goes through its captcha first, then here.</div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label text-dark" for="accent">Colour</label>
                                <input type="color" class="form-control form-control-color w-100" id="accent" name="accent" value="{{ old('accent', $game->accent ?: '#ff5a8a') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label text-dark" for="sortOrder">Order</label>
                                <input type="number" class="form-control" id="sortOrder" name="sortOrder" value="{{ old('sortOrder', $game->sortOrder) }}" min="0" max="9999">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark" for="logoFile">Logo or icon</label>
                            @if ($logo = \App\Http\Controllers\Games\GamesSiteController::imageUrl($game->logoImage))
                                <div class="d-flex align-items-center gap-2 mb-2"><img src="{{ $logo }}" alt="" style="height:48px;border-radius:.5rem"><label class="small"><input type="checkbox" name="logoRemove" value="1" class="form-check-input me-1">Remove</label></div>
                            @endif
                            <input type="file" class="form-control" id="logoFile" name="logoFile" accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark" for="heroFile">Big picture (the top of its page)</label>
                            @if ($hero = \App\Http\Controllers\Games\GamesSiteController::imageUrl($game->heroImage))
                                <div class="mb-2"><img src="{{ $hero }}" alt="" class="w-100 rounded"><label class="small mt-1"><input type="checkbox" name="heroRemove" value="1" class="form-check-input me-1">Remove</label></div>
                            @endif
                            <input type="file" class="form-control" id="heroFile" name="heroFile" accept="image/*">
                        </div>
                    </div></div>

                    <div class="card"><div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="card-title text-dark mb-0"><i class="bx bx-devices me-1"></i>Platforms</h4>
                            <button type="button" class="btn btn-light btn-sm" id="addPlatform"><i class="bx bx-plus"></i> Add</button>
                        </div>
                        <div id="platforms">
                            @foreach ($platforms as $i => $p)
                                <div class="platform-row">
                                    <select class="form-select form-select-sm" name="platforms[{{ $i }}][key]">
                                        @foreach (\App\Models\GameSiteGame::PLATFORMS as $value => $label)
                                            <option value="{{ $value }}" @selected(($p['key'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select class="form-select form-select-sm" name="platforms[{{ $i }}][status]">
                                        <option value="live" @selected(($p['status'] ?? '') === 'live')>Out now</option>
                                        <option value="soon" @selected(($p['status'] ?? '') === 'soon')>Soon</option>
                                    </select>
                                    <input type="url" class="form-control form-control-sm" name="platforms[{{ $i }}][url]" value="{{ $p['url'] ?? '' }}" placeholder="Store link">
                                    <button type="button" class="btn btn-light btn-sm text-danger" data-remove-row><i class="bx bx-x"></i></button>
                                </div>
                            @endforeach
                        </div>
                    </div></div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary"><i class="bx bx-save me-1"></i>Save</button>
                        <a href="{{ route('games-site.index') }}" class="btn btn-light">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('script')
<script>
    (function () {
        let n = 1000;
        const platformOptions = @json(\App\Models\GameSiteGame::PLATFORMS);
        $('#addFeature').on('click', () => {
            const i = n++;
            $('#features').append('<div class="repeat-row"><input class="form-control" name="features[' + i + '][title]" maxlength="80" placeholder="Title">'
                + '<input class="form-control" name="features[' + i + '][text]" maxlength="400" placeholder="A sentence">'
                + '<button type="button" class="btn btn-light text-danger" data-remove-row><i class="bx bx-x"></i></button></div>');
        });
        $('#addPlatform').on('click', () => {
            const i = n++;
            const options = Object.entries(platformOptions).map(([v, l]) => '<option value="' + v + '">' + l + '</option>').join('');
            $('#platforms').append('<div class="platform-row"><select class="form-select form-select-sm" name="platforms[' + i + '][key]">' + options + '</select>'
                + '<select class="form-select form-select-sm" name="platforms[' + i + '][status]"><option value="live">Out now</option><option value="soon" selected>Soon</option></select>'
                + '<input type="url" class="form-control form-control-sm" name="platforms[' + i + '][url]" placeholder="Store link">'
                + '<button type="button" class="btn btn-light btn-sm text-danger" data-remove-row><i class="bx bx-x"></i></button></div>');
        });
        $(document).on('click', '[data-remove-row]', function () { $(this).closest('.repeat-row, .platform-row').remove(); });
    })();
</script>
@endsection
