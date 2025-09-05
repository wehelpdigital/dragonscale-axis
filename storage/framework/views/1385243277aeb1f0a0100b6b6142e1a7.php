<?php $__env->startSection('title'); ?>
    Product Discounts
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
    <!-- Add any specific CSS for discounts page here -->
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

    .btn-outline-warning.badge-style {
        color: #6f42c1 !important;
        border-color: #6f42c1 !important;
    }

    .btn-outline-warning.badge-style:hover {
        background-color: #6f42c1 !important;
        color: white !important;
    }

    .btn-outline-secondary.badge-style {
        color: #74788d !important;
        border-color: #74788d !important;
    }

    .btn-outline-secondary.badge-style:hover {
        background-color: #74788d !important;
        color: white !important;
    }

    .btn-outline-success.badge-style {
        color: #198754 !important;
        border-color: #198754 !important;
    }

    .btn-outline-success.badge-style:hover {
        background-color: #198754 !important;
        color: white !important;
    }

    .btn-outline-dark.badge-style {
        color: #495057 !important;
        border-color: #495057 !important;
    }

    .btn-outline-dark.badge-style:hover {
        background-color: #495057 !important;
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
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            E-commerce
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('li_2'); ?>
            Products
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('li_3'); ?>
            Discounts
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Product Discounts
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title">Product Discounts</h4>
                            <p class="card-title-desc">Manage discounts for: <strong><?php echo e($product->productName); ?></strong></p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?php echo e(route('ecom-products.discounts.create', ['id' => $product->id])); ?>" class="btn btn-primary">
                                <i class="bx bx-plus me-1"></i>Add New Discount
                            </a>
                            <a href="<?php echo e(route('ecom-products')); ?>" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i>Back to Products
                            </a>
                        </div>
                    </div>


                    <!-- Discounts Table -->
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Discount Name</th>
                                    <th>Type</th>
                                    <th>Timer Type</th>
                                    <th>Discount Value Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $discounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $discount): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($discount->discountName); ?></td>
                                        <td>
                                            <span class="badge <?php echo e($discount->discountType === 'discount code' ? 'bg-primary' : 'bg-success'); ?>">
                                                <?php echo e($discount->discountType === 'discount code' ? 'Discount Code' : 'Auto Apply'); ?>

                                            </span>
                                        </td>
                                        <td><?php echo e($discount->timerType); ?></td>
                                        <td><?php echo e($discount->discountValueType); ?></td>
                                        <td class="text-center">
                                            <div class="d-flex flex-wrap gap-1 justify-content-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary badge-style"
                                                        onclick="viewDiscount(<?php echo e($discount->id); ?>)"
                                                        title="View Details">
                                                    <i class="bx bx-show me-1"></i>View
                                                </button>
                                                <a href="<?php echo e(route('ecom-products.discounts.edit', ['id' => $discount->id])); ?>"
                                                   class="btn btn-sm btn-outline-warning badge-style"
                                                   title="Edit">
                                                    <i class="bx bx-edit me-1"></i>Edit
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger badge-style"
                                                        onclick="deleteDiscount(<?php echo e($discount->id); ?>, '<?php echo e(addslashes($discount->discountName)); ?>')"
                                                        title="Delete">
                                                    <i class="bx bx-trash me-1"></i>Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="bx bx-tag display-4 text-muted"></i>
                                            <h5 class="mt-3 text-muted">No Discounts Found</h5>
                                            <p class="text-muted">No discounts have been created for this product yet.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
    <!-- Add any specific JavaScript for discounts page here -->
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

        // Discounts page specific JavaScript will go here
        console.log('Product Discounts page loaded');

        // Delete functionality
        let discountToDelete = null;

        // View discount details
        function viewDiscount(discountId) {
            // TODO: Implement view discount details functionality
            alert('View discount details for ID: ' + discountId);
        }

        // Edit discount
        function editDiscount(discountId) {
            // TODO: Implement edit discount functionality
            alert('Edit discount for ID: ' + discountId);
        }

        // Delete discount
        function deleteDiscount(discountId, discountName) {
            // Store discount info for deletion - use a more reliable method to get the row
            const button = event.target.closest('button');
            const row = button.closest('tr');

            discountToDelete = {
                id: discountId,
                name: discountName,
                row: row,
                button: button
            };

            // Show discount name in modal
            document.getElementById('deleteDiscountName').textContent = discountName;

            // Show the modal
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }

        // Handle delete confirmation
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (!discountToDelete) return;

            const btn = this;
            const originalText = btn.innerHTML;

            // Show loading state
            btn.disabled = true;
            btn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...';

            // Make AJAX request to delete discount
            fetch(`/ecom-products-discounts-delete?id=${discountToDelete.id}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hide modal
                    const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                    deleteModal.hide();

                    // Show success toastr notification
                    toastr.success(data.message, 'Success!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 3000
                    });

                    // Remove the row from the table with animation
                    const rowToRemove = discountToDelete.row;

                    console.log('Row to remove:', rowToRemove); // Debug log

                    if (rowToRemove) {
                        // Add fade out animation
                        rowToRemove.style.transition = 'opacity 0.4s ease';
                        rowToRemove.style.opacity = '0';

                        setTimeout(() => {
                            // Try multiple methods to remove the row
                            try {
                                // Method 1: Remove via parent node
                                if (rowToRemove && rowToRemove.parentNode) {
                                    console.log('Removing row via parentNode:', rowToRemove);
                                    rowToRemove.parentNode.removeChild(rowToRemove);
                                }
                                // Method 2: Remove using remove() method (modern browsers)
                                else if (rowToRemove && typeof rowToRemove.remove === 'function') {
                                    console.log('Removing row via remove() method:', rowToRemove);
                                    rowToRemove.remove();
                                }
                                // Method 3: Find row by discount ID and remove
                                else {
                                    console.log('Fallback: Finding row by discount ID');
                                    const allRows = document.querySelectorAll('tbody tr');
                                    allRows.forEach(row => {
                                        const deleteBtn = row.querySelector('button[onclick*="' + discountToDelete.id + '"]');
                                        if (deleteBtn) {
                                            console.log('Found and removing row:', row);
                                            row.remove();
                                        }
                                    });
                                }

                                // Check if table is empty
                                const tbody = document.querySelector('tbody');
                                if (tbody && tbody.children.length === 0) {
                                    tbody.innerHTML = `
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="bx bx-tag display-4 text-muted"></i>
                                                <p class="mt-2 text-muted">No discounts found</p>
                                            </td>
                                        </tr>
                                    `;
                                }
                            } catch (error) {
                                console.error('Error removing row:', error);
                                // Fallback: reload the page if row removal fails
                                window.location.reload();
                            }
                        }, 400);
                    } else {
                        console.error('Row element not found for removal');
                        // Fallback: reload the page if row removal fails
                        window.location.reload();
                    }
                } else {
                    toastr.error(data.message || 'An error occurred while deleting the discount.', 'Error!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('An error occurred while deleting the discount. Please try again.', 'Error!', {
                    closeButton: true,
                    progressBar: true,
                    timeOut: 5000
                });
            })
            .finally(() => {
                // Reset button state
                btn.disabled = false;
                btn.innerHTML = originalText;
                discountToDelete = null;
            });
        });

        // Reset discountToDelete when modal is hidden
        document.getElementById('deleteModal').addEventListener('hidden.bs.modal', function() {
            discountToDelete = null;
        });

    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/products/discounts.blade.php ENDPATH**/ ?>