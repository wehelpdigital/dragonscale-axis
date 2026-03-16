@extends('layouts.master')

@section('title') Forms @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
.status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
.form-url {
    font-size: 0.8125rem;
    color: #74788d;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* =====================================================
   MOBILE RESPONSIVE STYLES WITH ANIMATIONS
   ===================================================== */

/* Smooth transitions */
.btn, .form-control, .table tbody tr {
    transition: all 0.3s ease;
}

/* Card entrance animation */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: slideInUp 0.3s ease forwards;
}

/* Small monitors (1280px - 1400px) */
@media (max-width: 1400px) {
    .table th, .table td {
        padding: 11px 10px;
        font-size: 13px;
    }

    .form-url {
        max-width: 180px;
    }

    .status-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.45rem;
    }
}

/* iPad landscape / 1024px monitors */
@media (max-width: 1024px) {
    .table th {
        font-size: 11.5px;
        padding: 10px 8px;
    }

    .table td {
        font-size: 12.5px;
        padding: 10px 8px;
    }

    .form-url {
        font-size: 0.75rem;
        max-width: 150px;
    }

    .status-badge {
        font-size: 0.65rem;
        padding: 0.18rem 0.4rem;
    }

    .btn-soft-primary,
    .btn-soft-info,
    .btn-soft-secondary,
    .btn-soft-danger {
        padding: 5px 8px;
    }

    .btn-soft-primary i,
    .btn-soft-info i,
    .btn-soft-secondary i,
    .btn-soft-danger i {
        font-size: 13px;
    }

    /* Hide Created column on 1024px */
    .table th:nth-child(6),
    .table td:nth-child(6) {
        display: none;
    }
}

/* Tablet Styles */
@media (max-width: 991px) {
    .table th, .table td {
        padding: 10px 8px;
        font-size: 13px;
    }

    .form-url {
        max-width: 150px;
    }
}

/* Mobile Styles */
@media (max-width: 767px) {
    /* Table */
    .table {
        font-size: 12px;
    }

    .table thead th {
        font-size: 11px;
        padding: 10px 6px;
    }

    /* Hide less important columns */
    .table th:nth-child(3),
    .table td:nth-child(3),
    .table th:nth-child(6),
    .table td:nth-child(6) {
        display: none;
    }

    .form-url {
        max-width: 100px;
    }

    /* Card header */
    .card-header {
        flex-wrap: wrap;
        gap: 10px;
    }

    .card-header .btn {
        flex: 1;
    }

    /* Modal */
    .modal-dialog {
        margin: 10px;
    }

    /* Breadcrumb */
    .page-title-box h4 {
        font-size: 16px;
    }

    /* Action buttons */
    .btn-sm {
        padding: 5px 8px;
        font-size: 12px;
    }
}

/* Small Mobile */
@media (max-width: 575px) {
    .card-body {
        padding: 12px;
    }

    /* Table as cards */
    .table thead {
        display: none;
    }

    .table tbody tr {
        display: block;
        margin-bottom: 12px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 12px;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border: none;
        border-bottom: 1px solid #f0f0f0;
    }

    .table tbody td:last-child {
        border-bottom: none;
        justify-content: flex-end;
        padding-top: 12px;
    }

    .table tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #495057;
        font-size: 11px;
    }

    /* Show hidden columns */
    .table th:nth-child(3),
    .table td:nth-child(3),
    .table th:nth-child(6),
    .table td:nth-child(6) {
        display: flex;
    }

    .form-url {
        max-width: 120px;
        font-size: 11px;
    }

    /* Touch-friendly */
    .btn {
        min-height: 44px;
    }

    .btn-sm {
        min-height: 36px;
    }
}

/* Touch device */
@media (hover: none) and (pointer: coarse) {
    .table tbody tr:active {
        background-color: #f8f9fa;
    }

    .btn:active {
        transform: scale(0.98);
    }
}

/* Loading animation */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.btn .bx-loader-alt {
    animation: spin 1s linear infinite;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') CRM @endslot
    @slot('title') Forms @endslot
@endcomponent

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><i class="bx bx-file me-2"></i>Forms</h4>
                <a href="{{ route('crm-forms.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Create Form
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="formsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Form Name</th>
                                <th>Public URL</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 100px;">Submissions</th>
                                <th style="width: 120px;">Created</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($forms as $form)
                            <tr>
                                <td class="text-dark">{{ $form->id }}</td>
                                <td>
                                    <a href="{{ route('crm-forms.edit', ['id' => $form->id]) }}" class="text-dark fw-medium">
                                        {{ $form->formName }}
                                    </a>
                                    @if($form->formDescription)
                                    <br><small class="text-secondary">{{ Str::limit($form->formDescription, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($form->formStatus === 'active')
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="form-url">{{ url('/f/' . $form->formSlug) }}</span>
                                        <button type="button" class="btn btn-soft-secondary btn-sm copy-url" data-url="{{ url('/f/' . $form->formSlug) }}" title="Copy URL">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                    </div>
                                    @else
                                    <span class="text-secondary">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($form->formStatus === 'active')
                                    <span class="badge status-badge bg-success">Active</span>
                                    @elseif($form->formStatus === 'draft')
                                    <span class="badge status-badge bg-warning text-dark">Draft</span>
                                    @else
                                    <span class="badge status-badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('crm-forms.submissions', ['id' => $form->id]) }}" class="text-dark">
                                        {{ $form->submissions_count ?? 0 }}
                                    </a>
                                </td>
                                <td>
                                    <span class="text-dark">{{ $form->created_at->format('M d, Y') }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('crm-forms.edit', ['id' => $form->id]) }}" class="btn btn-soft-primary btn-sm" title="Edit">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <a href="{{ route('crm-forms.submissions', ['id' => $form->id]) }}" class="btn btn-soft-info btn-sm" title="Submissions">
                                            <i class="bx bx-list-check"></i>
                                        </a>
                                        <button type="button" class="btn btn-soft-secondary btn-sm duplicate-form" data-id="{{ $form->id }}" title="Duplicate">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                        <button type="button" class="btn btn-soft-danger btn-sm delete-form" data-id="{{ $form->id }}" data-name="{{ $form->formName }}" title="Delete">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="empty-state-icon mx-auto mb-3" style="width: 60px; height: 60px; border-radius: 50%; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                        <i class="bx bx-file-blank" style="font-size: 1.75rem; color: #adb5bd;"></i>
                                    </div>
                                    <h6 class="text-dark mb-2">No forms yet</h6>
                                    <p class="text-secondary mb-3">Create your first form to start collecting submissions.</p>
                                    <a href="{{ route('crm-forms.create') }}" class="btn btn-primary">
                                        <i class="bx bx-plus me-1"></i>Create Your First Form
                                    </a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Delete Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-1">Are you sure you want to delete <strong id="deleteFormName"></strong>?</p>
                <p class="text-secondary small mb-0">This will also delete all submissions and cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(document).ready(function() {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Initialize DataTable
    @if($forms->count() > 0)
    $('#formsTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        columnDefs: [
            { orderable: false, targets: [6] }
        ],
        language: {
            emptyTable: "No forms found"
        }
    });
    @endif

    // Copy URL
    $('.copy-url').on('click', function() {
        const url = $(this).data('url');
        navigator.clipboard.writeText(url).then(function() {
            toastr.success('URL copied to clipboard!');
        });
    });

    // Duplicate form
    $('.duplicate-form').on('click', function() {
        const formId = $(this).data('id');
        $.ajax({
            url: '/crm-forms-duplicate?id=' + formId,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success('Form duplicated successfully!');
                    location.reload();
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to duplicate form');
            }
        });
    });

    // Delete form
    let formToDelete = null;

    $('.delete-form').on('click', function() {
        formToDelete = $(this).data('id');
        $('#deleteFormName').text($(this).data('name'));
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function() {
        if (!formToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: '/crm-forms-delete?id=' + formToDelete,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    toastr.success('Form deleted successfully!');
                    location.reload();
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete form');
            },
            complete: function() {
                $btn.prop('disabled', false).html('Delete');
                formToDelete = null;
            }
        });
    });
});
</script>
@endsection
