@extends('layouts.master')

@section('title') Add Store @endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.logo-preview-container {
    width: 150px;
    height: 150px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    cursor: pointer;
    transition: all 0.2s ease;
    overflow: hidden;
}
.logo-preview-container:hover {
    border-color: #556ee6;
    background-color: #f0f4ff;
}
.logo-preview-container img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.logo-preview-container .placeholder-content {
    text-align: center;
    color: #adb5bd;
}
.logo-preview-container .placeholder-content i {
    font-size: 32px;
    margin-bottom: 8px;
}
.logo-preview-container .upload-loader {
    display: none;
    text-align: center;
}
.logo-preview-container .upload-loader i {
    font-size: 32px;
    color: #556ee6;
}
.logo-preview-container.loading .placeholder-content,
.logo-preview-container.loading img {
    display: none !important;
}
.logo-preview-container.loading .upload-loader {
    display: block !important;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
    @slot('li_1') E-commerce @endslot
    @slot('li_2') <a href="{{ route('ecom-stores') }}">Stores</a> @endslot
    @slot('title') Add Store @endslot
@endcomponent

<!-- Flash Messages -->
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4">Add New Store</h4>

                <form action="{{ route('ecom-stores.store') }}" method="POST" enctype="multipart/form-data" id="storeForm">
                    @csrf

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="storeName" class="form-label">Store Name <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('storeName') is-invalid @enderror"
                                       id="storeName"
                                       name="storeName"
                                       value="{{ old('storeName') }}"
                                       placeholder="Enter store name"
                                       required>
                                @error('storeName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="storeDescription" class="form-label">Store Description</label>
                                <textarea class="form-control @error('storeDescription') is-invalid @enderror"
                                          id="storeDescription"
                                          name="storeDescription"
                                          rows="4"
                                          placeholder="Enter store description (optional)">{{ old('storeDescription') }}</textarea>
                                @error('storeDescription')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Maximum 1000 characters</small>
                            </div>

                            <div class="mb-3">
                                <label for="isActive" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('isActive') is-invalid @enderror"
                                        id="isActive"
                                        name="isActive"
                                        required>
                                    <option value="1" {{ old('isActive', '1') == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('isActive') == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('isActive')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Store Logo</label>
                                <div class="logo-preview-container" id="logoPreviewContainer" onclick="document.getElementById('storeLogo').click()">
                                    <div class="placeholder-content" id="logoPlaceholder">
                                        <i class="bx bx-image-add d-block"></i>
                                        <span class="small">Click to upload</span>
                                    </div>
                                    <div class="upload-loader" id="uploadLoader">
                                        <i class="bx bx-loader-alt bx-spin d-block"></i>
                                        <span class="small text-muted">Loading...</span>
                                    </div>
                                    <img src="" alt="Logo Preview" id="logoPreview" class="d-none">
                                </div>
                                <input type="file"
                                       class="form-control d-none @error('storeLogo') is-invalid @enderror"
                                       id="storeLogo"
                                       name="storeLogo"
                                       accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml">
                                @error('storeLogo')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <small class="text-muted d-block mt-2">Max 2MB. Formats: JPEG, PNG, JPG, GIF, SVG</small>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2 d-none" id="removeLogo">
                                    <i class="bx bx-trash me-1"></i>Remove Logo
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('ecom-stores') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Create Store
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="bx bx-info-circle me-2 text-info"></i>Tips</h5>
                <ul class="text-muted mb-0" style="padding-left: 1.2rem;">
                    <li class="mb-2">Store name should be unique and descriptive</li>
                    <li class="mb-2">Use a clear logo that represents your store brand</li>
                    <li class="mb-2">Inactive stores won't appear in product creation forms</li>
                    <li>Products can be assigned to stores when creating or editing them</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Toastr options
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Logo preview handler
    $('#storeLogo').on('change', function() {
        const file = this.files[0];
        if (file) {
            // Validate file size (2MB)
            if (file.size > 2 * 1024 * 1024) {
                toastr.error('File size must be less than 2MB', 'Error!');
                this.value = '';
                return;
            }

            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml'];
            if (!validTypes.includes(file.type)) {
                toastr.error('Invalid file type. Please upload an image.', 'Error!');
                this.value = '';
                return;
            }

            // Show loader
            $('#logoPreviewContainer').addClass('loading');

            const reader = new FileReader();
            reader.onload = function(e) {
                // Create a new image to ensure it's loaded before displaying
                const img = new Image();
                img.onload = function() {
                    $('#logoPreview').attr('src', e.target.result).removeClass('d-none');
                    $('#logoPlaceholder').addClass('d-none');
                    $('#removeLogo').removeClass('d-none');
                    $('#logoPreviewContainer').removeClass('loading');
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Remove logo handler
    $('#removeLogo').on('click', function() {
        $('#storeLogo').val('');
        $('#logoPreview').addClass('d-none').attr('src', '');
        $('#logoPlaceholder').removeClass('d-none');
        $(this).addClass('d-none');
    });

    // Character count for description
    $('#storeDescription').on('input', function() {
        const remaining = 1000 - $(this).val().length;
        if (remaining < 100) {
            $(this).next('.invalid-feedback').length || $(this).after('<small class="text-warning chars-remaining">' + remaining + ' characters remaining</small>');
            $(this).siblings('.chars-remaining').text(remaining + ' characters remaining');
        } else {
            $(this).siblings('.chars-remaining').remove();
        }
    });
});
</script>
@endsection
