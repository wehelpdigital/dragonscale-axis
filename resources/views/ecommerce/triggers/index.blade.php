@extends('layouts.master')

@section('title') Triggers @endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('title') Triggers @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Triggers</h4>
                <p class="card-title-desc">This page is under development.</p>
            </div>
        </div>
    </div>
</div>

@endsection

