@extends('layouts.master')

@section('title') Edit Page @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('li_2') Site Pages @endslot
@slot('li_2_link') {{ route('resort-guru-static.index') }} @endslot
@slot('title') {{ $page->title ?? 'Edit' }} @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <form action="{{ route('resort-guru-static.update', ['id' => $page->id]) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Page Settings</h5>
                    <div class="form-check form-switch mb-0">
                        <input type="checkbox" class="form-check-input" name="is_published" id="isPublished" value="1" {{ $page->is_published ? 'checked' : '' }}>
                        <label class="form-check-label" for="isPublished">Published</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Page Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ $page->title }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug <small class="text-muted">(URL path)</small></label>
                        <input type="text" class="form-control" value="{{ $page->slug }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta Title</label>
                        <input type="text" name="meta_title" class="form-control" value="{{ $page->meta_title }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta Description</label>
                        <textarea name="meta_description" class="form-control" rows="2">{{ $page->meta_description }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>Save Settings</button>
                </div>
            </div>
        </form>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bx bx-layer me-1"></i>Content Builder</h5>
                <small class="text-muted">Drag blocks to reorder. Click edit to change content.</small>
            </div>
            <div class="card-body">
                @include('resortGuruAdmin.blocks.builder', [
                    'ownerType' => 'static_page',
                    'ownerId' => $page->id,
                    'allowed' => ['heading','rich_text','image','gallery','video','faq','cta','two_column','quote','divider','custom_html'],
                ])
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">About this page</h6></div>
            <div class="card-body small text-muted">
                <p>Slug: <code>{{ $page->slug }}</code></p>
                <p>Last updated: {{ \Carbon\Carbon::parse($page->updated_at)->diffForHumans() }}</p>
                @if($page->is_published)
                    <a href="{{ rtrim(config('app.frontend_url','http://localhost:8001'),'/') }}/{{ $page->slug }}" target="_blank" class="btn btn-sm btn-outline-success w-100">
                        <i class="bx bx-link-external"></i> View live
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
