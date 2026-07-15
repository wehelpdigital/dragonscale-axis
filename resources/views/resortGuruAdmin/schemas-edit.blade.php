@extends('layouts.master')

@section('title') Custom schema for {{ $page->slug }} @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('title') Custom schema — {{ $page->slug }} @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h5 class="card-title mb-1">{{ $page->title }}</h5>
                <small class="text-muted"><code>/{{ $page->slug }}</code></small>
            </div>
            <div>
                <a href="/resort-guru-schemas-preview?id={{ $page->id }}" class="btn btn-sm btn-outline-info"><i class="bx bx-show me-1"></i>Live preview</a>
                <a href="/resort-guru-schemas" class="btn btn-sm btn-light">Back to schemas</a>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form method="POST" action="/resort-guru-schemas-update">
            @csrf
            <input type="hidden" name="id" value="{{ $page->id }}">

            <div class="alert alert-info small">
                <strong>How this works:</strong> Paste a single JSON-LD object (or null to clear). It will be emitted as an
                additional <code>&lt;script type="application/ld+json"&gt;</code> tag <em>in addition to</em> the auto-generated
                BreadcrumbList, Article, FAQPage, ItemList, and AggregateRating schemas. Use this for things like custom Event,
                Recipe, HowTo, or specialized LocalBusiness markup.
            </div>

            <div class="mb-3">
                <label class="form-label">Custom JSON-LD (leave blank to remove)</label>
                <textarea name="schema_json" class="form-control font-monospace" rows="20" placeholder='{
  "@context": "https://schema.org",
  "@type": "Event",
  "name": "Pahiyas Festival",
  "startDate": "2026-05-15",
  "location": {
    "@type": "Place",
    "name": "Lucban, Quezon",
    "address": "Lucban, Quezon, Philippines"
  }
}'>{{ old('schema_json', $page->schema_json) }}</textarea>
                <small class="form-text text-muted">Must be valid JSON. Validation happens on save.</small>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success"><i class="bx bx-save me-1"></i>Save custom schema</button>
                <a href="/resort-guru-schemas" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
