@extends('layouts.master')

@section('title') Add Keyword @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('li_2') Keywords @endslot
@slot('li_2_link') {{ route('resort-guru-keywords.index') }} @endslot
@slot('title') Add Keyword @endslot
@endcomponent

<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('resort-guru-keywords.store') }}" method="POST">
                    @csrf
                    @include('resortGuruAdmin._keywords-fields', ['k' => null])
                    <div class="text-end">
                        <a href="{{ route('resort-guru-keywords.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save Keyword</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
