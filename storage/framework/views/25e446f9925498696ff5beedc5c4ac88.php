<?php $__env->startSection('title'); ?> Variant Shipping <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<!-- Toastr CSS -->
<link href="<?php echo e(URL::asset('build/libs/toastr/build/toastr.min.css')); ?>" rel="stylesheet" type="text/css" />

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 0.375rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 0.375rem 0.375rem 0 0 !important;
}

.btn-back {
    background-color: #6c757d;
    border-color: #6c757d;
    color: white;
    transition: all 0.2s ease;
}

.btn-back:hover {
    background-color: #5a6268;
    border-color: #545b62;
    color: white;
    transform: translateY(-1px);
}

.variant-info {
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.variant-name {
    font-size: 1rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.variant-details {
    color: #6c757d;
    font-size: 0.875rem;
}

/* Shipping row selection styling */
.shipping-row.table-primary {
    background-color: #f6b93b !important;
    color: #000 !important;
    --bs-table-hover-bg: #f6b93b !important;
    --bs-table-bg: #f6b93b !important;
    --bs-table-striped-bg: #f6b93b !important;
    --bs-table-striped-color: #000 !important;
}

.shipping-row.table-primary td {
    background-color: #f6b93b !important;
    color: #000 !important;
    vertical-align: middle !important;
}

.shipping-row.table-primary:hover,
.shipping-row.table-primary:hover td {
    background-color: #f6b93b !important;
    color: #000 !important;
}

/* Override Bootstrap striped table variables for selected rows */
.shipping-row.table-primary:nth-of-type(odd) {
    --bs-table-striped-bg: #f6b93b !important;
}

.shipping-row.table-primary:nth-of-type(even) {
    --bs-table-striped-bg: #f6b93b !important;
}

.shipping-row {
    cursor: pointer;
    transition: all 0.2s ease;
}

.shipping-row:hover {
    background-color: #f8f9fa;
}

.shipping-row td {
    vertical-align: middle !important;
}

.shipping-checkbox {
    cursor: pointer;
}

</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> E-commerce <?php $__env->endSlot(); ?>
<?php $__env->slot('li_2'); ?> Products <?php $__env->endSlot(); ?>
<?php $__env->slot('li_3'); ?> Variants <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Variant Shipping <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">Shipping Configuration</h4>
                        <p class="card-title-desc mb-0">Configure shipping settings for this variant</p>
                    </div>
                    <a href="<?php echo e(route('ecom-products.variants', ['id' => $product->id])); ?>" class="btn btn-back">
                        <i class="bx bx-arrow-back"></i> Back to Variants
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Success/Error Messages -->
                <?php if(session('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bx bx-check-circle me-2"></i>
                        <?php echo e(session('success')); ?>

                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if(session('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-2"></i>
                        <?php echo e(session('error')); ?>

                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Variant Information -->
                <div class="variant-info">
                    <div class="variant-name"><?php echo e($variant->ecomVariantName); ?></div>
                    <div class="variant-details">
                        <strong>Product:</strong> <?php echo e($product->productName); ?> |
                        <strong>Price:</strong> ₱<?php echo e(number_format($variant->ecomVariantPrice, 2)); ?>

                    </div>
                </div>

                <!-- Search and Controls -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bx bx-search"></i>
                            </span>
                            <input type="text" class="form-control" id="shipping-search" placeholder="Search shipping methods...">
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-outline-primary" id="refresh-shipping">
                            <i class="bx bx-refresh"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="shipping-table-loading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading shipping methods...</p>
                </div>

                <!-- Shipping Methods Table -->
                <div class="table-responsive" id="shipping-table-container">
                    <table class="table table-bordered table-striped" id="shipping-methods-table">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">
                                    <input type="checkbox" id="select-all-shipping" class="form-check-input">
                                </th>
                                <th>Shipping Name</th>
                                <th>Default Price</th>
                                <th>Default Max Quantity</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="shipping-methods-tbody">
                            <!-- Data will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Shipping methods pagination" class="mt-3">
                    <ul class="pagination justify-content-center" id="shipping-pagination">
                        <!-- Pagination will be generated dynamically -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Shipping Options Modal -->
<div class="modal fade" id="shippingOptionsModal" tabindex="-1" aria-labelledby="shippingOptionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shippingOptionsModalLabel">
                    <i class="bx bx-package text-primary me-2"></i>Shipping Options
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="shipping-options-loading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading shipping options...</p>
                </div>

                <div id="shipping-options-content" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Province Target</th>
                                    <th>Max Quantity</th>
                                    <th>Shipping Price</th>
                                </tr>
                            </thead>
                            <tbody id="shipping-options-tbody">
                                <!-- Data will be populated here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="shipping-options-empty" class="text-center py-4" style="display: none;">
                    <i class="bx bx-package display-4 text-muted"></i>
                    <p class="mt-2 text-muted">No shipping options found for this method.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<!-- Toastr -->
<script src="<?php echo e(URL::asset('build/libs/toastr/build/toastr.min.js')); ?>"></script>

<script>
// Toastr configuration
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000,
    extendedTimeOut: 1000,
    preventDuplicates: true
};

$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Shipping table variables
    let selectedShippingMethods = [];
    let currentPage = 1;
    let currentSearch = '';
    let searchTimeout;
    let currentVariantId = parseInt('<?php echo e($variant->id); ?>');

    // Load shipping methods on page load
    loadShippingMethods();

    // Search functionality
    $('#shipping-search').on('input', function() {
        clearTimeout(searchTimeout);
        const searchTerm = $(this).val();

        searchTimeout = setTimeout(function() {
            currentSearch = searchTerm;
            currentPage = 1;
            loadShippingMethods();
        }, 500); // 500ms delay for search
    });

    // Refresh button
    $('#refresh-shipping').on('click', function() {
        currentSearch = '';
        currentPage = 1;
        $('#shipping-search').val('');
        loadShippingMethods();
    });

    // Load shipping methods function
    function loadShippingMethods() {
        showLoading();

        // First, get existing selections
        $.ajax({
            url: '/ecom-products-variants-shipping-selections',
            type: 'GET',
            data: {
                variant_id: currentVariantId
            },
            success: function(selectionsResponse) {
                if (selectionsResponse.success) {
                    selectedShippingMethods = selectionsResponse.data;
                }

                // Then load shipping methods
                $.ajax({
                    url: '/ecom-products-variants-shipping-methods',
                    type: 'GET',
                    data: {
                        search: currentSearch,
                        page: currentPage,
                        per_page: 10
                    },
                    success: function(response) {
                        hideLoading();

                        if (response.success) {
                            populateShippingTable(response.data);
                            updatePagination(response.pagination);
                        } else {
                            showError('Failed to load shipping methods.');
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        console.error('Error loading shipping methods:', xhr);
                        showError('An error occurred while loading shipping methods.');
                    }
                });
            },
            error: function(xhr) {
                console.error('Error loading existing selections:', xhr);
                // Continue with loading shipping methods even if selections fail
                $.ajax({
                    url: '/ecom-products-variants-shipping-methods',
                    type: 'GET',
                    data: {
                        search: currentSearch,
                        page: currentPage,
                        per_page: 10
                    },
                    success: function(response) {
                        hideLoading();

                        if (response.success) {
                            populateShippingTable(response.data);
                            updatePagination(response.pagination);
                        } else {
                            showError('Failed to load shipping methods.');
                        }
                    },
                    error: function(xhr) {
                        hideLoading();
                        console.error('Error loading shipping methods:', xhr);
                        showError('An error occurred while loading shipping methods.');
                    }
                });
            }
        });
    }

    // Show loading state
    function showLoading() {
        $('#shipping-table-loading').show();
        $('#shipping-table-container').hide();
        $('#shipping-pagination').hide();
    }

    // Hide loading state
    function hideLoading() {
        $('#shipping-table-loading').hide();
        $('#shipping-table-container').show();
        $('#shipping-pagination').show();
    }

    // Show error message
    function showError(message) {
        $('#shipping-methods-tbody').html(`
            <tr>
                <td colspan="5" class="text-center text-danger">
                    <i class="bx bx-error-circle display-4"></i>
                    <p class="mt-2">${message}</p>
                </td>
            </tr>
        `);
    }

    // Populate shipping table
    function populateShippingTable(data) {
        const tbody = $('#shipping-methods-tbody');
        tbody.empty();

        if (data.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        <i class="bx bx-package display-4"></i>
                        <p class="mt-2">No shipping methods found</p>
                        <p class="text-muted">Shipping methods will appear here when they are added to the system.</p>
                    </td>
                </tr>
            `);
            return;
        }

        data.forEach(function(shipping) {
            // Check if this shipping method is already selected
            const isSelected = selectedShippingMethods.includes(shipping.id);
            const rowClass = isSelected ? 'shipping-row table-primary' : 'shipping-row';
            const checkboxChecked = isSelected ? 'checked' : '';
            const selectBtnClass = isSelected ? 'btn btn-sm btn-success shipping-select-btn' : 'btn btn-sm btn-outline-primary shipping-select-btn';
            const selectBtnIcon = isSelected ? 'bx-check-circle' : 'bx-check';
            const selectBtnTitle = isSelected ? 'Selected' : 'Select Shipping Method';

            const row = `
                <tr class="${rowClass}" data-shipping-id="${shipping.id}">
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input shipping-checkbox" data-shipping-id="${shipping.id}" ${checkboxChecked}>
                    </td>
                    <td>${shipping.shippingName}</td>
                    <td>₱${parseFloat(shipping.defaultPrice).toFixed(2)}</td>
                    <td>${shipping.defaultMaxQuantity}</td>
                    <td class="text-center">
                        <button type="button" class="${selectBtnClass}"
                                data-shipping-id="${shipping.id}" title="${selectBtnTitle}">
                            <i class="bx ${selectBtnIcon}"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info" title="View Details">
                            <i class="bx bx-show"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        // Re-bind events for dynamically loaded content
        bindRowEvents();
    }

    // Update pagination
    function updatePagination(pagination) {
        const paginationContainer = $('#shipping-pagination');
        paginationContainer.empty();

        if (pagination.last_page <= 1) {
            return;
        }

        // Previous button
        const prevDisabled = pagination.current_page === 1 ? 'disabled' : '';
        paginationContainer.append(`
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a>
            </li>
        `);

        // Page numbers
        for (let i = 1; i <= pagination.last_page; i++) {
            const active = i === pagination.current_page ? 'active' : '';
            paginationContainer.append(`
                <li class="page-item ${active}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        // Next button
        const nextDisabled = pagination.current_page === pagination.last_page ? 'disabled' : '';
        paginationContainer.append(`
            <li class="page-item ${nextDisabled}">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next</a>
            </li>
        `);
    }

    // Bind pagination events
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && page !== currentPage) {
            currentPage = page;
            loadShippingMethods();
        }
    });

    // Bind row events for dynamically loaded content
    function bindRowEvents() {
        // Re-bind all the existing event handlers
        bindShippingEvents();
    }

    // Bind all shipping-related events
    function bindShippingEvents() {
        // Handle individual checkbox selection - use event delegation
        $(document).off('change', '.shipping-checkbox').on('change', '.shipping-checkbox', function() {
        const shippingId = $(this).data('shipping-id');
        const row = $(this).closest('.shipping-row');
        const selectBtn = row.find('.shipping-select-btn');
        const shippingName = row.find('td:nth-child(2)').text();

        if ($(this).is(':checked')) {
            // Add to selected methods
            if (!selectedShippingMethods.includes(shippingId)) {
                selectedShippingMethods.push(shippingId);
            }
            row.addClass('table-primary');
            // Update select button
            selectBtn.removeClass('btn-outline-primary').addClass('btn-success');
            selectBtn.find('i').removeClass('bx-check').addClass('bx-check-circle');
            selectBtn.attr('title', 'Selected');

            // Add to database
            addShippingToVariant(shippingId, shippingName);
        } else {
            // Remove from selected methods
            selectedShippingMethods = selectedShippingMethods.filter(id => id !== shippingId);
            row.removeClass('table-primary');
            // Update select button
            selectBtn.removeClass('btn-success').addClass('btn-outline-primary');
            selectBtn.find('i').removeClass('bx-check-circle').addClass('bx-check');
            selectBtn.attr('title', 'Select Shipping Method');

            // Remove from database
            removeShippingFromVariant(shippingId, shippingName);
        }

        updateSelectAllCheckbox();
        console.log('Selected shipping methods:', selectedShippingMethods);
    });

    // Handle row click selection (excluding checkbox clicks) - use event delegation
    $(document).off('click', '.shipping-row').on('click', '.shipping-row', function(e) {
        // Don't trigger if clicking on checkbox or button
        if ($(e.target).is('input[type="checkbox"]') || $(e.target).is('button') || $(e.target).closest('button').length) {
            return;
        }

        const shippingId = $(this).data('shipping-id');
        const checkbox = $(this).find('.shipping-checkbox');

        // Toggle selection
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // Handle select all checkbox
    $('#select-all-shipping').off('change').on('change', function() {
        const isChecked = $(this).is(':checked');

        $('.shipping-checkbox').each(function() {
            $(this).prop('checked', isChecked).trigger('change');
        });
    });

    // Update select all checkbox state
    function updateSelectAllCheckbox() {
        const totalCheckboxes = $('.shipping-checkbox').length;
        const checkedCheckboxes = $('.shipping-checkbox:checked').length;

        if (checkedCheckboxes === 0) {
            $('#select-all-shipping').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#select-all-shipping').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#select-all-shipping').prop('indeterminate', true);
        }
    }

    // Handle select button clicks - use event delegation
    $(document).off('click', '.shipping-select-btn').on('click', '.shipping-select-btn', function(e) {
        e.stopPropagation(); // Prevent row selection when clicking select button

        const shippingId = $(this).data('shipping-id');
        const checkbox = $(this).closest('.shipping-row').find('.shipping-checkbox');

        // Toggle selection
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });

    // Handle view button clicks - use event delegation
    $(document).off('click', '.btn-outline-info').on('click', '.btn-outline-info', function(e) {
        e.stopPropagation(); // Prevent row selection when clicking view button

        const shippingId = $(this).closest('.shipping-row').data('shipping-id');
        const shippingName = $(this).closest('.shipping-row').find('td:nth-child(2)').text();

        console.log('View shipping method:', shippingId);

        // Show modal and load shipping options
        showShippingOptionsModal(shippingId, shippingName);
    });
    }

    // Function to show shipping options modal
    function showShippingOptionsModal(shippingId, shippingName) {
        // Update modal title
        $('#shippingOptionsModalLabel').html(`<i class="bx bx-package text-primary me-2"></i>Shipping Options - ${shippingName}`);

        // Show loading state
        $('#shipping-options-loading').show();
        $('#shipping-options-content').hide();
        $('#shipping-options-empty').hide();

        // Show modal
        $('#shippingOptionsModal').modal('show');

        // Fetch shipping options
        $.ajax({
            url: '/ecom-products-variants-shipping-options',
            type: 'GET',
            data: {
                shipping_id: shippingId
            },
            success: function(response) {
                $('#shipping-options-loading').hide();

                if (response.success && response.data.length > 0) {
                    // Populate table with data
                    populateShippingOptionsTable(response.data);
                    $('#shipping-options-content').show();
                } else {
                    // Show empty state
                    $('#shipping-options-empty').show();
                }
            },
            error: function(xhr) {
                $('#shipping-options-loading').hide();
                console.error('Error fetching shipping options:', xhr);

                toastr.error('An error occurred while loading shipping options.', 'Error!', {
                    closeButton: true,
                    progressBar: true,
                    timeOut: 5000
                });
            }
        });
    }

    // Function to populate shipping options table
    function populateShippingOptionsTable(data) {
        const tbody = $('#shipping-options-tbody');
        tbody.empty();

        data.forEach(function(option) {
            const row = `
                <tr>
                    <td>${option.provinceTarget}</td>
                    <td>${option.maxQuantity}</td>
                    <td>₱${parseFloat(option.shippingPrice).toFixed(2)}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Function to add shipping method to variant
    function addShippingToVariant(shippingId, shippingName) {
        $.ajax({
            url: '/ecom-products-variants-shipping-add',
            type: 'POST',
            data: {
                _token: '<?php echo e(csrf_token()); ?>',
                variant_id: currentVariantId,
                shipping_id: shippingId
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(`"${shippingName}" has been assigned to this variant.`, 'Shipping Method Added', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 3000
                    });
                } else {
                    toastr.error(response.message, 'Error!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while assigning the shipping method.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                toastr.error(errorMessage, 'Error!', {
                    closeButton: true,
                    progressBar: true,
                    timeOut: 5000
                });
            }
        });
    }

    // Function to remove shipping method from variant
    function removeShippingFromVariant(shippingId, shippingName) {
        $.ajax({
            url: '/ecom-products-variants-shipping-remove',
            type: 'POST',
            data: {
                _token: '<?php echo e(csrf_token()); ?>',
                variant_id: currentVariantId,
                shipping_id: shippingId
            },
            success: function(response) {
                if (response.success) {
                    toastr.info(`"${shippingName}" has been removed from this variant.`, 'Shipping Method Removed', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 3000
                    });
                } else {
                    toastr.error(response.message, 'Error!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while removing the shipping method.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                toastr.error(errorMessage, 'Error!', {
                    closeButton: true,
                    progressBar: true,
                    timeOut: 5000
                });
            }
        });
    }

    // Initialize events on page load - using event delegation, so no need to rebind
    bindShippingEvents();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/products/variants/shipping.blade.php ENDPATH**/ ?>