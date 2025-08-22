@extends('layouts.master')

@section('title') Crypto Settings @endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') Crypto @endslot
@slot('title') Crypto Settings @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Crypto Settings</h4>
                <p class="card-title-desc">This is a blank page for the Crypto Settings functionality.</p>

                <!-- Content will be added here -->
                <div class="text-center">
                    <p class="text-muted">Crypto Settings page is ready for development.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
