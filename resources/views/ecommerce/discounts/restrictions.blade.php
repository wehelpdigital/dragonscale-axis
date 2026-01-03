@extends('layouts.master')

@section('title') Discount Restrictions @endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.restriction-card {
    transition: all 0.3s ease;
}
.restriction-card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
.restriction-type-btn {
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 2px solid #dee2e6;
    background: #fff;
}
.restriction-type-btn:hover {
    border-color: #556ee6;
    background: #f8f9fa;
}
.restriction-type-btn.active {
    border-color: #556ee6;
    background: #556ee6;
    color: #fff;
}
.restriction-type-btn.active i {
    color: #fff !important;
}
.restriction-type-btn.active .btn-subtitle {
    color: rgba(255, 255, 255, 0.85) !important;
}
.restriction-type-btn i {
    font-size: 2rem;
    color: #556ee6;
}
.btn-subtitle {
    color: #74788d;
}
.selection-table-container {
    max-height: 500px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}
.selection-table {
    margin-bottom: 0;
}
.selection-table thead th {
    position: sticky;
    top: 0;
    background: #f8f9fa;
    z-index: 1;
    border-bottom: 2px solid #dee2e6;
}
.selection-table tbody tr.product-row {
    cursor: pointer;
    transition: background 0.15s ease;
}
.selection-table tbody tr.product-row:hover {
    background: #f8f9fa;
}
.selection-table tbody tr.selected {
    background: #e8f4f8;
}
.selection-table tbody tr.store-row {
    cursor: pointer;
    transition: background 0.15s ease;
}
.selection-table tbody tr.store-row:hover {
    background: #f8f9fa;
}
.selection-table .form-check-input {
    cursor: pointer;
    width: 1.2em;
    height: 1.2em;
}
.search-input-wrapper {
    position: relative;
}
.search-input-wrapper .form-control {
    padding-left: 2.5rem;
}
.search-input-wrapper .search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #74788d;
    z-index: 4;
    pointer-events: none;
}
.selected-count-badge {
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
}
.no-results-row td {
    text-align: center;
    padding: 2rem !important;
    color: #74788d;
}
.load-more-row td {
    text-align: center;
    padding: 1rem !important;
}
.load-more-btn {
    min-width: 150px;
}
.pagination-info {
    font-size: 0.8rem;
    color: #74788d;
}
/* Expandable product row styles */
.expand-toggle {
    cursor: pointer;
    transition: transform 0.2s ease;
    color: #556ee6;
}
.expand-toggle.expanded {
    transform: rotate(90deg);
}
.variant-row {
    background: #f8f9fa;
}
.variant-row td {
    padding: 0.5rem 1rem !important;
    border-top: none !important;
}
.variant-details {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.25rem 0;
}
.variant-image {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #dee2e6;
    background: #fff;
}
.variant-image-placeholder {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    border-radius: 4px;
    color: #adb5bd;
}
.variant-info {
    flex: 1;
}
.variant-name {
    font-size: 0.85rem;
    color: #495057;
}
.variant-price {
    font-size: 0.8rem;
    color: #28a745;
    font-weight: 500;
}
.variant-stock {
    font-size: 0.75rem;
    color: #6c757d;
}
.variants-loading {
    text-align: center;
    padding: 1rem;
    color: #74788d;
}
.variant-count-badge {
    font-size: 0.7rem;
    padding: 0.2em 0.5em;
    margin-left: 0.5rem;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') <a href="{{ route('ecom-discounts') }}">Discounts</a> @endslot
@slot('title') Discount Restrictions @endslot
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
        <div class="card restriction-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Discount Restrictions</h4>
                        <p class="text-secondary mb-0">
                            <strong class="text-dark">{{ $discount->discountName }}</strong> -
                            Configure which stores or products this discount applies to
                        </p>
                    </div>
                    <a href="{{ route('ecom-discounts') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back
                    </a>
                </div>

                <!-- Restriction Type Selection -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Restriction Type</label>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="restriction-type-btn text-center {{ ($discount->restrictionType ?? 'all') === 'all' ? 'active' : '' }}"
                                 data-type="all" onclick="selectRestrictionType('all')">
                                <i class="bx bx-globe d-block mb-2"></i>
                                <strong>All Products</strong>
                                <small class="d-block btn-subtitle">Apply to everything</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="restriction-type-btn text-center {{ ($discount->restrictionType ?? 'all') === 'stores' ? 'active' : '' }}"
                                 data-type="stores" onclick="selectRestrictionType('stores')">
                                <i class="bx bx-store d-block mb-2"></i>
                                <strong>Specific Stores</strong>
                                <small class="d-block btn-subtitle">Select stores</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="restriction-type-btn text-center {{ ($discount->restrictionType ?? 'all') === 'products' ? 'active' : '' }}"
                                 data-type="products" onclick="selectRestrictionType('products')">
                                <i class="bx bx-package d-block mb-2"></i>
                                <strong>Specific Products</strong>
                                <small class="d-block btn-subtitle">Select products</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Store Selection Section -->
                <div id="storeSelectionSection" class="mb-4" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="form-label fw-bold text-dark mb-0">Select Stores</label>
                        <div>
                            <span class="pagination-info me-2" id="storePaginationInfo"></span>
                            <span class="badge bg-primary selected-count-badge" id="storeSelectedCount">0 selected</span>
                        </div>
                    </div>

                    <!-- Store Search -->
                    <div class="search-input-wrapper mb-3">
                        <i class="bx bx-search search-icon"></i>
                        <input type="text" class="form-control" id="storeSearch" placeholder="Search stores...">
                    </div>

                    <!-- Store Table -->
                    <div class="selection-table-container">
                        <table class="table selection-table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAllStores">
                                        </div>
                                    </th>
                                    <th>Store Name</th>
                                </tr>
                            </thead>
                            <tbody id="storeTableBody">
                                <tr class="no-results-row">
                                    <td colspan="2">
                                        <i class="bx bx-loader-alt bx-spin me-1"></i>Loading stores...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-secondary mt-2 d-block">Click on a row or checkbox to select/deselect stores</small>
                </div>

                <!-- Product Selection Section -->
                <div id="productSelectionSection" class="mb-4" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <label class="form-label fw-bold text-dark mb-0">Select Products</label>
                        <div>
                            <span class="pagination-info me-2" id="productPaginationInfo"></span>
                            <span class="badge bg-primary selected-count-badge" id="productSelectedCount">0 selected</span>
                        </div>
                    </div>

                    <!-- Product Filters -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <select id="productStoreFilter" class="form-select">
                                <option value="">All Stores</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" data-name="{{ $store->storeName }}">{{ $store->storeName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <div class="search-input-wrapper">
                                <i class="bx bx-search search-icon"></i>
                                <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
                            </div>
                        </div>
                    </div>

                    <!-- Product Table -->
                    <div class="selection-table-container">
                        <table class="table selection-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 30px;"></th>
                                    <th style="width: 50px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAllProducts">
                                        </div>
                                    </th>
                                    <th>Product Name</th>
                                    <th>Store</th>
                                    <th style="width: 180px;">Price Range</th>
                                </tr>
                            </thead>
                            <tbody id="productTableBody">
                                <tr class="no-results-row">
                                    <td colspan="5">
                                        <i class="bx bx-loader-alt bx-spin me-1"></i>Loading products...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-secondary mt-2 d-block">Click the arrow to expand variants. Click on a row or checkbox to select/deselect products.</small>
                </div>

                <!-- Save Button -->
                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('ecom-discounts') }}" class="btn btn-light">
                        <i class="bx bx-x me-1"></i>Cancel
                    </a>
                    <button type="button" class="btn btn-primary" id="saveRestrictions">
                        <i class="bx bx-save me-1"></i>Save Restrictions
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Discount Info Card -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="bx bx-info-circle me-2 text-info"></i>Discount Details
                </h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <span class="text-secondary">Name:</span>
                        <span class="ms-2 text-dark fw-medium">{{ $discount->discountName }}</span>
                    </li>
                    <li class="mb-2">
                        <span class="text-secondary">Type:</span>
                        <span class="ms-2 badge bg-primary">{{ $discount->discountType }}</span>
                    </li>
                    <li class="mb-2">
                        <span class="text-secondary">Trigger:</span>
                        <span class="ms-2 badge bg-{{ $discount->discountTrigger === 'Auto Apply' ? 'success' : 'warning text-dark' }}">
                            {{ $discount->discountTrigger }}
                        </span>
                    </li>
                    <li class="mb-2">
                        <span class="text-secondary">Value:</span>
                        <span class="ms-2 text-dark fw-medium">{{ $discount->getDisplayValue() }}</span>
                    </li>
                    <li>
                        <span class="text-secondary">Status:</span>
                        <span class="ms-2 badge bg-{{ $discount->isActive ? 'success' : 'danger' }}">
                            {{ $discount->isActive ? 'Active' : 'Inactive' }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Help Card -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="bx bx-help-circle me-2 text-warning"></i>Help
                </h5>
                <ul class="text-secondary mb-0" style="padding-left: 1.2rem;">
                    <li class="mb-2"><strong class="text-dark">All Products:</strong> Discount applies to all eligible products</li>
                    <li class="mb-2"><strong class="text-dark">Specific Stores:</strong> Discount only applies to products from selected stores</li>
                    <li><strong class="text-dark">Specific Products:</strong> Discount only applies to individually selected products</li>
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

    // State
    window.currentRestrictionType = '{{ $discount->restrictionType ?? "all" }}';
    var selectedStoreIds = [];
    var selectedProductIds = [];
    var searchTimeout = null;
    var expandedProducts = {}; // Track which products are expanded
    var loadedVariants = {}; // Cache loaded variants

    // Pagination state
    var storePagination = { page: 1, hasMore: false, total: 0, search: '' };
    var productPagination = { page: 1, hasMore: false, total: 0, search: '', storeId: '' };

    // Initialize
    loadExistingRestrictions();
    updateSectionsVisibility();

    // Store search
    $('#storeSearch').on('input', function() {
        if (searchTimeout) clearTimeout(searchTimeout);
        var search = $(this).val();
        searchTimeout = setTimeout(function() {
            storePagination.page = 1;
            storePagination.search = search;
            loadStores(false);
        }, 300);
    });

    // Product search
    $('#productSearch').on('input', function() {
        if (searchTimeout) clearTimeout(searchTimeout);
        var search = $(this).val();
        searchTimeout = setTimeout(function() {
            productPagination.page = 1;
            productPagination.search = search;
            expandedProducts = {}; // Reset expanded state on search
            loadProducts(false);
        }, 300);
    });

    // Product store filter
    $('#productStoreFilter').on('change', function() {
        productPagination.page = 1;
        productPagination.storeId = $(this).val();
        expandedProducts = {}; // Reset expanded state on filter change
        loadProducts(false);
    });

    // Select all stores (visible only)
    $('#selectAllStores').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('#storeTableBody tr.store-row:visible').each(function() {
            var $checkbox = $(this).find('.store-checkbox');
            if ($checkbox.length) {
                $checkbox.prop('checked', isChecked);
                updateStoreSelection($(this).data('id'), isChecked);
                $(this).toggleClass('selected', isChecked);
            }
        });
        updateStoreCount();
    });

    // Select all products (visible only)
    $('#selectAllProducts').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('#productTableBody tr.product-row:visible').each(function() {
            var $checkbox = $(this).find('.product-checkbox');
            if ($checkbox.length) {
                $checkbox.prop('checked', isChecked);
                updateProductSelection($(this).data('id'), isChecked);
                $(this).toggleClass('selected', isChecked);
            }
        });
        updateProductCount();
    });

    // Store row click
    $(document).on('click', '#storeTableBody tr.store-row', function(e) {
        if ($(e.target).is('input[type="checkbox"]')) return;
        var $checkbox = $(this).find('.store-checkbox');
        if (!$checkbox.length) return;
        $checkbox.prop('checked', !$checkbox.prop('checked'));
        updateStoreSelection($(this).data('id'), $checkbox.prop('checked'));
        $(this).toggleClass('selected', $checkbox.prop('checked'));
        updateStoreCount();
    });

    // Store checkbox change
    $(document).on('change', '.store-checkbox', function() {
        var $row = $(this).closest('tr');
        updateStoreSelection($row.data('id'), $(this).prop('checked'));
        $row.toggleClass('selected', $(this).prop('checked'));
        updateStoreCount();
    });

    // Product row click (excluding expand toggle area)
    $(document).on('click', '#productTableBody tr.product-row td:not(:first-child)', function(e) {
        if ($(e.target).is('input[type="checkbox"]')) return;
        var $row = $(this).closest('tr');
        var $checkbox = $row.find('.product-checkbox');
        if (!$checkbox.length) return;
        $checkbox.prop('checked', !$checkbox.prop('checked'));
        updateProductSelection($row.data('id'), $checkbox.prop('checked'));
        $row.toggleClass('selected', $checkbox.prop('checked'));
        updateProductCount();
    });

    // Product checkbox change
    $(document).on('change', '.product-checkbox', function() {
        var $row = $(this).closest('tr');
        updateProductSelection($row.data('id'), $(this).prop('checked'));
        $row.toggleClass('selected', $(this).prop('checked'));
        updateProductCount();
    });

    // Expand/collapse product variants
    $(document).on('click', '.expand-toggle', function(e) {
        e.stopPropagation();
        var $toggle = $(this);
        var productId = $toggle.data('product-id');
        var $productRow = $toggle.closest('tr');

        if (expandedProducts[productId]) {
            // Collapse
            $toggle.removeClass('expanded');
            $('.variant-row[data-parent-id="' + productId + '"]').remove();
            delete expandedProducts[productId];
        } else {
            // Expand
            $toggle.addClass('expanded');
            expandedProducts[productId] = true;

            // Check if variants are cached
            if (loadedVariants[productId]) {
                renderVariants(productId, loadedVariants[productId], $productRow);
            } else {
                // Show loading row
                $productRow.after(
                    '<tr class="variant-row" data-parent-id="' + productId + '">' +
                        '<td colspan="5" class="variants-loading">' +
                            '<i class="bx bx-loader-alt bx-spin me-1"></i>Loading variants...' +
                        '</td>' +
                    '</tr>'
                );

                // Load variants
                $.ajax({
                    url: '/ecom-discounts/product-variants/' + productId,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            loadedVariants[productId] = response.variants;
                            $('.variant-row[data-parent-id="' + productId + '"]').remove();
                            renderVariants(productId, response.variants, $productRow);
                        }
                    },
                    error: function() {
                        $('.variant-row[data-parent-id="' + productId + '"]').html(
                            '<td colspan="5" class="variants-loading text-danger">' +
                                '<i class="bx bx-error-circle me-1"></i>Error loading variants' +
                            '</td>'
                        );
                    }
                });
            }
        }
    });

    // Load more stores
    $(document).on('click', '#loadMoreStores', function() {
        storePagination.page++;
        loadStores(true);
    });

    // Load more products
    $(document).on('click', '#loadMoreProducts', function() {
        productPagination.page++;
        loadProducts(true);
    });

    // Save restrictions
    $('#saveRestrictions').on('click', function() {
        saveRestrictions();
    });

    // Functions
    function renderVariants(productId, variants, $productRow) {
        if (variants.length === 0) {
            $productRow.after(
                '<tr class="variant-row" data-parent-id="' + productId + '">' +
                    '<td colspan="5" class="variants-loading">' +
                        '<i class="bx bx-info-circle me-1"></i>No active variants' +
                    '</td>' +
                '</tr>'
            );
            return;
        }

        var html = '';
        variants.forEach(function(variant) {
            var imageHtml = variant.image
                ? '<img src="' + variant.image + '" alt="" class="variant-image">'
                : '<div class="variant-image-placeholder"><i class="bx bx-image"></i></div>';

            html += '<tr class="variant-row" data-parent-id="' + productId + '">' +
                '<td></td>' +
                '<td></td>' +
                '<td colspan="3">' +
                    '<div class="variant-details">' +
                        imageHtml +
                        '<div class="variant-info">' +
                            '<div class="variant-name">' + escapeHtml(variant.name) + '</div>' +
                            '<div class="variant-price">' + variant.price + '</div>' +
                            '<div class="variant-stock">Stock: ' + variant.stock + '</div>' +
                        '</div>' +
                    '</div>' +
                '</td>' +
            '</tr>';
        });

        $productRow.after(html);
    }

    function loadExistingRestrictions() {
        $.ajax({
            url: '/ecom-discounts/{{ $discount->id }}/restrictions',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    window.currentRestrictionType = response.restrictionType || 'all';

                    // Update type buttons
                    $('.restriction-type-btn').removeClass('active');
                    $('.restriction-type-btn[data-type="' + window.currentRestrictionType + '"]').addClass('active');

                    // Load selected stores and products
                    response.restrictions.forEach(function(r) {
                        if (r.type === 'store' && r.storeId) {
                            selectedStoreIds.push(parseInt(r.storeId));
                        }
                        if (r.type === 'product' && r.productId) {
                            selectedProductIds.push(parseInt(r.productId));
                        }
                    });

                    updateStoreCount();
                    updateProductCount();
                    updateSectionsVisibility();

                    // Load initial data
                    loadStores(false);
                    loadProducts(false);
                }
            },
            error: function() {
                // Still try to load data even if restrictions fail
                loadStores(false);
                loadProducts(false);
            }
        });
    }

    function loadStores(append) {
        if (!append) {
            $('#storeTableBody').html(
                '<tr class="no-results-row"><td colspan="2"><i class="bx bx-loader-alt bx-spin me-1"></i>Loading stores...</td></tr>'
            );
        } else {
            $('#storeTableBody .load-more-row').remove();
        }

        $.ajax({
            url: '{{ route("ecom-discounts.search-stores") }}',
            type: 'GET',
            data: {
                search: storePagination.search,
                page: storePagination.page,
                per_page: 20
            },
            success: function(response) {
                if (response.success) {
                    storePagination.hasMore = response.pagination.has_more;
                    storePagination.total = response.pagination.total;

                    if (!append) {
                        $('#storeTableBody').empty();
                    }

                    if (response.stores.length === 0 && !append) {
                        $('#storeTableBody').html(
                            '<tr class="no-results-row"><td colspan="2"><i class="bx bx-info-circle me-1"></i>No active stores found</td></tr>'
                        );
                    } else {
                        response.stores.forEach(function(store) {
                            var isSelected = selectedStoreIds.includes(parseInt(store.id));
                            $('#storeTableBody').append(
                                '<tr data-id="' + store.id + '" class="store-row ' + (isSelected ? 'selected' : '') + '">' +
                                    '<td>' +
                                        '<div class="form-check">' +
                                            '<input class="form-check-input store-checkbox" type="checkbox" value="' + store.id + '"' + (isSelected ? ' checked' : '') + '>' +
                                        '</div>' +
                                    '</td>' +
                                    '<td>' +
                                        '<i class="bx bx-store text-primary me-2"></i>' +
                                        '<span class="text-dark">' + escapeHtml(store.name) + '</span>' +
                                    '</td>' +
                                '</tr>'
                            );
                        });

                        // Add load more button if there are more
                        if (storePagination.hasMore) {
                            $('#storeTableBody').append(
                                '<tr class="load-more-row">' +
                                    '<td colspan="2">' +
                                        '<button type="button" class="btn btn-sm btn-outline-primary load-more-btn" id="loadMoreStores">' +
                                            '<i class="bx bx-plus me-1"></i>Load More' +
                                        '</button>' +
                                    '</td>' +
                                '</tr>'
                            );
                        }
                    }

                    updateStorePaginationInfo();
                }
            },
            error: function() {
                if (!append) {
                    $('#storeTableBody').html(
                        '<tr class="no-results-row"><td colspan="2"><i class="bx bx-error-circle me-1"></i>Error loading stores</td></tr>'
                    );
                }
            }
        });
    }

    function loadProducts(append) {
        if (!append) {
            $('#productTableBody').html(
                '<tr class="no-results-row"><td colspan="5"><i class="bx bx-loader-alt bx-spin me-1"></i>Loading products...</td></tr>'
            );
        } else {
            $('#productTableBody .load-more-row').remove();
        }

        $.ajax({
            url: '{{ route("ecom-discounts.search-products") }}',
            type: 'GET',
            data: {
                search: productPagination.search,
                store_id: productPagination.storeId,
                page: productPagination.page,
                per_page: 20
            },
            success: function(response) {
                if (response.success) {
                    productPagination.hasMore = response.pagination.has_more;
                    productPagination.total = response.pagination.total;

                    if (!append) {
                        $('#productTableBody').empty();
                    }

                    if (response.products.length === 0 && !append) {
                        $('#productTableBody').html(
                            '<tr class="no-results-row"><td colspan="5"><i class="bx bx-info-circle me-1"></i>No active products found</td></tr>'
                        );
                    } else {
                        response.products.forEach(function(product) {
                            var isSelected = selectedProductIds.includes(parseInt(product.id));
                            var variantBadge = product.variantCount > 0
                                ? '<span class="badge bg-secondary variant-count-badge">' + product.variantCount + ' variants</span>'
                                : '';

                            $('#productTableBody').append(
                                '<tr data-id="' + product.id + '" class="product-row ' + (isSelected ? 'selected' : '') + '">' +
                                    '<td class="text-center">' +
                                        '<i class="bx bx-chevron-right expand-toggle" data-product-id="' + product.id + '"></i>' +
                                    '</td>' +
                                    '<td>' +
                                        '<div class="form-check">' +
                                            '<input class="form-check-input product-checkbox" type="checkbox" value="' + product.id + '"' + (isSelected ? ' checked' : '') + '>' +
                                        '</div>' +
                                    '</td>' +
                                    '<td>' +
                                        '<i class="bx bx-package text-success me-2"></i>' +
                                        '<span class="text-dark">' + escapeHtml(product.name) + '</span>' +
                                        variantBadge +
                                    '</td>' +
                                    '<td><span class="text-secondary">' + escapeHtml(product.store) + '</span></td>' +
                                    '<td><span class="badge bg-success text-white">' + product.price + '</span></td>' +
                                '</tr>'
                            );
                        });

                        // Add load more button if there are more
                        if (productPagination.hasMore) {
                            $('#productTableBody').append(
                                '<tr class="load-more-row">' +
                                    '<td colspan="5">' +
                                        '<button type="button" class="btn btn-sm btn-outline-primary load-more-btn" id="loadMoreProducts">' +
                                            '<i class="bx bx-plus me-1"></i>Load More' +
                                        '</button>' +
                                    '</td>' +
                                '</tr>'
                            );
                        }
                    }

                    updateProductPaginationInfo();
                }
            },
            error: function() {
                if (!append) {
                    $('#productTableBody').html(
                        '<tr class="no-results-row"><td colspan="5"><i class="bx bx-error-circle me-1"></i>Error loading products</td></tr>'
                    );
                }
            }
        });
    }

    function updateStorePaginationInfo() {
        var loaded = $('#storeTableBody tr.store-row').length;
        if (storePagination.total > 0) {
            $('#storePaginationInfo').text('Showing ' + loaded + ' of ' + storePagination.total);
        } else {
            $('#storePaginationInfo').text('');
        }
    }

    function updateProductPaginationInfo() {
        var loaded = $('#productTableBody tr.product-row').length;
        if (productPagination.total > 0) {
            $('#productPaginationInfo').text('Showing ' + loaded + ' of ' + productPagination.total);
        } else {
            $('#productPaginationInfo').text('');
        }
    }

    function updateStoreSelection(id, isSelected) {
        id = parseInt(id);
        if (isSelected && !selectedStoreIds.includes(id)) {
            selectedStoreIds.push(id);
        } else if (!isSelected) {
            selectedStoreIds = selectedStoreIds.filter(function(i) { return i !== id; });
        }
    }

    function updateProductSelection(id, isSelected) {
        id = parseInt(id);
        if (isSelected && !selectedProductIds.includes(id)) {
            selectedProductIds.push(id);
        } else if (!isSelected) {
            selectedProductIds = selectedProductIds.filter(function(i) { return i !== id; });
        }
    }

    function updateStoreCount() {
        $('#storeSelectedCount').text(selectedStoreIds.length + ' selected');
    }

    function updateProductCount() {
        $('#productSelectedCount').text(selectedProductIds.length + ' selected');
    }

    function updateSectionsVisibility() {
        $('#storeSelectionSection, #productSelectionSection').hide();

        if (window.currentRestrictionType === 'stores') {
            $('#storeSelectionSection').show();
        } else if (window.currentRestrictionType === 'products') {
            $('#productSelectionSection').show();
        }
    }

    function saveRestrictions() {
        var data = {
            restrictionType: window.currentRestrictionType,
            storeIds: selectedStoreIds,
            productIds: selectedProductIds,
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: '/ecom-discounts/{{ $discount->id }}/restrictions',
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success!');
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred', 'Error!');
            }
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
});

// Global function for restriction type selection
function selectRestrictionType(type) {
    $('.restriction-type-btn').removeClass('active');
    $('.restriction-type-btn[data-type="' + type + '"]').addClass('active');

    window.currentRestrictionType = type;

    // Update visibility
    $('#storeSelectionSection, #productSelectionSection').hide();

    if (type === 'stores') {
        $('#storeSelectionSection').show();
    } else if (type === 'products') {
        $('#productSelectionSection').show();
    }
}
</script>
@endsection
