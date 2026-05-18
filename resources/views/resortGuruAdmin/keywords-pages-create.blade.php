@extends('layouts.master')

@section('title') New Page @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('li_2') Keywords @endslot
@slot('li_2_link') {{ route('resort-guru-keywords.index') }} @endslot
@slot('title') New page for "{{ $keyword->phrase }}" @endslot
@endcomponent

@if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $e)<p class="mb-0">{{ $e }}</p>@endforeach</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <form action="{{ route('resort-guru-keywords-pages.store') }}" method="POST">
            @csrf
            <input type="hidden" name="keyword_id" value="{{ $keyword->id }}">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Create new page for "{{ $keyword->phrase }}"</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Page Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                            <small class="text-muted">Shown in the browser tab + as the H1 fallback. Example: "Best Resort in Bulacan with Pool and Garden"</small>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">URL Slug <small class="text-muted">(optional, auto-generated from title if blank)</small></label>
                            <div class="input-group">
                                <span class="input-group-text">/</span>
                                <input type="text" name="slug" class="form-control" value="{{ old('slug') }}" pattern="[a-z0-9-]+" placeholder="auto-generated">
                            </div>
                            <small class="text-muted">Lowercase letters, numbers, hyphens only. Must be unique across the whole site.</small>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">H1 Heading <small class="text-muted">(optional, defaults to title)</small></label>
                            <input type="text" name="h1" class="form-control" value="{{ old('h1') }}">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title') }}" maxlength="70">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="2" maxlength="200">{{ old('meta_description') }}</textarea>
                        </div>
                    </div>

                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" name="is_published" id="isPub" value="1">
                        <label for="isPub" class="form-check-label">Publish immediately</label>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="is_primary" id="isPrim" value="1">
                        <label for="isPrim" class="form-check-label">Mark as primary page (canonical for this keyword)</label>
                    </div>

                    <div class="alert alert-info small mb-3">
                        <i class="bx bx-info-circle me-1"></i> After creating the page you'll be taken to the builder to add content blocks (heading, rich text, image, gallery, video, FAQ, listing slot, etc.).
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Create Page</button>
                    <a href="{{ route('resort-guru-keywords-pages.index', ['id' => $keyword->id]) }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Keyword info</h6></div>
            <div class="card-body small">
                <p class="mb-2"><strong>{{ $keyword->phrase }}</strong></p>
                <ul class="list-unstyled mb-0 text-muted">
                    <li>Volume: {{ number_format($keyword->search_volume_monthly) }}/mo</li>
                    <li>Difficulty: {{ $keyword->keyword_difficulty }}</li>
                    <li>Cluster: {{ $keyword->cluster_tag ?: '—' }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
