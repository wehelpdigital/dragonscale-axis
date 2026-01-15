@extends('layouts.master')

@section('title') Refunds @endsection

@section('css')
<!-- Bootstrap Datepicker -->
<link href="{{ URL::asset('build/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.summary-card {
    border-left: 4px solid #556ee6;
    background: #fff;
}
.summary-card.pending { border-left-color: #f1b44c; }
.summary-card.approved { border-left-color: #50a5f1; }
.summary-card.processed { border-left-color: #34c38f; }
.summary-card.rejected { border-left-color: #f46a6a; }
.summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #495057;
}
.summary-label {
    font-size: 0.8rem;
    color: #74788d;
}
.filter-section {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
}
.refund-item {
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    background: #f8f9fa;
}
.refund-item.selected {
    border-color: #34c38f;
    background: #e8f8f2;
}
.qty-input {
    width: 70px;
    text-align: center;
}
.status-badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.65rem;
}
.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
}
.page-info {
    color: #74788d;
    font-size: 0.875rem;
}
/* Upload Area Styles */
.upload-area {
    cursor: pointer;
    transition: all 0.2s ease;
    background: #f8f9fa;
}
.upload-area:hover {
    background: #e9ecef;
    border-color: #556ee6 !important;
}
.upload-area.drag-over {
    background: #e8f0fe;
    border-color: #556ee6 !important;
    border-style: dashed !important;
}
.file-preview-item {
    display: inline-flex;
    align-items: center;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 0.5rem;
    margin: 0.25rem;
    max-width: 200px;
}
.file-preview-item img,
.file-preview-item video {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 0.25rem;
}
.file-preview-item .file-info {
    margin-left: 0.5rem;
    overflow: hidden;
}
.file-preview-item .file-name {
    font-size: 0.75rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100px;
}
.file-preview-item .file-size {
    font-size: 0.7rem;
    color: #74788d;
}
.file-preview-item .remove-file {
    margin-left: auto;
    cursor: pointer;
    color: #f46a6a;
    padding: 0.25rem;
}
/* Attachment Gallery Styles */
.attachment-gallery {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.attachment-item {
    position: relative;
    width: 120px;
    border-radius: 0.5rem;
    overflow: hidden;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}
.attachment-item img {
    width: 100%;
    height: 90px;
    object-fit: cover;
    cursor: pointer;
}
.attachment-item video {
    width: 100%;
    height: 90px;
    object-fit: cover;
}
.attachment-item .attachment-info {
    padding: 0.35rem 0.5rem;
    font-size: 0.7rem;
    background: #fff;
}
.attachment-item .attachment-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #495057;
}
.attachment-item .attachment-size {
    color: #74788d;
}
.attachment-item .video-badge {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    background: rgba(0,0,0,0.7);
    color: #fff;
    padding: 0.1rem 0.3rem;
    border-radius: 0.25rem;
    font-size: 0.65rem;
}
/* Lightbox */
.lightbox-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.lightbox-content {
    max-width: 90%;
    max-height: 90%;
}
.lightbox-content img,
.lightbox-content video {
    max-width: 100%;
    max-height: 85vh;
}
.lightbox-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    color: #fff;
    font-size: 2rem;
    cursor: pointer;
}
/* Audit Trail Timeline Styles */
.audit-timeline {
    position: relative;
    padding-left: 30px;
}
.audit-timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}
.audit-item {
    position: relative;
    padding-bottom: 1.5rem;
    margin-bottom: 0;
}
.audit-item:last-child {
    padding-bottom: 0;
}
.audit-item::before {
    content: '';
    position: absolute;
    left: -24px;
    top: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #556ee6;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}
.audit-item.action-created::before { background: #556ee6; }
.audit-item.action-approved::before { background: #50a5f1; }
.audit-item.action-rejected::before { background: #f1b44c; }
.audit-item.action-processed::before { background: #34c38f; }
.audit-item.action-deleted::before { background: #f46a6a; }
.audit-item-content {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    border: 1px solid #e9ecef;
}
.audit-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}
.audit-item-action {
    font-weight: 600;
    color: #495057;
}
.audit-item-time {
    font-size: 0.75rem;
    color: #74788d;
}
.audit-item-user {
    font-size: 0.8rem;
    color: #556ee6;
}
.audit-item-notes {
    font-size: 0.85rem;
    color: #495057;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px dashed #dee2e6;
}
.audit-item-meta {
    font-size: 0.75rem;
    color: #74788d;
    margin-top: 0.25rem;
}
.audit-empty {
    text-align: center;
    padding: 2rem;
    color: #74788d;
}
/* Fix datepicker z-index inside modals */
.datepicker, .datepicker-dropdown {
    z-index: 99999 !important;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('title') Refunds @endslot
@endcomponent

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-2 col-6 mb-3">
        <div class="card summary-card h-100 mb-0">
            <div class="card-body py-3">
                <p class="summary-label mb-1">Total Requests</p>
                <h4 class="summary-value mb-0" id="summaryTotal">0</h4>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card summary-card pending h-100 mb-0">
            <div class="card-body py-3">
                <p class="summary-label mb-1">Pending</p>
                <h4 class="summary-value mb-0" id="summaryPending">0</h4>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card summary-card approved h-100 mb-0">
            <div class="card-body py-3">
                <p class="summary-label mb-1">Approved</p>
                <h4 class="summary-value mb-0" id="summaryApproved">0</h4>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card summary-card processed h-100 mb-0">
            <div class="card-body py-3">
                <p class="summary-label mb-1">Processed</p>
                <h4 class="summary-value mb-0" id="summaryProcessed">0</h4>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card summary-card rejected h-100 mb-0">
            <div class="card-body py-3">
                <p class="summary-label mb-1">Rejected</p>
                <h4 class="summary-value mb-0" id="summaryRejected">0</h4>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="card summary-card processed h-100 mb-0">
            <div class="card-body py-3">
                <p class="summary-label mb-1">Total Refunded</p>
                <h4 class="summary-value mb-0 text-success" id="summaryTotalRefunded">₱0.00</h4>
            </div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <div class="row g-3">
        <div class="col-md-2">
            <label class="form-label text-dark fw-medium">Date From</label>
            <input type="text" class="form-control" id="filterDateFrom" placeholder="Select date" readonly>
        </div>
        <div class="col-md-2">
            <label class="form-label text-dark fw-medium">Date To</label>
            <input type="text" class="form-control" id="filterDateTo" placeholder="Select date" readonly>
        </div>
        <div class="col-md-2">
            <label class="form-label text-dark fw-medium">Store</label>
            <select class="form-select" id="filterStore">
                <option value="">All Stores</option>
                @foreach($stores as $store)
                    <option value="{{ $store->storeName }}">{{ $store->storeName }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label text-dark fw-medium">Status</label>
            <select class="form-select" id="filterStatus">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="processed">Processed</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label text-dark fw-medium">Refund #</label>
            <input type="text" class="form-control" id="filterRefundNumber" placeholder="Search...">
        </div>
        <div class="col-md-2">
            <label class="form-label text-dark fw-medium">Order #</label>
            <input type="text" class="form-control" id="filterOrderNumber" placeholder="Search...">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary" id="applyFiltersBtn">
                    <i class="bx bx-filter-alt me-1"></i>Apply Filters
                </button>
                <button type="button" class="btn btn-outline-secondary" id="clearFiltersBtn">
                    <i class="bx bx-x me-1"></i>Clear Filters
                </button>
                <button type="button" class="btn btn-secondary ms-auto" id="auditTrailBtn">
                    <i class="bx bx-history me-1"></i>Audit Trail
                </button>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createRefundModal">
                    <i class="bx bx-plus me-1"></i>Create Refund Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Refunds Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="refundsTable">
                <thead class="table-light">
                    <tr>
                        <th>Refund #</th>
                        <th>Order #</th>
                        <th>Client</th>
                        <th>Store</th>
                        <th>Status</th>
                        <th class="text-end">Requested</th>
                        <th class="text-end">Approved</th>
                        <th>Date</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="refundsTableBody">
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-secondary mt-2 mb-0">Loading refunds...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-container" id="paginationContainer" style="display: none;">
            <div class="page-info">
                Showing <span id="pageStart">0</span> to <span id="pageEnd">0</span> of <span id="totalRecords">0</span> refunds
            </div>
            <nav>
                <ul class="pagination mb-0" id="paginationNav">
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Create Refund Modal -->
<div class="modal fade" id="createRefundModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-undo text-primary me-2"></i>Create Refund Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Find Order -->
                <div id="createStep1">
                    <div class="mb-3">
                        <label class="form-label">Order Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchOrderNumber" placeholder="Enter order number (e.g., ORD-20260114-XXXX)">
                            <button class="btn btn-primary" type="button" id="searchOrderBtn">
                                <i class="bx bx-search"></i> Find Order
                            </button>
                        </div>
                    </div>
                    <div id="orderSearchResult" style="display: none;"></div>
                </div>

                <!-- Step 2: Select Items & Create Request -->
                <div id="createStep2" style="display: none;">
                    <div class="alert alert-info mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>Order:</strong> <span id="selectedOrderNumber"></span><br>
                                <strong>Client:</strong> <span id="selectedClientName"></span><br>
                                <strong>Order Total:</strong> <span id="selectedOrderTotal"></span>
                            </div>
                            <div class="text-end">
                                <strong>Refundable:</strong> <span id="selectedRefundable" class="text-success"></span><br>
                                <small class="text-muted">(Excludes shipping & discount)</small>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-dark mb-3">Select Items to Refund:</h6>
                    <div id="orderItemsContainer"></div>

                    <div class="mb-3 mt-4">
                        <label class="form-label">Reason for Refund <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="refundReason" rows="3" placeholder="Please provide the reason for this refund request..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Supporting Evidence (Optional)</label>
                        <input type="file" id="refundAttachments" name="attachments[]" multiple accept="image/*,video/*" class="d-none">
                        <div class="upload-area border rounded p-3 text-center" id="uploadArea">
                            <div class="upload-placeholder" id="uploadPlaceholder">
                                <i class="bx bx-cloud-upload text-primary" style="font-size: 2.5rem;"></i>
                                <p class="text-dark mb-1">Click or drag files here to upload</p>
                                <small class="text-secondary">Images (JPG, PNG, GIF) and Videos (MP4, MOV) - Max 50MB each, up to 10 files</small>
                            </div>
                        </div>
                        <div id="filePreviewContainer" class="mt-2"></div>
                    </div>

                    <div class="card bg-light border-0 mt-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-medium text-dark">Total Refund Amount:</span>
                                <span class="fs-4 fw-bold text-success" id="totalRefundAmount">₱0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitRefundBtn" style="display: none;">
                    <i class="bx bx-check me-1"></i>Submit Refund Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Refund Modal -->
<div class="modal fade" id="viewRefundModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-receipt text-info me-2"></i>Refund Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewRefundContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer" id="viewRefundFooter" style="display: none;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Process Refund Modal -->
<div class="modal fade" id="processRefundModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-check-circle text-success me-2"></i>Process Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="processRefundContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer" id="processRefundFooter" style="display: none;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="rejectRefundBtn">
                    <i class="bx bx-x me-1"></i>Reject
                </button>
                <button type="button" class="btn btn-success" id="confirmProcessBtn">
                    <i class="bx bx-check me-1"></i>Process Refund
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Delete Refund Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete refund request <strong id="deleteRefundNumber"></strong>?</p>
                <p class="text-secondary small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bx bx-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectReasonModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-x-circle text-warning me-2"></i>Reject Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">You are about to reject refund request <strong id="rejectRefundNumber"></strong>.</p>
                <div class="mb-3">
                    <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejectionReason" rows="3" placeholder="Please provide the reason for rejecting this refund..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmRejectBtn">
                    <i class="bx bx-x me-1"></i>Confirm Rejection
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Refund Modal -->
<div class="modal fade" id="approveRefundModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-check-circle text-primary me-2"></i>Approve Refund</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="approveRefundContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer" id="approveRefundFooter" style="display: none;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmApproveBtn">
                    <i class="bx bx-check me-1"></i>Approve Refund
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Audit Trail Modal -->
<div class="modal fade" id="auditTrailModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-history text-secondary me-2"></i>Refunds Audit Trail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Audit Trail Filters -->
                <div class="row g-2 mb-3 pb-3 border-bottom">
                    <div class="col-6 col-md-2">
                        <input type="text" class="form-control form-control-sm" id="auditFilterRefundNumber" placeholder="Refund #">
                    </div>
                    <div class="col-6 col-md-2">
                        <input type="text" class="form-control form-control-sm" id="auditFilterOrderNumber" placeholder="Order #">
                    </div>
                    <div class="col-6 col-md-2">
                        <select class="form-select form-select-sm" id="auditFilterAction">
                            <option value="">All Actions</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <input type="text" class="form-control form-control-sm" id="auditFilterUser" placeholder="User">
                    </div>
                    <div class="col-6 col-md-2">
                        <input type="text" class="form-control form-control-sm" id="auditFilterDateFrom" placeholder="From Date" readonly>
                    </div>
                    <div class="col-6 col-md-2">
                        <input type="text" class="form-control form-control-sm" id="auditFilterDateTo" placeholder="To Date" readonly>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary btn-sm" id="applyAuditFiltersBtn">
                            <i class="bx bx-filter-alt me-1"></i>Apply Filters
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearAuditFiltersBtn">
                            <i class="bx bx-x me-1"></i>Clear
                        </button>
                    </div>
                </div>

                <!-- Audit Trail Content -->
                <div id="auditTrailContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-secondary mt-2 mb-0">Loading audit trail...</p>
                    </div>
                </div>

                <!-- Audit Trail Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top" id="auditPaginationContainer" style="display: none !important;">
                    <div class="text-secondary small">
                        Showing <span id="auditPageStart">0</span> to <span id="auditPageEnd">0</span> of <span id="auditTotalRecords">0</span> logs
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="auditPaginationNav"></ul>
                    </nav>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<!-- Bootstrap Datepicker -->
<script src="{{ URL::asset('build/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Toastr config
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Initialize datepickers
    $('#filterDateFrom, #filterDateTo').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true,
        clearBtn: true
    });

    // Variables
    let currentPage = 1;
    let perPage = 15;
    let currentRefundId = null;
    let selectedOrderData = null;
    let selectedItems = {};

    // Load initial data
    loadRefunds();
    loadSummary();

    // Apply filters
    $('#applyFiltersBtn').on('click', function() {
        currentPage = 1;
        loadRefunds();
        loadSummary();
    });

    // Clear filters
    $('#clearFiltersBtn').on('click', function() {
        $('#filterDateFrom, #filterDateTo, #filterRefundNumber, #filterOrderNumber').val('');
        $('#filterStore, #filterStatus').val('');
        currentPage = 1;
        loadRefunds();
        loadSummary();
    });

    // Search order for refund
    $('#searchOrderBtn').on('click', function() {
        searchOrder();
    });

    $('#searchOrderNumber').on('keypress', function(e) {
        if (e.which === 13) {
            searchOrder();
        }
    });

    // Submit refund request
    $('#submitRefundBtn').on('click', function() {
        submitRefundRequest();
    });

    // Delete refund
    $('#confirmDeleteBtn').on('click', function() {
        deleteRefund();
    });

    // Process refund
    $('#confirmProcessBtn').on('click', function() {
        processRefund('process');
    });

    // Approve refund (from approve modal)
    $('#confirmApproveBtn').on('click', function() {
        processRefund('approve');
    });

    // Reject refund (from process modal)
    $('#rejectRefundBtn').on('click', function() {
        $('#processRefundModal').modal('hide');
        $('#rejectReasonModal').modal('show');
    });

    // Confirm reject (from reject modal)
    $('#confirmRejectBtn').on('click', function() {
        processRefund('reject');
    });

    // Reset create modal on close
    $('#createRefundModal').on('hidden.bs.modal', function() {
        $('#searchOrderNumber').val('');
        $('#orderSearchResult').hide().html('');
        $('#createStep2').hide();
        $('#createStep1').show();
        $('#submitRefundBtn').hide();
        $('#refundReason').val('');
        $('#refundAttachments').val('');
        $('#filePreviewContainer').html('');
        selectedFiles = [];
        selectedOrderData = null;
        selectedItems = {};
    });

    // File upload handling
    let selectedFiles = [];

    // Click to upload
    $('#uploadArea').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#refundAttachments').trigger('click');
    });

    // Drag and drop
    $('#uploadArea').on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-over');
    });

    $('#uploadArea').on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
    });

    $('#uploadArea').on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
        const files = e.originalEvent.dataTransfer.files;
        handleFileSelect(files);
    });

    // File input change
    $('#refundAttachments').on('change', function() {
        handleFileSelect(this.files);
    });

    // Handle file selection
    function handleFileSelect(files) {
        const maxFiles = 10;
        const maxSize = 50 * 1024 * 1024; // 50MB

        for (let i = 0; i < files.length; i++) {
            if (selectedFiles.length >= maxFiles) {
                toastr.warning('Maximum 10 files allowed');
                break;
            }

            const file = files[i];

            // Validate file type
            if (!file.type.match(/^(image|video)\//)) {
                toastr.warning(`${file.name} is not a supported file type`);
                continue;
            }

            // Validate file size
            if (file.size > maxSize) {
                toastr.warning(`${file.name} exceeds 50MB limit`);
                continue;
            }

            // Check for duplicates
            const isDuplicate = selectedFiles.some(f => f.name === file.name && f.size === file.size);
            if (isDuplicate) {
                continue;
            }

            selectedFiles.push(file);
            addFilePreview(file, selectedFiles.length - 1);
        }
    }

    // Add file preview
    function addFilePreview(file, index) {
        const isVideo = file.type.startsWith('video/');
        const fileSize = formatFileSize(file.size);

        let previewHtml = `
            <div class="file-preview-item" data-index="${index}">
        `;

        if (isVideo) {
            previewHtml += `
                <video muted>
                    <source src="${URL.createObjectURL(file)}" type="${file.type}">
                </video>
            `;
        } else {
            previewHtml += `
                <img src="${URL.createObjectURL(file)}" alt="${escapeHtml(file.name)}">
            `;
        }

        previewHtml += `
                <div class="file-info">
                    <div class="file-name text-dark" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</div>
                    <div class="file-size">${fileSize}</div>
                </div>
                <span class="remove-file" data-index="${index}" title="Remove">
                    <i class="bx bx-x"></i>
                </span>
            </div>
        `;

        $('#filePreviewContainer').append(previewHtml);

        // Bind remove handler
        $(`.remove-file[data-index="${index}"]`).on('click', function(e) {
            e.stopPropagation();
            removeFile(index);
        });
    }

    // Remove file
    function removeFile(index) {
        selectedFiles[index] = null;
        $(`.file-preview-item[data-index="${index}"]`).remove();
    }

    // Format file size
    function formatFileSize(bytes) {
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' GB';
        } else if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' bytes';
        }
    }

    // Load refunds data
    function loadRefunds() {
        const params = {
            page: currentPage,
            per_page: perPage,
            dateFrom: $('#filterDateFrom').val(),
            dateTo: $('#filterDateTo').val(),
            storeName: $('#filterStore').val(),
            status: $('#filterStatus').val(),
            refundNumber: $('#filterRefundNumber').val(),
            orderNumber: $('#filterOrderNumber').val()
        };

        $('#refundsTableBody').html(`
            <tr>
                <td colspan="9" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-secondary mt-2 mb-0">Loading refunds...</p>
                </td>
            </tr>
        `);

        $.ajax({
            url: '{{ route("ecom-refunds.data") }}',
            data: params,
            success: function(response) {
                if (response.success) {
                    renderRefundsTable(response.data);
                    renderPagination(response.pagination);
                }
            },
            error: function() {
                $('#refundsTableBody').html(`
                    <tr>
                        <td colspan="9" class="text-center py-4 text-danger">
                            <i class="bx bx-error-circle" style="font-size: 2rem;"></i>
                            <p class="mt-2 mb-0">Error loading refunds</p>
                        </td>
                    </tr>
                `);
            }
        });
    }

    // Render refunds table
    function renderRefundsTable(data) {
        if (!data || data.length === 0) {
            $('#refundsTableBody').html(`
                <tr>
                    <td colspan="9" class="text-center py-4">
                        <i class="bx bx-folder-open text-secondary" style="font-size: 2rem;"></i>
                        <p class="text-secondary mt-2 mb-0">No refund requests found</p>
                    </td>
                </tr>
            `);
            $('#paginationContainer').hide();
            return;
        }

        let html = '';
        data.forEach(function(refund) {
            html += `
                <tr>
                    <td>
                        <a href="javascript:void(0);" class="text-primary fw-medium view-refund-btn" data-id="${refund.id}">
                            ${escapeHtml(refund.refundNumber)}
                        </a>
                    </td>
                    <td class="text-dark">${escapeHtml(refund.orderNumber)}</td>
                    <td>
                        <span class="text-dark">${escapeHtml(refund.clientName || 'N/A')}</span><br>
                        <small class="text-secondary">${escapeHtml(refund.clientEmail || '')}</small>
                    </td>
                    <td class="text-dark">${escapeHtml(refund.storeName || 'N/A')}</td>
                    <td>
                        <span class="badge ${refund.statusBadgeClass} status-badge">${refund.statusLabel}</span>
                    </td>
                    <td class="text-end text-dark">${refund.formattedRequestedAmount}</td>
                    <td class="text-end">
                        ${refund.status === 'processed' ? '<span class="text-success fw-medium">' + refund.formattedApprovedAmount + '</span>' : '<span class="text-secondary">-</span>'}
                    </td>
                    <td class="text-secondary">${refund.requestedAt}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-info view-refund-btn" data-id="${refund.id}" title="View">
                                <i class="bx bx-show"></i>
                            </button>
                            ${refund.status === 'pending' ? `
                                <button class="btn btn-outline-primary approve-refund-btn" data-id="${refund.id}" title="Approve">
                                    <i class="bx bx-check"></i>
                                </button>
                                <button class="btn btn-outline-success process-refund-btn" data-id="${refund.id}" title="Process">
                                    <i class="bx bx-check-double"></i>
                                </button>
                                <button class="btn btn-outline-warning reject-refund-btn" data-id="${refund.id}" data-number="${escapeHtml(refund.refundNumber)}" title="Reject">
                                    <i class="bx bx-x"></i>
                                </button>
                                <button class="btn btn-outline-danger delete-refund-btn" data-id="${refund.id}" data-number="${escapeHtml(refund.refundNumber)}" title="Delete">
                                    <i class="bx bx-trash"></i>
                                </button>
                            ` : ''}
                            ${refund.status === 'approved' ? `
                                <button class="btn btn-outline-success process-refund-btn" data-id="${refund.id}" title="Process Refund">
                                    <i class="bx bx-check-double"></i>
                                </button>
                                <button class="btn btn-outline-warning reject-refund-btn" data-id="${refund.id}" data-number="${escapeHtml(refund.refundNumber)}" title="Reject">
                                    <i class="bx bx-x"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });

        $('#refundsTableBody').html(html);
        bindTableActions();
    }

    // Render pagination
    function renderPagination(pagination) {
        if (!pagination || pagination.total === 0) {
            $('#paginationContainer').hide();
            return;
        }

        const start = (pagination.current_page - 1) * pagination.per_page + 1;
        const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);

        $('#pageStart').text(start);
        $('#pageEnd').text(end);
        $('#totalRecords').text(pagination.total);

        let paginationHtml = '';

        // Previous button
        paginationHtml += `
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0);" data-page="${pagination.current_page - 1}">
                    <i class="bx bx-chevron-left"></i>
                </a>
            </li>
        `;

        // Page numbers
        const maxPages = 5;
        let startPage = Math.max(1, pagination.current_page - 2);
        let endPage = Math.min(pagination.last_page, startPage + maxPages - 1);

        if (endPage - startPage < maxPages - 1) {
            startPage = Math.max(1, endPage - maxPages + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0);" data-page="${i}">${i}</a>
                </li>
            `;
        }

        // Next button
        paginationHtml += `
            <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0);" data-page="${pagination.current_page + 1}">
                    <i class="bx bx-chevron-right"></i>
                </a>
            </li>
        `;

        $('#paginationNav').html(paginationHtml);
        $('#paginationContainer').show();

        // Bind pagination clicks
        $('#paginationNav .page-link').on('click', function() {
            const page = $(this).data('page');
            if (page && page >= 1 && page <= pagination.last_page) {
                currentPage = page;
                loadRefunds();
            }
        });
    }

    // Load summary
    function loadSummary() {
        const params = {
            dateFrom: $('#filterDateFrom').val(),
            dateTo: $('#filterDateTo').val(),
            storeName: $('#filterStore').val()
        };

        $.ajax({
            url: '{{ route("ecom-refunds.summary") }}',
            data: params,
            success: function(response) {
                if (response.success) {
                    const s = response.summary;
                    $('#summaryTotal').text(s.totalRequests);
                    $('#summaryPending').text(s.pendingRequests);
                    $('#summaryApproved').text(s.approvedRequests);
                    $('#summaryProcessed').text(s.processedRequests);
                    $('#summaryRejected').text(s.rejectedRequests);
                    $('#summaryTotalRefunded').text(s.formattedTotalRefunded);
                }
            }
        });
    }

    // Search order
    function searchOrder() {
        const orderNumber = $('#searchOrderNumber').val().trim();
        if (!orderNumber) {
            toastr.warning('Please enter an order number');
            return;
        }

        $('#searchOrderBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ route("ecom-refunds.get-order") }}',
            data: { orderNumber: orderNumber },
            success: function(response) {
                if (response.success) {
                    selectedOrderData = response;
                    showOrderItems(response);
                } else {
                    $('#orderSearchResult').html(`
                        <div class="alert alert-danger">
                            <i class="bx bx-error-circle me-2"></i>${response.message}
                        </div>
                    `).show();
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Error searching for order';
                $('#orderSearchResult').html(`
                    <div class="alert alert-danger">
                        <i class="bx bx-error-circle me-2"></i>${message}
                    </div>
                `).show();
            },
            complete: function() {
                $('#searchOrderBtn').prop('disabled', false).html('<i class="bx bx-search"></i> Find Order');
            }
        });
    }

    // Show order items for selection
    function showOrderItems(data) {
        const order = data.order;
        const items = data.items;

        // Update header info
        $('#selectedOrderNumber').text(order.orderNumber);
        $('#selectedClientName').text(order.clientName);
        $('#selectedOrderTotal').text(order.formattedGrandTotal);
        $('#selectedRefundable').text(order.formattedRemainingRefundable);

        // Reset selected items
        selectedItems = {};

        // Build items HTML
        let itemsHtml = '';
        items.forEach(function(item) {
            if (item.isFullyRefunded) {
                itemsHtml += `
                    <div class="refund-item opacity-50">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="bx bx-check-circle text-success" style="font-size: 1.5rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong class="text-dark">${escapeHtml(item.productName)}</strong>
                                ${item.variantName ? '<br><small class="text-secondary">' + escapeHtml(item.variantName) + '</small>' : ''}
                                <br><small class="text-secondary">Store: ${escapeHtml(item.productStore || 'N/A')}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success">Fully Refunded</span>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                itemsHtml += `
                    <div class="refund-item" data-item-id="${item.id}">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <input type="checkbox" class="form-check-input item-checkbox" data-item-id="${item.id}"
                                       data-unit-price="${item.effectiveUnitPrice}" data-max-qty="${item.remainingQuantity}">
                            </div>
                            <div class="flex-grow-1">
                                <strong class="text-dark">${escapeHtml(item.productName)}</strong>
                                ${item.variantName ? '<br><small class="text-secondary">' + escapeHtml(item.variantName) + '</small>' : ''}
                                <br><small class="text-secondary">Store: ${escapeHtml(item.productStore || 'N/A')} | Refundable Price: ${item.formattedUnitPrice}${item.effectiveUnitPrice < item.unitPrice ? ' <span class="text-muted">(was ' + item.formattedOriginalPrice + ')</span>' : ''}</small>
                            </div>
                            <div class="text-end d-flex align-items-center gap-2">
                                <div>
                                    <small class="text-secondary">Qty (max ${item.remainingQuantity})</small><br>
                                    <input type="number" class="form-control form-control-sm qty-input item-qty"
                                           data-item-id="${item.id}" min="1" max="${item.remainingQuantity}" value="${item.remainingQuantity}" disabled>
                                </div>
                                <div class="ms-2 text-dark fw-medium item-subtotal" data-item-id="${item.id}">
                                    ₱0.00
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        });

        $('#orderItemsContainer').html(itemsHtml);

        // Show step 2
        $('#orderSearchResult').hide();
        $('#createStep1').hide();
        $('#createStep2').show();
        $('#submitRefundBtn').show();

        // Bind item selection events
        bindItemSelection();
    }

    // Bind item selection events
    function bindItemSelection() {
        $('.item-checkbox').on('change', function() {
            const itemId = $(this).data('item-id');
            const isChecked = $(this).is(':checked');
            const $parent = $(`.refund-item[data-item-id="${itemId}"]`);
            const $qtyInput = $(`.item-qty[data-item-id="${itemId}"]`);

            if (isChecked) {
                $parent.addClass('selected');
                $qtyInput.prop('disabled', false);
                updateItemSubtotal(itemId);
            } else {
                $parent.removeClass('selected');
                $qtyInput.prop('disabled', true);
                $(`.item-subtotal[data-item-id="${itemId}"]`).text('₱0.00');
                delete selectedItems[itemId];
            }
            updateTotalRefundAmount();
        });

        $('.item-qty').on('input', function() {
            const itemId = $(this).data('item-id');
            updateItemSubtotal(itemId);
            updateTotalRefundAmount();
        });
    }

    // Update item subtotal
    function updateItemSubtotal(itemId) {
        const $checkbox = $(`.item-checkbox[data-item-id="${itemId}"]`);
        const $qtyInput = $(`.item-qty[data-item-id="${itemId}"]`);
        const unitPrice = parseFloat($checkbox.data('unit-price'));
        const maxQty = parseInt($checkbox.data('max-qty'));
        let qty = parseInt($qtyInput.val()) || 0;

        // Validate quantity
        if (qty < 1) qty = 1;
        if (qty > maxQty) qty = maxQty;
        $qtyInput.val(qty);

        const subtotal = qty * unitPrice;
        $(`.item-subtotal[data-item-id="${itemId}"]`).text('₱' + subtotal.toLocaleString('en-PH', {minimumFractionDigits: 2}));

        if ($checkbox.is(':checked')) {
            selectedItems[itemId] = {
                orderItemId: itemId,
                refundQuantity: qty,
                subtotal: subtotal
            };
        }
    }

    // Update total refund amount
    function updateTotalRefundAmount() {
        let total = 0;
        Object.values(selectedItems).forEach(function(item) {
            total += item.subtotal;
        });
        $('#totalRefundAmount').text('₱' + total.toLocaleString('en-PH', {minimumFractionDigits: 2}));
    }

    // Submit refund request
    function submitRefundRequest() {
        const items = Object.values(selectedItems);
        const reason = $('#refundReason').val().trim();

        if (items.length === 0) {
            toastr.warning('Please select at least one item to refund');
            return;
        }

        if (!reason) {
            toastr.warning('Please provide a reason for the refund');
            return;
        }

        const $btn = $('#submitRefundBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Submitting...');

        // Use FormData for file uploads
        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('orderId', selectedOrderData.order.id);
        formData.append('requestReason', reason);

        // Add items as JSON string to preserve array structure
        items.forEach((item, index) => {
            formData.append(`items[${index}][orderItemId]`, item.orderItemId);
            formData.append(`items[${index}][refundQuantity]`, item.refundQuantity);
        });

        // Add files (filter out null entries from removed files)
        const validFiles = selectedFiles.filter(f => f !== null);
        validFiles.forEach((file) => {
            formData.append('attachments[]', file);
        });

        $.ajax({
            url: '{{ route("ecom-refunds.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('Refund request created successfully');
                    $('#createRefundModal').modal('hide');
                    loadRefunds();
                    loadSummary();
                } else {
                    toastr.error(response.message || 'Error creating refund request');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error creating refund request');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Submit Refund Request');
            }
        });
    }

    // Bind table actions
    function bindTableActions() {
        $('.view-refund-btn').off('click').on('click', function() {
            const id = $(this).data('id');
            viewRefund(id);
        });

        $('.approve-refund-btn').off('click').on('click', function() {
            const id = $(this).data('id');
            showApproveModal(id);
        });

        $('.process-refund-btn').off('click').on('click', function() {
            const id = $(this).data('id');
            showProcessModal(id);
        });

        $('.reject-refund-btn').off('click').on('click', function() {
            const id = $(this).data('id');
            const number = $(this).data('number');
            currentRefundId = id;
            $('#rejectRefundNumber').text(number);
            $('#rejectionReason').val('');
            $('#rejectReasonModal').modal('show');
        });

        $('.delete-refund-btn').off('click').on('click', function() {
            const id = $(this).data('id');
            const number = $(this).data('number');
            currentRefundId = id;
            $('#deleteRefundNumber').text(number);
            $('#deleteModal').modal('show');
        });
    }

    // View refund details
    function viewRefund(id) {
        $('#viewRefundContent').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        `);
        $('#viewRefundFooter').hide();
        $('#viewRefundModal').modal('show');

        $.ajax({
            url: '{{ url("ecom-refunds") }}/' + id,
            success: function(response) {
                if (response.success) {
                    renderRefundDetails(response.refund, response.items, response.attachments || []);
                    $('#viewRefundFooter').show();
                }
            },
            error: function() {
                $('#viewRefundContent').html(`
                    <div class="alert alert-danger">Error loading refund details</div>
                `);
            }
        });
    }

    // Render refund details
    function renderRefundDetails(refund, items, attachments) {
        let itemsHtml = '';
        items.forEach(function(item) {
            itemsHtml += `
                <tr>
                    <td class="text-dark">${escapeHtml(item.productName)}${item.variantName ? '<br><small class="text-secondary">' + escapeHtml(item.variantName) + '</small>' : ''}</td>
                    <td class="text-secondary">${escapeHtml(item.productStore || 'N/A')}</td>
                    <td class="text-center text-dark">${item.refundQuantity}</td>
                    <td class="text-end text-dark">${item.formattedUnitPrice}</td>
                    <td class="text-end text-dark fw-medium">${item.formattedRefundAmount}</td>
                </tr>
            `;
        });

        // Build attachments HTML
        let attachmentsHtml = '';
        if (attachments && attachments.length > 0) {
            attachmentsHtml = `
                <hr>
                <h6 class="text-dark mb-3"><i class="bx bx-paperclip me-1"></i>Supporting Evidence (${attachments.length} file${attachments.length > 1 ? 's' : ''})</h6>
                <div class="attachment-gallery">
            `;
            attachments.forEach(function(attachment) {
                if (attachment.isImage) {
                    attachmentsHtml += `
                        <div class="attachment-item">
                            <img src="${attachment.url}" alt="${escapeHtml(attachment.fileName)}"
                                 onclick="openLightbox('${attachment.url}', 'image')" title="Click to enlarge">
                            <div class="attachment-info">
                                <div class="attachment-name" title="${escapeHtml(attachment.fileName)}">${escapeHtml(attachment.fileName)}</div>
                                <div class="attachment-size">${attachment.formattedFileSize}</div>
                            </div>
                        </div>
                    `;
                } else {
                    attachmentsHtml += `
                        <div class="attachment-item">
                            <video onclick="openLightbox('${attachment.url}', 'video')" title="Click to play">
                                <source src="${attachment.url}" type="${attachment.mimeType}">
                            </video>
                            <span class="video-badge"><i class="bx bx-play"></i> Video</span>
                            <div class="attachment-info">
                                <div class="attachment-name" title="${escapeHtml(attachment.fileName)}">${escapeHtml(attachment.fileName)}</div>
                                <div class="attachment-size">${attachment.formattedFileSize}</div>
                            </div>
                        </div>
                    `;
                }
            });
            attachmentsHtml += '</div>';
        }

        const html = `
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Refund #:</strong> <span class="text-dark">${escapeHtml(refund.refundNumber)}</span></p>
                    <p class="mb-1"><strong>Order #:</strong> <span class="text-dark">${escapeHtml(refund.orderNumber)}</span></p>
                    <p class="mb-1"><strong>Store:</strong> <span class="text-dark">${escapeHtml(refund.storeName || 'N/A')}</span></p>
                    <p class="mb-0"><strong>Status:</strong> <span class="badge ${refund.statusBadgeClass}">${refund.statusLabel}</span></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Client:</strong> <span class="text-dark">${escapeHtml(refund.clientName || 'N/A')}</span></p>
                    <p class="mb-1"><strong>Email:</strong> <span class="text-dark">${escapeHtml(refund.clientEmail || 'N/A')}</span></p>
                    <p class="mb-1"><strong>Phone:</strong> <span class="text-dark">${escapeHtml(refund.clientPhone || 'N/A')}</span></p>
                    <p class="mb-0"><strong>Requested:</strong> <span class="text-dark">${escapeHtml(refund.requestedAt)}</span></p>
                </div>
            </div>
            <hr>
            <h6 class="text-dark mb-3">Refund Items</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Store</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Refund</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Reason:</strong></p>
                    <p class="text-dark mb-0">${escapeHtml(refund.requestReason || 'N/A')}</p>
                    ${refund.rejectionReason ? '<p class="mb-1 mt-2"><strong class="text-danger">Rejection Reason:</strong></p><p class="text-dark mb-0">' + escapeHtml(refund.rejectionReason) + '</p>' : ''}
                    ${refund.adminNotes ? '<p class="mb-1 mt-2"><strong>Admin Notes:</strong></p><p class="text-secondary mb-0">' + escapeHtml(refund.adminNotes) + '</p>' : ''}
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-1"><strong>Order Total:</strong> <span class="text-dark">${refund.formattedOrderGrandTotal}</span></p>
                    <p class="mb-1"><strong>Requested Amount:</strong> <span class="text-dark">${refund.formattedRequestedAmount}</span></p>
                    ${refund.status === 'processed' ? '<p class="mb-0"><strong>Approved Amount:</strong> <span class="text-success fs-5">' + refund.formattedApprovedAmount + '</span></p>' : ''}
                    ${refund.processedBy ? '<p class="mb-0 mt-2"><small class="text-secondary">Processed by ' + escapeHtml(refund.processedBy) + ' on ' + escapeHtml(refund.processedAt) + '</small></p>' : ''}
                </div>
            </div>
            ${attachmentsHtml}
        `;

        $('#viewRefundContent').html(html);
    }

    // Show approve modal
    function showApproveModal(id) {
        currentRefundId = id;
        $('#approveRefundContent').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        `);
        $('#approveRefundFooter').hide();
        $('#approveRefundModal').modal('show');

        $.ajax({
            url: '{{ url("ecom-refunds") }}/' + id,
            success: function(response) {
                if (response.success) {
                    renderApproveForm(response.refund, response.items);
                    $('#approveRefundFooter').show();
                }
            },
            error: function() {
                $('#approveRefundContent').html(`
                    <div class="alert alert-danger">Error loading refund details</div>
                `);
            }
        });
    }

    // Render approve form
    function renderApproveForm(refund, items) {
        let itemsHtml = '';
        items.forEach(function(item) {
            itemsHtml += `
                <tr>
                    <td class="text-dark">${escapeHtml(item.productName)}</td>
                    <td class="text-center text-dark">${item.refundQuantity}</td>
                    <td class="text-end text-dark">${item.formattedRefundAmount}</td>
                </tr>
            `;
        });

        const html = `
            <div class="alert alert-info">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Refund #:</strong> ${escapeHtml(refund.refundNumber)}<br>
                        <strong>Order #:</strong> ${escapeHtml(refund.orderNumber)}<br>
                        <strong>Client:</strong> ${escapeHtml(refund.clientName)}
                    </div>
                    <div class="col-md-6 text-end">
                        <strong>Requested Amount:</strong> <span class="text-dark fs-5">${refund.formattedRequestedAmount}</span>
                    </div>
                </div>
            </div>
            <h6 class="text-dark mb-3">Items to Refund</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
            </div>
            <div class="mb-3">
                <label class="form-label">Reason for Refund</label>
                <p class="text-dark border rounded p-2 bg-light">${escapeHtml(refund.requestReason || 'No reason provided')}</p>
            </div>
            <div class="mb-3">
                <label class="form-label">Admin Notes (Optional)</label>
                <textarea class="form-control" id="approveAdminNotes" rows="2" placeholder="Optional notes for approval..."></textarea>
            </div>
            <div class="alert alert-primary mb-0">
                <i class="bx bx-info-circle me-2"></i>
                <strong>Note:</strong> Approving this refund will move it to <strong>Approved</strong> status.
                The refund can then be processed (finalized) or rejected later.
            </div>
        `;

        $('#approveRefundContent').html(html);
    }

    // Show process modal
    function showProcessModal(id) {
        currentRefundId = id;
        $('#processRefundContent').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        `);
        $('#processRefundFooter').hide();
        $('#processRefundModal').modal('show');

        $.ajax({
            url: '{{ url("ecom-refunds") }}/' + id,
            success: function(response) {
                if (response.success) {
                    renderProcessForm(response.refund, response.items);
                    $('#processRefundFooter').show();
                }
            },
            error: function() {
                $('#processRefundContent').html(`
                    <div class="alert alert-danger">Error loading refund details</div>
                `);
            }
        });
    }

    // Render process form
    function renderProcessForm(refund, items) {
        let itemsHtml = '';
        items.forEach(function(item) {
            itemsHtml += `
                <tr>
                    <td class="text-dark">${escapeHtml(item.productName)}</td>
                    <td class="text-center text-dark">${item.refundQuantity}</td>
                    <td class="text-end text-dark">${item.formattedRefundAmount}</td>
                </tr>
            `;
        });

        const html = `
            <div class="alert alert-info">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Refund #:</strong> ${escapeHtml(refund.refundNumber)}<br>
                        <strong>Order #:</strong> ${escapeHtml(refund.orderNumber)}<br>
                        <strong>Client:</strong> ${escapeHtml(refund.clientName)}
                    </div>
                    <div class="col-md-6 text-end">
                        <strong>Requested:</strong> ${refund.formattedRequestedAmount}
                    </div>
                </div>
            </div>
            <h6 class="text-dark mb-3">Items to Refund</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
            </div>
            <div class="mb-3">
                <label class="form-label">Approved Amount <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">₱</span>
                    <input type="number" class="form-control" id="approvedAmount" step="0.01" min="0"
                           max="${refund.requestedAmount}" value="${refund.requestedAmount}">
                </div>
                <small class="text-secondary">Max: ${refund.formattedRequestedAmount}</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Admin Notes</label>
                <textarea class="form-control" id="processAdminNotes" rows="2" placeholder="Optional notes..."></textarea>
            </div>
            <div class="alert alert-warning mb-0">
                <i class="bx bx-info-circle me-2"></i>
                <strong>Note:</strong> Processing this refund will change the order status to <strong>Refunded</strong>.
            </div>
        `;

        $('#processRefundContent').html(html);
    }

    // Process refund (approve, process, or reject)
    function processRefund(action) {
        let $btn;
        if (action === 'reject') {
            $btn = $('#confirmRejectBtn');
        } else if (action === 'approve') {
            $btn = $('#confirmApproveBtn');
        } else {
            $btn = $('#confirmProcessBtn');
        }

        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        const data = {
            _token: '{{ csrf_token() }}',
            action: action
        };

        if (action === 'reject') {
            data.rejectionReason = $('#rejectionReason').val().trim();
            if (!data.rejectionReason) {
                toastr.warning('Please provide a rejection reason');
                $btn.prop('disabled', false).html(originalHtml);
                return;
            }
        } else if (action === 'approve') {
            data.adminNotes = $('#approveAdminNotes').val().trim();
        } else {
            data.approvedAmount = parseFloat($('#approvedAmount').val()) || 0;
            data.adminNotes = $('#processAdminNotes').val().trim();
            if (data.approvedAmount <= 0) {
                toastr.warning('Please enter a valid approved amount');
                $btn.prop('disabled', false).html(originalHtml);
                return;
            }
        }

        $.ajax({
            url: '{{ url("ecom-refunds") }}/' + currentRefundId + '/process',
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#approveRefundModal').modal('hide');
                    $('#processRefundModal').modal('hide');
                    $('#rejectReasonModal').modal('hide');
                    loadRefunds();
                    loadSummary();
                } else {
                    toastr.error(response.message || 'Error processing refund');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error processing refund');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    }

    // Delete refund
    function deleteRefund() {
        const $btn = $('#confirmDeleteBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: '{{ url("ecom-refunds") }}/' + currentRefundId,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success('Refund request deleted successfully');
                    $('#deleteModal').modal('hide');
                    loadRefunds();
                    loadSummary();
                } else {
                    toastr.error(response.message || 'Error deleting refund');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error deleting refund');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
            }
        });
    }

    // Escape HTML helper
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    // ==================== UNIFIED AUDIT TRAIL ====================

    let auditCurrentPage = 1;
    let auditPerPage = 20;

    // Initialize audit trail datepickers (with container to fix z-index in modal)
    $('#auditFilterDateFrom, #auditFilterDateTo').datepicker({
        format: 'MM d, yyyy',
        autoclose: true,
        todayHighlight: true,
        clearBtn: true,
        container: '#auditTrailModal'
    });

    // Open audit trail modal
    $('#auditTrailBtn').on('click', function() {
        // Reset filters
        $('#auditFilterRefundNumber, #auditFilterOrderNumber, #auditFilterUser').val('');
        $('#auditFilterAction').val('');
        $('#auditFilterDateFrom, #auditFilterDateTo').val('');
        auditCurrentPage = 1;

        $('#auditTrailModal').modal('show');
        loadAuditTrail();
    });

    // Apply audit filters
    $('#applyAuditFiltersBtn').on('click', function() {
        auditCurrentPage = 1;
        loadAuditTrail();
    });

    // Clear audit filters
    $('#clearAuditFiltersBtn').on('click', function() {
        $('#auditFilterRefundNumber, #auditFilterOrderNumber, #auditFilterUser').val('');
        $('#auditFilterAction').val('');
        $('#auditFilterDateFrom, #auditFilterDateTo').val('');
        auditCurrentPage = 1;
        loadAuditTrail();
    });

    // Convert display date format to API format (yyyy-mm-dd)
    function convertDateForApi(dateStr) {
        if (!dateStr) return '';
        // Parse "January 5, 2025" format
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return '';
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Load audit trail data
    function loadAuditTrail() {
        const params = {
            page: auditCurrentPage,
            per_page: auditPerPage,
            refundNumber: $('#auditFilterRefundNumber').val(),
            orderNumber: $('#auditFilterOrderNumber').val(),
            action: $('#auditFilterAction').val(),
            actionBy: $('#auditFilterUser').val(),
            dateFrom: convertDateForApi($('#auditFilterDateFrom').val()),
            dateTo: convertDateForApi($('#auditFilterDateTo').val())
        };

        $('#auditTrailContent').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="text-secondary mt-2 mb-0">Loading audit trail...</p>
            </div>
        `);

        $.ajax({
            url: '{{ route("ecom-refunds.all-audit-logs") }}',
            data: params,
            success: function(response) {
                if (response.success) {
                    // Populate action filter if empty
                    if ($('#auditFilterAction option').length <= 1 && response.actionTypes) {
                        response.actionTypes.forEach(function(type) {
                            $('#auditFilterAction').append(`<option value="${type.value}">${type.label}</option>`);
                        });
                    }
                    renderUnifiedAuditTrail(response.data);
                    renderAuditPagination(response.pagination);
                } else {
                    $('#auditTrailContent').html(`
                        <div class="alert alert-danger mb-0">
                            <i class="bx bx-error-circle me-2"></i>${response.message || 'Error loading audit trail'}
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                $('#auditTrailContent').html(`
                    <div class="alert alert-danger mb-0">
                        <i class="bx bx-error-circle me-2"></i>${xhr.responseJSON?.message || 'Error loading audit trail'}
                    </div>
                `);
            }
        });
    }

    // Render unified audit trail
    function renderUnifiedAuditTrail(auditLogs) {
        if (!auditLogs || auditLogs.length === 0) {
            $('#auditTrailContent').html(`
                <div class="audit-empty">
                    <i class="bx bx-history text-secondary" style="font-size: 3rem;"></i>
                    <p class="text-dark mt-2 mb-1">No audit logs found</p>
                    <small class="text-secondary">Refund activities will appear here</small>
                </div>
            `);
            $('#auditPaginationContainer').hide();
            return;
        }

        let html = '<div class="audit-timeline">';

        auditLogs.forEach(function(log) {
            const actionClass = 'action-' + log.action;
            let notesHtml = '';

            if (log.notes) {
                notesHtml = `<div class="audit-item-notes"><i class="bx bx-message-detail me-1"></i>${escapeHtml(log.notes)}</div>`;
            }

            html += `
                <div class="audit-item ${actionClass}">
                    <div class="audit-item-content">
                        <div class="audit-item-header">
                            <div>
                                <span class="badge ${log.actionBadgeClass} me-2"><i class="${log.actionIcon} me-1"></i>${escapeHtml(log.actionLabel)}</span>
                            </div>
                            <div class="audit-item-time">
                                <div>${escapeHtml(log.actionAt)}</div>
                                <small class="text-muted">${escapeHtml(log.relativeTime)}</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div>
                                <span class="text-primary fw-medium">${escapeHtml(log.refundNumber || 'N/A')}</span>
                                <span class="text-secondary mx-1">|</span>
                                <span class="text-dark">${escapeHtml(log.orderNumber || 'N/A')}</span>
                            </div>
                            <div class="audit-item-user">
                                <i class="bx bx-user me-1"></i>${escapeHtml(log.actionBy)}
                                ${log.ipAddress ? '<span class="text-muted ms-2"><i class="bx bx-globe"></i> ' + escapeHtml(log.ipAddress) + '</span>' : ''}
                            </div>
                        </div>
                        ${notesHtml}
                    </div>
                </div>
            `;
        });

        html += '</div>';
        $('#auditTrailContent').html(html);
    }

    // Render audit pagination
    function renderAuditPagination(pagination) {
        if (!pagination || pagination.total === 0) {
            $('#auditPaginationContainer').hide();
            return;
        }

        const start = (pagination.current_page - 1) * pagination.per_page + 1;
        const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);

        $('#auditPageStart').text(start);
        $('#auditPageEnd').text(end);
        $('#auditTotalRecords').text(pagination.total);

        let paginationHtml = '';

        // Previous button
        paginationHtml += `
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0);" data-page="${pagination.current_page - 1}">
                    <i class="bx bx-chevron-left"></i>
                </a>
            </li>
        `;

        // Page numbers
        const maxPages = 5;
        let startPage = Math.max(1, pagination.current_page - 2);
        let endPage = Math.min(pagination.last_page, startPage + maxPages - 1);

        if (endPage - startPage < maxPages - 1) {
            startPage = Math.max(1, endPage - maxPages + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="javascript:void(0);" data-page="${i}">${i}</a>
                </li>
            `;
        }

        // Next button
        paginationHtml += `
            <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="javascript:void(0);" data-page="${pagination.current_page + 1}">
                    <i class="bx bx-chevron-right"></i>
                </a>
            </li>
        `;

        $('#auditPaginationNav').html(paginationHtml);
        $('#auditPaginationContainer').css('display', 'flex');

        // Bind pagination clicks
        $('#auditPaginationNav .page-link').off('click').on('click', function() {
            const page = $(this).data('page');
            if (page && page >= 1 && page <= pagination.last_page) {
                auditCurrentPage = page;
                loadAuditTrail();
            }
        });
    }
});

// Lightbox function (outside document.ready for global access)
function openLightbox(url, type) {
    const overlay = document.createElement('div');
    overlay.className = 'lightbox-overlay';
    overlay.onclick = function(e) {
        if (e.target === overlay) {
            closeLightbox();
        }
    };

    const content = document.createElement('div');
    content.className = 'lightbox-content';

    if (type === 'image') {
        const img = document.createElement('img');
        img.src = url;
        img.alt = 'Attachment';
        content.appendChild(img);
    } else {
        const video = document.createElement('video');
        video.controls = true;
        video.autoplay = true;
        video.style.maxWidth = '100%';
        video.style.maxHeight = '85vh';
        const source = document.createElement('source');
        source.src = url;
        video.appendChild(source);
        content.appendChild(video);
    }

    const closeBtn = document.createElement('span');
    closeBtn.className = 'lightbox-close';
    closeBtn.innerHTML = '&times;';
    closeBtn.onclick = closeLightbox;

    overlay.appendChild(content);
    overlay.appendChild(closeBtn);
    document.body.appendChild(overlay);

    // Close on Escape key
    document.addEventListener('keydown', handleEscKey);
}

function closeLightbox() {
    const overlay = document.querySelector('.lightbox-overlay');
    if (overlay) {
        // Stop any playing video
        const video = overlay.querySelector('video');
        if (video) {
            video.pause();
        }
        overlay.remove();
    }
    document.removeEventListener('keydown', handleEscKey);
}

function handleEscKey(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    }
}
</script>
@endsection
