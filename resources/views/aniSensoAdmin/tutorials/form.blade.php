@extends('layouts.master')

@section('title') {{ $mode === 'edit' ? 'Edit tutorial' : 'New tutorial' }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') <a href="{{ route('anisenso-tutorials.index') }}">Tutorials</a> @endslot
        @slot('title') {{ $mode === 'edit' ? 'Edit' : 'New' }} @endslot
    @endcomponent

    <form method="POST"
          action="{{ $mode === 'edit' ? route('anisenso-tutorials.update', ['id' => $tutorial->id]) : route('anisenso-tutorials.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="row">
            <div class="col-lg-8"><div class="card"><div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required maxlength="191" value="{{ old('title', $tutorial->title) }}">
                    @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">YouTube link</label>
                    <input type="text" name="videoUrl" class="form-control" placeholder="https://youtu.be/…"
                           value="{{ old('videoUrl', $tutorial->youtubeId ? 'https://youtu.be/' . $tutorial->youtubeId : '') }}">
                    <div class="form-text">Paste any YouTube URL — we extract the video id. The thumbnail becomes the cover automatically.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="5">{{ old('description', $tutorial->description) }}</textarea>
                </div>
            </div></div></div>

            <div class="col-lg-4"><div class="card"><div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" maxlength="80" value="{{ old('category', $tutorial->category) }}" placeholder="e.g. Getting started">
                </div>
                <div class="mb-3">
                    <label class="form-label">Sort order</label>
                    <input type="number" name="sortOrder" class="form-control" value="{{ old('sortOrder', $tutorial->sortOrder ?? 0) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Custom cover (optional)</label>
                    @if($tutorial->coverImagePath)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($tutorial->coverImagePath) }}" class="img-fluid rounded mb-2" alt="cover">
                    @endif
                    <input type="file" name="cover" class="form-control" accept="image/*">
                    <div class="form-text">Overrides the YouTube thumbnail. Max 8 MB.</div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="isPublished" value="1" id="isPublished" {{ old('isPublished', $tutorial->isPublished ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isPublished">Published</label>
                </div>
                <button class="btn btn-primary w-100" type="submit">{{ $mode === 'edit' ? 'Save changes' : 'Create tutorial' }}</button>
                <a href="{{ route('anisenso-tutorials.index') }}" class="btn btn-light w-100 mt-2">Cancel</a>
            </div></div></div>
        </div>
    </form>
@endsection
