@extends('layouts.master')

@section('title') Resort Guru Settings @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Settings @endslot
@endcomponent

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

{{-- Each row in rg_settings carries a `group` column. We render one
     tab per group with a Bootstrap nav-tabs control + tab-content
     panels below. The Frontend tab is populated by the
     App\Support\RgFrontend helper on first access (it self-seeds an
     `rg_frontend_url` row in rg_settings if missing). --}}

@php
    // Friendlier display labels for known group keys; the rest fall
    // through to a title-cased version of the raw group name.
    $labels = [
        'frontend' => 'Frontend',
        'pricing' => 'Pricing',
        'listing' => 'Listing',
        'general' => 'General',
        'integrations' => 'Integrations',
        'ai' => 'AI',
        'seo' => 'SEO',
        'email' => 'Email',
        'media' => 'Media',
        'bidding' => 'Bidding',
    ];
    $tabIcons = [
        'frontend' => 'bx-link-external',
        'pricing' => 'bx-dollar',
        'listing' => 'bx-list-ul',
        'general' => 'bx-cog',
        'integrations' => 'bx-plug',
        'ai' => 'bx-brain',
        'seo' => 'bx-search-alt',
        'email' => 'bx-envelope',
        'media' => 'bx-image',
        'bidding' => 'bx-trending-up',
    ];
    // Sort so "frontend" sits first — it's the most-actionable + new.
    $groupKeys = $grouped->keys()->all();
    usort($groupKeys, function ($a, $b) {
        if ($a === 'frontend') return -1;
        if ($b === 'frontend') return 1;
        return strcmp($a, $b);
    });
@endphp

<form action="{{ route('resort-guru-settings.update') }}" method="POST">
    @csrf

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                @foreach($groupKeys as $i => $group)
                    @php
                        $label = $labels[$group] ?? ucwords(str_replace('_', ' ', $group));
                        $icon = $tabIcons[$group] ?? 'bx-cog';
                    @endphp
                    <li class="nav-item">
                        <a class="nav-link {{ $i === 0 ? 'active' : '' }}"
                           data-bs-toggle="tab" href="#rg-settings-tab-{{ $group }}" role="tab">
                            <i class="bx {{ $icon }} align-middle me-1"></i>
                            <span>{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content">
                @foreach($groupKeys as $i => $group)
                    <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}"
                         id="rg-settings-tab-{{ $group }}" role="tabpanel">
                        <div class="row g-3">
                            @foreach($grouped[$group] as $row)
                                @php
                                    // Frontend URL gets a full-width row with extra
                                    // formatting because it is the most-impactful
                                    // setting on this whole page.
                                    $isFullWidth = $row->key === 'rg_frontend_url';
                                @endphp
                                <div class="{{ $isFullWidth ? 'col-12' : 'col-md-6' }}">
                                    <label class="form-label">
                                        {{ $row->label }}
                                        <code class="ms-2 text-muted small">{{ $row->key }}</code>
                                    </label>
                                    @if($row->type === 'text')
                                        <textarea name="{{ $row->key }}" class="form-control" rows="3">{{ $row->value }}</textarea>
                                    @elseif($row->type === 'bool')
                                        <select name="{{ $row->key }}" class="form-select">
                                            <option value="1" {{ $row->value == '1' ? 'selected' : '' }}>Yes</option>
                                            <option value="0" {{ $row->value == '0' ? 'selected' : '' }}>No</option>
                                        </select>
                                    @elseif($row->type === 'int')
                                        <input type="number" name="{{ $row->key }}" value="{{ $row->value }}" class="form-control">
                                    @else
                                        <input type="text" name="{{ $row->key }}" value="{{ $row->value }}" class="form-control"
                                               @if($row->key === 'rg_frontend_url')
                                                   placeholder="http://localhost:8001"
                                                   spellcheck="false"
                                                   style="font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;"
                                               @endif>
                                    @endif
                                    @if($row->help_text)
                                        <small class="text-muted d-block mt-1">{{ $row->help_text }}</small>
                                    @endif
                                    @if($row->key === 'rg_frontend_url')
                                        <div class="alert alert-info py-2 px-3 mt-2 mb-0 small">
                                            <i class="bx bx-info-circle me-1"></i>
                                            Set this to the URL where you can reach the public Resort Guru PH frontend in a browser.
                                            <strong>Do not include a trailing slash.</strong> This value powers the Live Editor preview iframe,
                                            every "View live" button, the URL slug previews on the create / edit page forms, and the schema
                                            validator.
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="text-end mb-4">
        <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save All Settings</button>
    </div>
</form>
@endsection
