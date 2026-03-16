@extends('layouts.master')

@section('title') View Crop Breed @endsection

@section('css')
<style>
    .breed-detail-card {
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .breed-image-container {
        position: relative;
        background: #f8f9fa;
        border-radius: 8px;
        overflow: hidden;
        text-align: center;
        padding: 20px;
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .breed-image-container img {
        max-width: 100%;
        max-height: 300px;
        border-radius: 8px;
    }
    .breed-image-placeholder {
        color: #adb5bd;
    }
    .breed-image-placeholder i {
        font-size: 5rem;
    }
    .detail-label {
        font-weight: 600;
        color: #495057;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }
    .detail-value {
        color: #212529;
        font-size: 1rem;
    }
    .gene-tag {
        display: inline-block;
        background: #e9ecef;
        color: #495057;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        margin: 3px;
    }
    .info-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        height: 100%;
    }
    .info-card .icon {
        font-size: 2rem;
        color: #556ee6;
    }
    .brochure-link {
        display: inline-flex;
        align-items: center;
        background: #dc3545;
        color: #fff;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        transition: all 0.2s;
    }
    .brochure-link:hover {
        background: #c82333;
        color: #fff;
    }
    .brochure-link i {
        font-size: 1.5rem;
        margin-right: 10px;
    }
    .source-link {
        display: inline-flex;
        align-items: center;
        color: #556ee6;
        text-decoration: none;
    }
    .source-link:hover {
        text-decoration: underline;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Knowledgebase @endslot
        @slot('li_3') Crop Breeds @endslot
        @slot('title') {{ $breed->name }} @endslot
    @endcomponent

    <div class="row">
        <!-- Left Column: Image and Quick Info -->
        <div class="col-lg-4">
            <div class="card breed-detail-card mb-4">
                <div class="card-body">
                    <div class="breed-image-container mb-3">
                        @if($breed->imagePath)
                            <img src="{{ asset($breed->imagePath) }}" alt="{{ $breed->name }}">
                        @else
                            <div class="breed-image-placeholder">
                                <i class="bx bx-leaf"></i>
                                <p class="text-muted mt-2 mb-0">No image available</p>
                            </div>
                        @endif
                    </div>

                    <h4 class="text-dark text-center mb-3">{{ $breed->name }}</h4>

                    <div class="text-center mb-3">
                        @if($breed->cropType == 'corn')
                            <span class="badge bg-warning text-dark px-3 py-2">
                                <i class="bx bx-leaf me-1"></i>Corn (Mais)
                            </span>
                        @else
                            <span class="badge bg-success px-3 py-2">
                                <i class="bx bx-leaf me-1"></i>Rice (Palay)
                            </span>
                        @endif

                        @if($breed->breedType)
                            <span class="badge bg-primary px-3 py-2 ms-1">
                                {{ $breedTypeLabels[$breed->breedType] ?? $breed->breedType }}
                            </span>
                        @endif

                        @if($breed->cropType == 'corn' && $breed->cornType)
                            <span class="badge bg-info text-white px-3 py-2 ms-1">
                                {{ $cornTypeLabels[$breed->cornType] ?? $breed->cornType }}
                            </span>
                        @endif
                    </div>

                    @if($breed->manufacturer)
                        <p class="text-center text-secondary mb-3">
                            <i class="bx bx-building me-1"></i>{{ $breed->manufacturer }}
                        </p>
                    @endif

                    <hr>

                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('knowledgebase.crop-breeds.edit', ['id' => $breed->id]) }}" class="btn btn-primary">
                            <i class="bx bx-edit me-1"></i>Edit
                        </a>
                        <a href="{{ route('knowledgebase.crop-breeds') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Brochure Download -->
            @if($breed->brochurePath)
                <div class="card breed-detail-card mb-4">
                    <div class="card-body text-center">
                        <h6 class="text-dark mb-3"><i class="bx bx-file-blank me-2"></i>Product Brochure</h6>
                        <a href="{{ asset($breed->brochurePath) }}" target="_blank" class="brochure-link">
                            <i class="bx bxs-file-pdf"></i>
                            Download PDF
                        </a>
                    </div>
                </div>
            @endif

            <!-- Source URL -->
            @if($breed->sourceUrl)
                <div class="card breed-detail-card mb-4">
                    <div class="card-body">
                        <h6 class="text-dark mb-3"><i class="bx bx-link me-2"></i>Source</h6>
                        <a href="{{ $breed->sourceUrl }}" target="_blank" class="source-link">
                            <i class="bx bx-link-external me-2"></i>
                            {{ parse_url($breed->sourceUrl, PHP_URL_HOST) }}
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Detailed Information -->
        <div class="col-lg-8">
            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="info-card">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-target-lock icon me-3"></i>
                            <div>
                                <div class="detail-label">Potential Yield</div>
                                <div class="detail-value">{{ $breed->potentialYield ?? 'Not specified' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="info-card">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-time icon me-3"></i>
                            <div>
                                <div class="detail-label">Maturity</div>
                                <div class="detail-value">{{ $breed->maturityDays ?? 'Not specified' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="info-card">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-check-circle icon me-3"></i>
                            <div>
                                <div class="detail-label">Status</div>
                                <div class="detail-value">
                                    @if($breed->isActive)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gene Protection / Traits -->
            @if($breed->geneProtection && count($breed->geneProtection) > 0)
                <div class="card breed-detail-card mb-4">
                    <div class="card-body">
                        <h5 class="text-dark mb-3"><i class="bx bx-shield me-2"></i>Gene Protection & Traits</h5>
                        <div>
                            @foreach($breed->geneProtection as $gene)
                                <span class="gene-tag">{{ $gene }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Characteristics -->
            @if($breed->characteristics)
                <div class="card breed-detail-card mb-4">
                    <div class="card-body">
                        <h5 class="text-dark mb-3"><i class="bx bx-info-circle me-2"></i>Characteristics</h5>
                        <p class="text-dark mb-0" style="white-space: pre-wrap;">{{ $breed->characteristics }}</p>
                    </div>
                </div>
            @endif

            <!-- Related Information -->
            @if($breed->relatedInformation)
                <div class="card breed-detail-card mb-4">
                    <div class="card-body">
                        <h5 class="text-dark mb-3"><i class="bx bx-book-open me-2"></i>Related Information</h5>
                        <p class="text-dark mb-0" style="white-space: pre-wrap;">{{ $breed->relatedInformation }}</p>
                    </div>
                </div>
            @endif

            <!-- Meta Information -->
            <div class="card breed-detail-card">
                <div class="card-body">
                    <h6 class="text-secondary mb-3"><i class="bx bx-time me-2"></i>Record Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Created: {{ $breed->created_at->format('M d, Y h:i A') }}</small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted">Last Updated: {{ $breed->updated_at->format('M d, Y h:i A') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
@endsection
