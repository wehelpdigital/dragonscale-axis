@extends('layouts.master')

@section('title') Special Tags - {{ $store->storeName }} @endsection

@section('css')
<!-- Toastr CSS -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .tags-table {
        width: 100%;
    }
    .tags-table th {
        background: #f8f9fa;
        font-weight: 600;
        padding: 0.75rem 1rem;
        border-bottom: 2px solid #dee2e6;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #495057;
    }
    .tags-table td {
        padding: 0.75rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
    }
    .tags-table tbody tr:hover {
        background: #f8f9fa;
    }
    .tags-table tbody tr.inactive {
        opacity: 0.6;
        background: #fafafa;
    }
    .tag-value {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        background: #e9ecef;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.85rem;
        display: inline-block;
    }
    .tag-name {
        font-weight: 600;
        color: #212529;
    }
    .tag-description {
        font-size: 0.85rem;
        color: #6c757d;
        max-width: 300px;
    }
    .tag-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: nowrap;
    }
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }
    .empty-state i {
        font-size: 4rem;
        opacity: 0.3;
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') <a href="{{ route('ecom-stores') }}">Stores</a> @endslot
@slot('title') Special Tags @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">
                            <i class="bx bx-purchase-tag-alt text-primary me-2"></i>Special Tags
                        </h4>
                        <p class="text-secondary mb-0">
                            Manage special tags for <strong>{{ $store->storeName }}</strong>
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('ecom-stores') }}" class="btn btn-outline-secondary me-2">
                            <i class="bx bx-arrow-back me-1"></i>Back to Stores
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tagModal" onclick="openCreateModal()">
                            <i class="bx bx-plus me-1"></i>Add Tag
                        </button>
                    </div>
                </div>

                <!-- Tags List -->
                <div class="table-responsive" id="tagsContainer">
                    @if($tags->count() > 0)
                        <table class="tags-table">
                            <thead>
                                <tr>
                                    <th>Tag Name</th>
                                    <th>Tag Value</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th style="width: 140px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tags as $tag)
                                    <tr id="tag-{{ $tag->id }}" class="{{ !$tag->isActive ? 'inactive' : '' }}">
                                        <td>
                                            <span class="tag-name">{{ $tag->tagName }}</span>
                                        </td>
                                        <td>
                                            <code class="tag-value">{{ $tag->tagValue }}</code>
                                        </td>
                                        <td>
                                            @if($tag->tagDescription)
                                                <span class="tag-description">{{ $tag->tagDescription }}</span>
                                            @else
                                                <span class="text-muted fst-italic">No description</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($tag->isActive)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="tag-actions">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openEditModal({{ $tag->id }})" title="Edit">
                                                    <i class="bx bx-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-{{ $tag->isActive ? 'warning' : 'success' }}" onclick="toggleStatus({{ $tag->id }})" title="{{ $tag->isActive ? 'Deactivate' : 'Activate' }}">
                                                    <i class="bx bx-{{ $tag->isActive ? 'pause' : 'play' }}"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete({{ $tag->id }}, '{{ addslashes($tag->tagName) }}')" title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <i class="bx bx-purchase-tag-alt"></i>
                            <h5 class="mt-3 text-dark">No Special Tags Yet</h5>
                            <p class="text-secondary">Create your first special tag for this store.</p>
                            <button type="button" class="btn btn-primary mt-2 d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#tagModal" onclick="openCreateModal()">
                                <i class="bx bx-plus me-1"></i><span>Add First Tag</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Tag Modal -->
<div class="modal fade" id="tagModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tagModalTitle">
                    <i class="bx bx-purchase-tag-alt text-primary me-2"></i>Add Special Tag
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tagId" value="">
                <input type="hidden" id="storeId" value="{{ $store->id }}">

                <div class="mb-3">
                    <label for="tagName" class="form-label text-dark">Tag Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="tagName" placeholder="e.g., VIP Customer, Wholesale">
                    <small class="text-secondary">A friendly name for the tag</small>
                </div>

                <div class="mb-3">
                    <label for="tagValue" class="form-label text-dark">Tag Value <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="tagValue" placeholder="e.g., vip_customer, wholesale">
                    <small class="text-secondary">A unique identifier for the tag (used in system)</small>
                </div>

                <div class="mb-3">
                    <label for="tagDescription" class="form-label text-dark">Description</label>
                    <textarea class="form-control" id="tagDescription" rows="3" placeholder="Optional description for this tag..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveTagBtn" onclick="saveTag()">
                    <i class="bx bx-save me-1"></i>Save Tag
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bx bx-trash me-2"></i>Delete Tag
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete this tag?</p>
                <p class="text-secondary mb-0"><strong>Tag:</strong> <span id="deleteTagName" class="text-dark"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bx bx-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
// Toastr configuration
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000
};

let tagToDelete = null;

function openCreateModal() {
    $('#tagModalTitle').html('<i class="bx bx-purchase-tag-alt text-primary me-2"></i>Add Special Tag');
    $('#tagId').val('');
    $('#tagName').val('');
    $('#tagValue').val('');
    $('#tagDescription').val('');
    $('#saveTagBtn').html('<i class="bx bx-save me-1"></i>Save Tag');
}

function openEditModal(id) {
    $('#tagModalTitle').html('<i class="bx bx-edit text-primary me-2"></i>Edit Special Tag');
    $('#saveTagBtn').html('<i class="bx bx-loader-alt bx-spin me-1"></i>Loading...');
    $('#saveTagBtn').prop('disabled', true);

    $.ajax({
        url: '/ecom-store-special-tags/' + id,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#tagId').val(response.tag.id);
                $('#tagName').val(response.tag.tagName);
                $('#tagValue').val(response.tag.tagValue);
                $('#tagDescription').val(response.tag.tagDescription || '');
                $('#tagModal').modal('show');
            } else {
                toastr.error('Failed to load tag data.');
            }
        },
        error: function() {
            toastr.error('An error occurred while loading the tag.');
        },
        complete: function() {
            $('#saveTagBtn').html('<i class="bx bx-save me-1"></i>Save Tag');
            $('#saveTagBtn').prop('disabled', false);
        }
    });
}

function saveTag() {
    const tagId = $('#tagId').val();
    const isEdit = tagId !== '';

    const data = {
        storeId: $('#storeId').val(),
        tagName: $('#tagName').val().trim(),
        tagValue: $('#tagValue').val().trim(),
        tagDescription: $('#tagDescription').val().trim(),
        _token: '{{ csrf_token() }}'
    };

    if (!data.tagName) {
        toastr.error('Tag name is required.');
        $('#tagName').focus();
        return;
    }

    if (!data.tagValue) {
        toastr.error('Tag value is required.');
        $('#tagValue').focus();
        return;
    }

    const $btn = $('#saveTagBtn');
    const originalText = $btn.html();
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

    const url = isEdit ? '/ecom-store-special-tags/' + tagId : '/ecom-store-special-tags';
    const method = isEdit ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        type: method,
        data: data,
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#tagModal').modal('hide');
                // Reload to show updated data
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to save tag.');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            toastr.error(response.message || 'An error occurred while saving.');
        },
        complete: function() {
            $btn.prop('disabled', false).html(originalText);
        }
    });
}

function toggleStatus(id) {
    $.ajax({
        url: '/ecom-store-special-tags/' + id + '/toggle-status',
        type: 'PATCH',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to update status.');
            }
        },
        error: function() {
            toastr.error('An error occurred while updating the status.');
        }
    });
}

function confirmDelete(id, name) {
    tagToDelete = id;
    $('#deleteTagName').text(name);
    $('#deleteModal').modal('show');
}

$('#confirmDeleteBtn').on('click', function() {
    if (!tagToDelete) return;

    const $btn = $(this);
    const originalText = $btn.html();
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

    $.ajax({
        url: '/ecom-store-special-tags/' + tagToDelete,
        type: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#deleteModal').modal('hide');
                $('#tag-' + tagToDelete).fadeOut(300, function() {
                    $(this).remove();
                    // Check if no tags left
                    if ($('#tagsContainer tbody tr').length === 0) {
                        $('#tagsContainer').html(`
                            <div class="empty-state">
                                <i class="bx bx-purchase-tag-alt"></i>
                                <h5 class="mt-3 text-dark">No Special Tags Yet</h5>
                                <p class="text-secondary">Create your first special tag for this store.</p>
                                <button type="button" class="btn btn-primary mt-2 d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#tagModal" onclick="openCreateModal()">
                                    <i class="bx bx-plus me-1"></i><span>Add First Tag</span>
                                </button>
                            </div>
                        `);
                    }
                });
            } else {
                toastr.error(response.message || 'Failed to delete tag.');
            }
        },
        error: function() {
            toastr.error('An error occurred while deleting the tag.');
        },
        complete: function() {
            $btn.prop('disabled', false).html(originalText);
            tagToDelete = null;
        }
    });
});

$('#deleteModal').on('hidden.bs.modal', function() {
    tagToDelete = null;
});
</script>
@endsection
