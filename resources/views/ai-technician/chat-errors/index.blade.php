@extends('layouts.master')

@section('title') Chat Errors @endsection

@section('css')
<link rel="stylesheet" href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}">
<style>
    .error-card {
        border-left: 4px solid #f46a6a;
        transition: all 0.2s ease;
    }
    .error-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
    .error-card.status-fixed {
        border-left-color: #34c38f;
    }
    .error-actions {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .error-card:hover .error-actions {
        opacity: 1;
    }
    .stats-card {
        border-radius: 8px;
        padding: 16px 20px;
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
        background-color: #fff5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
    }
    .empty-state-icon i {
        font-size: 2.5rem;
        color: #f46a6a;
    }
    .chat-preview {
        background-color: #f8f9fa;
        border-radius: 6px;
        padding: 10px 14px;
        font-size: 0.9rem;
        color: #495057;
    }
    .chat-thread-modal .message-item {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    .chat-thread-modal .message-item.user {
        background-color: #e3f2fd;
        margin-left: 30px;
    }
    .chat-thread-modal .message-item.assistant {
        background-color: #f5f5f5;
        margin-right: 30px;
    }
    .chat-thread-modal .message-role {
        font-weight: 600;
        font-size: 0.8rem;
        margin-bottom: 4px;
        text-transform: uppercase;
    }
    .chat-thread-modal .message-role.user {
        color: #1976d2;
    }
    .chat-thread-modal .message-role.assistant {
        color: #388e3c;
    }
    .chat-thread-modal .message-content {
        font-size: 0.95rem;
        color: #212529;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .flow-logs-preview {
        background-color: #1e1e1e;
        color: #d4d4d4;
        border-radius: 8px;
        padding: 16px;
        font-family: 'Courier New', monospace;
        font-size: 0.8rem;
        max-height: 400px;
        overflow-y: auto;
        white-space: pre-wrap;
    }
    .date-badge {
        font-size: 0.85rem;
        color: #6c757d;
    }
    .filter-card {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 16px;
    }

    /* =====================================================
       MOBILE RESPONSIVE STYLES WITH ANIMATIONS
       ===================================================== */

    /* Smooth transitions */
    .btn, .form-control, .form-select, .error-card, .stats-card {
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

    .stats-card, .card {
        animation: slideInUp 0.3s ease forwards;
    }

    /* Staggered animations */
    .row.mb-4 > div:nth-child(1) .stats-card { animation-delay: 0.05s; }
    .row.mb-4 > div:nth-child(2) .stats-card { animation-delay: 0.1s; }
    .row.mb-4 > div:nth-child(3) .stats-card { animation-delay: 0.15s; }

    /* Small monitors (1280px - 1400px) */
    @media (max-width: 1400px) {
        .stats-card {
            padding: 14px 18px;
        }

        .stats-card h3 {
            font-size: 1.4rem;
        }

        .chat-preview {
            font-size: 12px;
            padding: 8px 10px;
        }

        .date-badge {
            font-size: 0.8rem;
        }
    }

    /* iPad landscape / 1024px monitors */
    @media (max-width: 1024px) {
        .stats-card {
            padding: 12px 14px;
        }

        .stats-card h3 {
            font-size: 1.25rem;
        }

        .stats-card p {
            font-size: 12px;
        }

        .stats-card i {
            font-size: 1.6rem !important;
        }

        .filter-card {
            padding: 12px;
        }

        .filter-card .form-label {
            font-size: 12px;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            font-size: 13px;
            padding: 7px 10px;
        }

        .table thead th {
            font-size: 11.5px;
            padding: 10px 8px;
        }

        .table tbody td {
            font-size: 12.5px;
            padding: 10px 8px;
        }

        .chat-preview {
            font-size: 11px;
            padding: 6px 8px;
            max-width: 180px;
        }

        .date-badge {
            font-size: 0.75rem;
        }

        .btn-soft-info,
        .btn-soft-primary,
        .btn-soft-danger {
            padding: 5px 8px;
        }

        /* Stats row - keep 3 columns but smaller */
        .row.mb-4 > .col-md-4 {
            padding-left: 8px;
            padding-right: 8px;
        }
    }

    /* Tablet Styles */
    @media (max-width: 991px) {
        .stats-card {
            padding: 12px 16px;
        }

        .stats-card h3 {
            font-size: 1.5rem;
        }

        .filter-card .row > div {
            margin-bottom: 12px;
        }
    }

    /* Mobile Styles */
    @media (max-width: 767px) {
        /* Stats row */
        .row.mb-4 > .col-md-4 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 10px;
        }

        .stats-card {
            padding: 12px 14px;
        }

        .stats-card h3 {
            font-size: 1.25rem;
        }

        .stats-card i {
            font-size: 1.5rem !important;
        }

        /* Filter card */
        .filter-card {
            padding: 12px;
        }

        .filter-card .row > div {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 10px;
        }

        .filter-card .row > div:last-child {
            display: flex;
            gap: 10px;
        }

        .filter-card .row > div:last-child .btn {
            flex: 1;
        }

        /* Table */
        .table thead {
            display: none;
        }

        .table tbody tr {
            display: block;
            border: 1px solid #dee2e6;
            border-left: 4px solid #f46a6a;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .table tbody tr.status-fixed {
            border-left-color: #34c38f;
        }

        .table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border: none;
            border-bottom: 1px solid #f0f0f0;
        }

        .table tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #495057;
            font-size: 11px;
            flex-shrink: 0;
            margin-right: 10px;
        }

        .table tbody td:last-child {
            border-bottom: none;
            justify-content: flex-end;
            padding-top: 10px;
            gap: 6px;
        }

        /* Chat preview */
        .chat-preview {
            font-size: 11px;
            padding: 8px 10px;
            max-width: 180px;
        }

        /* Actions always visible */
        .error-actions {
            opacity: 1;
        }

        /* Pagination */
        .d-flex.justify-content-between.align-items-center.mt-4 {
            flex-direction: column;
            gap: 12px;
        }

        .pagination {
            flex-wrap: wrap;
            justify-content: center;
        }
    }

    /* Small Mobile */
    @media (max-width: 575px) {
        .card-body {
            padding: 12px;
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

        /* Modal adjustments */
        .modal-lg {
            max-width: calc(100% - 16px);
        }

        .modal-body {
            padding: 16px;
        }

        .modal-footer .btn {
            flex: 1;
        }

        /* Chat thread in modal */
        .chat-thread-modal .message-item {
            padding: 10px 12px;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .chat-thread-modal .message-content {
            font-size: 13px;
        }

        /* Flow logs */
        .flow-logs-preview {
            font-size: 11px;
            padding: 12px;
            max-height: 300px;
        }

        /* Status buttons in modal */
        .modal-body .d-flex.gap-3 {
            flex-direction: column;
            gap: 10px !important;
        }

        /* Tabs */
        .nav-tabs .nav-link {
            padding: 8px 12px;
            font-size: 13px;
        }
    }

    /* Touch device */
    @media (hover: none) and (pointer: coarse) {
        .error-card:active {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        .btn:active {
            transform: scale(0.98);
        }

        /* Make actions always visible on touch */
        .error-actions {
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
        @slot('title') Chat Errors @endslot
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

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card stats-card bg-danger bg-gradient text-white mb-0">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bx bx-bug" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ $totalErrors }}</h3>
                        <p class="mb-0 small opacity-75">Total Errors</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card bg-warning bg-gradient text-dark mb-0">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bx bx-time-five" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ $pendingErrors }}</h3>
                        <p class="mb-0 small opacity-75">Pending</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stats-card bg-success bg-gradient text-white mb-0">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bx bx-check-circle" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ $fixedErrors }}</h3>
                        <p class="mb-0 small opacity-75">Fixed</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body filter-card">
            <form method="GET" action="{{ route('ai-technician.chat-errors') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="status" class="form-label text-dark">Status</label>
                    <select class="form-select" name="status" id="status">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="fixed" {{ $statusFilter === 'fixed' ? 'selected' : '' }}>Fixed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start_date" class="form-label text-dark">From Date</label>
                    <input type="date" class="form-control" name="start_date" id="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label text-dark">To Date</label>
                    <input type="date" class="form-control" name="end_date" id="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bx bx-filter-alt me-1"></i> Filter
                    </button>
                    <a href="{{ route('ai-technician.chat-errors') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-reset me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Errors List -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="card-title mb-0">
                    <i class="bx bx-bug text-danger me-2"></i>Chat Errors
                </h4>
            </div>

            @if($errors->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 180px;">Date & Time</th>
                                <th style="width: 120px;">Submitted By</th>
                                <th>Error Description</th>
                                <th style="width: 180px;">Chat Preview</th>
                                <th style="width: 70px;">Messages</th>
                                <th style="width: 90px;">Status</th>
                                <th style="width: 140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($errors as $error)
                                <tr class="error-row" data-id="{{ $error->id }}">
                                    <td data-label="Date">
                                        <span class="date-badge">
                                            <i class="bx bx-calendar me-1"></i>{{ $error->formatted_date }}
                                        </span>
                                    </td>
                                    <td data-label="Submitted By">
                                        <span class="text-dark">
                                            <i class="bx bx-user me-1 text-secondary"></i>{{ $error->user->name ?? 'Unknown' }}
                                        </span>
                                    </td>
                                    <td data-label="Description">
                                        <div class="text-dark">
                                            {{ \Str::limit($error->errorDescription, 80) ?: '-' }}
                                        </div>
                                    </td>
                                    <td data-label="Preview">
                                        <div class="chat-preview small">
                                            {{ $error->chat_preview }}
                                        </div>
                                    </td>
                                    <td class="text-center" data-label="Messages">
                                        <span class="badge bg-light text-dark">
                                            {{ $error->message_count }}
                                        </span>
                                    </td>
                                    <td data-label="Status">
                                        <span class="status-badge">{!! $error->status_badge !!}</span>
                                    </td>
                                    <td data-label="Actions">
                                        <button type="button" class="btn btn-sm btn-soft-info view-error-btn"
                                                data-id="{{ $error->id }}" title="View Details">
                                            <i class="bx bx-show"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-soft-primary change-status-btn"
                                                data-id="{{ $error->id }}"
                                                data-status="{{ $error->status }}" title="Change Status">
                                            <i class="bx bx-refresh"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-soft-danger delete-error-btn"
                                                data-id="{{ $error->id }}" title="Delete">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-secondary">
                        Showing {{ $errors->firstItem() ?? 0 }} to {{ $errors->lastItem() ?? 0 }} of {{ $errors->total() }} errors
                    </div>
                    {{ $errors->appends(request()->query())->links() }}
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bx bx-check-shield"></i>
                    </div>
                    <h5 class="text-dark">No Chat Errors Found</h5>
                    <p class="text-secondary mb-0">
                        When you save chat errors from the AI Technician chat, they will appear here.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- View Error Modal -->
    <div class="modal fade chat-thread-modal" id="viewErrorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-bug text-danger me-2"></i>Chat Error Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Error Info -->
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom flex-wrap gap-2">
                        <div>
                            <small class="text-secondary">Date & Time</small>
                            <div class="fw-medium text-dark" id="viewErrorDate">-</div>
                        </div>
                        <div>
                            <small class="text-secondary">Submitted By</small>
                            <div class="fw-medium text-dark" id="viewErrorUser">-</div>
                        </div>
                        <div>
                            <small class="text-secondary">Status</small>
                            <div id="viewErrorStatus">-</div>
                        </div>
                        <div>
                            <small class="text-secondary">Messages</small>
                            <div class="fw-medium text-dark" id="viewErrorMsgCount">-</div>
                        </div>
                    </div>

                    <!-- Error Description -->
                    <div class="mb-4 pb-3 border-bottom">
                        <small class="text-secondary d-block mb-1">Error Description</small>
                        <div class="text-dark" id="viewErrorDescription" style="white-space: pre-wrap;">-</div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#chatThreadTab" role="tab">
                                <i class="bx bx-chat me-1"></i> Chat Thread
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#flowLogsTab" role="tab">
                                <i class="bx bx-code-block me-1"></i> Flow Logs
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content pt-3">
                        <!-- Chat Thread Tab -->
                        <div class="tab-pane fade show active" id="chatThreadTab" role="tabpanel">
                            <div id="chatThreadContent">
                                <div class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    <span class="text-secondary ms-2">Loading...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Flow Logs Tab -->
                        <div class="tab-pane fade" id="flowLogsTab" role="tabpanel">
                            <div id="flowLogsContent">
                                <div class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    <span class="text-secondary ms-2">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Status Modal -->
    <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-refresh text-primary me-2"></i>Change Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Select new status for this error:</p>
                    <input type="hidden" id="changeStatusErrorId">
                    <div class="d-flex gap-3">
                        <button type="button" class="btn btn-warning flex-fill status-option-btn" data-status="pending">
                            <i class="bx bx-time-five me-1"></i> Pending
                        </button>
                        <button type="button" class="btn btn-success flex-fill status-option-btn" data-status="fixed">
                            <i class="bx bx-check-circle me-1"></i> Fixed
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteErrorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to delete this chat error?</p>
                    <p class="text-secondary small mb-0">This action cannot be undone.</p>
                    <input type="hidden" id="deleteErrorId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
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
    // CRITICAL FIX: Move ALL modals to body to escape .page-content stacking context
    // The .page-content has transform animation which creates stacking context, trapping modals inside
    $('.modal').appendTo('body');

    // Toastr options
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // View Error
    $('.view-error-btn').on('click', function() {
        const errorId = $(this).data('id');
        loadErrorDetails(errorId);
        $('#viewErrorModal').modal('show');
    });

    function loadErrorDetails(errorId) {
        // Reset content
        $('#chatThreadContent').html('<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="text-secondary ms-2">Loading...</span></div>');
        $('#flowLogsContent').html('<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div><span class="text-secondary ms-2">Loading...</span></div>');

        $.ajax({
            url: '/ai-technician-chat-errors-show?id=' + errorId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;

                    // Update header info
                    $('#viewErrorDate').text(data.formattedDate);
                    $('#viewErrorUser').text(data.userName || 'Unknown');
                    $('#viewErrorStatus').html(data.statusBadge);
                    $('#viewErrorMsgCount').text(data.messageCount + ' messages');
                    $('#viewErrorDescription').text(data.errorDescription || 'No description provided');

                    // Render chat thread
                    let chatHtml = '';
                    if (data.chatThread && data.chatThread.length > 0) {
                        data.chatThread.forEach(function(msg) {
                            const roleClass = msg.role === 'user' ? 'user' : 'assistant';
                            const roleLabel = msg.role === 'user' ? 'User' : 'AI';
                            chatHtml += `
                                <div class="message-item ${roleClass}">
                                    <div class="message-role ${roleClass}">${roleLabel}</div>
                                    <div class="message-content">${escapeHtml(msg.content || '')}</div>
                                </div>
                            `;
                        });
                    } else {
                        chatHtml = '<div class="text-center text-secondary py-4">No messages in thread</div>';
                    }
                    $('#chatThreadContent').html(chatHtml);

                    // Render flow logs
                    let flowHtml = '';
                    if (data.hasFlowLogs && data.flowLogs) {
                        flowHtml = '<div class="flow-logs-preview">' + escapeHtml(JSON.stringify(data.flowLogs, null, 2)) + '</div>';
                    } else {
                        flowHtml = '<div class="text-center text-secondary py-4">No flow logs available</div>';
                    }
                    $('#flowLogsContent').html(flowHtml);
                }
            },
            error: function(xhr) {
                toastr.error('Failed to load error details');
                $('#chatThreadContent').html('<div class="text-center text-danger py-4">Failed to load</div>');
                $('#flowLogsContent').html('<div class="text-center text-danger py-4">Failed to load</div>');
            }
        });
    }

    // Change Status
    $('.change-status-btn').on('click', function() {
        const errorId = $(this).data('id');
        const currentStatus = $(this).data('status');
        $('#changeStatusErrorId').val(errorId);

        // Highlight current status
        $('.status-option-btn').removeClass('active');
        $(`.status-option-btn[data-status="${currentStatus}"]`).addClass('active');

        $('#changeStatusModal').modal('show');
    });

    $('.status-option-btn').on('click', function() {
        const errorId = $('#changeStatusErrorId').val();
        const newStatus = $(this).data('status');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '/ai-technician-chat-errors-status?id=' + errorId,
            type: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#changeStatusModal').modal('hide');

                    // Update the row
                    const $row = $(`.error-row[data-id="${errorId}"]`);
                    $row.find('.status-badge').html(response.data.statusBadge);
                    $row.find('.change-status-btn').data('status', response.data.status);
                } else {
                    toastr.error(response.message || 'Failed to update status');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to update status');
            },
            complete: function() {
                // Reset buttons
                $('.status-option-btn[data-status="pending"]').prop('disabled', false).html('<i class="bx bx-time-five me-1"></i> Pending');
                $('.status-option-btn[data-status="fixed"]').prop('disabled', false).html('<i class="bx bx-check-circle me-1"></i> Fixed');
            }
        });
    });

    // Delete Error
    $('.delete-error-btn').on('click', function() {
        const errorId = $(this).data('id');
        $('#deleteErrorId').val(errorId);
        $('#deleteErrorModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        const errorId = $('#deleteErrorId').val();
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

        $.ajax({
            url: '/ai-technician-chat-errors-delete?id=' + errorId,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#deleteErrorModal').modal('hide');

                    // Remove the row with animation
                    $(`.error-row[data-id="${errorId}"]`).fadeOut(400, function() {
                        $(this).remove();

                        // Check if table is empty
                        if ($('.error-row').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message || 'Failed to delete');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
            }
        });
    });

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endsection
