@extends('layouts.master')

@section('title') Newsletter @endsection

@section('css')
    {{-- The screen is anee.io's own newsletter kit, the same one its admin
         panel mounts: one module, two homes (2026-10-17). --}}
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ $aneeBase }}/nl-kit/nl.css?v={{ $kitVersion }}">
    <style>
        /* The kit carries its own colors; give it a light ground in dark mode too. */
        #nlk { background: #f4f6f0; border-radius: 1.1rem; padding: 1rem; }
        @media (max-width: 640px) { #nlk { padding: .6rem; border-radius: .8rem; } }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AniSystem @endslot
        @slot('li_2') Website @endslot
        @slot('title') Newsletter @endslot
    @endcomponent

    @unless ($linked)
        <div class="alert alert-warning">This app is not linked to anee.io yet (ANISYSTEM_URL and ANISYSTEM_MEDIA_TOKEN), so the newsletter cannot load.</div>
    @endunless

    <div id="nlk"></div>
@endsection

@section('script')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    <script src="{{ $aneeBase }}/nl-kit/nl.js?v={{ $kitVersion }}"></script>
    <script>
        (function () {
            const el = document.getElementById('nlk');
            if (!window.NLK) {
                el.innerHTML = '<div class="alert alert-danger">The newsletter screen did not load from anee.io. Check that anee.io is up, then reload.</div>';
                return;
            }
            window.NLK.mount(el, {
                api: @json(url('/anisenso-newsletter-api') . '/'),
                csrf: @json(csrf_token()),
                userEmail: @json((string) (auth()->user()->email ?? '')),
            });
        })();
    </script>
@endsection
