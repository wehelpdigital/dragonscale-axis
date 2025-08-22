@extends('layouts.master')

@section('title') Welcome @endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') Welcome @endslot
@slot('title') Welcome Page @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="text-center">
                    <h1 class="display-4 text-primary">Welcome!</h1>
                    <p class="lead">You have successfully logged in to your account.</p>
                    <p class="text-muted">This is your welcome page after login.</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
