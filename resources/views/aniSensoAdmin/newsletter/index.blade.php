@extends('layouts.master')

@section('title') Newsletter @endsection

@section('css')
    {{-- The screen is anee.io's own newsletter kit, the same one its admin
         panel mounts: one module, two homes (2026-10-17). Its two files are
         fetched by this app and served from its own address (kit()). --}}
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ route('anisenso-newsletter.kit', 'nl.css') }}?v={{ $kitVersion }}">
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AniSystem @endslot
        @slot('li_2') Website @endslot
        @slot('title') Newsletter @endslot
    @endcomponent

    @unless ($linked)
        <div class="alert alert-warning">This app is not linked to anee.io yet (ANISYSTEM_MEDIA_TOKEN is not set), so the newsletter cannot load its emails.</div>
    @endunless

    <div id="nlk"></div>
@endsection

@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    <script src="{{ route('anisenso-newsletter.kit', 'nl.js') }}?v={{ $kitVersion }}"></script>
    <script>
        (function () {
            const el = document.getElementById('nlk');
            if (!window.NLK) {
                const why = window.NLK_ERROR || 'The newsletter screen did not load.';
                el.innerHTML = '<div class="alert alert-danger"></div>';
                el.firstChild.textContent = why + ' Reload the page in a minute; if it stays, check that anee.io is up.';
                return;
            }
            window.NLK.mount(el, {
                api: @json(url('/anisenso-newsletter-api') . '/'),
                csrf: @json(csrf_token()),
                userEmail: @json((string) (auth()->user()->email ?? '')),
                framed: true,
            });
        })();
    </script>
@endsection
