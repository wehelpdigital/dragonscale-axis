@extends('layouts.master')

@section('title') Streamr {{ $page ?? '' }} @endsection

@section('css')
@include('streamr._styles', ['tables' => false])
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Streamr @endslot
        @slot('title') {{ $page ?? 'Streamr' }} @endslot
    @endcomponent

    <div id="st">
        <div class="card"><div class="card-body">
            <div class="text-center py-5">
                <i class="bx bx-data text-secondary" style="font-size: 2.5rem;"></i>
                <p class="text-dark mt-2 mb-1">The Streamr tables are not installed yet (run the deploy migrations).</p>
                <small class="text-secondary">This page fills in once the Streamr module's migration has run on this host.</small>
            </div>
        </div></div>
    </div>
@endsection
