@extends('layouts.master')

@section('title') Website: Games & Site @endsection

@section('css')
@include('games.salpakan._styles', ['tables' => false])
<style>
    #gs .game-row { display: flex; gap: 1rem; align-items: center; padding: .8rem 0; border-bottom: 1px solid var(--bs-border-color); }
    #gs .game-row:last-child { border-bottom: 0; }
    #gs .game-logo { width: 56px; height: 56px; border-radius: .8rem; object-fit: cover; background: var(--bs-secondary-bg); flex: none; display: grid; place-items: center; font-size: 1.6rem; color: var(--bs-secondary-color); }
    #gs .swatch { display: inline-block; width: .8rem; height: .8rem; border-radius: 50%; vertical-align: middle; margin-right: .3rem; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Games @endslot
        @slot('li_2') Website @endslot
        @slot('title') Games &amp; Site @endslot
    @endcomponent

    <div id="gs">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h4 class="card-title mb-1 text-dark"><i class="bx bx-grid-alt me-1"></i>Games on the website</h4>
                                <p class="text-secondary small mb-0">Every live and coming-soon game shows on the website's home page and gets its own page; hidden ones don't. The website reads changes within a few minutes.</p>
                            </div>
                            <a href="{{ route('games-site.edit') }}" class="btn btn-primary btn-sm"><i class="bx bx-plus me-1"></i>Add a game</a>
                        </div>

                        @forelse ($games as $game)
                            <div class="game-row">
                                @if ($logo = \App\Http\Controllers\Games\GamesSiteController::imageUrl($game->logoImage))
                                    <img src="{{ $logo }}" alt="" class="game-logo">
                                @else
                                    <div class="game-logo"><i class="bx bx-joystick"></i></div>
                                @endif
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-dark">
                                        @if ($game->accent)<span class="swatch" style="background: {{ $game->accent }}"></span>@endif
                                        {{ $game->name }}
                                        <span class="badge {{ ['live' => 'bg-success', 'coming_soon' => 'bg-info text-white', 'hidden' => 'bg-secondary'][$game->status] ?? 'bg-secondary' }} ms-1">{{ \App\Models\GameSiteGame::STATUSES[$game->status] ?? $game->status }}</span>
                                    </div>
                                    <div class="sub">{{ $game->tagline ?: 'No tagline yet' }}</div>
                                    <div class="sub mono">/games/{{ $game->slug }} · key {{ $game->gameKey }} · {{ count((array) $game->screenshots) }} screenshots</div>
                                </div>
                                <div class="d-flex gap-1">
                                    @if ($siteUrl)
                                        <a href="{{ $siteUrl }}/games/{{ $game->slug }}" target="_blank" rel="noopener" class="btn btn-light btn-sm" title="Open on the website"><i class="bx bx-link-external"></i></a>
                                    @endif
                                    <a href="{{ route('games-site.edit', ['id' => $game->id]) }}" class="btn btn-light btn-sm"><i class="bx bx-edit-alt"></i> Edit</a>
                                    <button type="button" class="btn btn-light btn-sm text-danger" data-delete="{{ $game->id }}" data-name="{{ $game->name }}"><i class="bx bx-trash"></i></button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-secondary py-4">
                                <i class="bx bx-joystick fs-1 d-block mb-2"></i>
                                No games yet. Until one is added the website shows its built-in Salpakan Tactics! page.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-1 text-dark"><i class="bx bx-cog me-1"></i>The website</h4>
                        <p class="text-secondary small">Its name and how people reach the studio, shown in its header, footer and legal pages.</p>
                        <form method="POST" action="{{ route('games-site.settings') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label text-dark" for="siteName">Name</label>
                                <input class="form-control" id="siteName" name="siteName" value="{{ old('siteName', $site['siteName']) }}" maxlength="80" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-dark" for="tagline">Tagline</label>
                                <input class="form-control" id="tagline" name="tagline" value="{{ old('tagline', $site['tagline']) }}" maxlength="200">
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-dark" for="contactEmail">Contact email</label>
                                <input type="email" class="form-control" id="contactEmail" name="contactEmail" value="{{ old('contactEmail', $site['contactEmail']) }}" maxlength="255" placeholder="hello@yourdomain.com">
                                <div class="form-text">On the privacy, terms and support pages. Every app store asks for one.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-dark" for="announcement">Announcement bar</label>
                                <input class="form-control" id="announcement" name="announcement" value="{{ old('announcement', $site['announcement']) }}" maxlength="300" placeholder="Empty = none">
                            </div>
                            <button class="btn btn-primary"><i class="bx bx-save me-1"></i>Save</button>
                        </form>
                        <hr>
                        <dl class="kv mb-0">
                            <dt>Address</dt>
                            <dd>@if ($siteUrl)<a href="{{ $siteUrl }}" target="_blank" rel="noopener">{{ $siteUrl }}</a>@else<span class="text-secondary">GAMES_SITE_URL is not set</span>@endif</dd>
                            <dt>Connection</dt>
                            <dd>{!! $tokenSet ? '<span class="ok"><i class="bx bx-check-circle"></i> GAMES_SITE_TOKEN is set</span>' : '<span class="bad"><i class="bx bx-error"></i> GAMES_SITE_TOKEN is not set: the website can\'t read this or send payments</span>' !!}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    @include('games.salpakan._script-helpers')
    $('[data-delete]').on('click', function () {
        const $b = $(this);
        if (!confirm('Take ' + $b.data('name') + ' off the website?')) { return; }
        $.post(@json(route('games-site.delete')), { id: $b.data('delete'), _token: @json(csrf_token()) })
            .done((r) => { toastr.success(r.message); setTimeout(() => location.reload(), 600); })
            .fail((x) => fail(x, 'Could not remove it.'));
    });
</script>
@endsection
