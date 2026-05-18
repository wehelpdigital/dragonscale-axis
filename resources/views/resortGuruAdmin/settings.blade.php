@extends('layouts.master')

@section('title') Resort Guru Settings @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Settings @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<form action="{{ route('resort-guru-settings.update') }}" method="POST">
    @csrf
    @foreach($grouped as $group => $rows)
        <div class="card">
            <div class="card-body">
                <h5 class="card-title text-capitalize">{{ str_replace('_', ' ', $group) }}</h5>
                <div class="row g-3">
                    @foreach($rows as $row)
                        <div class="col-md-6">
                            <label class="form-label">{{ $row->label }}</label>
                            @if($row->type === 'text')
                                <textarea name="{{ $row->key }}" class="form-control" rows="3">{{ $row->value }}</textarea>
                            @elseif($row->type === 'bool')
                                <select name="{{ $row->key }}" class="form-select">
                                    <option value="1" {{ $row->value == '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ $row->value == '0' ? 'selected' : '' }}>No</option>
                                </select>
                            @elseif($row->type === 'int')
                                <input type="number" name="{{ $row->key }}" value="{{ $row->value }}" class="form-control">
                            @else
                                <input type="text" name="{{ $row->key }}" value="{{ $row->value }}" class="form-control">
                            @endif
                            @if($row->help_text)
                                <small class="text-muted">{{ $row->help_text }}</small>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    <div class="text-end mb-4">
        <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save All Settings</button>
    </div>
</form>
@endsection
