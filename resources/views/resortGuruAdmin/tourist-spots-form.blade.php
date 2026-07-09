@extends('layouts.master')

@section('title') {{ $spot ? 'Edit' : 'New' }} Tourist Spot @endsection

@section('css')
<link href="{{ URL::asset('build/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') <a href="/resort-guru-tourist-spots">Tourist Spots</a> @endslot
@slot('title') {{ $spot ? 'Edit' : 'New' }} Tourist Spot @endslot
@endcomponent

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
</div>
@endif

<form action="{{ $spot ? '/resort-guru-tourist-spots-update' : '/resort-guru-tourist-spots-store' }}"
      method="POST" enctype="multipart/form-data">
    @csrf
    @if ($spot) <input type="hidden" name="id" value="{{ $spot->id }}"> @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Basics</h5>

                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $spot->name ?? '') }}" required maxlength="255">
                        <div class="form-text">e.g. <em>Hundred Islands</em>, <em>Magellan's Cross</em>, <em>Cagsawa Ruins</em></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control"
                                   value="{{ old('location', $spot->location ?? '') }}" maxlength="255"
                                   placeholder="e.g. Alaminos, Pangasinan">
                            <div class="form-text">Shows in the carousel + search result subtitle.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Region label</label>
                            <input type="text" name="region_label" class="form-control"
                                   value="{{ old('region_label', $spot->region_label ?? '') }}" maxlength="120"
                                   placeholder="e.g. North Luzon">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cluster</label>
                            <select name="cluster_tag" class="form-select">
                                <option value="">— select cluster —</option>
                                @foreach ($clusterOptions as $key => $label)
                                    <option value="{{ $key }}" {{ old('cluster_tag', $spot->cluster_tag ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Destination key <small class="text-muted">(optional)</small></label>
                            <input type="text" name="destination_key" class="form-control"
                                   value="{{ old('destination_key', $spot->destination_key ?? '') }}" maxlength="80"
                                   placeholder="e.g. alaminos-hundred-islands">
                            <div class="form-text">Matches a key in <code>database/data/destinations.php</code>. Used to group spots by destination.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Linked keyword page</label>
                        <select name="keyword_id" class="form-select select2-keyword">
                            <option value="">— not linked (won't appear in carousel) —</option>
                            @foreach ($keywords as $kw)
                                <option value="{{ $kw->id }}" {{ (int) old('keyword_id', $spot->keyword_id ?? 0) === (int) $kw->id ? 'selected' : '' }}>
                                    {{ $kw->phrase }}@if ($kw->cluster_tag) — {{ $kw->cluster_tag }}@endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">The "See nearby stays" CTA on the spot card sends visitors to this keyword page.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <small class="text-muted">(optional — used for future spot detail pages)</small></label>
                        <textarea name="description" rows="3" class="form-control" maxlength="2000">{{ old('description', $spot->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">Image</h5>

                    @if ($spot && $spot->media_id)
                        @php
                            $cur = \Illuminate\Support\Facades\DB::table('rg_media')->where('id', $spot->media_id)->first();
                            $previewUrl = $cur ? \App\Support\RgFrontend::urlFor('storage/' . ltrim($cur->path, '/')) : null;
                        @endphp
                        @if ($previewUrl)
                            <div class="mb-3">
                                <div class="text-muted small mb-1">Current image:</div>
                                <img src="{{ $previewUrl }}" alt="" style="max-width:320px;border-radius:8px;border:1px solid #e2e8f0">
                                <div class="text-muted small mt-1"><code>{{ $cur->path }}</code></div>
                            </div>
                        @endif
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Upload a new image</label>
                        <input type="file" name="image_upload" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <div class="form-text">Max 8 MB. JPG / PNG / WebP. Saved to <code>storage/app/public/rg-media/spots/</code>.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">…or pick an existing image</label>
                        <select name="media_id_pick" class="form-select select2-media">
                            <option value="">— leave current —</option>
                            @foreach ($mediaOptions as $m)
                                <option value="{{ $m->id }}">{{ $m->path }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Uploading a new file takes precedence over this selection.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Publishing</h5>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" id="status-published" name="status" value="published"
                                       {{ old('status', $spot->status ?? 'published') === 'published' ? 'checked' : '' }}>
                                <label class="form-check-label" for="status-published">Published</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" id="status-draft" name="status" value="draft"
                                       {{ old('status', $spot->status ?? '') === 'draft' ? 'checked' : '' }}>
                                <label class="form-check-label" for="status-draft">Draft</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Featured order <small class="text-muted">(carousel position)</small></label>
                        <input type="number" name="featured_order" class="form-control" min="0" max="9999"
                               value="{{ old('featured_order', $spot->featured_order ?? '') }}"
                               placeholder="Leave blank to hide from carousel">
                        <div class="form-text">Smaller numbers show first. Leave blank to keep this spot out of the carousel (it'll still appear in search).</div>
                    </div>

                    <hr>
                    <button class="btn btn-primary w-100" type="submit">
                        <i class="bx bx-save me-1"></i>{{ $spot ? 'Update' : 'Create' }} spot
                    </button>
                    <a href="/resort-guru-tourist-spots" class="btn btn-light w-100 mt-2">Cancel</a>
                </div>
            </div>

            @if ($spot)
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">Slug</h6>
                        <code>{{ $spot->slug }}</code>
                        <div class="text-muted small mt-2">Slug stays stable so any link to this spot keeps working.</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</form>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/select2/js/select2.min.js') }}"></script>
<script>
$(function () {
    $('.select2-keyword').select2({ placeholder: '— not linked —', allowClear: true, width: '100%' });
    $('.select2-media').select2({ placeholder: '— leave current —', allowClear: true, width: '100%' });
});
</script>
@endsection
