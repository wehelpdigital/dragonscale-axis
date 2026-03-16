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

    /* =====================================================
       MOBILE RESPONSIVE STYLES WITH ANIMATIONS
       ===================================================== */

    /* Smooth transitions */
    .btn, .flow-card {
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

    .flow-card, .card {
        animation: slideInUp 0.3s ease forwards;
    }

    /* Staggered animations */
    .flow-card:nth-child(1) { animation-delay: 0.05s; }
    .flow-card:nth-child(2) { animation-delay: 0.1s; }
    .flow-card:nth-child(3) { animation-delay: 0.15s; }
    .flow-card:nth-child(4) { animation-delay: 0.2s; }
    .flow-card:nth-child(5) { animation-delay: 0.25s; }

    /* Small monitors (1280px - 1400px) */
    @media (max-width: 1400px) {
        .flow-card .card-body {
            padding: 16px;
        }

        .flow-card h5 {
            font-size: 15px;
        }

        .priority-badge {
            width: 26px;
            height: 26px;
            font-size: 0.7rem;
        }

        .node-count {
            font-size: 12px;
        }
    }

    /* iPad landscape / 1024px monitors */
    @media (max-width: 1024px) {
        .flow-card .card-body {
            padding: 14px;
        }

        .flow-card h5 {
            font-size: 14px;
        }

        .flow-card p.text-secondary {
            font-size: 12.5px;
        }

        .priority-badge {
            width: 24px;
            height: 24px;
            font-size: 0.65rem;
            margin-right: 10px !important;
        }

        .node-count {
            font-size: 11px;
            padding: 3px 6px;
        }

        .flow-card .d-flex.align-items-center.gap-3 {
            gap: 10px !important;
        }

        .flow-card .text-secondary.small {
            font-size: 11px;
        }

        .badge {
            font-size: 10px;
            padding: 3px 6px;
        }

        .btn-group .btn {
            padding: 5px 8px;
        }

        .btn-group .btn i {
            font-size: 13px;
        }
    }

    /* Tablet Styles */
    @media (max-width: 991px) {
        .flow-card .card-body {
            padding: 15px;
        }

        .node-count {
            padding: 3px 6px;
            font-size: 12px;
        }
    }

    /* Mobile Styles */
    @media (max-width: 767px) {
        /* Header */
        .d-flex.justify-content-between.align-items-center {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 12px;
        }

        .d-flex.justify-content-between.align-items-center > div:first-child {
            width: 100%;
        }

        .d-flex.justify-content-between.align-items-center > .btn {
            width: 100%;
        }

        /* Flow cards */
        .flow-card .card-body {
            padding: 12px;
        }

        .flow-card .d-flex.justify-content-between.align-items-start {
            flex-direction: column;
        }

        .priority-badge {
            width: 24px;
            height: 24px;
            font-size: 11px;
        }

        .flow-card h5 {
            font-size: 15px;
        }

        .flow-card .d-flex.align-items-center.mb-2 {
            flex-wrap: wrap;
            gap: 4px;
        }

        .flow-card p.text-secondary {
            font-size: 13px;
        }

        .node-count {
            font-size: 11px;
            padding: 3px 6px;
        }

        .flow-card .d-flex.align-items-center.gap-3 {
            flex-wrap: wrap;
            gap: 8px !important;
        }

        .flow-card .text-secondary.small {
            font-size: 11px;
        }

        /* Actions always visible on mobile */
        .flow-actions {
            opacity: 1;
            margin-top: 12px;
            margin-left: 0 !important;
            width: 100%;
            display: flex;
            justify-content: flex-end;
        }

        .flow-actions .btn-group .btn {
            padding: 6px 10px;
        }

        /* Badges */
        .badge {
            font-size: 11px;
            padding: 3px 6px;
        }
    }

    /* Small Mobile */
    @media (max-width: 575px) {
        .flow-card .d-flex.align-items-start.flex-grow-1 {
            flex-direction: column;
        }

        .priority-badge {
            margin-bottom: 10px;
            margin-right: 0 !important;
        }

        /* Empty state */
        .empty-state {
            padding: 50px 15px;
        }

        .empty-state-icon {
            width: 60px;
            height: 60px;
        }

        .empty-state-icon i {
            font-size: 2rem;
        }

        .empty-state h5 {
            font-size: 16px;
        }

        .empty-state p {
            font-size: 13px;
        }

        /* Modal */
        .modal-footer .btn {
            flex: 1;
        }
    }

    /* Touch device */
    @media (hover: none) and (pointer: coarse) {
        .flow-card:active {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        .btn:active {
            transform: scale(0.98);
        }

        /* Make actions always visible on touch */
        .flow-actions {
            opacity: 1;
        }
    }

    /* Loading animation */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .bx-spin, .bx-loader-alt {
        animation: spin 1s linear infinite;
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
