@extends('layouts.master')

@section('title') {{ $post ? 'Edit Blog Post' : 'New Blog Post' }} @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('li_2') Blog @endslot
@slot('li_2_link') {{ route('resort-guru-blog.index') }} @endslot
@slot('title') {{ $post ? 'Edit Post' : 'New Post' }} @endslot
@endcomponent

@if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $e)<p class="mb-0">{{ $e }}</p>@endforeach</div>
@endif
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <form action="{{ $post ? route('resort-guru-blog.update', ['id' => $post->id]) : route('resort-guru-blog.store') }}" method="POST">
            @csrf
            @if($post) @method('PUT') @endif

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $post ? 'Edit Post' : 'New Post' }}</h5>
                    <select name="status" class="form-select form-select-sm" style="width:auto">
                        <option value="draft" {{ ($post->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ ($post->status ?? '') === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $post->title ?? '') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Excerpt <small class="text-muted">(shown on blog index)</small></label>
                        <textarea name="excerpt" class="form-control" rows="2">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cover Image URL</label>
                        <input type="text" name="cover_path" class="form-control" value="{{ old('cover_path', $post->cover_path ?? '') }}" placeholder="/storage/...">
                    </div>
                    <hr>
                    <h6>SEO</h6>
                    <div class="mb-3">
                        <label class="form-label">Meta Title <small class="text-muted">(50-60 chars)</small></label>
                        <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $post->meta_title ?? '') }}" maxlength="70">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Meta Description <small class="text-muted">(140-160 chars)</small></label>
                        <textarea name="meta_description" class="form-control" rows="2" maxlength="200">{{ old('meta_description', $post->meta_description ?? '') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bx bx-save me-1"></i>{{ $post ? 'Save Post' : 'Create Post' }}</button>
                </div>
            </div>
        </form>

        @if($post)
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bx bx-layer me-1"></i>Post Content Builder</h5>
                </div>
                <div class="card-body">
                    @include('resortGuruAdmin.blocks.builder', [
                        'ownerType' => 'blog_post',
                        'ownerId' => $post->id,
                        'allowed' => ['heading','rich_text','image','gallery','video','quote','cta','two_column','divider','custom_html'],
                    ])
                </div>
            </div>
        @else
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-1"></i> Create the post first; the content builder appears after the post is saved.
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Tips</h6></div>
            <div class="card-body small text-muted">
                <ul class="ps-3 mb-0">
                    <li>Keep titles under 60 characters.</li>
                    <li>Add a cover image for stronger blog index display.</li>
                    <li>Use H2 blocks for section headings.</li>
                    <li>Internal links to keyword pages boost SEO.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
