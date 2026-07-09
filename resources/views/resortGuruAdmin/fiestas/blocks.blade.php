@extends('layouts.master')

@section('title') Fiesta Blocks @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('li_2') <a href="{{ route('resort-guru-fiestas.index') }}">Fiestas</a> @endslot
@slot('title') {{ $fiesta->name }} — Content Blocks @endslot
@endcomponent

<div class="row mb-2">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-1">{{ $fiesta->name }}</h4>
            <div class="text-muted small">
                {{ $fiesta->city_or_town }}{{ $fiesta->province ? ', ' . $fiesta->province : '' }}
                @if($fiesta->date_label) · {{ $fiesta->date_label }} @endif
            </div>
        </div>
        <div>
            <a href="{{ route('resort-guru-fiestas.edit', $fiesta->id) }}" class="btn btn-outline-secondary">
                <i class="bx bx-edit"></i> Edit metadata
            </a>
            <a href="{{ url('/fiestas/' . $fiesta->slug) }}" target="_blank" class="btn btn-outline-info">
                <i class="bx bx-link-external"></i> View public page
            </a>
        </div>
    </div>
</div>

@include('resortGuruAdmin.blocks.builder', [
    'ownerType' => 'fiesta',
    'ownerId'   => $fiesta->id,
])
@endsection
