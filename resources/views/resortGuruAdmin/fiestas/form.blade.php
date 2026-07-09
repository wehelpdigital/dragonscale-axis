@extends('layouts.master')

@section('title') {{ $fiesta->exists ? 'Edit Fiesta' : 'Add Fiesta' }} @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('li_2') Fiestas @endslot
@slot('title') {{ $fiesta->exists ? $fiesta->name : 'Add Fiesta' }} @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ $action }}" method="POST">
                    @csrf
                    @if($method === 'PUT') @method('PUT') @endif

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required value="{{ old('name', $fiesta->name) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" name="slug" value="{{ old('slug', $fiesta->slug) }}" placeholder="auto from name">
                            <small class="text-muted">URL: /fiestas/<strong>{{ $fiesta->slug ?: 'slug-here' }}</strong></small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Region <span class="text-danger">*</span></label>
                            <select class="form-select" name="region_cluster" required>
                                @foreach(\App\Models\RgFiesta::REGION_LABELS as $key => $label)
                                    <option value="{{ $key }}" {{ old('region_cluster', $fiesta->region_cluster) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Province</label>
                            <input type="text" class="form-control" name="province" value="{{ old('province', $fiesta->province) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">City / Town</label>
                            <input type="text" class="form-control" name="city_or_town" value="{{ old('city_or_town', $fiesta->city_or_town) }}">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Month (1-12)</label>
                            <select class="form-select" name="month">
                                <option value="">— Movable / Unknown —</option>
                                @foreach(\App\Models\RgFiesta::MONTH_LABELS as $num => $label)
                                    <option value="{{ $num }}" {{ (string) old('month', $fiesta->month) === (string) $num ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-9 mb-3">
                            <label class="form-label">Date label (shown on the page)</label>
                            <input type="text" class="form-control" name="date_label" value="{{ old('date_label', $fiesta->date_label) }}" placeholder="e.g. Third Sunday of January, May 14-15, Movable (Holy Week)">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Summary (cards + meta description fallback)</label>
                        <textarea class="form-control" name="summary" rows="3" maxlength="500">{{ old('summary', $fiesta->summary) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cover image path</label>
                            <input type="text" class="form-control" name="cover_image_path" value="{{ old('cover_image_path', $fiesta->cover_image_path) }}" placeholder="rg-media/fiestas/sinulog.jpg">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">OG image path (optional)</label>
                            <input type="text" class="form-control" name="og_image_path" value="{{ old('og_image_path', $fiesta->og_image_path) }}">
                        </div>
                    </div>

                    <div class="card mb-3 bg-light">
                        <div class="card-body">
                            <h5 class="card-title">SEO meta</h5>
                            <div class="mb-3">
                                <label class="form-label">Meta title</label>
                                <input type="text" class="form-control" name="meta_title" value="{{ old('meta_title', $fiesta->meta_title) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta description</label>
                                <textarea class="form-control" name="meta_description" rows="2">{{ old('meta_description', $fiesta->meta_description) }}</textarea>
                            </div>
                            <div>
                                <label class="form-label">H1 (page heading override)</label>
                                <input type="text" class="form-control" name="h1" value="{{ old('h1', $fiesta->h1) }}">
                            </div>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_published" value="1" id="isPublished" {{ old('is_published', $fiesta->is_published ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isPublished">Published</label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('resort-guru-fiestas.index') }}" class="btn btn-outline-secondary">Back to list</a>
                        <div>
                            @if($fiesta->exists)
                                <a href="{{ route('resort-guru-fiestas.blocks', $fiesta->id) }}" class="btn btn-success me-2">
                                    <i class="bx bx-cube"></i> Edit content blocks
                                </a>
                            @endif
                            <button type="submit" class="btn btn-primary">{{ $fiesta->exists ? 'Save changes' : 'Create + go to blocks' }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
