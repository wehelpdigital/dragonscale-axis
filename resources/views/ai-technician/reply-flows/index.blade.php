@extends('layouts.master')

@section('title') AI Reply Flows @endsection

@section('css')
<link rel="stylesheet" href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}">
<style>
    .flow-card {
        border-left: 4px solid #556ee6;
        transition: all 0.2s ease;
    }
    .flow-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
    .flow-card.inactive {
        border-left-color: #74788d;
        opacity: 0.7;
    }
    .flow-card.default {
        border-left-color: #34c38f;
    }
    .flow-actions {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .flow-card:hover .flow-actions {
        opacity: 1;
    }
    .node-count {
        background-color: #f8f9fa;
        border-radius: 4px;
        padding: 4px 8px;
        font-size: 0.8rem;
    }
    .priority-badge {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #556ee6;
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .empty-state {
        padding: 80px 20px;
        text-align: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        border-radius: 8px;
    }
    .empty-state-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #e8f0fe;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
    }
    .empty-state-icon i {
        font-size: 2.5rem;
        color: #556ee6;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AI Technician @endslot
        @slot('title') Reply Flows @endslot
    @endcomponent

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1 text-dark">AI Reply Flows</h4>
                    <p class="text-secondary mb-0">Create visual flows to control how the AI generates responses to chat queries.</p>
                </div>
                <a href="{{ route('ai-technician.reply-flows.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> Create Flow
                </a>
            </div>
        </div>
    </div>

    <!-- Flows List -->
    <div class="row">
        <div class="col-12">
            @if($flows->isEmpty())
                <div class="card">
                    <div class="card-body empty-state">
                        <div class="empty-state-icon">
                            <i class="bx bx-git-branch"></i>
                        </div>
                        <h5 class="text-dark mb-2">No Reply Flows Created</h5>
                        <p class="text-secondary mb-4">Create your first reply flow to control how the AI processes and responds to chat queries.</p>
                        <a href="{{ route('ai-technician.reply-flows.create') }}" class="btn btn-primary btn-lg">
                            <i class="bx bx-plus me-1"></i> Create First Flow
                        </a>
                    </div>
                </div>
            @else
                <div id="flowsList">
                    @foreach($flows as $flow)
                        <div class="card flow-card mb-3 {{ !$flow->isActive ? 'inactive' : '' }} {{ $flow->isDefault ? 'default' : '' }}" data-flow-id="{{ $flow->id }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-start flex-grow-1">
                                        <div class="priority-badge me-3" title="Priority: {{ $flow->priority }}">
                                            {{ $flow->priority }}
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <h5 class="mb-0 me-2 text-dark">{{ $flow->flowName }}</h5>
                                                {!! $flow->status_badge !!}
                                                @if($flow->isDefault)
                                                    <span class="badge bg-success text-white ms-1">Default</span>
                                                @endif
                                            </div>
                                            @if($flow->flowDescription)
                                                <p class="text-secondary mb-2">{{ $flow->flowDescription }}</p>
                                            @endif
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="node-count text-dark">
                                                    <i class="bx bx-cube me-1"></i>{{ $flow->node_count }} nodes
                                                </span>
                                                <span class="text-secondary small">
                                                    <i class="bx bx-time me-1"></i>Updated {{ $flow->updated_at->diffForHumans() }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flow-actions ms-3">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary toggle-flow"
                                                    data-flow-id="{{ $flow->id }}"
                                                    title="{{ $flow->isActive ? 'Disable' : 'Enable' }}">
                                                <i class="bx {{ $flow->isActive ? 'bx-pause' : 'bx-play' }}"></i>
                                            </button>
                                            <a href="{{ route('ai-technician.reply-flows.edit', ['id' => $flow->id]) }}"
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-info duplicate-flow"
                                                    data-flow-id="{{ $flow->id }}" title="Duplicate">
                                                <i class="bx bx-copy"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-flow"
                                                    data-flow-id="{{ $flow->id }}"
                                                    data-flow-name="{{ $flow->flowName }}" title="Delete">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Delete Flow</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to delete the flow "<strong id="deleteFlowName"></strong>"?</p>
                    <p class="text-secondary small mb-0">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                        <i class="bx bx-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Toastr configuration
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 3000
        };
    }

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    let flowToDelete = null;

    // Toggle flow status
    $(document).on('click', '.toggle-flow', function() {
        const flowId = $(this).data('flow-id');
        const btn = $(this);

        $.ajax({
            url: `/ai-technician-reply-flows/${flowId}/toggle`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    const card = btn.closest('.flow-card');
                    const icon = btn.find('i');
                    const statusBadge = card.find('.badge.bg-success, .badge.bg-secondary').first();

                    if (response.data.isActive) {
                        card.removeClass('inactive');
                        icon.removeClass('bx-play').addClass('bx-pause');
                        btn.attr('title', 'Disable');
                        statusBadge.removeClass('bg-secondary').addClass('bg-success').text('Active');
                    } else {
                        card.addClass('inactive');
                        icon.removeClass('bx-pause').addClass('bx-play');
                        btn.attr('title', 'Enable');
                        statusBadge.removeClass('bg-success').addClass('bg-secondary').text('Inactive');
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to toggle flow status.');
            }
        });
    });

    // Duplicate flow
    $(document).on('click', '.duplicate-flow', function() {
        const flowId = $(this).data('flow-id');
        const btn = $(this);

        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: `/ai-technician-reply-flows/${flowId}/duplicate`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to duplicate flow.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bx bx-copy"></i>');
            }
        });
    });

    // Open delete confirmation
    $(document).on('click', '.delete-flow', function() {
        flowToDelete = {
            id: $(this).data('flow-id'),
            name: $(this).data('flow-name'),
            card: $(this).closest('.flow-card')
        };
        $('#deleteFlowName').text(flowToDelete.name);
        deleteModal.show();
    });

    // Confirm delete
    $('#btnConfirmDelete').on('click', function() {
        if (!flowToDelete) return;

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

        $.ajax({
            url: `/ai-technician-reply-flows/${flowToDelete.id}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    deleteModal.hide();
                    toastr.success(response.message);
                    flowToDelete.card.fadeOut(400, function() {
                        $(this).remove();
                        if ($('#flowsList .flow-card').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete flow.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
                flowToDelete = null;
            }
        });
    });
});
</script>
@endsection
