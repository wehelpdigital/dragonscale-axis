@extends('layouts.master')

@section('title') Add New Product @endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') Products @endslot
@slot('title') Add New Product @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Add New Product</h4>
                <p class="card-title-desc">Fill in the form below to add a new product.</p>

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Error!</strong> Please fix the following errors:
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form id="addProductForm" method="POST" action="{{ route('ecom-products.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="productName" class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('productName') is-invalid @enderror"
                                       id="productName" name="productName"
                                       value="{{ old('productName') }}"
                                       placeholder="Enter product name">
                                <div class="invalid-feedback" id="productNameError"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="productStore" class="form-label">Product Store <span class="text-danger">*</span></label>
                                <select class="form-select @error('productStore') is-invalid @enderror"
                                        id="productStore" name="productStore">
                                    <option value="">Select a store</option>
                                    <option value="Ani-Senso" {{ old('productStore') == 'Ani-Senso' ? 'selected' : '' }}>Ani-Senso</option>
                                </select>
                                <div class="invalid-feedback" id="productStoreError"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="productType" class="form-label">Product Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('productType') is-invalid @enderror"
                                        id="productType" name="productType">
                                    <option value="">Select product type</option>
                                    <option value="access" {{ old('productType') == 'access' ? 'selected' : '' }}>Access</option>
                                    <option value="ship" {{ old('productType') == 'ship' ? 'selected' : '' }}>Ship</option>
                                </select>
                                <div class="invalid-feedback" id="productTypeError"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="productDescription" class="form-label">Product Description <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('productDescription') is-invalid @enderror"
                                  id="productDescription" name="productDescription"
                                  rows="4"
                                  placeholder="Enter product description">{{ old('productDescription') }}</textarea>
                        <div class="invalid-feedback" id="productDescriptionError"></div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('ecom-products') }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save"></i> Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
$(document).ready(function() {
    // Remove validation classes on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
        $('#' + $(this).attr('id') + 'Error').text('');
    });

    // Form submission with dynamic validation
    $('#addProductForm').on('submit', function(e) {
        e.preventDefault();

        // Reset previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        let isValid = true;
        const errors = {};

        // Validate Product Name
        const productName = $('#productName').val().trim();
        if (!productName) {
            $('#productName').addClass('is-invalid');
            $('#productNameError').text('Product name is required.');
            isValid = false;
            errors.productName = 'Product name is required.';
        }

        // Validate Product Store
        const productStore = $('#productStore').val();
        if (!productStore) {
            $('#productStore').addClass('is-invalid');
            $('#productStoreError').text('Product store is required.');
            isValid = false;
            errors.productStore = 'Product store is required.';
        }

        // Validate Product Type
        const productType = $('#productType').val();
        if (!productType) {
            $('#productType').addClass('is-invalid');
            $('#productTypeError').text('Product type is required.');
            isValid = false;
            errors.productType = 'Product type is required.';
        }

        // Validate Product Description
        const productDescription = $('#productDescription').val().trim();
        if (!productDescription) {
            $('#productDescription').addClass('is-invalid');
            $('#productDescriptionError').text('Product description is required.');
            isValid = false;
            errors.productDescription = 'Product description is required.';
        }

        // If validation passes, submit the form
        if (isValid) {
            this.submit();
        } else {
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.is-invalid').first().offset().top - 100
            }, 500);
        }
    });
});
</script>
@endsection
