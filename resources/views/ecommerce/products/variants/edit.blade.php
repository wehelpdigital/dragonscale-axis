@extends('layouts.master')

@section('title') Edit Variant @endsection

@section('css')
<style>
    .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .form-control.is-valid {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
    }

    .invalid-feedback {
        display: block;
    }

    .valid-feedback {
        display: block;
    }
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') Products @endslot
@slot('li_3') Variants @endslot
@slot('title') Edit Variant @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title">Edit Variant</h4>
                        <p class="card-title-desc">Edit variant: <strong>{{ $variant->ecomVariantName }}</strong> ({{ $product->productName }})</p>
                    </div>
                    <a href="{{ route('ecom-products.variants', ['id' => $product->id]) }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Back to Variants
                    </a>
                </div>

                <!-- Edit Variant Form -->
                <form action="{{ route('ecom-products.variants.update') }}" method="POST" id="variantForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="variantId" value="{{ $variant->id }}">

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="ecomVariantName" class="form-label">Variant Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('ecomVariantName') is-invalid @enderror"
                                       id="ecomVariantName" name="ecomVariantName"
                                       value="{{ old('ecomVariantName', $variant->ecomVariantName) }}"
                                       placeholder="Enter variant name">
                                <div class="invalid-feedback" id="ecomVariantName-error"></div>
                                <div class="valid-feedback" id="ecomVariantName-success">Looks good!</div>
                                @error('ecomVariantName')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="ecomVariantPrice" class="form-label">Variant Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control @error('ecomVariantPrice') is-invalid @enderror"
                                           id="ecomVariantPrice" name="ecomVariantPrice"
                                           value="{{ old('ecomVariantPrice', number_format($variant->ecomVariantPrice, 2)) }}"
                                           placeholder="0.00">
                                </div>
                                <div class="invalid-feedback" id="ecomVariantPrice-error"></div>
                                <div class="valid-feedback" id="ecomVariantPrice-success">Looks good!</div>
                                @error('ecomVariantPrice')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="stocksAvailable" class="form-label">Stocks Available <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('stocksAvailable') is-invalid @enderror"
                                       id="stocksAvailable" name="stocksAvailable"
                                       value="{{ old('stocksAvailable', $variant->stocksAvailable) }}"
                                       placeholder="0">
                                <div class="invalid-feedback" id="stocksAvailable-error"></div>
                                <div class="valid-feedback" id="stocksAvailable-success">Looks good!</div>
                                @error('stocksAvailable')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="ecomVariantDescription" class="form-label">Variant Description <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('ecomVariantDescription') is-invalid @enderror"
                                          id="ecomVariantDescription" name="ecomVariantDescription"
                                          rows="3" placeholder="Enter variant description">{{ old('ecomVariantDescription', $variant->ecomVariantDescription) }}</textarea>
                                <div class="invalid-feedback" id="ecomVariantDescription-error"></div>
                                <div class="valid-feedback" id="ecomVariantDescription-success">Looks good!</div>
                                @error('ecomVariantDescription')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('ecom-products.variants', ['id' => $product->id]) }}" class="btn btn-secondary">
                                    <i class="bx bx-x"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save"></i> Update Variant
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('variantForm');
    const variantNameInput = document.getElementById('ecomVariantName');
    const variantPriceInput = document.getElementById('ecomVariantPrice');
    const stocksAvailableInput = document.getElementById('stocksAvailable');
    const variantDescriptionInput = document.getElementById('ecomVariantDescription');

    // Validation functions
    function validateVariantName() {
        const value = variantNameInput.value.trim();
        if (value === '') {
            showError(variantNameInput, 'ecomVariantName-error', 'Variant name is required.');
            return false;
        } else if (value.length > 255) {
            showError(variantNameInput, 'ecomVariantName-error', 'Variant name must not exceed 255 characters.');
            return false;
        } else {
            showSuccess(variantNameInput, 'ecomVariantName-success');
            return true;
        }
    }

    function validateVariantPrice() {
        const value = variantPriceInput.value.trim();
        if (value === '') {
            showError(variantPriceInput, 'ecomVariantPrice-error', 'Variant price is required.');
            return false;
        }

        // Remove currency symbol and commas, then validate
        const cleanValue = value.replace(/[₱,\s]/g, '');
        const price = parseFloat(cleanValue);

        if (isNaN(price)) {
            showError(variantPriceInput, 'ecomVariantPrice-error', 'Variant price must be a valid number.');
            return false;
        } else if (price < 0) {
            showError(variantPriceInput, 'ecomVariantPrice-error', 'Variant price must be greater than or equal to 0.');
            return false;
        } else {
            showSuccess(variantPriceInput, 'ecomVariantPrice-success');
            return true;
        }
    }

    function validateStocksAvailable() {
        const value = stocksAvailableInput.value.trim();
        if (value === '') {
            showError(stocksAvailableInput, 'stocksAvailable-error', 'Stocks available is required.');
            return false;
        }

        // Remove commas and validate
        const cleanValue = value.replace(/[,]/g, '');
        const stocks = parseInt(cleanValue);

        if (isNaN(stocks)) {
            showError(stocksAvailableInput, 'stocksAvailable-error', 'Stocks available must be a valid number.');
            return false;
        } else if (stocks < 0) {
            showError(stocksAvailableInput, 'stocksAvailable-error', 'Stocks available must be greater than or equal to 0.');
            return false;
        } else {
            showSuccess(stocksAvailableInput, 'stocksAvailable-success');
            return true;
        }
    }

    function validateVariantDescription() {
        const value = variantDescriptionInput.value.trim();
        if (value === '') {
            showError(variantDescriptionInput, 'ecomVariantDescription-error', 'Variant description is required.');
            return false;
        } else if (value.length > 1000) {
            showError(variantDescriptionInput, 'ecomVariantDescription-error', 'Variant description must not exceed 1000 characters.');
            return false;
        } else {
            showSuccess(variantDescriptionInput, 'ecomVariantDescription-success');
            return true;
        }
    }

    function showError(input, errorId, message) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        document.getElementById(errorId).textContent = message;
        document.getElementById(errorId).style.display = 'block';
        document.getElementById(errorId.replace('-error', '-success')).style.display = 'none';
    }

    function showSuccess(input, successId) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        document.getElementById(successId).style.display = 'block';
        document.getElementById(successId.replace('-success', '-error')).style.display = 'none';
    }

    function clearValidation(input, errorId, successId) {
        input.classList.remove('is-valid', 'is-invalid');
        document.getElementById(errorId).style.display = 'none';
        document.getElementById(successId).style.display = 'none';
    }

    // Event listeners for real-time validation
    variantNameInput.addEventListener('blur', validateVariantName);
    variantNameInput.addEventListener('input', function() {
        clearValidation(variantNameInput, 'ecomVariantName-error', 'ecomVariantName-success');
    });

    variantPriceInput.addEventListener('blur', validateVariantPrice);
    variantPriceInput.addEventListener('input', function() {
        clearValidation(variantPriceInput, 'ecomVariantPrice-error', 'ecomVariantPrice-success');
    });

    stocksAvailableInput.addEventListener('blur', validateStocksAvailable);
    stocksAvailableInput.addEventListener('input', function() {
        clearValidation(stocksAvailableInput, 'stocksAvailable-error', 'stocksAvailable-success');
    });

    variantDescriptionInput.addEventListener('blur', validateVariantDescription);
    variantDescriptionInput.addEventListener('input', function() {
        clearValidation(variantDescriptionInput, 'ecomVariantDescription-error', 'ecomVariantDescription-success');
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const isVariantNameValid = validateVariantName();
        const isVariantPriceValid = validateVariantPrice();
        const isStocksAvailableValid = validateStocksAvailable();
        const isVariantDescriptionValid = validateVariantDescription();

        if (isVariantNameValid && isVariantPriceValid && isStocksAvailableValid && isVariantDescriptionValid) {
            // Clean up the values before submission
            const cleanPrice = variantPriceInput.value.replace(/[₱,\s]/g, '');
            const cleanStocks = stocksAvailableInput.value.replace(/[,]/g, '');

            // Set the cleaned values directly to the inputs
            variantPriceInput.value = cleanPrice;
            stocksAvailableInput.value = cleanStocks;

            // Submit the form
            form.submit();
        } else {
            // Focus on the first invalid field
            if (!isVariantNameValid) {
                variantNameInput.focus();
            } else if (!isVariantPriceValid) {
                variantPriceInput.focus();
            } else if (!isStocksAvailableValid) {
                stocksAvailableInput.focus();
            } else if (!isVariantDescriptionValid) {
                variantDescriptionInput.focus();
            }
        }
    });
});
</script>
@endsection
