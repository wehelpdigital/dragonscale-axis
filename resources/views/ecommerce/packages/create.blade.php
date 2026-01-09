@extends('layouts.master')

@section('title') Create Package @endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
/* Product card with left border */
.product-card {
    border-left: 4px solid #3498db;
    margin-bottom: 8px;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.product-card .product-header {
    padding: 12px 15px;
    cursor: pointer;
    transition: background-color 0.2s;
}
.product-card .product-header:hover {
    background-color: #f8f9fa;
}
.product-card .chevron-icon {
    transition: transform 0.2s;
}
.product-card .chevron-icon.rotated {
    transform: rotate(180deg);
}
/* Variants container */
.variants-container {
    display: none;
    border-top: 1px solid #e9ecef;
    background: #fafafa;
}
.variants-container.show {
    display: block;
}
/* Variant row */
.variant-row {
    padding: 10px 15px;
    border-bottom: 1px solid #e9ecef;
    background: #fff;
    margin: 0 10px;
    border-radius: 4px;
    margin-bottom: 8px;
}
.variant-row:first-child {
    margin-top: 10px;
}
.variant-row:last-child {
    margin-bottom: 10px;
    border-bottom: none;
}
.variant-row:hover {
    background-color: #f8f9fa;
}
.variant-row.selected {
    background-color: #d4edda;
    border-left: 3px solid #28a745;
}
/* Selected product item */
.selected-product-item {
    border-left: 4px solid #3498db;
    margin-bottom: 10px;
    background: #fff;
    border-radius: 4px;
    padding: 12px 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
.selected-product-item:hover {
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}
/* Product image */
.product-img {
    width: 45px;
    height: 45px;
    object-fit: cover;
    border-radius: 4px;
}
.product-img-placeholder {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f0f0;
    border-radius: 4px;
    color: #adb5bd;
}
/* Quantity input */
.quantity-input {
    width: 70px;
    text-align: center;
}
/* Price summary */
.price-summary {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    position: sticky;
    top: 100px;
}
/* Type badge */
.type-badge {
    font-size: 0.7rem;
    padding: 2px 6px;
}
/* Product search container */
.products-search-container {
    max-height: 500px;
    overflow-y: auto;
}
/* Selected items container */
.selected-items-container {
    max-height: 400px;
    overflow-y: auto;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') <a href="{{ route('ecom-packages') }}">Packages</a> @endslot
@slot('title') Create Package @endslot
@endcomponent

<form id="packageForm">
    @csrf
    <div class="row">
        <!-- Left Column - Package Details -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4"><i class="bx bx-package me-2"></i>Package Information</h4>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="packageName" class="form-label text-dark">Package Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="packageName" name="packageName" placeholder="Enter package name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="packageStatus" class="form-label text-dark">Status</label>
                            <select class="form-select" id="packageStatus" name="packageStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="packageDescription" class="form-label text-dark">Description</label>
                        <textarea class="form-control" id="packageDescription" name="packageDescription" rows="2" placeholder="Optional description"></textarea>
                    </div>
                </div>
            </div>

            <!-- Product Selection Card -->
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="card-title mb-0" style="color: #fff !important;">
                        <i class="mdi mdi-package-variant me-2"></i>Add Products to Package
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Search Bar -->
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                                <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-store"></i></span>
                                <select class="form-select" id="storeFilter">
                                    <option value="">All Stores</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->storeName }}">{{ $store->storeName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-primary w-100" id="searchProducts">
                                <i class="mdi mdi-magnify"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Products List -->
                    <div id="productsContainer" class="products-search-container">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-secondary mb-0">Loading products...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Products Card -->
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="card-title mb-0" style="color: #fff !important;">
                        <i class="mdi mdi-cart me-2"></i>Selected Products
                        <span class="badge bg-light text-dark ms-2" id="selectedCount">0</span>
                    </h6>
                </div>
                <div class="card-body">
                    <div id="selectedItemsContainer" class="selected-items-container">
                        <div id="noItemsPlaceholder" class="text-center py-4">
                            <i class="mdi mdi-cart-outline text-secondary" style="font-size: 3rem;"></i>
                            <p class="mt-2 mb-0 text-dark">No products selected yet.</p>
                            <small class="text-secondary">Click the + button on variants above to add them.</small>
                        </div>
                    </div>

                    <!-- Cart Summary -->
                    <div class="mt-3 pt-3 border-top" id="cartSummary" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <strong class="text-dark">Total Items:</strong>
                                    <span id="totalItems" class="text-dark">0</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between">
                                    <strong class="text-dark">Calculated Total:</strong>
                                    <span id="totalAmount" class="text-primary fw-bold">₱0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Price Summary -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body price-summary">
                    <h4 class="card-title mb-4"><i class="bx bx-calculator me-2"></i>Price Summary</h4>

                    <div class="mb-3">
                        <label class="form-label text-dark">Calculated Total</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="text" class="form-control" id="calculatedPrice" value="0.00" readonly>
                        </div>
                        <small class="text-secondary">Sum of all product prices × quantities</small>
                    </div>

                    <div class="mb-3">
                        <label for="packagePrice" class="form-label text-dark">Package Price <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="packagePrice" name="packagePrice" step="0.01" min="0" value="0.00" required>
                        </div>
                        <small class="text-secondary">Set a custom price for bundle discount</small>
                    </div>

                    <div id="discountInfo" class="alert alert-success mb-3" style="display: none;">
                        <i class="bx bx-purchase-tag me-1"></i>
                        <span id="discountText"></span>
                    </div>

                    <hr>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary btn-lg" id="savePackage">
                            <i class="bx bx-save me-1"></i>Save Package
                        </button>
                        <a href="{{ route('ecom-packages') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

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

    // Store colors for consistent coloring
    const storeColors = [
        '#3498db', '#e74c3c', '#2ecc71', '#9b59b6', '#f39c12',
        '#1abc9c', '#e67e22', '#34495e', '#16a085', '#c0392b',
        '#27ae60', '#8e44ad', '#d35400', '#2c3e50', '#f1c40f'
    ];
    const storeColorMap = {};

    function getStoreColor(storeName) {
        if (!storeName) return '#3498db';
        if (storeColorMap[storeName]) return storeColorMap[storeName];

        let hash = 0;
        for (let i = 0; i < storeName.length; i++) {
            hash = storeName.charCodeAt(i) + ((hash << 5) - hash);
        }
        const colorIndex = Math.abs(hash) % storeColors.length;
        storeColorMap[storeName] = storeColors[colorIndex];
        return storeColorMap[storeName];
    }

    // Selected items storage - keyed by variantId
    let selectedItems = {};
    let availableProducts = [];

    // Search products
    function searchProducts() {
        const search = $('#productSearch').val();
        const store = $('#storeFilter').val();

        $('#productsContainer').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-secondary mb-0">Loading products...</p>
            </div>
        `);

        $.ajax({
            url: '{{ route("ecom-packages.products") }}',
            type: 'GET',
            data: { search: search, store: store },
            success: function(response) {
                if (response.success) {
                    availableProducts = response.products;
                    renderProducts(response.products);
                }
            },
            error: function() {
                $('#productsContainer').html('<div class="text-center py-4 text-danger">Error loading products</div>');
                toastr.error('Failed to load products', 'Error');
            }
        });
    }

    // Render products with expandable variants
    function renderProducts(products) {
        if (products.length === 0) {
            $('#productsContainer').html('<div class="text-center py-4 text-secondary">No products found</div>');
            return;
        }

        let html = '';
        products.forEach(function(product) {
            const storeColor = getStoreColor(product.productStore);

            html += `
                <div class="product-card" style="border-left-color: ${storeColor};" data-product-id="${product.productId}">
                    <div class="product-header d-flex justify-content-between align-items-center" onclick="toggleVariants(${product.productId})">
                        <div>
                            <h6 class="mb-1 text-dark">${escapeHtml(product.productName)}</h6>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-secondary">
                                    <i class="mdi mdi-store" style="color: ${storeColor};"></i>
                                    ${escapeHtml(product.productStore)}
                                </small>
                                <small class="text-secondary">${product.variants.length} variant(s)</small>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary">
                            <i class="mdi mdi-chevron-down chevron-icon" id="chevron-${product.productId}"></i>
                        </button>
                    </div>
                    <div class="variants-container" id="variants-${product.productId}">
            `;

            // Render variants
            product.variants.forEach(function(variant) {
                const isSelected = selectedItems.hasOwnProperty(variant.variantId);
                const selectedClass = isSelected ? 'selected' : '';

                const imgHtml = variant.imageUrl
                    ? `<img src="${variant.imageUrl}" class="product-img">`
                    : `<div class="product-img-placeholder"><i class="mdi mdi-image"></i></div>`;

                // Type badge for each variant
                const variantTypeBadge = variant.productType === 'access'
                    ? '<span class="badge bg-info text-white type-badge">Access</span>'
                    : '<span class="badge bg-warning text-dark type-badge">Ship</span>';

                html += `
                    <div class="variant-row ${selectedClass}" data-variant-id="${variant.variantId}">
                        <div class="d-flex align-items-center">
                            ${imgHtml}
                            <div class="ms-3 flex-grow-1">
                                <span class="text-dark fw-medium">${escapeHtml(variant.variantName)}</span>
                                <div class="d-flex align-items-center gap-2">
                                    ${variantTypeBadge}
                                    <small class="text-secondary">Stock: ${variant.stocksAvailable}</small>
                                </div>
                            </div>
                            <div class="text-end me-3">
                                <strong class="text-primary">₱${parseFloat(variant.variantPrice).toFixed(2)}</strong>
                            </div>
                            <button type="button" class="btn btn-sm ${isSelected ? 'btn-success' : 'btn-primary'} add-variant-btn"
                                    data-variant-id="${variant.variantId}"
                                    data-product-id="${product.productId}"
                                    data-product-name="${escapeHtml(product.productName)}"
                                    data-product-type="${variant.productType}"
                                    data-product-store="${escapeHtml(product.productStore)}"
                                    data-variant-name="${escapeHtml(variant.variantName)}"
                                    data-variant-price="${variant.variantPrice}"
                                    data-image-url="${variant.imageUrl || ''}"
                                    data-store-color="${storeColor}"
                                    ${isSelected ? 'disabled' : ''}>
                                <i class="mdi ${isSelected ? 'mdi-check' : 'mdi-plus'}"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });

        $('#productsContainer').html(html);
    }

    // Toggle variants visibility
    window.toggleVariants = function(productId) {
        const $container = $(`#variants-${productId}`);
        const $chevron = $(`#chevron-${productId}`);

        $container.toggleClass('show');
        $chevron.toggleClass('rotated');
    };

    // Add variant to selected items
    $(document).on('click', '.add-variant-btn', function(e) {
        e.stopPropagation();
        const $btn = $(this);
        const variantId = $btn.data('variant-id');

        if (selectedItems.hasOwnProperty(variantId)) {
            toastr.warning('This variant is already added', 'Warning');
            return;
        }

        selectedItems[variantId] = {
            productId: $btn.data('product-id'),
            variantId: variantId,
            productName: $btn.data('product-name'),
            productType: $btn.data('product-type'),
            variantName: $btn.data('variant-name'),
            storeName: $btn.data('product-store'),
            unitPrice: parseFloat($btn.data('variant-price')),
            quantity: 1,
            imageUrl: $btn.data('image-url') || null,
            storeColor: $btn.data('store-color')
        };

        renderSelectedItems();
        updatePriceSummary();

        // Update button state
        $btn.removeClass('btn-primary').addClass('btn-success').prop('disabled', true);
        $btn.find('i').removeClass('mdi-plus').addClass('mdi-check');
        $btn.closest('.variant-row').addClass('selected');

        toastr.success('Variant added to package', 'Success');
    });

    // Render selected items
    function renderSelectedItems() {
        const $container = $('#selectedItemsContainer');
        const itemsArray = Object.values(selectedItems);

        if (itemsArray.length === 0) {
            $container.html(`
                <div id="noItemsPlaceholder" class="text-center py-4">
                    <i class="mdi mdi-cart-outline text-secondary" style="font-size: 3rem;"></i>
                    <p class="mt-2 mb-0 text-dark">No products selected yet.</p>
                    <small class="text-secondary">Click the + button on variants above to add them.</small>
                </div>
            `);
            $('#selectedCount').text('0');
            $('#cartSummary').hide();
            return;
        }

        $('#selectedCount').text(itemsArray.length);
        $('#cartSummary').show();

        let html = '';
        let totalQuantity = 0;

        itemsArray.forEach(function(item) {
            const storeColor = item.storeColor || getStoreColor(item.storeName);
            const imgHtml = item.imageUrl
                ? `<img src="${item.imageUrl}" class="product-img">`
                : `<div class="product-img-placeholder"><i class="mdi mdi-image"></i></div>`;

            const subtotal = item.unitPrice * item.quantity;
            totalQuantity += item.quantity;

            const typeBadge = item.productType === 'access'
                ? '<span class="badge bg-info text-white type-badge">Access</span>'
                : '<span class="badge bg-warning text-dark type-badge">Ship</span>';

            html += `
                <div class="selected-product-item" style="border-left-color: ${storeColor};" data-variant-id="${item.variantId}">
                    <div class="d-flex align-items-center">
                        ${imgHtml}
                        <div class="ms-3 flex-grow-1">
                            <strong class="text-dark">${escapeHtml(item.productName)}</strong> ${typeBadge}
                            <div><small class="text-secondary">${escapeHtml(item.variantName)}</small></div>
                            <small class="text-secondary">
                                <i class="mdi mdi-store me-1" style="color: ${storeColor};"></i>${escapeHtml(item.storeName)}
                            </small>
                        </div>
                        <div class="text-end me-2">
                            <div class="text-dark">₱${parseFloat(item.unitPrice).toFixed(2)}</div>
                        </div>
                        <div class="me-2">
                            <input type="number" class="form-control quantity-input item-quantity"
                                   value="${item.quantity}" min="1" data-variant-id="${item.variantId}">
                        </div>
                        <div class="text-end me-2" style="min-width: 90px;">
                            <strong class="text-primary item-subtotal">₱${subtotal.toFixed(2)}</strong>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" data-variant-id="${item.variantId}">
                            <i class="mdi mdi-trash-can"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        $container.html(html);
        $('#totalItems').text(totalQuantity);
    }

    // Update quantity
    $(document).on('change', '.item-quantity', function() {
        const variantId = $(this).data('variant-id');
        const newQuantity = parseInt($(this).val()) || 1;

        if (newQuantity < 1) {
            $(this).val(1);
            selectedItems[variantId].quantity = 1;
        } else {
            selectedItems[variantId].quantity = newQuantity;
        }

        const subtotal = selectedItems[variantId].unitPrice * selectedItems[variantId].quantity;
        $(this).closest('.selected-product-item').find('.item-subtotal').text('₱' + subtotal.toFixed(2));

        updatePriceSummary();
    });

    // Remove item
    $(document).on('click', '.remove-item-btn', function() {
        const variantId = $(this).data('variant-id');

        if (!selectedItems.hasOwnProperty(variantId)) return;

        delete selectedItems[variantId];

        renderSelectedItems();
        updatePriceSummary();

        // Update search result if visible
        const $variantRow = $(`.variant-row[data-variant-id="${variantId}"]`);
        if ($variantRow.length) {
            $variantRow.removeClass('selected');
            const $btn = $variantRow.find('.add-variant-btn');
            $btn.removeClass('btn-success').addClass('btn-primary').prop('disabled', false);
            $btn.find('i').removeClass('mdi-check').addClass('mdi-plus');
        }

        toastr.info('Product removed from package', 'Info');
    });

    // Update price summary
    function updatePriceSummary() {
        let calculatedTotal = 0;
        let totalQuantity = 0;

        Object.values(selectedItems).forEach(function(item) {
            calculatedTotal += item.unitPrice * item.quantity;
            totalQuantity += item.quantity;
        });

        $('#calculatedPrice').val(calculatedTotal.toFixed(2));
        $('#packagePrice').val(calculatedTotal.toFixed(2));
        $('#totalAmount').text('₱' + calculatedTotal.toFixed(2));
        $('#totalItems').text(totalQuantity);

        updateDiscountInfo();
    }

    // Update discount info
    function updateDiscountInfo() {
        const calculated = parseFloat($('#calculatedPrice').val()) || 0;
        const packagePrice = parseFloat($('#packagePrice').val()) || 0;

        if (calculated > packagePrice && packagePrice > 0) {
            const discount = calculated - packagePrice;
            const percentage = ((discount / calculated) * 100).toFixed(1);
            $('#discountText').text(`Discount: ₱${discount.toFixed(2)} (${percentage}% off)`);
            $('#discountInfo').show();
        } else {
            $('#discountInfo').hide();
        }
    }

    // Package price change handler
    $('#packagePrice').on('input', function() {
        updateDiscountInfo();
    });

    // Search button click
    $('#searchProducts').on('click', function() {
        searchProducts();
    });

    // Search on enter
    $('#productSearch').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            searchProducts();
        }
    });

    // Store filter change
    $('#storeFilter').on('change', function() {
        searchProducts();
    });

    // Save package
    $('#savePackage').on('click', function() {
        const packageName = $('#packageName').val().trim();
        const packagePrice = parseFloat($('#packagePrice').val()) || 0;
        const itemsArray = Object.values(selectedItems);

        if (!packageName) {
            toastr.error('Package name is required', 'Validation Error');
            $('#packageName').focus();
            return;
        }

        if (itemsArray.length === 0) {
            toastr.error('Please add at least one product to the package', 'Validation Error');
            return;
        }

        if (packagePrice <= 0) {
            toastr.error('Package price must be greater than 0', 'Validation Error');
            $('#packagePrice').focus();
            return;
        }

        const $btn = $(this);
        const originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        const items = itemsArray.map(item => ({
            variantId: item.variantId,
            quantity: item.quantity
        }));

        $.ajax({
            url: '{{ route("ecom-packages.store") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                packageName: packageName,
                packageDescription: $('#packageDescription').val(),
                packagePrice: packagePrice,
                packageStatus: $('#packageStatus').val(),
                items: items
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    window.location.href = '{{ route("ecom-packages") }}';
                } else {
                    toastr.error(response.message || 'Failed to create package', 'Error');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON || {};
                if (response.errors) {
                    Object.values(response.errors).forEach(function(errors) {
                        errors.forEach(function(error) {
                            toastr.error(error, 'Validation Error');
                        });
                    });
                } else {
                    toastr.error(response.message || 'Failed to create package', 'Error');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Escape HTML helper
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initial product search
    searchProducts();
});
</script>
@endsection
