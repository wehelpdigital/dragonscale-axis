<?php $__env->startSection('title'); ?> Discounts <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<!-- DataTables -->
<link href="<?php echo e(URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />
<!-- Responsive datatable examples -->
<link href="<?php echo e(URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link href="<?php echo e(URL::asset('build/libs/toastr/build/toastr.min.css')); ?>" rel="stylesheet" type="text/css" />

<style>
.badge-style {
    border-radius: 20px !important;
    padding: 4px 12px !important;
    font-size: 11px !important;
    font-weight: 500 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    border-width: 1px !important;
    transition: all 0.2s ease !important;
    min-width: auto !important;
    line-height: 1.2 !important;
}

.badge-style:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
}

.badge-style:active {
    transform: translateY(0) !important;
}

/* Specific color enhancements for better badge appearance */
.btn-outline-primary.badge-style {
    color: #556ee6 !important;
    border-color: #556ee6 !important;
}

.btn-outline-primary.badge-style:hover {
    background-color: #556ee6 !important;
    color: white !important;
}

.btn-outline-info.badge-style {
    color: #50a5f1 !important;
    border-color: #50a5f1 !important;
}

.btn-outline-info.badge-style:hover {
    background-color: #50a5f1 !important;
    color: white !important;
}

.btn-outline-success.badge-style {
    color: #34c38f !important;
    border-color: #34c38f !important;
}

.btn-outline-success.badge-style:hover {
    background-color: #34c38f !important;
    color: white !important;
}

.btn-outline-danger.badge-style {
    color: #f46a6a !important;
    border-color: #f46a6a !important;
}

.btn-outline-danger.badge-style:hover {
    background-color: #f46a6a !important;
    color: white !important;
}

/* Loading Overlay Styles */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.9);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
}

.loading-spinner {
    text-align: center;
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.loading-text {
    color: #6c757d;
    font-weight: 500;
    font-size: 0.9rem;
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> E-commerce <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Discounts <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Discounts</h4>
                    <a href="<?php echo e(route('ecom-discounts.create')); ?>" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Create New Discount
                    </a>
                </div>

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

                <!-- Search -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="search-box">
                            <div class="position-relative">
                                <input type="text" class="form-control" autocomplete="off" id="searchTableList" placeholder="Search discounts...">
                                <i class="bx bx-search-alt search-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Discounts Table -->
                <div class="table-responsive position-relative">
                    <!-- Loading overlay -->
                    <div id="table-loading" class="loading-overlay" style="display: none;">
                        <div class="loading-spinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="loading-text mt-2">Loading discounts...</div>
                        </div>
                    </div>

                    <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-bordered table-striped" id="discounts-table">
                        <thead class="table-light">
                            <tr>
                                <th>Discount Name</th>
                                <th>Type</th>
                                <th>Trigger</th>
                                <th>Value</th>
                                <th>Active</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">
                    <i class="bx bx-toggle-right text-primary me-2"></i>Update Discount Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Update the status for: <strong id="statusDiscountName"></strong></p>
                <div class="mb-3">
                    <label for="statusSelect" class="form-label">Status</label>
                    <select class="form-select" id="statusSelect">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmStatusUpdate">
                    <i class="bx bx-check me-1"></i>Update Status
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this discount?</p>
                <p class="text-muted mb-0"><strong>Discount:</strong> <span id="deleteDiscountName"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="bx bx-trash me-1"></i>Delete Discount
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">
                    <i class="bx bx-info-circle text-info me-2"></i>Discount Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="detailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading details...</p>
                    </div>
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
<!-- Required datatable js -->
<script src="<?php echo e(URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js')); ?>"></script>
<!-- Responsive examples -->
<script src="<?php echo e(URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js')); ?>"></script>
<script src="<?php echo e(URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js')); ?>"></script>
<!-- Toastr -->
<script src="<?php echo e(URL::asset('build/libs/toastr/build/toastr.min.js')); ?>"></script>

<script>
    $(document).ready(function() {
        // Show loading indicator initially
        $('#table-loading').show();

        // Initialize DataTable
        var table = $('#discounts-table').DataTable({
            processing: false, // Disable built-in processing indicator
            serverSide: true,
            ajax: {
                url: "<?php echo e(route('ecom-discounts.data')); ?>",
                type: 'GET',
                beforeSend: function() {
                    $('#table-loading').show();
                },
                complete: function() {
                    $('#table-loading').hide();
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error);
                    $('#table-loading').hide();
                    toastr.error('Error loading discounts data. Please refresh the page.', 'Error', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000
                    });
                }
            },
            columns: [
                { data: 'discountName', name: 'discountName' },
                { data: 'discountType', name: 'discountType' },
                { data: 'discountTrigger', name: 'discountTrigger' },
                { data: 'value', name: 'value', orderable: false, searchable: false },
                { data: 'active', name: 'isActive', orderable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
            ],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            responsive: true,
            language: {
                emptyTable: "No discounts found",
                zeroRecords: "No matching discounts found",
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                infoFiltered: "(filtered from _MAX_ total entries)",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            dom: '<"row"<"col-sm-12 col-md-6"l>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            initComplete: function() {
                // Hide loading indicator when table is initialized
                $('#table-loading').hide();
                console.log('Discounts table initialized successfully');
            }
        });

        // Search functionality with loading indicator
        $('#searchTableList').on('keyup', function() {
            $('#table-loading').show();
            table.search(this.value).draw();
        });

        // Details button click handler (delegated)
        $(document).on('click', '.details-btn', function() {
            const discountId = $(this).data('discount-id');
            const discountName = $(this).data('discount-name');

            // Show modal with loading state
            $('#detailsModal').modal('show');
            $('#detailsContent').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading details...</p>
                </div>
            `);

            // Fetch discount details
            $.ajax({
                url: '/ecom-discounts/' + discountId,
                type: 'GET',
                success: function(response) {
                    if (response.success && response.details) {
                        let detailsHtml = '<div class="table-responsive"><table class="table table-bordered">';

                        // Loop through details and display them
                        $.each(response.details, function(label, value) {
                            detailsHtml += `
                                <tr>
                                    <td style="width: 40%; font-weight: 600; background-color: #f8f9fa;">${label}</td>
                                    <td style="width: 60%;">${value}</td>
                                </tr>
                            `;
                        });

                        detailsHtml += '</table></div>';
                        $('#detailsContent').html(detailsHtml);
                    } else {
                        $('#detailsContent').html(`
                            <div class="alert alert-warning text-center">
                                <i class="bx bx-error-circle me-2"></i>
                                Unable to load discount details.
                            </div>
                        `);
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while loading discount details.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    $('#detailsContent').html(`
                        <div class="alert alert-danger text-center">
                            <i class="bx bx-error-circle me-2"></i>
                            ${errorMessage}
                        </div>
                    `);
                }
            });
        });


        // Status button click handler (delegated)
        let discountToUpdateStatus = null;

        $(document).on('click', '.status-btn', function() {
            const discountId = $(this).data('discount-id');
            const discountName = $(this).data('discount-name');
            const rawStatus = $(this).data('current-status');

            // Handle empty or null values
            let currentStatus = 0; // Default to 0 (Inactive)
            if (rawStatus !== null && rawStatus !== undefined && rawStatus !== '') {
                currentStatus = parseInt(rawStatus);
                if (isNaN(currentStatus)) {
                    currentStatus = 0; // Default to 0 if parsing fails
                }
            }

            discountToUpdateStatus = {
                id: discountId,
                name: discountName,
                currentStatus: currentStatus,
                button: $(this)
            };

            $('#statusDiscountName').text(discountName);
            $('#statusSelect').val(currentStatus);

            $('#statusModal').modal('show');
        });

        // Handle status update confirmation
        $('#confirmStatusUpdate').on('click', function() {
            if (!discountToUpdateStatus) return;

            const $btn = $(this);
            const originalText = $btn.html();
            const newStatus = $('#statusSelect').val();

            // Show loading state
            $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');

            $.ajax({
                url: '/ecom-discounts/' + discountToUpdateStatus.id + '/status',
                type: 'PATCH',
                data: {
                    _token: '<?php echo e(csrf_token()); ?>',
                    isActive: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        // Hide modal
                        $('#statusModal').modal('hide');

                        // Show success toastr notification
                        toastr.success(response.message, 'Success!', {
                            closeButton: true,
                            progressBar: true,
                            timeOut: 3000
                        });

                        // Update the data attribute on the status button
                        discountToUpdateStatus.button.data('current-status', newStatus);

                        // Reload the DataTable to reflect changes
                        table.ajax.reload(null, false);
                    } else {
                        toastr.error(response.message, 'Error!', {
                            closeButton: true,
                            progressBar: true,
                            timeOut: 5000
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while updating the status.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    toastr.error(errorMessage, 'Error!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000
                    });
                },
                complete: function() {
                    // Reset button state
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Delete button click handler (delegated)
        let discountToDelete = null;

        $(document).on('click', '.delete-btn', function() {
            const discountId = $(this).data('discount-id');
            const discountName = $(this).data('discount-name');

            discountToDelete = {
                id: discountId,
                name: discountName
            };

            $('#deleteDiscountName').text(discountName);
            $('#deleteModal').modal('show');
        });

        // Handle delete confirmation
        $('#confirmDelete').on('click', function() {
            if (!discountToDelete) return;

            const $btn = $(this);
            const originalText = $btn.html();

            // Show loading state
            $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

            $.ajax({
                url: '/ecom-discounts/' + discountToDelete.id,
                type: 'DELETE',
                data: {
                    _token: '<?php echo e(csrf_token()); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Hide modal
                        $('#deleteModal').modal('hide');

                        // Show success toastr notification
                        toastr.success(response.message, 'Success!', {
                            closeButton: true,
                            progressBar: true,
                            timeOut: 3000
                        });

                        // Reload the DataTable to reflect changes
                        table.ajax.reload(null, false);
                    } else {
                        toastr.error(response.message, 'Error!', {
                            closeButton: true,
                            progressBar: true,
                            timeOut: 5000
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred while deleting the discount.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    toastr.error(errorMessage, 'Error!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000
                    });
                },
                complete: function() {
                    // Reset button state
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/discounts/index.blade.php ENDPATH**/ ?>