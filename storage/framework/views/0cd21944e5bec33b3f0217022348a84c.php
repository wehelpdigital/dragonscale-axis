<?php $__env->startSection('title'); ?> Product Triggers <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<!-- Toastr CSS -->
<link href="<?php echo e(URL::asset('build/libs/toastr/build/toastr.min.css')); ?>" rel="stylesheet" type="text/css" />
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> E-commerce <?php $__env->endSlot(); ?>
<?php $__env->slot('li_2'); ?> Products <?php $__env->endSlot(); ?>
<?php $__env->slot('li_3'); ?> Triggers <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Product Triggers <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title">Product Triggers</h4>
                        <?php if($product): ?>
                            <p class="card-title-desc">Manage triggers for: <strong><?php echo e($product->productName); ?></strong></p>
                        <?php else: ?>
                            <p class="card-title-desc">Product not found.</p>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo e(route('ecom-products')); ?>" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Back to Products
                    </a>
                </div>

                <?php if($product): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-primary">
                                    <i class="bx bx-tag me-2"></i>Tags to Trigger the Course
                                </h5>
                                <p class="text-muted">These are the access tags that can trigger access to this course.</p>
                            </div>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTriggerTagModal">
                                <i class="bx bx-plus me-1"></i>Add Trigger Tag
                            </button>
                        </div>
                    </div>

                    <?php if($productTags->count() > 0): ?>
                        <div class="table-responsive" id="mainTableContainer">
                            <table class="table table-bordered table-striped" id="mainProductTagsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tag Name</th>
                                        <th>Expiration Length</th>
                                        <th>Product Status</th>
                                        <th width="100">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="productTagsTableBody">
                                    <?php $__currentLoopData = $productTags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr data-tag-id="<?php echo e($tag->id); ?>" data-tag-name="<?php echo e($tag->tagName); ?>">
                                            <td><?php echo e($tag->tagName); ?></td>
                                            <td><?php echo e($tag->expirationLength); ?></td>
                                            <td>
                                                <?php if($product->isActive == 1): ?>
                                                    <span class="badge bg-success">Enabled</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Disabled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-tag-btn"
                                                        data-tag-id="<?php echo e($tag->id); ?>"
                                                        data-tag-name="<?php echo e($tag->tagName); ?>"
                                                        title="Delete Tag">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5" id="noTagsMessage">
                            <i class="bx bx-tag display-1 text-muted"></i>
                            <h5 class="mt-3 text-muted">No Tags Found</h5>
                            <p class="text-muted">No access tags have been configured for this course yet.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bx bx-exclamation-triangle me-2"></i>
                        <strong>Warning!</strong> The requested product could not be found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



<!-- Delete Tag Confirmation Modal -->
<div class="modal fade" id="deleteTagModal" tabindex="-1" aria-labelledby="deleteTagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTagModalLabel">
                    <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove this tag from the product?</p>
                <p class="text-muted mb-0"><strong>Tag:</strong> <span id="deleteTagName"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteTag">
                    <i class="bx bx-trash me-1"></i>Delete Tag
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Trigger Tag Modal -->
<div class="modal fade" id="addTriggerTagModal" tabindex="-1" aria-labelledby="addTriggerTagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTriggerTagModalLabel">
                    <i class="bx bx-plus-circle me-2"></i>Add Trigger Tag
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Search Fields -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="searchTag" class="form-label">Search Tag</label>
                        <input type="text" class="form-control" id="searchTag" placeholder="Search by tag name...">
                    </div>
                    <div class="col-md-6">
                        <label for="searchItem" class="form-label">Search Item</label>
                        <input type="text" class="form-control" id="searchItem" placeholder="Search by item name...">
                    </div>
                </div>

                <!-- Loading Spinner -->
                <div id="loadingSpinner" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Loading available tags...</p>
                </div>

                <!-- Available Tags Table -->
                <div class="table-responsive" id="tableContainer" style="display: none;">
                    <table class="table table-bordered table-striped" id="availableTagsTable">
                        <thead class="table-light">
                            <tr>
                                <th width="50">
                                    <input type="checkbox" class="form-check-input" id="selectAllTags">
                                </th>
                                <th>Tag Name</th>
                                <th>Type</th>
                                <th>Item Name</th>
                                <th>Expiration Length</th>
                            </tr>
                        </thead>
                        <tbody id="availableTagsTableBody">
                            <!-- Data will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>

                <div id="noTagsFound" class="text-center py-4" style="display: none;">
                    <i class="bx bx-search display-4 text-muted"></i>
                    <h5 class="mt-3 text-muted">No Tags Found</h5>
                    <p class="text-muted">No available tags found matching your search criteria.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveTriggerTags">
                    <i class="bx bx-save me-1"></i>Save Selected Tags
                </button>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<!-- Toastr JS -->
<script src="<?php echo e(URL::asset('build/libs/toastr/build/toastr.min.js')); ?>"></script>
<script>
// Ensure Toastr is loaded after jQuery
if (typeof jQuery !== 'undefined' && typeof toastr === 'undefined') {
    console.error('Toastr failed to load. Please check the file path.');
}

$(document).ready(function() {
    // Triggers page specific JavaScript will go here
    console.log('Product Triggers page loaded');

    // Wait for Toastr to be fully loaded
    function waitForToastr(callback, maxAttempts = 10) {
        if (typeof toastr !== 'undefined' && typeof toastr.success === 'function') {
            callback();
        } else if (maxAttempts > 0) {
            setTimeout(function() {
                waitForToastr(callback, maxAttempts - 1);
            }, 100);
        } else {
            console.warn('Toastr not available, using fallback notifications');
        }
    }

    // Configure Toastr when it's ready
    waitForToastr(function() {
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": false,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
        console.log('Toastr configured successfully');
    });

    // Fallback notification function
    function showNotification(type, message) {
        try {
            if (typeof toastr !== 'undefined' && typeof toastr[type] === 'function') {
                toastr[type](message);
            } else {
                // Fallback to alert if toastr is not available
                console.warn('Toastr not available, using alert fallback');
                alert(message);
            }
        } catch (error) {
            console.error('Notification error:', error);
            // Ultimate fallback
            alert(message);
        }
    }

    // Load available tags when modal is shown
    $('#addTriggerTagModal').on('shown.bs.modal', function() {
        loadAvailableTags();
    });

        // Load available tags from database
    function loadAvailableTags() {
        const productId = '<?php echo e($product->id ?? ""); ?>';

        // Show loading spinner and hide other elements
        $('#loadingSpinner').show();
        $('#tableContainer').hide();
        $('#noTagsFound').hide();

        $.ajax({
            url: '<?php echo e(route("ecom-products.triggers.available-tags")); ?>',
            method: 'GET',
            data: {
                id: productId
            },
            success: function(response) {
                if (response.success) {
                    displayAvailableTags(response.tags);
                } else {
                    console.error('Error loading tags:', response.message);
                    $('#loadingSpinner').hide();
                    $('#noTagsFound').show().html(`
                        <i class="bx bx-error-circle display-4 text-danger"></i>
                        <h5 class="mt-3 text-danger">Error Loading Tags</h5>
                        <p class="text-muted">Failed to load available tags. Please try again.</p>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#loadingSpinner').hide();
                $('#noTagsFound').show().html(`
                    <i class="bx bx-error-circle display-4 text-danger"></i>
                    <h5 class="mt-3 text-danger">Error Loading Tags</h5>
                    <p class="text-muted">Failed to load available tags. Please try again.</p>
                `);
            }
        });
    }

    // Display available tags in the table
    function displayAvailableTags(tags) {
        const tbody = $('#availableTagsTableBody');
        tbody.empty();

        // Hide loading spinner
        $('#loadingSpinner').hide();

        if (tags.length === 0) {
            $('#noTagsFound').show();
            $('#tableContainer').hide();
        } else {
            $('#noTagsFound').hide();
            $('#tableContainer').show();

            tags.forEach(function(tag) {
                const row = `
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input tag-checkbox"
                                   value="${tag.id}" data-tag-name="${tag.tagName}">
                        </td>
                        <td>${tag.tagName}</td>
                        <td>${tag.tagType || 'N/A'}</td>
                        <td>${tag.courseName || 'N/A'}</td>
                        <td>${tag.expirationLength}</td>
                    </tr>
                `;
                tbody.append(row);
            });
        }
    }

    // Search functionality
    $('#searchTag, #searchItem').on('input', function() {
        const tagSearch = $('#searchTag').val().toLowerCase();
        const itemSearch = $('#searchItem').val().toLowerCase();

        // Show loading spinner during search
        if (tagSearch || itemSearch) {
            $('#loadingSpinner').show();
            $('#tableContainer').hide();
            $('#noTagsFound').hide();

            // Add a small delay to show loading state and prevent excessive API calls
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(function() {
                performSearch(tagSearch, itemSearch);
            }, 300);
        } else {
            // If search fields are empty, show all results
            $('#loadingSpinner').hide();
            $('#tableContainer').show();
            $('#noTagsFound').hide();
        }
    });

    // Perform the actual search
    function performSearch(tagSearch, itemSearch) {
        $('#availableTagsTableBody tr').each(function() {
            const tagName = $(this).find('td:eq(1)').text().toLowerCase();
            const itemName = $(this).find('td:eq(3)').text().toLowerCase();

            const tagMatch = tagName.includes(tagSearch);
            const itemMatch = itemName.includes(itemSearch);

            if (tagMatch && itemMatch) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Show/hide no results message
        const visibleRows = $('#availableTagsTableBody tr:visible').length;
        if (visibleRows === 0) {
            $('#loadingSpinner').hide();
            $('#tableContainer').hide();
            $('#noTagsFound').show();
        } else {
            $('#loadingSpinner').hide();
            $('#tableContainer').show();
            $('#noTagsFound').hide();
        }
    }

    // Select all functionality
    $('#selectAllTags').change(function() {
        $('.tag-checkbox').prop('checked', $(this).is(':checked'));
    });

    // Function to clear the modal form
    function clearModalForm() {
        // Clear search fields
        $('#searchTag').val('');
        $('#searchItem').val('');

        // Uncheck all checkboxes
        $('.tag-checkbox').prop('checked', false);
        $('#selectAllTags').prop('checked', false);

        // Clear the table body
        $('#availableTagsTableBody').empty();

        // Hide table and show no tags message
        $('#tableContainer').hide();
        $('#noTagsFound').hide();
        $('#loadingSpinner').hide();
    }

    // Function to refresh the product tags table
    function refreshProductTags() {
        const productId = '<?php echo e($product->id ?? ""); ?>';

        // Show loading state on main table
        $('#mainTableContainer').hide();
        $('#noTagsMessage').hide();

        // Create a loading spinner for the main table
        if ($('#mainTableLoadingSpinner').length === 0) {
            $('<div id="mainTableLoadingSpinner" class="text-center py-5">' +
                '<div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">' +
                '<span class="visually-hidden">Loading...</span></div>' +
                '<p class="mt-3 text-muted">Updating table...</p></div>').insertAfter('#mainTableContainer');
        }
        $('#mainTableLoadingSpinner').show();

        $.ajax({
            url: '<?php echo e(route("ecom-products.triggers")); ?>',
            method: 'GET',
            data: {
                id: productId,
                refresh: true
            },
            success: function(response) {
                // Parse the HTML response to extract the table data
                const tempDiv = $('<div>').html(response);
                const newTableBody = tempDiv.find('#productTagsTableBody');
                const newNoTagsMessage = tempDiv.find('#noTagsMessage');

                if (newTableBody.length > 0 && newTableBody.find('tr').length > 0) {
                    // Update the existing table body
                    $('#productTagsTableBody').html(newTableBody.html());

                    // Show the table and hide loading
                    $('#mainTableLoadingSpinner').hide();
                    $('#mainTableContainer').show();
                    $('#noTagsMessage').hide();

                    showNotification('success', 'Table updated successfully!');
                } else if (newNoTagsMessage.length > 0) {
                    // Show no tags message
                    $('#mainTableLoadingSpinner').hide();
                    $('#mainTableContainer').hide();
                    $('#noTagsMessage').show();

                    showNotification('info', 'No tags found for this product.');
                }

                // Also refresh the available tags in the modal
                loadAvailableTags();
            },
            error: function(xhr, status, error) {
                console.error('Refresh Error:', error);
                $('#mainTableLoadingSpinner').hide();
                $('#mainTableContainer').show(); // Show the old table
                showNotification('error', 'Failed to refresh table data.');
            }
        });
    }

    // Handle delete tag button clicks
    $(document).on('click', '.delete-tag-btn', function() {
        const tagId = $(this).data('tag-id');
        const tagName = $(this).data('tag-name');

        // Set the tag name in the confirmation modal
        $('#deleteTagName').text(tagName);

        // Store the tag ID for deletion
        $('#confirmDeleteTag').data('tag-id', tagId);

        // Show the confirmation modal
        $('#deleteTagModal').modal('show');
    });

    // Handle delete confirmation
    $('#confirmDeleteTag').click(function() {
        const tagId = $(this).data('tag-id');

        if (!tagId) {
            showNotification('error', 'Tag ID not found.');
            return;
        }

        // Disable button and show loading state
        const deleteBtn = $(this);
        const originalText = deleteBtn.html();
        deleteBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        // Make AJAX call to delete the tag
        $.ajax({
            url: '<?php echo e(route("ecom-products.triggers.delete-tag", ["id" => ":id"])); ?>'.replace(':id', tagId),
            method: 'DELETE',
            data: {
                _token: '<?php echo e(csrf_token()); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Show success notification
                    showNotification('success', response.message);

                    // Close the delete modal
                    $('#deleteTagModal').modal('hide');

                    // Remove the row from the table
                    $(`tr[data-tag-id="${tagId}"]`).fadeOut(300, function() {
                        $(this).remove();

                        // Check if table is empty
                        if ($('#productTagsTableBody tr').length === 0) {
                            // Hide table and show no tags message
                            $('#mainTableContainer').hide();
                            $('#noTagsMessage').show();
                        }
                    });

                    // Also refresh the available tags in the modal
                    loadAvailableTags();
                } else {
                    showNotification('error', response.message || 'Failed to delete tag.');
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete Error:', error);
                let errorMessage = 'An error occurred while deleting the tag.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                showNotification('error', errorMessage);
            },
            complete: function() {
                // Re-enable button
                deleteBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Save selected tags
    $('#saveTriggerTags').click(function() {
        const selectedTags = [];
        $('.tag-checkbox:checked').each(function() {
            selectedTags.push($(this).val());
        });

        if (selectedTags.length === 0) {
            alert('Please select at least one tag to add.');
            return;
        }

        // Disable save button and show loading state
        const saveBtn = $(this);
        const originalText = saveBtn.html();
        saveBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        // Make AJAX call to save the selected tags
        $.ajax({
            url: '<?php echo e(route("ecom-products.triggers.save-tags")); ?>',
            method: 'POST',
            data: {
                productId: '<?php echo e($product->id ?? ""); ?>',
                tagIds: selectedTags,
                _token: '<?php echo e(csrf_token()); ?>'
            },
                        success: function(response) {
                if (response.success) {
                    // Show success notification using Toastr
                    showNotification('success', response.message);

                    // Clear the modal form
                    clearModalForm();

                    // Close the add trigger tag modal
                    $('#addTriggerTagModal').modal('hide');

                    // Dynamically refresh the table data
                    refreshProductTags();
                } else {
                    showNotification('error', response.message || 'Failed to save tags.');
                }
            },
                        error: function(xhr, status, error) {
                console.error('Save Error:', error);
                let errorMessage = 'An error occurred while saving the tags.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                showNotification('error', errorMessage);
            },
            complete: function() {
                // Re-enable save button
                saveBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/products/triggers.blade.php ENDPATH**/ ?>