@extends('layouts.master')

@section('title') Form Submissions @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
.submission-status {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
.submission-data-table td {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #e9ecef;
}
.submission-data-table td:first-child {
    font-weight: 500;
    color: #495057;
    width: 40%;
}
.submission-data-table td:last-child {
    color: #74788d;
}
.empty-submissions {
    padding: 4rem 2rem;
    text-align: center;
}
.empty-submissions i {
    font-size: 4rem;
    color: #ced4da;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') CRM @endslot
    @slot('li_2') <a href="{{ route('crm-forms') }}">Forms</a> @endslot
    @slot('title') Submissions: {{ $form->formName }} @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('crm-forms') }}" class="btn btn-soft-secondary">
                        <i class="bx bx-arrow-back"></i>
                    </a>
                    <div>
                        <h4 class="card-title mb-1">
                            <i class="bx bx-list-check me-2"></i>{{ $form->formName }}
                        </h4>
                        <p class="text-secondary mb-0 small">{{ $submissions->count() }} submission(s)</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('crm-forms.edit', ['id' => $form->id]) }}" class="btn btn-soft-primary">
                        <i class="bx bx-edit me-1"></i>Edit Form
                    </a>
                    @if($submissions->count() > 0)
                    <a href="{{ route('crm-forms.export', ['id' => $form->id]) }}" class="btn btn-soft-success">
                        <i class="bx bx-download me-1"></i>Export CSV
                    </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($submissions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="submissionsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Submitter</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissions as $submission)
                            <tr>
                                <td class="text-dark">{{ $submission->id }}</td>
                                <td>
                                    <div>
                                        @if($submission->submitterName)
                                        <span class="text-dark fw-medium">{{ $submission->submitterName }}</span><br>
                                        @endif
                                        @if($submission->submitterEmail)
                                        <small class="text-secondary">{{ $submission->submitterEmail }}</small>
                                        @else
                                        <span class="text-secondary">Anonymous</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge submission-status {{ $submission->getStatusBadgeClass() }}">
                                        {{ ucfirst($submission->submissionStatus) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-dark">{{ $submission->created_at->format('M d, Y') }}</span><br>
                                    <small class="text-secondary">{{ $submission->created_at->format('h:i A') }}</small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-soft-info btn-sm view-submission" data-id="{{ $submission->id }}">
                                        <i class="bx bx-show"></i>
                                    </button>
                                    <button type="button" class="btn btn-soft-danger btn-sm delete-submission" data-id="{{ $submission->id }}">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="empty-submissions">
                    <i class="bx bx-inbox d-block mb-3"></i>
                    <h5 class="text-dark">No submissions yet</h5>
                    <p class="text-secondary mb-4">Share your form to start collecting responses.</p>
                    @if($form->formStatus === 'active')
                    <div class="input-group mx-auto" style="max-width: 500px;">
                        <input type="text" class="form-control" value="{{ $form->publicUrl }}" readonly id="formUrl">
                        <button class="btn btn-primary" type="button" onclick="copyUrl()">
                            <i class="bx bx-copy me-1"></i>Copy Link
                        </button>
                    </div>
                    @else
                    <p class="text-warning"><i class="bx bx-info-circle me-1"></i>Activate your form to start collecting submissions.</p>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- View Submission Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-file me-2"></i>Submission Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="submissionContent">
                <div class="text-center py-4">
                    <i class="bx bx-loader-alt bx-spin" style="font-size: 2rem;"></i>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Delete Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete this submission?</p>
                <p class="text-secondary small mb-0">This action cannot be undone.</p>
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

    const formId = {{ $form->id }};

    // Initialize DataTable
    @if($submissions->count() > 0)
    $('#submissionsTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: "No submissions found"
        }
    });
    @endif

    // View submission
    $('.view-submission').on('click', function() {
        const submissionId = $(this).data('id');
        $('#submissionContent').html('<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin" style="font-size: 2rem;"></i></div>');
        $('#viewModal').modal('show');

        $.ajax({
            url: '/crm-forms-submission?formId=' + formId + '&submissionId=' + submissionId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    renderSubmissionDetails(response.data);
                }
            },
            error: function(xhr) {
                $('#submissionContent').html('<div class="alert alert-danger">Failed to load submission</div>');
            }
        });
    });

    // Render submission details
    function renderSubmissionDetails(data) {
        const submission = data.submission;
        const formElements = data.formElements || [];
        const submissionData = submission.submissionData || {};

        let html = `
            <div class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong class="text-dark">Submission ID:</strong> <span class="text-secondary">#${submission.id}</span></p>
                        <p class="mb-1"><strong class="text-dark">Status:</strong> <span class="badge ${getStatusClass(submission.submissionStatus)}">${submission.submissionStatus}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong class="text-dark">Submitted:</strong> <span class="text-secondary">${formatDate(submission.created_at)}</span></p>
                        <p class="mb-1"><strong class="text-dark">IP Address:</strong> <span class="text-secondary">${submission.submitterIp || 'N/A'}</span></p>
                    </div>
                </div>
            </div>
            <hr>
            <h6 class="text-dark mb-3">Submitted Data</h6>
            <table class="table submission-data-table mb-0">
        `;

        // Map field IDs to labels
        const fieldLabels = {};
        formElements.forEach(el => {
            if (el.id && el.label) {
                fieldLabels[el.id] = el.label;
            }
        });

        // Render each field
        for (const [key, value] of Object.entries(submissionData)) {
            const label = fieldLabels[key] || key;
            let displayValue = value;

            if (Array.isArray(value)) {
                displayValue = value.join(', ');
            } else if (value === null || value === '') {
                displayValue = '<span class="text-secondary fst-italic">Empty</span>';
            } else {
                displayValue = escapeHtml(String(value));
            }

            html += `
                <tr>
                    <td>${escapeHtml(label)}</td>
                    <td>${displayValue}</td>
                </tr>
            `;
        }

        html += '</table>';
        $('#submissionContent').html(html);
    }

    // Delete submission
    let submissionToDelete = null;

    $('.delete-submission').on('click', function() {
        submissionToDelete = $(this).data('id');
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function() {
        if (!submissionToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: '/crm-forms-submission-delete?formId=' + formId + '&submissionId=' + submissionToDelete,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    toastr.success('Submission deleted');
                    location.reload();
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete');
            },
            complete: function() {
                $btn.prop('disabled', false).html('Delete');
                submissionToDelete = null;
            }
        });
    });

    // Copy URL
    window.copyUrl = function() {
        const input = document.getElementById('formUrl');
        input.select();
        document.execCommand('copy');
        toastr.success('Link copied to clipboard!');
    };

    // Helper functions
    function getStatusClass(status) {
        const classes = {
            'new': 'bg-primary',
            'read': 'bg-info',
            'processed': 'bg-success',
            'archived': 'bg-secondary'
        };
        return classes[status] || 'bg-secondary';
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endsection
