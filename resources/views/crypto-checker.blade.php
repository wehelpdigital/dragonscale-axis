@extends('layouts.master')

@section('title') Crypto Checker @endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') Crypto @endslot
@slot('title') Crypto Checker @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Crypto Checker</h4>
                <p class="card-title-desc">This is a blank page for the Crypto Checker functionality.</p>

                <!-- Content will be added here -->
                <div class="text-center">
                    <p class="text-muted">Crypto Checker page is ready for development.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
