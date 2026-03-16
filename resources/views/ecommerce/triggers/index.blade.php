@extends('layouts.master')

@section('title') Trigger Flows @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr CSS -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    #flows-table tbody td {
        padding: 0.5rem 0.75rem;
        vertical-align: middle;
    }
    .flow-name-cell {
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .flow-status-badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
    }
    .action-buttons {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.25rem;
    }
    .trigger-product-cell {
        line-height: 1.4;
    }
    .bg-purple {
        background-color: #8B5CF6 !important;
    }
    .bg-orange {
        background-color: #F97316 !important;
    }
    .bg-red-500 {
        background-color: #EF4444 !important;
    }
    .bg-emerald-500 {
        background-color: #10B981 !important;
    }
    .node-count-badge {
        background-color: #f3f6f9;
        color: #495057;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
    }
    /* Preview Canvas Styles */
    .preview-canvas-container {
        height: 450px;
        max-height: 70vh;
        overflow: auto;
        background: #f8fafc;
        background-image:
            linear-gradient(rgba(0,0,0,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(0,0,0,0.03) 1px, transparent 1px);
        background-size: 20px 20px;
        position: relative;
        border-radius: 0 0 8px 8px;
    }
    .preview-canvas {
        position: relative;
        min-width: 100%;
        min-height: 100%;
    }
    .preview-svg {
        position: absolute;
        top: 0;
        left: 0;
        pointer-events: none;
        z-index: 5;
        overflow: visible;
    }
    .preview-svg path {
        stroke-opacity: 1;
    }
    .preview-node {
        position: absolute;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08);
        width: 200px;
        height: 120px;
        border: 2px solid #e2e8f0;
        z-index: 10;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    .preview-node.start-node {
        border-color: #10B981;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3), 0 1px 3px rgba(0,0,0,0.08);
    }
    .preview-node.action-node {
        border-color: #3B82F6;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.2), 0 1px 3px rgba(0,0,0,0.08);
    }
    .preview-node.condition-node {
        border-color: #F59E0B;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.25), 0 1px 3px rgba(0,0,0,0.08);
    }
    .preview-node-header {
        padding: 6px 8px;
        background: #f8fafc;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-shrink: 0;
    }
    .preview-node-header .node-icon {
        width: 22px;
        height: 22px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        color: #fff;
        flex-shrink: 0;
    }
    .preview-node-header .node-title {
        font-size: 9px;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.2;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .preview-node-body {
        padding: 6px 10px;
        font-size: 10px;
        color: #475569;
        background: #fff;
        flex: 1;
        overflow: hidden;
        line-height: 1.4;
    }
    .preview-node-body .detail-row {
        display: flex;
        align-items: center;
        margin-bottom: 3px;
        max-width: 100%;
    }
    .preview-node-body .detail-row:last-child {
        margin-bottom: 0;
    }
    .preview-node-body .detail-label {
        font-size: 9px;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        min-width: 50px;
        flex-shrink: 0;
    }
    .preview-node-body .detail-value {
        font-weight: 500;
        color: #334155;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 120px;
    }
    .preview-node-body .status-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 9px;
        font-weight: 600;
        max-width: 80px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .preview-node-body .text-muted {
        font-size: 9px;
        color: #94a3b8;
    }
    .preview-svg path {
        stroke-opacity: 1;
    }
    /* Node type header colors */
    .preview-node .icon-trigger_tag { background: linear-gradient(135deg, #10B981, #059669); }
    .preview-node .icon-course_access_start { background: linear-gradient(135deg, #F59E0B, #D97706); }
    .preview-node .icon-course_tag_start { background: linear-gradient(135deg, #3B82F6, #2563EB); }
    .preview-node .icon-product_variant_start { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }
    .preview-node .icon-special_tag_start { background: linear-gradient(135deg, #06B6D4, #0891B2); }
    .preview-node .icon-order_status_start { background: linear-gradient(135deg, #6366F1, #4F46E5); }
    .preview-node .icon-delay { background: linear-gradient(135deg, #6B7280, #4B5563); }
    .preview-node .icon-schedule { background: linear-gradient(135deg, #6B7280, #4B5563); }
    .preview-node .icon-email { background: linear-gradient(135deg, #EF4444, #DC2626); }
    .preview-node .icon-send_sms { background: linear-gradient(135deg, #10B981, #059669); }
    .preview-node .icon-send_whatsapp { background: linear-gradient(135deg, #25D366, #128C7E); }
    .preview-node .icon-if_else { background: linear-gradient(135deg, #F59E0B, #D97706); }
    .preview-node .icon-y_flow { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }
    .preview-node .icon-course_access { background: linear-gradient(135deg, #3B82F6, #2563EB); }
    .preview-node .icon-remove_access { background: linear-gradient(135deg, #374151, #1F2937); }
    .preview-node .icon-add_as_affiliate { background: linear-gradient(135deg, #F59E0B, #D97706); }
    .preview-node .icon-add_login_access { background: linear-gradient(135deg, #14B8A6, #0D9488); }
    .preview-node .icon-course_subscription { background: linear-gradient(135deg, #EC4899, #DB2777); }
    .preview-node .icon-flow_action { background: linear-gradient(135deg, #10B981, #059669); }
    .preview-node .icon-ai_add_referral { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }
    /* Connection output labels */
    .preview-node .output-labels {
        display: flex;
        justify-content: space-between;
        padding: 4px 10px;
        background: #f8fafc;
        border-top: 1px solid #e9ecef;
        border-radius: 0 0 8px 8px;
        font-size: 9px;
        color: #64748b;
        flex-shrink: 0;
    }
    .preview-node .output-labels span {
        padding: 1px 5px;
        border-radius: 3px;
    }
    .preview-node .output-labels .yes-label { background: #dcfce7; color: #166534; }
    .preview-node .output-labels .no-label { background: #fee2e2; color: #991b1b; }
    .preview-node .output-labels .path-label { background: #e0e7ff; color: #3730a3; }
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
                                    <th class="text-dark">Trigger/Product</th>
                                    <th class="text-dark text-center">Nodes</th>
                                    <th class="text-dark text-center">Status</th>
                                    <th class="text-dark">Created</th>
                                    <th class="text-dark" width="220">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($flows as $flow)
                                    <tr data-flow-id="{{ $flow->id }}">
                                        <td class="text-dark flow-name-cell" title="{{ $flow->flowName }}">
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
                                            @elseif($flowType === 'shipping_complete')
                                                <span class="badge bg-info text-white">
                                                    <i class="bx bx-package me-1"></i>Shipping Complete
                                                </span>
                                            @elseif($flowType === 'affiliate_earning')
                                                <span class="badge bg-primary text-white">
                                                    <i class="bx bx-dollar-circle me-1"></i>Affiliate Earning
                                                </span>
                                            @elseif($flowType === 'payments')
                                                @php
                                                    $paymentAction = 'pending';
                                                    $paymentFlowData = $flow->flowData;
                                                    if (is_array($paymentFlowData) && isset($paymentFlowData['nodes'])) {
                                                        foreach ($paymentFlowData['nodes'] as $pNode) {
                                                            if (($pNode['type'] ?? '') === 'product_variant_start') {
                                                                $paymentAction = $pNode['data']['paymentAction'] ?? 'pending';
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    $paymentBadgeConfig = match($paymentAction) {
                                                        'accept' => ['bg' => '#10B981', 'icon' => 'bx-check-circle', 'label' => 'Accept Payment'],
                                                        'reject' => ['bg' => '#EF4444', 'icon' => 'bx-x-circle', 'label' => 'Reject Payment'],
                                                        default => ['bg' => '#8B5CF6', 'icon' => 'bx-time-five', 'label' => 'Pending Payment']
                                                    };
                                                @endphp
                                                <span class="badge text-white" style="background-color: {{ $paymentBadgeConfig['bg'] }};">
                                                    <i class="bx {{ $paymentBadgeConfig['icon'] }} me-1"></i>{{ $paymentBadgeConfig['label'] }}
                                                </span>
                                            @elseif($flowType === 'shopping_abandonment')
                                                <span class="badge text-white" style="background-color: #F97316;">
                                                    <i class="bx bx-cart-alt me-1"></i>Shopping Abandonment
                                                </span>
                                            @elseif($flowType === 'special_trigger')
                                                <span class="badge text-white" style="background-color: #06B6D4;">
                                                    <i class="bx bx-purchase-tag-alt me-1"></i>Special Trigger
                                                </span>
                                            @elseif($flowType === 'change_order_status')
                                                <span class="badge text-white" style="background-color: #6366F1;">
                                                    <i class="bx bx-transfer-alt me-1"></i>Order Status Change
                                                </span>
                                            @else
                                                <span class="badge bg-secondary text-white">{{ $flowType }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(in_array($flowType, ['payments', 'shopping_abandonment']))
                                                @php
                                                    $productName = '-';
                                                    $variantName = '';
                                                    $paymentActionForBadge = 'pending';
                                                    $flowData = $flow->flowData;
                                                    if (is_array($flowData) && isset($flowData['nodes'])) {
                                                        foreach ($flowData['nodes'] as $node) {
                                                            if (($node['type'] ?? '') === 'product_variant_start') {
                                                                $productName = $node['data']['productName'] ?? '-';
                                                                $variantName = $node['data']['variantName'] ?? '';
                                                                $paymentActionForBadge = $node['data']['paymentAction'] ?? 'pending';
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    if ($flowType === 'shopping_abandonment') {
                                                        $badgeClass = 'bg-orange';
                                                    } elseif ($flowType === 'payments') {
                                                        $badgeClass = match($paymentActionForBadge) {
                                                            'accept' => 'bg-emerald-500',
                                                            'reject' => 'bg-red-500',
                                                            default => 'bg-purple'
                                                        };
                                                    } else {
                                                        $badgeClass = 'bg-purple';
                                                    }
                                                @endphp
                                                @if($productName !== '-')
                                                    <div class="trigger-product-cell" title="{{ $productName }} - {{ $variantName }}">
                                                        <span class="badge {{ $badgeClass }} text-white">
                                                            <i class="bx bx-package me-1"></i>{{ Str::limit($productName, 15) }}
                                                        </span>
                                                        @if($variantName)
                                                            <br><small class="text-secondary"><i class="bx bx-cube me-1"></i>{{ Str::limit($variantName, 18) }}</small>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-secondary">-</span>
                                                @endif
                                            @elseif($flowType === 'change_order_status')
                                                @php
                                                    $fromStatus = '-';
                                                    $toStatus = '-';
                                                    $productName = '';
                                                    $variantName = '';
                                                    $flowData = $flow->flowData;
                                                    $statusLabels = [
                                                        'any' => 'Any Status',
                                                        'none' => 'None (New)',
                                                        'pending' => 'Pending',
                                                        'processing' => 'Processing',
                                                        'on_hold' => 'On Hold',
                                                        'completed' => 'Completed',
                                                        'cancelled' => 'Cancelled',
                                                        'refunded' => 'Refunded',
                                                        'failed' => 'Failed',
                                                        'shipped' => 'Shipped',
                                                        'delivered' => 'Delivered'
                                                    ];
                                                    if (is_array($flowData) && isset($flowData['nodes'])) {
                                                        foreach ($flowData['nodes'] as $node) {
                                                            if (($node['type'] ?? '') === 'order_status_start') {
                                                                $fromStatus = $statusLabels[$node['data']['fromStatus'] ?? ''] ?? $node['data']['fromStatus'] ?? '-';
                                                                $toStatus = $statusLabels[$node['data']['toStatus'] ?? ''] ?? $node['data']['toStatus'] ?? '-';
                                                                $productName = $node['data']['productName'] ?? '';
                                                                $variantName = $node['data']['variantName'] ?? '';
                                                                break;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                @if($fromStatus !== '-' && $toStatus !== '-')
                                                    <div class="trigger-product-cell" title="From {{ $fromStatus }} to {{ $toStatus }}{{ $productName ? ' | Product: ' . $productName : '' }}">
                                                        <span class="badge text-white" style="background-color: #6366F1;">
                                                            <i class="bx bx-transfer-alt me-1"></i>{{ $fromStatus }}
                                                        </span>
                                                        <i class="bx bx-right-arrow-alt mx-1 text-secondary"></i>
                                                        <span class="badge text-white" style="background-color: #6366F1;">
                                                            {{ $toStatus }}
                                                        </span>
                                                        @if($productName)
                                                            <br><small class="text-secondary"><i class="bx bx-package me-1"></i>{{ Str::limit($productName, 15) }}{{ $variantName ? ' / ' . Str::limit($variantName, 12) : '' }}</small>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-secondary">-</span>
                                                @endif
                                            @elseif($flowType === 'special_trigger')
                                                @php
                                                    $storeName = '-';
                                                    $tagName = '';
                                                    $flowData = $flow->flowData;
                                                    if (is_array($flowData) && isset($flowData['nodes'])) {
                                                        foreach ($flowData['nodes'] as $node) {
                                                            if (($node['type'] ?? '') === 'special_tag_start') {
                                                                $storeName = $node['data']['storeName'] ?? '-';
                                                                $tagName = $node['data']['tagName'] ?? '';
                                                                break;
                                                            }
                                                        }
                                                    }
                                                @endphp
                                                @if($storeName !== '-')
                                                    <div class="trigger-product-cell" title="{{ $storeName }} - {{ $tagName }}">
                                                        <span class="badge text-white" style="background-color: #06B6D4;">
                                                            <i class="bx bx-store me-1"></i>{{ Str::limit($storeName, 15) }}
                                                        </span>
                                                        @if($tagName)
                                                            <br><small class="text-secondary"><i class="bx bx-purchase-tag-alt me-1"></i>{{ Str::limit($tagName, 18) }}</small>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-secondary">-</span>
                                                @endif
                                            @elseif($flow->triggerTag)
                                                <span class="badge bg-info text-white">
                                                    <i class="bx bx-tag me-1"></i>{{ $flow->triggerTag->triggerTagName }}
                                                </span>
                                            @else
                                                <span class="text-secondary">-</span>
                                            @endif
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
                                                <button type="button" class="btn btn-sm btn-outline-secondary preview-btn"
                                                        data-flow-id="{{ $flow->id }}"
                                                        data-flow-name="{{ $flow->flowName }}"
                                                        data-flow-description="{{ $flow->flowDescription }}"
                                                        data-flow-data='@json($flow->flowData)'
                                                        title="Preview Flow">
                                                    <i class="bx bx-show"></i>
                                                </button>
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

<!-- Preview Flow Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title text-dark">
                    <i class="bx bx-git-branch text-primary me-2"></i><span id="previewFlowName">Flow Preview</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Description Section -->
                <div class="p-3 border-bottom bg-light" id="previewDescriptionSection">
                    <label class="form-label text-secondary mb-1"><i class="bx bx-info-circle me-1"></i>Description</label>
                    <p class="text-dark mb-0" id="previewDescription">-</p>
                </div>
                <!-- Canvas Preview -->
                <div class="preview-canvas-container" id="previewCanvasContainer">
                    <div class="preview-canvas" id="previewCanvas"></div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary" id="previewEditBtn">
                    <i class="bx bx-edit me-1"></i>Edit Flow
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Close
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
        order: [[5, 'desc']], // Created column (0: Name, 1: Type, 2: Trigger, 3: Nodes, 4: Status, 5: Created, 6: Actions)
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

    // Preview flow - store data for rendering after modal is shown
    let pendingPreviewData = null;

    $(document).on('click', '.preview-btn', function() {
        const flowId = $(this).data('flow-id');
        const flowName = $(this).data('flow-name');
        const flowDescription = $(this).data('flow-description');
        let flowData = $(this).data('flow-data');

        // Ensure flowData is an object (jQuery may have parsed it already)
        if (typeof flowData === 'string') {
            try {
                flowData = JSON.parse(flowData);
            } catch(e) {
                console.error('Failed to parse flowData:', e);
            }
        }

        $('#previewFlowName').text(flowName);
        $('#previewDescription').text(flowDescription || 'No description provided.');
        $('#previewEditBtn').attr('href', `/ecom-triggers-edit?id=${flowId}`);

        // Store data for rendering after modal is shown
        pendingPreviewData = flowData;

        $('#previewModal').modal('show');
    });

    // Render canvas after modal is fully shown (ensures proper layout)
    $('#previewModal').on('shown.bs.modal', function() {
        if (pendingPreviewData) {
            renderPreviewCanvas(pendingPreviewData);
            pendingPreviewData = null;
        }
    });

    function renderPreviewCanvas(flowData) {
        const $canvas = $('#previewCanvas');
        $canvas.empty();

        if (!flowData || !flowData.nodes || flowData.nodes.length === 0) {
            $canvas.html('<div class="text-center text-secondary py-5"><i class="bx bx-info-circle display-4"></i><p class="mt-3">No nodes in this flow</p></div>');
            return;
        }

        const nodes = flowData.nodes;
        const connections = flowData.connections || [];

        // Node type labels and icons
        const nodeLabels = {
            'trigger_tag': 'Trigger Tag',
            'course_access_start': 'Course Access Start',
            'course_tag_start': 'Trigger Tag',
            'product_variant_start': 'Product & Variant',
            'special_tag_start': 'Special Tag',
            'order_status_start': 'Order Status Change',
            'delay': 'Delay / Wait',
            'schedule': 'Schedule',
            'email': 'Send Email',
            'send_sms': 'Send SMS',
            'send_whatsapp': 'Send WhatsApp',
            'if_else': 'If / Else Condition',
            'y_flow': 'Y Flow Split',
            'course_access': 'Grant Course Access',
            'remove_access': 'Remove Access',
            'add_as_affiliate': 'Add as Affiliate',
            'add_login_access': 'Grant Login Access',
            'course_subscription': 'Course Subscription',
            'flow_action': 'Flow Action',
            'ai_add_referral': 'AI Add Referral'
        };

        const nodeIcons = {
            'trigger_tag': 'bx-tag',
            'course_access_start': 'bx-key',
            'course_tag_start': 'bx-key',
            'product_variant_start': 'bx-package',
            'special_tag_start': 'bx-purchase-tag-alt',
            'order_status_start': 'bx-transfer-alt',
            'delay': 'bx-time-five',
            'schedule': 'bx-calendar',
            'email': 'bx-envelope',
            'send_sms': 'bx-message-rounded-dots',
            'send_whatsapp': 'bxl-whatsapp',
            'if_else': 'bx-git-branch',
            'y_flow': 'bx-git-merge',
            'course_access': 'bx-key',
            'remove_access': 'bx-block',
            'add_as_affiliate': 'bx-user-plus',
            'add_login_access': 'bx-log-in-circle',
            'course_subscription': 'bx-book-open',
            'flow_action': 'bx-git-branch',
            'ai_add_referral': 'bx-bot'
        };

        const startTypes = ['trigger_tag', 'course_access_start', 'course_tag_start', 'product_variant_start', 'special_tag_start', 'order_status_start'];
        const conditionTypes = ['if_else', 'y_flow'];
        const actionTypes = ['email', 'send_sms', 'send_whatsapp', 'course_access', 'remove_access', 'add_as_affiliate', 'add_login_access', 'course_subscription', 'flow_action', 'ai_add_referral'];

        // ============================================
        // AUTO-LAYOUT ALGORITHM
        // Arranges nodes hierarchically with equal spacing
        // ============================================
        const NODE_WIDTH = 200;       // Actual node width
        const NODE_HEIGHT = 120;      // Fixed node height for consistent spacing
        const HORIZONTAL_GAP = 80;    // Gap between nodes horizontally
        const VERTICAL_GAP = 70;      // Gap between levels (space for arrows)
        const ARROW_SPACE = 50;       // Extra space reserved for arrow visibility

        // Build connection maps
        const incomingConnections = {};
        const outgoingConnections = {};
        nodes.forEach(n => {
            incomingConnections[n.id] = [];
            outgoingConnections[n.id] = [];
        });
        connections.forEach(conn => {
            if (incomingConnections[conn.target]) {
                incomingConnections[conn.target].push(conn.source);
            }
            if (outgoingConnections[conn.source]) {
                outgoingConnections[conn.source].push(conn.target);
            }
        });

        // Find start nodes (nodes with no incoming connections, or start type nodes)
        let startNodeIds = nodes
            .filter(n => incomingConnections[n.id].length === 0 || startTypes.includes(n.type))
            .map(n => n.id);

        // If no start nodes found, use the first node
        if (startNodeIds.length === 0 && nodes.length > 0) {
            startNodeIds = [nodes[0].id];
        }

        // Assign levels using BFS (breadth-first search)
        const levels = {};
        const queue = startNodeIds.map(id => ({ id, level: 0 }));
        const visited = new Set();

        while (queue.length > 0) {
            const { id, level } = queue.shift();
            if (visited.has(id)) {
                // If already visited at a lower level, keep the lower level
                if (levels[id] !== undefined && levels[id] <= level) continue;
            }
            visited.add(id);
            levels[id] = level;

            outgoingConnections[id].forEach(targetId => {
                const newLevel = level + 1;
                if (levels[targetId] === undefined || levels[targetId] < newLevel) {
                    queue.push({ id: targetId, level: newLevel });
                }
            });
        }

        // Handle disconnected nodes (place them at level 0)
        nodes.forEach(n => {
            if (levels[n.id] === undefined) {
                levels[n.id] = 0;
            }
        });

        // Group nodes by level
        const nodesByLevel = {};
        nodes.forEach(n => {
            const level = levels[n.id];
            if (!nodesByLevel[level]) nodesByLevel[level] = [];
            nodesByLevel[level].push(n);
        });

        // Sort levels
        const sortedLevels = Object.keys(nodesByLevel).map(Number).sort((a, b) => a - b);
        const numLevels = sortedLevels.length;

        // Calculate max width needed
        let maxLevelWidth = 0;
        sortedLevels.forEach(level => {
            const nodesAtLevel = nodesByLevel[level];
            const levelWidth = nodesAtLevel.length * NODE_WIDTH + (nodesAtLevel.length - 1) * HORIZONTAL_GAP;
            maxLevelWidth = Math.max(maxLevelWidth, levelWidth);
        });

        // Fixed row height = node height + arrow space
        const ROW_HEIGHT = NODE_HEIGHT + VERTICAL_GAP + ARROW_SPACE;

        // Calculate total content dimensions
        const contentWidth = maxLevelWidth;
        const contentHeight = numLevels * ROW_HEIGHT - ARROW_SPACE; // No arrow space after last row

        // Get container dimensions for centering
        const containerWidth = $('#previewCanvasContainer').width() || 800;
        const containerHeight = $('#previewCanvasContainer').height() || 450;

        // Calculate offsets to center the content
        const offsetX = Math.max(40, (containerWidth - contentWidth) / 2);
        const offsetY = Math.max(40, (containerHeight - contentHeight) / 2);

        // Calculate layout positions for each node
        const layoutPositions = {};

        sortedLevels.forEach((level, levelIndex) => {
            const nodesAtLevel = nodesByLevel[level];
            const levelWidth = nodesAtLevel.length * NODE_WIDTH + (nodesAtLevel.length - 1) * HORIZONTAL_GAP;
            const levelOffsetX = (maxLevelWidth - levelWidth) / 2; // Center nodes within level

            // Sort nodes at this level by their connection order for consistency
            nodesAtLevel.sort((a, b) => {
                const aParents = incomingConnections[a.id];
                const bParents = incomingConnections[b.id];
                if (aParents.length > 0 && bParents.length > 0) {
                    const aParentX = layoutPositions[aParents[0]]?.x || 0;
                    const bParentX = layoutPositions[bParents[0]]?.x || 0;
                    return aParentX - bParentX;
                }
                return 0;
            });

            nodesAtLevel.forEach((node, nodeIndex) => {
                layoutPositions[node.id] = {
                    x: offsetX + levelOffsetX + nodeIndex * (NODE_WIDTH + HORIZONTAL_GAP),
                    y: offsetY + levelIndex * ROW_HEIGHT
                };
            });
        });

        // Calculate canvas dimensions
        const canvasWidth = Math.max(contentWidth + offsetX * 2, containerWidth);
        const canvasHeight = Math.max(contentHeight + offsetY * 2, containerHeight);

        $canvas.css({
            'min-width': Math.max(canvasWidth, 400) + 'px',
            'min-height': Math.max(canvasHeight, 300) + 'px'
        });

        // Create SVG for connections
        const svgNS = "http://www.w3.org/2000/svg";
        const svg = document.createElementNS(svgNS, "svg");
        const svgWidth = Math.max(canvasWidth, 400);
        const svgHeight = Math.max(canvasHeight, 300);
        svg.setAttribute("class", "preview-svg");
        svg.setAttribute("width", svgWidth);
        svg.setAttribute("height", svgHeight);
        svg.setAttribute("viewBox", `0 0 ${svgWidth} ${svgHeight}`);
        svg.style.cssText = `position: absolute; top: 0; left: 0; z-index: 5; overflow: visible; pointer-events: none;`;

        // Add arrow marker definitions with unique IDs
        const markerId = Date.now();
        const defs = document.createElementNS(svgNS, "defs");

        const createMarker = (id, color) => {
            const marker = document.createElementNS(svgNS, "marker");
            marker.setAttribute("id", id);
            marker.setAttribute("markerWidth", "14");
            marker.setAttribute("markerHeight", "14");
            marker.setAttribute("refX", "12");
            marker.setAttribute("refY", "7");
            marker.setAttribute("orient", "auto");
            marker.setAttribute("markerUnits", "userSpaceOnUse");
            const poly = document.createElementNS(svgNS, "polygon");
            poly.setAttribute("points", "0,0 14,7 0,14 3,7");
            poly.setAttribute("fill", color);
            marker.appendChild(poly);
            return marker;
        };

        const grayMarkerId = `arrow-gray-${markerId}`;
        const greenMarkerId = `arrow-green-${markerId}`;
        const redMarkerId = `arrow-red-${markerId}`;

        defs.appendChild(createMarker(grayMarkerId, "#64748b"));
        defs.appendChild(createMarker(greenMarkerId, "#10B981"));
        defs.appendChild(createMarker(redMarkerId, "#EF4444"));
        svg.appendChild(defs);

        // Draw connections
        if (connections && connections.length > 0) {
            connections.forEach((conn, idx) => {
                const fromNode = nodes.find(n => n.id === conn.source);
                const toNode = nodes.find(n => n.id === conn.target);

                if (fromNode && toNode && layoutPositions[fromNode.id] && layoutPositions[toNode.id]) {
                    const fromPos = layoutPositions[fromNode.id];
                    const toPos = layoutPositions[toNode.id];

                    // Use fixed NODE_HEIGHT for consistent arrow placement
                    const x1 = fromPos.x + (NODE_WIDTH / 2);
                    const y1 = fromPos.y + NODE_HEIGHT;
                    const x2 = toPos.x + (NODE_WIDTH / 2);
                    const y2 = toPos.y;

                    // Determine color based on connection type
                    let strokeColor = '#64748b';
                    let arrowId = grayMarkerId;
                    if (conn.type === 'yes') {
                        strokeColor = '#10B981';
                        arrowId = greenMarkerId;
                    } else if (conn.type === 'no') {
                        strokeColor = '#EF4444';
                        arrowId = redMarkerId;
                    } else if (conn.type === 'path_a' || conn.type === 'path_b') {
                        strokeColor = '#8B5CF6';
                        arrowId = grayMarkerId;
                    }

                    // Create smooth bezier path
                    const path = document.createElementNS(svgNS, "path");
                    const deltaX = Math.abs(x2 - x1);
                    const midY = (y1 + y2) / 2;

                    let d;
                    if (deltaX < 20) {
                        // Nearly straight vertical - gentle S-curve
                        const ctrlOffset = (y2 - y1) * 0.3;
                        d = `M${x1},${y1} C${x1},${y1 + ctrlOffset} ${x2},${y2 - ctrlOffset} ${x2},${y2}`;
                    } else {
                        // Horizontal offset - smooth curve through midpoint
                        d = `M${x1},${y1} C${x1},${midY} ${x2},${midY} ${x2},${y2}`;
                    }

                    path.setAttribute("d", d);
                    path.setAttribute("fill", "none");
                    path.setAttribute("stroke", strokeColor);
                    path.setAttribute("stroke-width", "2");
                    path.setAttribute("stroke-linecap", "round");
                    path.setAttribute("marker-end", `url(#${arrowId})`);

                    svg.appendChild(path);
                }
            });
        }

        // Append SVG first (behind nodes) - use native DOM method for SVG
        $canvas[0].appendChild(svg);

        // Render nodes using auto-layout positions
        nodes.forEach(node => {
            const isStart = startTypes.includes(node.type);
            const isCondition = conditionTypes.includes(node.type);
            const isAction = actionTypes.includes(node.type);
            const label = nodeLabels[node.type] || node.type;
            const icon = nodeIcons[node.type] || 'bx-cube';

            let nodeClass = 'preview-node';
            if (isStart) nodeClass += ' start-node';
            else if (isCondition) nodeClass += ' condition-node';
            else if (isAction) nodeClass += ' action-node';

            // Use auto-layout position
            const pos = layoutPositions[node.id];
            if (!pos) return; // Skip if position not calculated

            const x = pos.x;
            const y = pos.y;

            let bodyContent = getPreviewBodyContent(node);
            let outputLabels = '';

            // Add output labels for condition nodes
            if (node.type === 'if_else') {
                outputLabels = '<div class="output-labels"><span class="yes-label">YES</span><span class="no-label">NO</span></div>';
            } else if (node.type === 'y_flow') {
                outputLabels = '<div class="output-labels"><span class="path-label">Path A</span><span class="path-label">Path B</span></div>';
            }

            const nodeHtml = `
                <div class="${nodeClass}" style="left: ${x}px; top: ${y}px;" data-node-id="${node.id}">
                    <div class="preview-node-header">
                        <div class="node-icon icon-${node.type}">
                            <i class="bx ${icon}"></i>
                        </div>
                        <span class="node-title">${label}</span>
                    </div>
                    <div class="preview-node-body">${bodyContent}</div>
                    ${outputLabels}
                </div>
            `;
            $canvas.append(nodeHtml);
        });
    }

    function getPreviewBodyContent(node) {
        const escapeHtml = (str) => {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        };

        const truncate = (str, len = 18) => {
            if (!str) return '';
            return str.length > len ? str.substring(0, len) + '...' : str;
        };

        const formatTime12h = (time24) => {
            if (!time24) return '';
            const [hours, minutes] = time24.split(':');
            let h = parseInt(hours, 10);
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            return `${h}:${minutes} ${ampm}`;
        };

        const statusLabels = {
            'any': 'Any Status',
            'none': 'None (New Order)',
            'pending': 'Pending',
            'processing': 'Processing',
            'on_hold': 'On Hold',
            'completed': 'Completed',
            'cancelled': 'Cancelled',
            'refunded': 'Refunded',
            'failed': 'Failed',
            'shipped': 'Shipped',
            'delivered': 'Delivered'
        };

        const conditionLabels = {
            'has_tag': 'Has Tag',
            'not_has_tag': 'Does Not Have Tag',
            'store_in_order': 'Store in Order',
            'product_in_order': 'Product in Order',
            'order_total': 'Order Total',
            'has_course_access': 'Has Course Access',
            'is_affiliate': 'Is Affiliate',
            'client_province': 'Client Province',
            'payment_method': 'Payment Method',
            'has_discount': 'Has Discount'
        };

        switch(node.type) {
            case 'trigger_tag':
                if (node.data.tagName) {
                    return `<div class="detail-row"><span class="detail-label">Tag:</span><span class="detail-value" title="${escapeHtml(node.data.tagName)}">${escapeHtml(truncate(node.data.tagName))}</span></div>`;
                }
                return '<span class="text-muted">Not configured</span>';

            case 'course_access_start':
            case 'course_tag_start':
                if (node.data.tagName) {
                    return `<div class="detail-row"><span class="detail-label">Tag:</span><span class="detail-value" title="${escapeHtml(node.data.tagName)}">${escapeHtml(truncate(node.data.tagName))}</span></div>`;
                }
                return '<span class="text-muted">Not configured</span>';

            case 'product_variant_start':
                if (node.data.productName) {
                    let html = `<div class="detail-row"><span class="detail-label">Prod:</span><span class="detail-value" title="${escapeHtml(node.data.productName)}">${escapeHtml(truncate(node.data.productName, 16))}</span></div>`;
                    if (node.data.variantName) {
                        html += `<div class="detail-row"><span class="detail-label">Var:</span><span class="detail-value" title="${escapeHtml(node.data.variantName)}">${escapeHtml(truncate(node.data.variantName, 16))}</span></div>`;
                    }
                    if (node.data.paymentAction) {
                        const actionLabels = { 'pending': 'Pending', 'accept': 'Accept', 'reject': 'Reject' };
                        html += `<div class="detail-row"><span class="detail-label">Act:</span><span class="detail-value">${actionLabels[node.data.paymentAction] || node.data.paymentAction}</span></div>`;
                    }
                    return html;
                }
                return '<span class="text-muted">Not configured</span>';

            case 'order_status_start':
                if (node.data.fromStatus && node.data.toStatus) {
                    const fromLabel = statusLabels[node.data.fromStatus] || node.data.fromStatus;
                    const toLabel = statusLabels[node.data.toStatus] || node.data.toStatus;
                    let html = `<div class="detail-row"><span class="detail-label">From:</span><span class="detail-value status-badge" style="background:#fee2e2;color:#991b1b;" title="${fromLabel}">${truncate(fromLabel, 12)}</span></div>`;
                    html += `<div class="detail-row"><span class="detail-label">To:</span><span class="detail-value status-badge" style="background:#dcfce7;color:#166534;" title="${toLabel}">${truncate(toLabel, 12)}</span></div>`;
                    return html;
                }
                return '<span class="text-muted">Not configured</span>';

            case 'special_tag_start':
                if (node.data.storeName) {
                    let html = `<div class="detail-row"><span class="detail-label">Store:</span><span class="detail-value" title="${escapeHtml(node.data.storeName)}">${escapeHtml(truncate(node.data.storeName))}</span></div>`;
                    if (node.data.tagName) {
                        html += `<div class="detail-row"><span class="detail-label">Tag:</span><span class="detail-value" title="${escapeHtml(node.data.tagName)}">${escapeHtml(truncate(node.data.tagName))}</span></div>`;
                    }
                    return html;
                }
                return '<span class="text-muted">Not configured</span>';

            case 'delay':
                let delayText = `${node.data.delayValue || 1} ${node.data.delayType || 'days'}`;
                let delayHtml = `<div class="detail-row"><span class="detail-label">Wait:</span><span class="detail-value">${delayText}</span></div>`;
                if (node.data.delayType === 'days' && node.data.delayTime) {
                    delayHtml += `<div class="detail-row"><span class="detail-label">At:</span><span class="detail-value">${formatTime12h(node.data.delayTime)}</span></div>`;
                }
                return delayHtml;

            case 'schedule':
                if (node.data.scheduleDate) {
                    const schedTime = node.data.scheduleTime ? formatTime12h(node.data.scheduleTime) : '';
                    const fullDateTime = `${node.data.scheduleDate}${schedTime ? ' ' + schedTime : ''}`;
                    return `<div class="detail-row"><span class="detail-label">Date:</span><span class="detail-value" title="${fullDateTime}">${truncate(node.data.scheduleDate, 12)}</span></div>${schedTime ? `<div class="detail-row"><span class="detail-label">Time:</span><span class="detail-value">${schedTime}</span></div>` : ''}`;
                }
                return '<span class="text-muted">Not scheduled</span>';

            case 'email':
                if (node.data.subject) {
                    return `<div class="detail-row"><span class="detail-label">Subj:</span><span class="detail-value" title="${escapeHtml(node.data.subject)}">${escapeHtml(truncate(node.data.subject, 20))}</span></div>`;
                }
                return '<span class="text-muted">No email</span>';

            case 'send_sms':
                if (node.data.message) {
                    return `<div class="detail-row"><span class="detail-label">Msg:</span><span class="detail-value" title="${escapeHtml(node.data.message)}">${escapeHtml(truncate(node.data.message, 22))}</span></div>`;
                }
                return '<span class="text-muted">No SMS</span>';

            case 'send_whatsapp':
                if (node.data.message) {
                    return `<div class="detail-row"><span class="detail-label">Msg:</span><span class="detail-value" title="${escapeHtml(node.data.message)}">${escapeHtml(truncate(node.data.message, 22))}</span></div>`;
                }
                return '<span class="text-muted">No message</span>';

            case 'if_else':
                const condType = conditionLabels[node.data.conditionType] || node.data.conditionType || 'Condition';
                let ifHtml = `<div class="detail-row"><span class="detail-label">Check:</span><span class="detail-value" title="${condType}">${truncate(condType, 14)}</span></div>`;
                if (node.data.conditionValueLabel || node.data.tagName || node.data.storeName) {
                    const val = node.data.conditionValueLabel || node.data.tagName || node.data.storeName || '';
                    ifHtml += `<div class="detail-row"><span class="detail-label">Val:</span><span class="detail-value" title="${escapeHtml(val)}">${escapeHtml(truncate(val, 16))}</span></div>`;
                }
                return ifHtml;

            case 'y_flow':
                return '<div class="detail-row"><span class="detail-value">Split into 2 paths</span></div>';

            case 'course_access':
                if (node.data.tagName) {
                    return `<div class="detail-row"><span class="detail-label">Grant:</span><span class="detail-value" title="${escapeHtml(node.data.tagName)}">${escapeHtml(truncate(node.data.tagName))}</span></div>`;
                }
                return '<span class="text-muted">Not configured</span>';

            case 'remove_access':
                if (node.data.tagName) {
                    return `<div class="detail-row"><span class="detail-label">Remove:</span><span class="detail-value" title="${escapeHtml(node.data.tagName)}">${escapeHtml(truncate(node.data.tagName))}</span></div>`;
                }
                return '<span class="text-muted">Not configured</span>';

            case 'add_as_affiliate':
                if (node.data.storeName) {
                    let html = `<div class="detail-row"><span class="detail-label">Store:</span><span class="detail-value" title="${escapeHtml(node.data.storeName)}">${escapeHtml(truncate(node.data.storeName))}</span></div>`;
                    html += `<div class="detail-row"><span class="detail-label">Rate:</span><span class="detail-value">${node.data.commissionRate || 10}%</span></div>`;
                    return html;
                }
                return '<span class="text-muted">Not configured</span>';

            case 'add_login_access':
                if (node.data.storeName) {
                    return `<div class="detail-row"><span class="detail-label">Store:</span><span class="detail-value" title="${escapeHtml(node.data.storeName)}">${escapeHtml(truncate(node.data.storeName))}</span></div>`;
                }
                return '<span class="text-muted">Not configured</span>';

            case 'course_subscription':
                if (node.data.courseName) {
                    const actionLabel = node.data.action === 'add' ? 'Subscribe' : 'Unsub';
                    let html = `<div class="detail-row"><span class="detail-label">Act:</span><span class="detail-value">${actionLabel}</span></div>`;
                    html += `<div class="detail-row"><span class="detail-label">Course:</span><span class="detail-value" title="${escapeHtml(node.data.courseName)}">${escapeHtml(truncate(node.data.courseName, 14))}</span></div>`;
                    return html;
                }
                return '<span class="text-muted">Not configured</span>';

            case 'flow_action':
                if (node.data.flowName) {
                    const actionLabel = node.data.action === 'add' ? 'Add' : 'Remove';
                    let html = `<div class="detail-row"><span class="detail-label">Act:</span><span class="detail-value">${actionLabel}</span></div>`;
                    html += `<div class="detail-row"><span class="detail-label">Flow:</span><span class="detail-value" title="${escapeHtml(node.data.flowName)}">${escapeHtml(truncate(node.data.flowName))}</span></div>`;
                    return html;
                }
                return '<span class="text-muted">Not configured</span>';

            case 'ai_add_referral':
                if (node.data.affiliateName) {
                    return `<div class="detail-row"><span class="detail-label">Aff:</span><span class="detail-value" title="${escapeHtml(node.data.affiliateName)}">${escapeHtml(truncate(node.data.affiliateName))}</span></div>`;
                }
                return '<div class="detail-row"><span class="detail-value">AI assigns referral</span></div>';

            default:
                return '<span class="text-muted">-</span>';
        }
    }
});
</script>
@endsection
