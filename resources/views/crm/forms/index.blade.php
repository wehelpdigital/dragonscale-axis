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
