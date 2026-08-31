@extends('layouts.master')

@section('title') {{ $mode === 'edit' ? 'Edit article' : 'New article' }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') <a href="{{ route('anisenso-blog.index') }}">Technician's Blog</a> @endslot
        @slot('title') {{ $mode === 'edit' ? 'Edit' : 'New' }} @endslot
    @endcomponent

    <form method="POST"
          action="{{ $mode === 'edit' ? route('anisenso-blog.update', ['id' => $post->id]) : route('anisenso-blog.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($mode === 'edit') @method('PUT') @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="card"><div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required maxlength="191" value="{{ old('title', $post->title) }}">
                        @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Excerpt <span class="text-secondary">(shown on the card)</span></label>
                        <textarea name="excerpt" class="form-control" rows="2" maxlength="500">{{ old('excerpt', $post->excerpt) }}</textarea>
                    </div>
                    {{-- The body is built, not typed. It stays reachable as raw
                         markup for the odd fix, but the builder is the door:
                         it shows the article as anee.io will draw it while it
                         is being written, which a textarea never could. --}}
                    @if($mode === 'edit')
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <label class="form-label mb-0">Body</label>
                            <a href="{{ route('anisenso-blog.build', ['id' => $post->id]) }}" class="btn btn-primary btn-sm">
                                <i class="bx bx-layer me-1"></i> Open the builder
                            </a>
                        </div>
                        <div class="mb-3">
                            <textarea name="body" class="form-control" rows="10"
                                      placeholder="Written by the builder. Editing it here is fine — the builder will offer it back as one block of text.">{{ old('body', $post->body) }}</textarea>
                        </div>
                    @else
                        <div class="mb-3">
                            <label class="form-label">Body</label>
                            <textarea name="body" class="form-control" rows="8" placeholder="Save the article first, then build it — the builder needs somewhere to put the pieces.">{{ old('body', $post->body) }}</textarea>
                        </div>
                    @endif
                </div></div>
            </div>

            <div class="col-lg-4">
                <div class="card"><div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Author name</label>
                        <input type="text" name="authorName" class="form-control" maxlength="120" value="{{ old('authorName', $post->authorName) }}" placeholder="e.g. Eng. Dela Cruz">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cover photo</label>
                        @if($post->coverImagePath)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($post->coverImagePath) }}" class="img-fluid rounded mb-2" alt="cover">
                        @endif
                        <input type="file" name="cover" class="form-control" accept="image/*">
                        <div class="form-text">Replaces the current cover. Max 8 MB.</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="isPublished" value="1" id="isPublished" {{ old('isPublished', $post->isPublished) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isPublished">Published</label>
                        <div class="form-text">Publishing (first time) notifies every member.</div>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">{{ $mode === 'edit' ? 'Save changes' : 'Create article' }}</button>
                    <a href="{{ route('anisenso-blog.index') }}" class="btn btn-light w-100 mt-2">Cancel</a>
                </div></div>
            </div>
        </div>
    </form>
@endsection
