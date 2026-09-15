@extends('layouts.master')

@section('title') Search indexing @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') AniSystem @endslot
        @slot('title') Search indexing @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @php $base = rtrim((string) config('anisystem.url'), '/'); @endphp

    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h4 class="card-title mb-1 text-dark">Search engines and anee.io</h4>
                <p class="text-secondary mb-0">Everything behind the login — a farmer's schedules, notes, pictures, the community, the admin — is closed to search engines always; that is not a setting. This switch is about the public site only: the home page, features, pricing, about, contact and the legal pages.</p>
            </div>
        </div>

        @unless ($tableReady)
            <div class="alert alert-warning mb-3">The settings shelf (<code>as_site_settings</code>) is not in the database yet — anee.io has to deploy its migration first. Until then every page answers <code>noindex</code>.</div>
        @endunless

        <form method="POST" action="{{ route('anisenso-seo.save') }}">
            @csrf
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="publicIndexable" value="1" id="publicIndexable" {{ $publicIndexable ? 'checked' : '' }} {{ $tableReady ? '' : 'disabled' }}>
                        <label class="form-check-label fw-semibold" for="publicIndexable">Let search engines index the public site</label>
                    </div>
                    <div class="form-text">
                        Off, and the whole domain says <code>noindex, nofollow</code> — in <code>robots.txt</code> (<code>Disallow: /</code>), in an <code>X-Robots-Tag</code> header on every response, and in the robots meta of every page. On, and only the public pages open; <code>robots.txt</code> lists everything behind the login as disallowed.
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-center gap-2">
                    <button class="btn btn-primary" type="submit" {{ $tableReady ? '' : 'disabled' }}><i class="bx bx-save"></i> Save</button>
                    <a class="btn btn-light" href="{{ $base }}/robots.txt" target="_blank" rel="noopener"><i class="bx bx-link-external"></i> See robots.txt as it stands</a>
                </div>
            </div>
        </form>

        <hr class="my-4">
        <h5 class="text-dark">Right now</h5>
        <ul class="mb-0 text-secondary">
            <li>Public site (<code>/</code>, <code>/features</code>, <code>/pricing</code>, <code>/about</code>, <code>/contact</code>, <code>/legal/…</code>): <strong>{{ $publicIndexable ? 'index, follow' : 'noindex, nofollow' }}</strong></li>
            <li>Login, signup and password pages: <strong>noindex, nofollow</strong></li>
            <li>Shared plans, shared posts and worker invitations (token links): <strong>noindex, nofollow</strong></li>
            <li>Everything behind the login (<code>/app/…</code>, <code>/admin/…</code>, account, purchase): <strong>noindex, nofollow</strong></li>
        </ul>
    </div></div></div></div>
@endsection
