@extends('layouts.master')

@section('title') Trigger Flows @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr CSS -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .flow-status-badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
    }
    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
    }
    .node-count-badge {
        background-color: #f3f6f9;
        color: #495057;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('title') Trigger Flows @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Trigger Flows</h4>
                        <p class="text-secondary mb-0">Create automation flows that trigger actions based on purchase events.</p>
                    </div>
                    <a href="{{ route('ecom-triggers.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>Create New Flow
                    </a>
                </div>

                @if($flows->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped dt-responsive nowrap w-100" id="flows-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-dark">Flow Name</th>
                                    <th class="text-dark">Type</th>
                                    <th class="text-dark">Trigger</th>
                                    <th class="text-dark">Description</th>
                                    <th class="text-dark text-center">Nodes</th>
                                    <th class="text-dark text-center">Status</th>
                                    <th class="text-dark">Created</th>
                                    <th class="text-dark" width="200">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($flows as $flow)
                                    <tr data-flow-id="{{ $flow->id }}">
                                        <td class="text-dark">
                                            <i class="bx bx-git-branch text-primary me-1"></i>
                                            <strong>{{ $flow->flowName }}</strong>
                                        </td>
                                        <td>
                                            @php $flowType = $flow->flowType ?? 'trigger'; @endphp
                                            @if($flowType === 'trigger')
                                                <span class="badge bg-success text-white">
                                                    <i class="bx bx-play-circle me-1"></i>Trigger
                                                </span>
                                            @elseif($flowType === 'expiration')
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bx bx-time-five me-1"></i>Expiration
                                                </span>
                                            @elseif($flowType === 'order_not_complete')
                                                <span class="badge bg-danger text-white">
                                                    <i class="bx bx-error-circle me-1"></i>Order Not Complete
                                                </span>
                                            @elseif($flowType === 'shipping_complete')
                                                <span class="badge bg-info text-white">
                                                    <i class="bx bx-package me-1"></i>Shipping Complete
                                                </span>
                                            @elseif($flowType === 'affiliate_earning')
                                                <span class="badge bg-primary text-white">
                                                    <i class="bx bx-dollar-circle me-1"></i>Affiliate Earning
                                                </span>
                                            @else
                                                <span class="badge bg-secondary text-white">{{ $flowType }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($flow->triggerTag)
                                                <span class="badge bg-info text-white">
                                                    <i class="bx bx-tag me-1"></i>{{ $flow->triggerTag->triggerTagName }}
                                                </span>
                                            @else
                                                <span class="text-secondary">-</span>
                                            @endif
                                        </td>
                                        <td class="text-secondary" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $flow->flowDescription }}">
                                            {{ $flow->flowDescription ?: '-' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="node-count-badge">
                                                <i class="bx bx-cube me-1"></i>{{ $flow->node_count }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($flow->isActive)
                                                <span class="badge bg-success flow-status-badge">
                                                    <i class="bx bx-check-circle me-1"></i>Active
                                                </span>
                                            @else
                                                <span class="badge bg-secondary flow-status-badge">
                                                    <i class="bx bx-stop-circle me-1"></i>Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-secondary">
                                            <small>{{ $flow->created_at->format('M j, Y') }}</small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="{{ route('ecom-triggers.edit', ['id' => $flow->id]) }}"
                                                   class="btn btn-sm btn-outline-primary" title="Edit Flow">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-{{ $flow->isActive ? 'warning' : 'success' }} toggle-status-btn"
                                                        data-flow-id="{{ $flow->id }}"
                                                        data-is-active="{{ $flow->isActive ? '1' : '0' }}"
                                                        title="{{ $flow->isActive ? 'Deactivate' : 'Activate' }}">
                                                    <i class="bx bx-{{ $flow->isActive ? 'stop' : 'play' }}"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info duplicate-btn"
                                                        data-flow-id="{{ $flow->id }}"
                                                        data-flow-name="{{ $flow->flowName }}"
                                                        title="Duplicate Flow">
                                                    <i class="bx bx-copy"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-btn"
                                                        data-flow-id="{{ $flow->id }}"
                                                        data-flow-name="{{ $flow->flowName }}"
                                                        title="Delete Flow">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-git-branch display-1 text-muted"></i>
                        <h5 class="mt-3 text-dark">No Trigger Flows Yet</h5>
                        <p class="text-secondary">Create your first automation flow to trigger actions based on purchase events.</p>
                        <a href="{{ route('ecom-triggers.create') }}" class="btn btn-primary mt-2">
                            <i class="bx bx-plus me-1"></i>Create Your First Flow
                        </a>
                    </div>
                @endif
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
                    <i class="bx bx-trash me-2"></i>Delete Flow
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete this trigger flow?</p>
                <p class="text-secondary mb-0"><strong>Flow:</strong> <span id="deleteFlowName" class="text-dark"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="bx bx-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- DataTables -->
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Initialize DataTable
    $('#flows-table').DataTable({
        order: [[6, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            emptyTable: "No trigger flows found",
            zeroRecords: "No matching flows found"
        }
    });

    let flowToDelete = null;

    // Toggle status
    $(document).on('click', '.toggle-status-btn', function() {
        const flowId = $(this).data('flow-id');
        const isActive = $(this).data('is-active') === 1;
        const $btn = $(this);

        $.ajax({
            url: `/ecom-triggers-toggle-status?id=${flowId}`,
            type: 'PUT',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to update status.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred.');
            }
        });
    });

    // Duplicate flow
    $(document).on('click', '.duplicate-btn', function() {
        const flowId = $(this).data('flow-id');
        const flowName = $(this).data('flow-name');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: `/ecom-triggers-duplicate?id=${flowId}`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to duplicate flow.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-copy"></i>');
            }
        });
    });

    // Delete flow - open modal
    $(document).on('click', '.delete-btn', function() {
        flowToDelete = {
            id: $(this).data('flow-id'),
            name: $(this).data('flow-name')
        };
        $('#deleteFlowName').text(flowToDelete.name);
        $('#deleteModal').modal('show');
    });

    // Confirm delete
    $('#confirmDelete').on('click', function() {
        if (!flowToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: `/ecom-triggers-delete?id=${flowToDelete.id}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#deleteModal').modal('hide');
                    $(`tr[data-flow-id="${flowToDelete.id}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    toastr.error(response.message || 'Failed to delete flow.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
                flowToDelete = null;
            }
        });
    });
});
</script>
@endsection
