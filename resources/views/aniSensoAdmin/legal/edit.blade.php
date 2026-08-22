@extends('layouts.master')

@section('title') Edit {{ $page->title }} @endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') <a href="{{ route('anisenso-legal.index') }}">Legal &amp; Info</a> @endslot
        @slot('title') Edit @endslot
    @endcomponent

    <form method="POST" action="{{ route('anisenso-legal.update', ['id' => $page->id]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-9"><div class="card"><div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required maxlength="191" value="{{ old('title', $page->title) }}">
                    @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <textarea name="body" class="form-control" rows="18" placeholder="Basic HTML allowed: headings (h3/h4), paragraphs, lists, bold, links.">{{ old('body', $page->body) }}</textarea>
                    <div class="form-text">Allowed tags are sanitised on display (p, h3, h4, ul/ol/li, b/strong, i/em, a, blockquote).</div>
                </div>
            </div></div></div>

            <div class="col-lg-3"><div class="card"><div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input type="text" class="form-control" value="/legal/{{ $page->slug }}" disabled>
                    <div class="form-text">The public URL. Fixed for the core pages.</div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="isPublished" value="1" id="isPublished" {{ old('isPublished', $page->isPublished) ? 'checked' : '' }}>
                    <label class="form-check-label" for="isPublished">Published</label>
                </div>
                <button class="btn btn-primary w-100" type="submit">Save changes</button>
                <a href="{{ route('anisenso-legal.index') }}" class="btn btn-light w-100 mt-2">Cancel</a>
                <a href="#" class="btn btn-soft-secondary w-100 mt-2" onclick="window.open('{{ rtrim(config('anisystem.url'),'/') }}/legal/{{ $page->slug }}','_blank');return false;">Preview ↗</a>
            </div></div></div>
        </div>
    </form>
@endsection
