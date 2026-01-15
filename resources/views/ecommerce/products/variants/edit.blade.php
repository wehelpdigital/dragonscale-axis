@extends('layouts.master')

@section('title') Edit Variant @endsection

@section('css')
<style>
    .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .invalid-feedback {
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
                                @error('ecomVariantPrice')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="rawPrice" class="form-label">Raw Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control @error('rawPrice') is-invalid @enderror"
                                           id="rawPrice" name="rawPrice"
                                           value="{{ old('rawPrice', number_format($variant->ecomRawVariantPrice ?? 0, 2)) }}"
                                           placeholder="0.00">
                                </div>
                                <div class="invalid-feedback" id="rawPrice-error"></div>
                                @error('rawPrice')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="costPrice" class="form-label">Showed Before Cost Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control @error('costPrice') is-invalid @enderror"
                                           id="costPrice" name="costPrice"
                                           value="{{ old('costPrice', number_format($variant->costPrice ?? 0, 2)) }}"
                                           placeholder="0.00">
                                </div>
                                <div class="invalid-feedback" id="costPrice-error"></div>
                                @error('costPrice')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="affiliatePrice" class="form-label">Affiliate Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control @error('affiliatePrice') is-invalid @enderror"
                                           id="affiliatePrice" name="affiliatePrice"
                                           value="{{ old('affiliatePrice', number_format($variant->affiliatePrice ?? 0, 2)) }}"
                                           placeholder="0.00">
                                </div>
                                <div class="invalid-feedback" id="affiliatePrice-error"></div>
                                @error('affiliatePrice')
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
                                @error('stocksAvailable')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="maxOrderPerTransaction" class="form-label">Maximum Number of Order per Transaction <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('maxOrderPerTransaction') is-invalid @enderror"
                                       id="maxOrderPerTransaction" name="maxOrderPerTransaction"
                                       value="{{ old('maxOrderPerTransaction', $variant->maxOrderPerTransaction ?? 1) }}"
                                       min="1" placeholder="1">
                                <div class="invalid-feedback" id="maxOrderPerTransaction-error"></div>
                                @error('maxOrderPerTransaction')
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
    const rawPriceInput = document.getElementById('rawPrice');
    const costPriceInput = document.getElementById('costPrice');
    const affiliatePriceInput = document.getElementById('affiliatePrice');
    const stocksAvailableInput = document.getElementById('stocksAvailable');
    const maxOrderPerTransactionInput = document.getElementById('maxOrderPerTransaction');
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
            clearError(variantNameInput);
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
            clearError(variantPriceInput);
            return true;
        }
    }

    function validateRawPrice() {
        const value = rawPriceInput.value.trim();
        if (value === '') {
            showError(rawPriceInput, 'rawPrice-error', 'Raw price is required.');
            return false;
        }

        // Remove currency symbol and commas, then validate
        const cleanValue = value.replace(/[₱,\s]/g, '');
        const price = parseFloat(cleanValue);

        if (isNaN(price)) {
            showError(rawPriceInput, 'rawPrice-error', 'Raw price must be a valid number.');
            return false;
        } else if (price < 0) {
            showError(rawPriceInput, 'rawPrice-error', 'Raw price must be greater than or equal to 0.');
            return false;
        } else {
            clearError(rawPriceInput);
            return true;
        }
    }

    function validateCostPrice() {
        const value = costPriceInput.value.trim();
        // Cost price is optional - if empty, it's valid
        if (value === '' || value === '0.00') {
            clearError(costPriceInput);
            return true;
        }

        // Remove peso symbol and commas, then validate as number
        const cleanValue = value.replace(/[₱,\s]/g, '');
        const price = parseFloat(cleanValue);

        if (isNaN(price)) {
            showError(costPriceInput, 'costPrice-error', 'Please enter a valid price.');
            return false;
        } else if (price < 0) {
            showError(costPriceInput, 'costPrice-error', 'Cost price must be greater than or equal to 0.');
            return false;
        } else {
            clearError(costPriceInput);
            return true;
        }
    }

    function validateAffiliatePrice() {
        const value = affiliatePriceInput.value.trim();
        if (value === '') {
            showError(affiliatePriceInput, 'affiliatePrice-error', 'Affiliate price is required.');
            return false;
        }

        // Remove peso symbol and commas, then validate as number
        const cleanValue = value.replace(/[₱,\s]/g, '');
        const price = parseFloat(cleanValue);

        if (isNaN(price)) {
            showError(affiliatePriceInput, 'affiliatePrice-error', 'Please enter a valid price.');
            return false;
        } else if (price < 0) {
            showError(affiliatePriceInput, 'affiliatePrice-error', 'Affiliate price must be greater than or equal to 0.');
            return false;
        } else {
            clearError(affiliatePriceInput);
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
            clearError(stocksAvailableInput);
            return true;
        }
    }

    function validateMaxOrderPerTransaction() {
        const value = maxOrderPerTransactionInput.value.trim();
        if (value === '') {
            showError(maxOrderPerTransactionInput, 'maxOrderPerTransaction-error', 'Maximum order per transaction is required.');
            return false;
        }

        const maxOrder = parseInt(value);

        if (isNaN(maxOrder)) {
            showError(maxOrderPerTransactionInput, 'maxOrderPerTransaction-error', 'Please enter a valid number.');
            return false;
        } else if (maxOrder < 1) {
            showError(maxOrderPerTransactionInput, 'maxOrderPerTransaction-error', 'Maximum order per transaction must be at least 1.');
            return false;
        } else if (!Number.isInteger(maxOrder)) {
            showError(maxOrderPerTransactionInput, 'maxOrderPerTransaction-error', 'Maximum order per transaction must be a whole number.');
            return false;
        } else {
            clearError(maxOrderPerTransactionInput);
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
            clearError(variantDescriptionInput);
            return true;
        }
    }

    function showError(input, errorId, message) {
        input.classList.add('is-invalid');
        document.getElementById(errorId).textContent = message;
        document.getElementById(errorId).style.display = 'block';
    }

    function clearError(input) {
        input.classList.remove('is-invalid');
        // Find and hide the error feedback element
        const errorElement = input.closest('.mb-3').querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    // Restrict price fields to only accept numbers, decimal point, and commas (max 2 decimal places)
    function restrictToNumbers(e) {
        const allowedKeys = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];

        // Allow control keys
        if (allowedKeys.includes(e.key)) {
            return;
        }

        // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
        if (e.ctrlKey && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) {
            return;
        }

        // Allow numbers, decimal point, and comma
        if (!/^[0-9.,]$/.test(e.key)) {
            e.preventDefault();
            return;
        }

        // Prevent multiple decimal points
        if (e.key === '.' && e.target.value.includes('.')) {
            e.preventDefault();
            return;
        }

        // Limit to 2 decimal places
        const value = e.target.value;
        const cursorPos = e.target.selectionStart;
        const selectionEnd = e.target.selectionEnd;
        const hasSelection = cursorPos !== selectionEnd;
        const decimalIndex = value.indexOf('.');

        if (decimalIndex !== -1 && /^[0-9]$/.test(e.key)) {
            const decimals = value.substring(decimalIndex + 1);
            // If cursor is after decimal and already has 2 decimal places, prevent input
            // But allow if user has selected text (replacing)
            if (cursorPos > decimalIndex && decimals.length >= 2 && !hasSelection) {
                e.preventDefault();
            }
        }
    }

    // Apply number restriction to all price fields
    variantPriceInput.addEventListener('keydown', restrictToNumbers);
    rawPriceInput.addEventListener('keydown', restrictToNumbers);
    costPriceInput.addEventListener('keydown', restrictToNumbers);
    affiliatePriceInput.addEventListener('keydown', restrictToNumbers);

    // Also handle paste events to strip non-numeric characters and limit to 2 decimal places
    function handlePaste(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        let cleanedText = pastedText.replace(/[^0-9.,]/g, '');

        // Limit to 2 decimal places
        if (cleanedText.includes('.')) {
            const parts = cleanedText.split('.');
            cleanedText = parts[0] + '.' + parts[1].substring(0, 2);
        }

        document.execCommand('insertText', false, cleanedText);
    }

    variantPriceInput.addEventListener('paste', handlePaste);
    rawPriceInput.addEventListener('paste', handlePaste);
    costPriceInput.addEventListener('paste', handlePaste);
    affiliatePriceInput.addEventListener('paste', handlePaste);

    // Event listeners for real-time validation
    variantNameInput.addEventListener('blur', validateVariantName);
    variantNameInput.addEventListener('input', function() {
        clearError(variantNameInput);
    });

    variantPriceInput.addEventListener('blur', validateVariantPrice);
    variantPriceInput.addEventListener('input', function() {
        clearError(variantPriceInput);
    });

    rawPriceInput.addEventListener('blur', validateRawPrice);
    rawPriceInput.addEventListener('input', function() {
        clearError(rawPriceInput);
    });

    costPriceInput.addEventListener('blur', validateCostPrice);
    costPriceInput.addEventListener('input', function() {
        clearError(costPriceInput);
    });

    affiliatePriceInput.addEventListener('blur', validateAffiliatePrice);
    affiliatePriceInput.addEventListener('input', function() {
        clearError(affiliatePriceInput);
    });

    stocksAvailableInput.addEventListener('blur', validateStocksAvailable);
    stocksAvailableInput.addEventListener('input', function() {
        clearError(stocksAvailableInput);
    });

    maxOrderPerTransactionInput.addEventListener('blur', validateMaxOrderPerTransaction);
    maxOrderPerTransactionInput.addEventListener('input', function() {
        clearError(maxOrderPerTransactionInput);
    });

    variantDescriptionInput.addEventListener('blur', validateVariantDescription);
    variantDescriptionInput.addEventListener('input', function() {
        clearError(variantDescriptionInput);
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const isVariantNameValid = validateVariantName();
        const isVariantPriceValid = validateVariantPrice();
        const isRawPriceValid = validateRawPrice();
        const isCostPriceValid = validateCostPrice();
        const isAffiliatePriceValid = validateAffiliatePrice();
        const isStocksAvailableValid = validateStocksAvailable();
        const isMaxOrderPerTransactionValid = validateMaxOrderPerTransaction();
        const isVariantDescriptionValid = validateVariantDescription();

        if (isVariantNameValid && isVariantPriceValid && isRawPriceValid && isCostPriceValid && isAffiliatePriceValid && isStocksAvailableValid && isMaxOrderPerTransactionValid && isVariantDescriptionValid) {
            // Clean up the values before submission
            const cleanPrice = variantPriceInput.value.replace(/[₱,\s]/g, '');
            const cleanRawPrice = rawPriceInput.value.replace(/[₱,\s]/g, '');
            const cleanCostPrice = costPriceInput.value.replace(/[₱,\s]/g, '');
            const cleanAffiliatePrice = affiliatePriceInput.value.replace(/[₱,\s]/g, '');
            const cleanStocks = stocksAvailableInput.value.replace(/[,]/g, '');

            // Set the cleaned values directly to the inputs
            variantPriceInput.value = cleanPrice;
            rawPriceInput.value = cleanRawPrice;
            costPriceInput.value = cleanCostPrice;
            affiliatePriceInput.value = cleanAffiliatePrice;
            stocksAvailableInput.value = cleanStocks;

            // Submit the form
            form.submit();
        } else {
            // Focus on the first invalid field
            if (!isVariantNameValid) {
                variantNameInput.focus();
            } else if (!isVariantPriceValid) {
                variantPriceInput.focus();
            } else if (!isRawPriceValid) {
                rawPriceInput.focus();
            } else if (!isCostPriceValid) {
                costPriceInput.focus();
            } else if (!isAffiliatePriceValid) {
                affiliatePriceInput.focus();
            } else if (!isStocksAvailableValid) {
                stocksAvailableInput.focus();
            } else if (!isMaxOrderPerTransactionValid) {
                maxOrderPerTransactionInput.focus();
            } else if (!isVariantDescriptionValid) {
                variantDescriptionInput.focus();
            }
        }
    });
});
</script>
@endsection
