@extends('layouts.master')

@section('title') Add Crop Breed @endsection

@section('css')
<style>
    .form-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .form-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #dee2e6;
    }
    .corn-type-section {
        display: none;
    }
    .file-preview-container {
        margin-top: 10px;
    }
    .file-preview-container img {
        max-width: 150px;
        max-height: 150px;
        border-radius: 8px;
        border: 2px solid #dee2e6;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Knowledgebase @endslot
        @slot('li_3') Crop Breeds @endslot
        @slot('title') Add New Breed @endslot
    @endcomponent

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-0">
                        <i class="bx bx-plus-circle text-primary me-2"></i>Add New Crop Breed
                    </h4>
                    <p class="text-secondary mb-0 mt-1">Enter the details of the crop variety</p>
                </div>
                <a href="{{ route('knowledgebase.crop-breeds') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to List
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('knowledgebase.crop-breeds.store') }}" enctype="multipart/form-data">
                @csrf

                <!-- Basic Information -->
                <div class="form-section">
                    <h6 class="form-section-title"><i class="bx bx-info-circle me-2"></i>Basic Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label text-dark">Variety Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" placeholder="e.g., NSIC Rc222">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="manufacturer" class="form-label text-dark">Manufacturer / Developer</label>
                            <input type="text" class="form-control @error('manufacturer') is-invalid @enderror"
                                   id="manufacturer" name="manufacturer" value="{{ old('manufacturer') }}" placeholder="e.g., PhilRice, Pioneer, Syngenta">
                            @error('manufacturer')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="cropType" class="form-label text-dark">Crop Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('cropType') is-invalid @enderror" id="cropType" name="cropType">
                                <option value="">Select Crop Type</option>
                                @foreach($cropTypeLabels as $value => $label)
                                    <option value="{{ $value }}" {{ old('cropType') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('cropType')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="breedType" class="form-label text-dark">Breed Type</label>
                            <select class="form-select @error('breedType') is-invalid @enderror" id="breedType" name="breedType">
                                <option value="">Select Breed Type</option>
                                @foreach($breedTypeLabels as $value => $label)
                                    <option value="{{ $value }}" {{ old('breedType') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('breedType')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4 mb-3 corn-type-section" id="cornTypeSection">
                            <label for="cornType" class="form-label text-dark">Corn Type</label>
                            <select class="form-select @error('cornType') is-invalid @enderror" id="cornType" name="cornType">
                                <option value="">Select Corn Type</option>
                                @foreach($cornTypeLabels as $value => $label)
                                    <option value="{{ $value }}" {{ old('cornType') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('cornType')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Yield & Maturity -->
                <div class="form-section">
                    <h6 class="form-section-title"><i class="bx bx-bar-chart-alt-2 me-2"></i>Yield & Maturity</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="potentialYield" class="form-label text-dark">Potential Yield</label>
                            <input type="text" class="form-control @error('potentialYield') is-invalid @enderror"
                                   id="potentialYield" name="potentialYield" value="{{ old('potentialYield') }}" placeholder="e.g., 6-8 tons/ha">
                            <small class="text-secondary">Average yield per hectare</small>
                            @error('potentialYield')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="maturityDays" class="form-label text-dark">Days to Maturity</label>
                            <input type="text" class="form-control @error('maturityDays') is-invalid @enderror"
                                   id="maturityDays" name="maturityDays" value="{{ old('maturityDays') }}" placeholder="e.g., 110-115 days">
                            <small class="text-secondary">From planting to harvest</small>
                            @error('maturityDays')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Gene Protection & Characteristics -->
                <div class="form-section">
                    <h6 class="form-section-title"><i class="bx bx-shield me-2"></i>Gene Protection & Characteristics</h6>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="geneProtection" class="form-label text-dark">Gene Protection / Resistance</label>
                            <input type="text" class="form-control @error('geneProtection') is-invalid @enderror"
                                   id="geneProtection" name="geneProtection" value="{{ old('geneProtection') }}"
                                   placeholder="e.g., Bacterial Leaf Blight, Tungro, Brown Planthopper">
                            <small class="text-secondary">Enter comma-separated values for multiple resistances</small>
                            @error('geneProtection')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="characteristics" class="form-label text-dark">Characteristics</label>
                            <textarea class="form-control @error('characteristics') is-invalid @enderror"
                                      id="characteristics" name="characteristics" rows="3"
                                      placeholder="e.g., Semi-dwarf plant type, good tillering ability, intermediate amylose content">{{ old('characteristics') }}</textarea>
                            <small class="text-secondary">Physical and agronomic characteristics</small>
                            @error('characteristics')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Files & Media -->
                <div class="form-section">
                    <h6 class="form-section-title"><i class="bx bx-image me-2"></i>Files & Media</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="breedImage" class="form-label text-dark">Breed Image</label>
                            <input type="file" class="form-control @error('breedImage') is-invalid @enderror"
                                   id="breedImage" name="breedImage" accept="image/*">
                            <small class="text-secondary">Max 5MB. Formats: JPEG, PNG, GIF, WebP</small>
                            @error('breedImage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="file-preview-container" id="imagePreview" style="display: none;">
                                <p class="text-dark mb-1"><small>Preview:</small></p>
                                <img src="" alt="Preview" id="imagePreviewImg">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="brochure" class="form-label text-dark">Product Brochure (PDF)</label>
                            <input type="file" class="form-control @error('brochure') is-invalid @enderror"
                                   id="brochure" name="brochure" accept=".pdf">
                            <small class="text-secondary">Max 10MB. PDF only</small>
                            @error('brochure')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="sourceUrl" class="form-label text-dark">Source URL</label>
                            <input type="url" class="form-control @error('sourceUrl') is-invalid @enderror"
                                   id="sourceUrl" name="sourceUrl" value="{{ old('sourceUrl') }}"
                                   placeholder="https://www.example.com/variety-info">
                            <small class="text-secondary">Link to official product page or documentation</small>
                            @error('sourceUrl')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="form-section">
                    <h6 class="form-section-title"><i class="bx bx-file me-2"></i>Additional Information</h6>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="relatedInformation" class="form-label text-dark">Related Information</label>
                            <textarea class="form-control @error('relatedInformation') is-invalid @enderror"
                                      id="relatedInformation" name="relatedInformation" rows="4"
                                      placeholder="Any additional notes, recommendations, or important details about this variety">{{ old('relatedInformation') }}</textarea>
                            @error('relatedInformation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('knowledgebase.crop-breeds') }}" class="btn btn-secondary">
                        <i class="bx bx-x me-1"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i>Save Breed
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
<script>
    // Show/hide corn type based on crop type selection
    $('#cropType').on('change', function() {
        if ($(this).val() === 'corn') {
            $('#cornTypeSection').show();
        } else {
            $('#cornTypeSection').hide();
            $('#cornType').val('');
        }
    });

    // Initialize on page load
    if ($('#cropType').val() === 'corn') {
        $('#cornTypeSection').show();
    }

    // Image preview
    $('#breedImage').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreviewImg').attr('src', e.target.result);
                $('#imagePreview').show();
            };
            reader.readAsDataURL(file);
        } else {
            $('#imagePreview').hide();
        }
    });
</script>
@endsection
