@extends('layouts.master')

@section('title') Variant Triggers @endsection

@section('css')
<!-- Toastr CSS -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .trigger-description {
        max-width: 400px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .nav-tabs .nav-link {
        color: #495057;
    }
    .nav-tabs .nav-link.active {
        font-weight: 600;
    }
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') Products @endslot
@slot('li_3') Variants @endslot
@slot('li_4') Triggers @endslot
@slot('title') Variant Triggers @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title">Variant Triggers</h4>
                        @if($variant && $product)
                            <p class="card-title-desc">Manage trigger tags for: <strong>{{ $variant->ecomVariantName }}</strong> ({{ $product->productName }})</p>
                        @else
                            <p class="card-title-desc">Variant not found.</p>
                        @endif
                    </div>
                    <a href="{{ route('ecom-products.variants', ['id' => $product->id ?? '']) }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Back to Variants
                    </a>
                </div>

                @if($variant && $product)
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="text-primary">
                                    <i class="bx bx-tag me-2"></i>Trigger Tags
                                </h5>
                                <p class="text-muted mb-0">These trigger tags are associated with this variant for access control.</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addExistingTagModal">
                                    <i class="bx bx-list-check me-1"></i>Add Existing Tag
                                </button>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNewTagModal">
                                    <i class="bx bx-plus me-1"></i>Create New Tag
                                </button>
                            </div>
                        </div>
                    </div>

                    @if($variantTags->count() > 0)
                        <div class="table-responsive" id="mainTableContainer">
                            <table class="table table-bordered table-striped" id="mainVariantTagsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-dark">Tag Name</th>
                                        <th class="text-dark">Description</th>
                                        <th class="text-dark" width="100">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="variantTagsTableBody">
                                    @foreach($variantTags as $tag)
                                        <tr data-tag-id="{{ $tag->id }}" data-tag-name="{{ $tag->triggerTagName }}">
                                            <td class="text-dark">
                                                <i class="bx bx-tag text-primary me-1"></i>
                                                {{ $tag->triggerTagName }}
                                            </td>
                                            <td class="text-secondary trigger-description" title="{{ $tag->triggerTagDescription }}">
                                                {{ $tag->triggerTagDescription ?: '-' }}
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-tag-btn"
                                                        data-tag-id="{{ $tag->id }}"
                                                        data-tag-name="{{ $tag->triggerTagName }}"
                                                        title="Remove Tag">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5" id="noTagsMessage">
                            <i class="bx bx-tag display-1 text-muted"></i>
                            <h5 class="mt-3 text-dark">No Trigger Tags Found</h5>
                            <p class="text-secondary">No trigger tags have been configured for this variant yet.</p>
                            <p class="text-secondary">Use "Add Existing Tag" to select from available tags, or "Create New Tag" to create a new trigger tag.</p>
                        </div>
                    @endif
                @else
                    <div class="alert alert-warning">
                        <i class="bx bx-exclamation-triangle me-2"></i>
                        <strong>Warning!</strong> The requested variant could not be found.
                    </div>
                @endif
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
                    <i class="bx bx-trash text-danger me-2"></i>Confirm Remove
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to remove this trigger tag from the variant?</p>
                <p class="text-secondary mb-0"><strong>Tag:</strong> <span id="deleteTagName" class="text-dark"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteTag">
                    <i class="bx bx-trash me-1"></i>Remove Tag
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Existing Trigger Tag Modal -->
<div class="modal fade" id="addExistingTagModal" tabindex="-1" aria-labelledby="addExistingTagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addExistingTagModalLabel">
                    <i class="bx bx-list-check me-2"></i>Add Existing Trigger Tag
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Search Field -->
                <div class="mb-3">
                    <label for="searchExistingTag" class="form-label text-dark">Search Trigger Tags</label>
                    <input type="text" class="form-control" id="searchExistingTag" placeholder="Search by tag name or description...">
                </div>

                <!-- Loading Spinner -->
                <div id="existingTagsLoadingSpinner" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-secondary">Loading available tags...</p>
                </div>

                <!-- Available Tags Table -->
                <div class="table-responsive" id="existingTagsTableContainer" style="display: none;">
                    <table class="table table-bordered table-hover" id="existingTagsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-dark" width="50">
                                    <input type="checkbox" class="form-check-input" id="selectAllExistingTags">
                                </th>
                                <th class="text-dark">Tag Name</th>
                                <th class="text-dark">Description</th>
                            </tr>
                        </thead>
                        <tbody id="existingTagsTableBody">
                            <!-- Data will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>

                <div id="noExistingTagsFound" class="text-center py-4" style="display: none;">
                    <i class="bx bx-search display-4 text-secondary"></i>
                    <h5 class="mt-3 text-dark">No Available Tags</h5>
                    <p class="text-secondary">No available trigger tags found. Create a new one using the "Create New Tag" button.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveExistingTags">
                    <i class="bx bx-check me-1"></i>Add Selected Tags
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create New Trigger Tag Modal -->
<div class="modal fade" id="createNewTagModal" tabindex="-1" aria-labelledby="createNewTagModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="createNewTagModalLabel">
                    <i class="bx bx-plus-circle me-2"></i>Create New Trigger Tag
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createTagForm">
                    <div class="mb-3">
                        <label for="triggerTagName" class="form-label text-dark">Trigger Tag Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="triggerTagName" name="triggerTagName"
                               placeholder="Enter trigger tag name" required maxlength="255">
                        <div class="invalid-feedback" id="triggerTagNameError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="triggerTagDescription" class="form-label text-dark">Description</label>
                        <textarea class="form-control" id="triggerTagDescription" name="triggerTagDescription"
                                  rows="3" placeholder="Enter a description for this trigger tag (optional)"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="addToVariantAfterCreate" checked>
                        <label class="form-check-label text-dark" for="addToVariantAfterCreate">
                            Add this tag to the current variant after creation
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-success" id="createTriggerTagBtn">
                    <i class="bx bx-plus me-1"></i>Create Tag
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Toastr JS -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Toastr configuration
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

    const variantId = '{{ $variant->id ?? "" }}';

    // Show notification helper
    function showNotification(type, message) {
        if (typeof toastr !== 'undefined' && typeof toastr[type] === 'function') {
            toastr[type](message);
        } else {
            alert(message);
        }
    }

    // Escape HTML helper
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Load available existing tags when modal is shown
    $('#addExistingTagModal').on('shown.bs.modal', function() {
        loadExistingTags();
    });

    // Load existing tags from database
    function loadExistingTags() {
        $('#existingTagsLoadingSpinner').show();
        $('#existingTagsTableContainer').hide();
        $('#noExistingTagsFound').hide();

        $.ajax({
            url: '{{ route("ecom-products.variants.triggers.available-tags") }}',
            method: 'GET',
            data: { id: variantId },
            success: function(response) {
                if (response.success) {
                    displayExistingTags(response.tags);
                } else {
                    $('#existingTagsLoadingSpinner').hide();
                    $('#noExistingTagsFound').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#existingTagsLoadingSpinner').hide();
                $('#noExistingTagsFound').html(`
                    <i class="bx bx-error-circle display-4 text-danger"></i>
                    <h5 class="mt-3 text-danger">Error Loading Tags</h5>
                    <p class="text-secondary">Failed to load available tags. Please try again.</p>
                `).show();
            }
        });
    }

    // Display existing tags in table
    function displayExistingTags(tags) {
        const tbody = $('#existingTagsTableBody');
        tbody.empty();
        $('#existingTagsLoadingSpinner').hide();

        if (tags.length === 0) {
            $('#noExistingTagsFound').show();
            $('#existingTagsTableContainer').hide();
        } else {
            $('#noExistingTagsFound').hide();
            $('#existingTagsTableContainer').show();

            tags.forEach(function(tag) {
                const description = tag.triggerTagDescription || '-';
                const row = `
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input existing-tag-checkbox"
                                   value="${tag.id}" data-tag-name="${escapeHtml(tag.triggerTagName)}">
                        </td>
                        <td class="text-dark">
                            <i class="bx bx-tag text-primary me-1"></i>
                            ${escapeHtml(tag.triggerTagName)}
                        </td>
                        <td class="text-secondary trigger-description" title="${escapeHtml(description)}">
                            ${escapeHtml(description)}
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }
    }

    // Search functionality for existing tags
    $('#searchExistingTag').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();

        $('#existingTagsTableBody tr').each(function() {
            const tagName = $(this).find('td:eq(1)').text().toLowerCase();
            const description = $(this).find('td:eq(2)').text().toLowerCase();

            if (tagName.includes(searchTerm) || description.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Show/hide no results message
        const visibleRows = $('#existingTagsTableBody tr:visible').length;
        if (visibleRows === 0 && searchTerm) {
            $('#existingTagsTableContainer').hide();
            $('#noExistingTagsFound').html(`
                <i class="bx bx-search display-4 text-secondary"></i>
                <h5 class="mt-3 text-dark">No Matching Tags</h5>
                <p class="text-secondary">No tags found matching "${escapeHtml(searchTerm)}".</p>
            `).show();
        } else if ($('#existingTagsTableBody tr').length > 0) {
            $('#noExistingTagsFound').hide();
            $('#existingTagsTableContainer').show();
        }
    });

    // Select all existing tags functionality
    $('#selectAllExistingTags').change(function() {
        $('.existing-tag-checkbox:visible').prop('checked', $(this).is(':checked'));
    });

    // Save selected existing tags
    $('#saveExistingTags').click(function() {
        const selectedTags = [];
        $('.existing-tag-checkbox:checked').each(function() {
            selectedTags.push($(this).val());
        });

        if (selectedTags.length === 0) {
            showNotification('warning', 'Please select at least one tag to add.');
            return;
        }

        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: '{{ route("ecom-products.variants.triggers.save-tags") }}',
            method: 'POST',
            data: {
                variantId: variantId,
                tagIds: selectedTags,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.message);
                    $('#addExistingTagModal').modal('hide');

                    // Clear form and refresh
                    $('#searchExistingTag').val('');
                    $('.existing-tag-checkbox').prop('checked', false);
                    $('#selectAllExistingTags').prop('checked', false);

                    // Refresh the page to show updated tags
                    location.reload();
                } else {
                    showNotification('error', response.message || 'Failed to save tags.');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while saving the tags.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showNotification('error', errorMessage);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Create new trigger tag
    $('#createTriggerTagBtn').click(function() {
        const tagName = $('#triggerTagName').val().trim();
        const tagDescription = $('#triggerTagDescription').val().trim();
        const addToVariant = $('#addToVariantAfterCreate').is(':checked');

        // Validate
        if (!tagName) {
            $('#triggerTagName').addClass('is-invalid');
            $('#triggerTagNameError').text('Trigger tag name is required.');
            return;
        }

        $('#triggerTagName').removeClass('is-invalid');

        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Creating...');

        $.ajax({
            url: '{{ route("ecom-products.variants.triggers.create-tag") }}',
            method: 'POST',
            data: {
                triggerTagName: tagName,
                triggerTagDescription: tagDescription,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.message);

                    if (addToVariant && response.tag) {
                        // Add the newly created tag to this variant
                        $.ajax({
                            url: '{{ route("ecom-products.variants.triggers.save-tags") }}',
                            method: 'POST',
                            data: {
                                variantId: variantId,
                                tagIds: [response.tag.id],
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(saveResponse) {
                                if (saveResponse.success) {
                                    showNotification('success', 'Tag created and added to variant!');
                                }
                                $('#createNewTagModal').modal('hide');
                                location.reload();
                            },
                            error: function() {
                                $('#createNewTagModal').modal('hide');
                                showNotification('info', 'Tag created but could not be added to variant. Add it manually.');
                                location.reload();
                            }
                        });
                    } else {
                        $('#createNewTagModal').modal('hide');
                        // Clear form
                        $('#triggerTagName').val('');
                        $('#triggerTagDescription').val('');
                        showNotification('success', 'Trigger tag created! You can now add it from the existing tags list.');
                    }
                } else {
                    if (response.message && response.message.includes('already exists')) {
                        $('#triggerTagName').addClass('is-invalid');
                        $('#triggerTagNameError').text(response.message);
                    } else {
                        showNotification('error', response.message || 'Failed to create trigger tag.');
                    }
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while creating the trigger tag.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showNotification('error', errorMessage);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Clear validation on input
    $('#triggerTagName').on('input', function() {
        $(this).removeClass('is-invalid');
    });

    // Clear form when modal is hidden
    $('#createNewTagModal').on('hidden.bs.modal', function() {
        $('#triggerTagName').val('').removeClass('is-invalid');
        $('#triggerTagDescription').val('');
        $('#addToVariantAfterCreate').prop('checked', true);
    });

    // Handle delete tag button clicks
    $(document).on('click', '.delete-tag-btn', function() {
        const tagId = $(this).data('tag-id');
        const tagName = $(this).data('tag-name');

        $('#deleteTagName').text(tagName);
        $('#confirmDeleteTag').data('tag-id', tagId);
        $('#deleteTagModal').modal('show');
    });

    // Handle delete confirmation
    $('#confirmDeleteTag').click(function() {
        const tagId = $(this).data('tag-id');

        if (!tagId) {
            showNotification('error', 'Tag ID not found.');
            return;
        }

        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Removing...');

        $.ajax({
            url: '{{ route("ecom-products.variants.triggers.delete-tag", ["id" => ":id"]) }}'.replace(':id', tagId),
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.message);
                    $('#deleteTagModal').modal('hide');

                    // Remove the row from the table
                    $(`tr[data-tag-id="${tagId}"]`).fadeOut(300, function() {
                        $(this).remove();

                        // Check if table is empty
                        if ($('#variantTagsTableBody tr').length === 0) {
                            $('#mainTableContainer').hide();
                            $('#noTagsMessage').show();
                        }
                    });
                } else {
                    showNotification('error', response.message || 'Failed to remove tag.');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while removing the tag.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showNotification('error', errorMessage);
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endsection
