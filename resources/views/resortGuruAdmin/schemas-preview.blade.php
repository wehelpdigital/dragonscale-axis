@extends('layouts.master')

@section('title') Schema preview — {{ $page->slug }} @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('title') Schema live preview @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h5 class="card-title mb-1">{{ $page->title }}</h5>
                <small class="text-muted">Fetched from <a href="{{ $url }}" target="_blank">{{ $url }}</a></small>
            </div>
            <a href="/resort-guru-schemas" class="btn btn-sm btn-light">Back to schemas</a>
        </div>

        @if(!empty($error))
            <div class="alert alert-warning">{{ $error }}</div>
        @endif

        @if(empty($blocks))
            <div class="text-center text-muted py-4">No JSON-LD blocks found on the page.</div>
        @else
            <div class="alert alert-success small mb-3">
                <strong>{{ count($blocks) }} JSON-LD block{{ count($blocks) === 1 ? '' : 's' }}</strong> emitted on the live page.
                Each block is what Google + AI engines see. Dynamic data (listings, reviews, FAQ items) is included as it exists right now.
            </div>
            @foreach($blocks as $i => $block)
                <details class="mb-3 border rounded p-3" {{ $i === 0 ? 'open' : '' }}>
                    <summary class="fw-bold">
                        @php
                            $first = json_decode($block, true);
                            $type = $first['@type'] ?? 'Unknown';
                        @endphp
                        Block {{ $i + 1 }} — <code class="text-primary">{{ is_array($type) ? implode(' + ', $type) : $type }}</code>
                    </summary>
                    <pre class="bg-light border rounded p-3 mt-2 small" style="max-height:500px;overflow:auto">{{ $block }}</pre>
                </details>
            @endforeach
        @endif
    </div>
</div>
@endsection
