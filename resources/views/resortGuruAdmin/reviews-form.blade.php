@extends('layouts.master')

@section('title') {{ $review ? 'Edit Review' : 'Add Review' }} @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') {{ $review ? 'Edit Review' : 'Add Review' }} @endslot
@endcomponent

@php $action = $review ? '/resort-guru-reviews-update' : '/resort-guru-reviews-store'; @endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if($review)<input type="hidden" name="id" value="{{ $review->id }}">@endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Review</h5>

                    <div class="mb-3">
                        <label class="form-label">Keyword (destination this review attaches to)</label>
                        <select name="keyword_id" class="form-select">
                            <option value="">— Global (shows on all pages) —</option>
                            @foreach($keywords as $k)
                                <option value="{{ $k->id }}" {{ (int) old('keyword_id', $review->keyword_id ?? 0) === (int) $k->id ? 'selected' : '' }}>{{ $k->phrase }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reviewer name *</label>
                            <input type="text" name="reviewer_name" class="form-control" value="{{ old('reviewer_name', $review->reviewer_name ?? '') }}" maxlength="120" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reviewer location</label>
                            <input type="text" name="reviewer_location" class="form-control" placeholder="e.g. Quezon City, Cebu" value="{{ old('reviewer_location', $review->reviewer_location ?? '') }}" maxlength="120">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reviewer avatar URL (optional)</label>
                        <input type="url" name="reviewer_avatar" class="form-control" placeholder="https://i.pravatar.cc/200?img=12" value="{{ old('reviewer_avatar', $review->reviewer_avatar ?? '') }}" maxlength="500">
                        <small class="form-text text-muted">Leave blank to auto-generate from the reviewer name.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Rating *</label>
                            <select name="rating" class="form-select" required>
                                @for($i=5;$i>=1;$i--)
                                    <option value="{{ $i }}" {{ (int) old('rating', $review->rating ?? 5) === $i ? 'selected' : '' }}>{{ str_repeat('★', $i) }} ({{ $i }})</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Review date</label>
                            <input type="date" name="review_date" class="form-control" value="{{ old('review_date', $review->review_date ?? '') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sort order</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $review->sort_order ?? 0) }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Review text *</label>
                        <textarea name="review_text" class="form-control" rows="5" required>{{ old('review_text', $review->review_text ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Visibility</h5>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="published" {{ ($review->status ?? 'published') === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="draft" {{ ($review->status ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>
                    <label class="form-check">
                        <input type="checkbox" name="is_featured" value="1" class="form-check-input" {{ ($review->is_featured ?? false) ? 'checked' : '' }}>
                        <span class="form-check-label">Featured (shown first)</span>
                    </label>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success"><i class="bx bx-save me-1"></i>{{ $review ? 'Update Review' : 'Add Review' }}</button>
                <a href="/resort-guru-reviews" class="btn btn-light">Cancel</a>
            </div>
        </div>
    </div>
</form>
@endsection
