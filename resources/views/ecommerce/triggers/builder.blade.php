@extends('layouts.master')

@section('title') {{ isset($flow) ? 'Edit' : 'Create' }} Trigger Flow @endsection

@section('css')
<!-- Toastr CSS -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .flow-builder-container {
        display: flex;
        gap: 1rem;
        min-height: 600px;
    }

    /* Sidebar with draggable elements */
    .flow-sidebar {
        width: 280px;
        flex-shrink: 0;
    }

    .flow-element {
        padding: 0.75rem 1rem;
        margin-bottom: 0.5rem;
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 0.5rem;
        cursor: grab;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .flow-element:hover {
        border-color: #556ee6;
        box-shadow: 0 2px 8px rgba(85, 110, 230, 0.15);
    }

    .flow-element:active {
        cursor: grabbing;
    }

    .flow-element.dragging {
        opacity: 0.5;
    }

    .flow-element-icon {
        width: 36px;
        height: 36px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .flow-element-info h6 {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .flow-element-info small {
        color: #6c757d;
        font-size: 0.75rem;
    }

    /* Canvas area */
    .flow-canvas-wrapper {
        flex-grow: 1;
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 0.5rem;
        position: relative;
        overflow: auto;
        min-height: 500px;
    }

    .flow-canvas {
        min-width: 100%;
        min-height: 500px;
        padding: 2rem;
        position: relative;
    }

    .flow-canvas.drag-over {
        background: #e3e8f0;
        border-color: #556ee6;
    }

    /* Flow nodes on canvas */
    .flow-node {
        position: absolute;
        min-width: 220px;
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 0.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        cursor: move;
        z-index: 10;
    }

    .flow-node.selected {
        border-color: #556ee6;
        box-shadow: 0 0 0 3px rgba(85, 110, 230, 0.25);
    }

    .flow-node-header {
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid #e9ecef;
        border-radius: 0.5rem 0.5rem 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .flow-node-header .node-icon {
        width: 28px;
        height: 28px;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .flow-node-header .node-title {
        flex-grow: 1;
        font-size: 0.8125rem;
        font-weight: 600;
        margin: 0;
    }

    .flow-node-header .node-actions {
        display: flex;
        gap: 0.25rem;
    }

    .flow-node-header .node-actions button {
        padding: 0.125rem 0.375rem;
        font-size: 0.75rem;
    }

    .flow-node-body {
        padding: 0.75rem;
        font-size: 0.8125rem;
    }

    .flow-node-connector {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #556ee6;
        border: 2px solid #fff;
        position: absolute;
        cursor: crosshair;
        z-index: 20;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .flow-node-connector.output {
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
    }

    .flow-node-connector.input {
        top: -8px;
        left: 50%;
        transform: translateX(-50%);
    }

    .flow-node-connector.output-left {
        bottom: -8px;
        left: 30%;
    }

    .flow-node-connector.output-right {
        bottom: -8px;
        right: 30%;
        left: auto;
    }

    /* Start node special styling */
    .flow-node.start-node {
        border-color: #34c38f;
    }

    .flow-node.start-node .flow-node-header {
        background: linear-gradient(135deg, #34c38f 0%, #28a879 100%);
        color: #fff;
    }

    /* Connection lines */
    .flow-connection {
        position: absolute;
        pointer-events: none;
        z-index: 5;
    }

    .flow-connection line {
        stroke: #556ee6;
        stroke-width: 2;
    }

    .flow-connection .arrow {
        fill: #556ee6;
    }

    /* Empty state */
    .flow-canvas-empty {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        color: #6c757d;
    }

    .flow-canvas-empty i {
        font-size: 4rem;
        opacity: 0.3;
    }

    /* Node type colors */
    .node-type-trigger_tag .node-icon { background: #34c38f; color: #fff; }
    .node-type-course_access_start .node-icon { background: #f1b44c; color: #fff; }
    .node-type-delay .node-icon { background: #f1b44c; color: #fff; }
    .node-type-schedule .node-icon { background: #50a5f1; color: #fff; }
    .node-type-email .node-icon { background: #556ee6; color: #fff; }
    .node-type-send_sms .node-icon { background: #34c38f; color: #fff; }
    .node-type-send_whatsapp .node-icon { background: #25D366; color: #fff; }
    .node-type-y_flow .node-icon { background: #f46a6a; color: #fff; }
    .node-type-if_else .node-icon { background: #0891B2; color: #fff; }
    .node-type-course_access .node-icon { background: #74788d; color: #fff; }
    .node-type-remove_access .node-icon { background: #343a40; color: #fff; }
    .node-type-ai_add_referral .node-icon { background: #8B5CF6; color: #fff; }
    .node-type-add_as_affiliate .node-icon { background: #F59E0B; color: #fff; }
    .node-type-course_subscription .node-icon { background: #EC4899; color: #fff; }

    /* Expiration flow start node styling */
    .flow-node.expiration-start-node {
        border-color: #f1b44c;
    }

    .flow-node.expiration-start-node .flow-node-header {
        background: linear-gradient(135deg, #f1b44c 0%, #e0a800 100%);
        color: #fff;
    }

    /* Hide elements based on flow type */
    .flow-element-trigger-only.d-none { display: none !important; }

    /* Properties panel as floating overlay */
    .properties-panel {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.8);
        width: 360px;
        max-width: 90%;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 0.75rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        opacity: 0;
        visibility: hidden;
        transition: transform 0.25s ease, opacity 0.25s ease, visibility 0.25s;
        z-index: 100;
    }

    .properties-panel.active {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
        visibility: visible;
    }

    .properties-panel-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
        border-radius: 0.75rem 0.75rem 0 0;
    }

    .properties-panel-body {
        padding: 1.25rem;
        max-height: 350px;
        overflow-y: auto;
    }

    /* Overlay backdrop when properties panel is open */
    .properties-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.3);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.25s ease, visibility 0.25s;
        z-index: 99;
    }

    .properties-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    /* Node drop animation */
    .flow-node {
        animation: nodeDropIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .flow-node.node-updating {
        animation: nodePulse 0.3s ease;
    }

    @keyframes nodeDropIn {
        0% {
            opacity: 0;
            transform: scale(0.5) translateY(-20px);
        }
        100% {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    @keyframes nodePulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); box-shadow: 0 4px 15px rgba(85, 110, 230, 0.3); }
        100% { transform: scale(1); }
    }

    /* Merge tags dropdown */
    .merge-tag-list {
        max-height: 200px;
        overflow-y: auto;
    }

    .merge-tag-item {
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 0.25rem;
        transition: background 0.2s ease;
    }

    .merge-tag-item:hover {
        background: #f3f6f9;
    }

    .merge-tag-item code {
        font-size: 0.75rem;
    }

    /* Email HTML Builder */
    .email-visual-editor {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-size: 14px;
        line-height: 1.6;
    }

    .email-visual-editor:focus {
        outline: none;
        border-color: #556ee6 !important;
    }

    .email-visual-editor img {
        max-width: 100%;
        height: auto;
    }

    .email-toolbar .btn {
        border-color: #ced4da;
    }

    .email-toolbar .btn:hover {
        background-color: #e9ecef;
    }

    #emailHtmlEditor {
        font-family: 'Courier New', Consolas, monospace;
        resize: vertical;
    }

    /* Flow Type Card Selector */
    .flow-type-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .flow-type-card {
        flex: 1;
        min-width: 180px;
        max-width: 220px;
        padding: 1.25rem;
        border: 2px solid #e9ecef;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #fff;
        text-align: center;
    }

    .flow-type-card:hover:not(.disabled) {
        border-color: #556ee6;
        box-shadow: 0 4px 12px rgba(85, 110, 230, 0.15);
        transform: translateY(-2px);
    }

    .flow-type-card.selected {
        border-color: #556ee6;
        background: linear-gradient(135deg, rgba(85, 110, 230, 0.05) 0%, rgba(85, 110, 230, 0.1) 100%);
        box-shadow: 0 4px 12px rgba(85, 110, 230, 0.2);
    }

    .flow-type-card.disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .flow-type-card-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin: 0 auto 0.75rem;
    }

    .flow-type-card h6 {
        font-size: 0.9375rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .flow-type-card small {
        font-size: 0.75rem;
        line-height: 1.4;
    }

    /* Flow type specific colors */
    .flow-type-card[data-type="trigger"] .flow-type-card-icon { background: rgba(52, 195, 143, 0.15); color: #34c38f; }
    .flow-type-card[data-type="expiration"] .flow-type-card-icon { background: rgba(241, 180, 76, 0.15); color: #f1b44c; }
    .flow-type-card[data-type="order_not_complete"] .flow-type-card-icon { background: rgba(244, 106, 106, 0.15); color: #f46a6a; }
    .flow-type-card[data-type="shipping_complete"] .flow-type-card-icon { background: rgba(80, 165, 241, 0.15); color: #50a5f1; }
    .flow-type-card[data-type="affiliate_earning"] .flow-type-card-icon { background: rgba(85, 110, 230, 0.15); color: #556ee6; }

    .flow-type-card.selected[data-type="trigger"] { border-color: #34c38f; }
    .flow-type-card.selected[data-type="expiration"] { border-color: #f1b44c; }
    .flow-type-card.selected[data-type="order_not_complete"] { border-color: #f46a6a; }
    .flow-type-card.selected[data-type="shipping_complete"] { border-color: #50a5f1; }
    .flow-type-card.selected[data-type="affiliate_earning"] { border-color: #556ee6; }

    /* Start node styling for new flow types */
    .flow-node.order-start-node { border-color: #f46a6a; }
    .flow-node.order-start-node .flow-node-header {
        background: linear-gradient(135deg, #f46a6a 0%, #dc3545 100%);
        color: #fff;
    }

    .flow-node.shipping-start-node { border-color: #50a5f1; }
    .flow-node.shipping-start-node .flow-node-header {
        background: linear-gradient(135deg, #50a5f1 0%, #3498db 100%);
        color: #fff;
    }

    .flow-node.affiliate-start-node { border-color: #556ee6; }
    .flow-node.affiliate-start-node .flow-node-header {
        background: linear-gradient(135deg, #556ee6 0%, #4458cb 100%);
        color: #fff;
    }

    .node-type-course_tag_start .node-icon { background: #556ee6; color: #fff; }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') Trigger Flows @endslot
@slot('title') {{ isset($flow) ? 'Edit' : 'Create' }} Flow @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Flow Type Selection -->
                <div class="row mb-4">
                    <div class="col-12">
                        <label class="form-label text-dark">Flow Type <span class="text-danger">*</span></label>
                        <div class="flow-type-cards" id="flowTypeCards">
                            <div class="flow-type-card {{ (!isset($flow) || ($flow->flowType ?? 'trigger') === 'trigger') ? 'selected' : '' }} {{ isset($flow) ? 'disabled' : '' }}"
                                 data-type="trigger">
                                <div class="flow-type-card-icon">
                                    <i class="bx bx-play-circle"></i>
                                </div>
                                <h6 class="text-dark">Trigger Flow</h6>
                                <small class="text-secondary">When a purchase tag is applied</small>
                            </div>
                            <div class="flow-type-card {{ (isset($flow) && ($flow->flowType ?? '') === 'expiration') ? 'selected' : '' }} {{ isset($flow) ? 'disabled' : '' }}"
                                 data-type="expiration">
                                <div class="flow-type-card-icon">
                                    <i class="bx bx-time-five"></i>
                                </div>
                                <h6 class="text-dark">Expiration Flow</h6>
                                <small class="text-secondary">When course access expires</small>
                            </div>
                            <div class="flow-type-card {{ (isset($flow) && ($flow->flowType ?? '') === 'order_not_complete') ? 'selected' : '' }} {{ isset($flow) ? 'disabled' : '' }}"
                                 data-type="order_not_complete">
                                <div class="flow-type-card-icon">
                                    <i class="bx bx-error-circle"></i>
                                </div>
                                <h6 class="text-dark">Order Not Complete</h6>
                                <small class="text-secondary">When an order is abandoned</small>
                            </div>
                            <div class="flow-type-card {{ (isset($flow) && ($flow->flowType ?? '') === 'shipping_complete') ? 'selected' : '' }} {{ isset($flow) ? 'disabled' : '' }}"
                                 data-type="shipping_complete">
                                <div class="flow-type-card-icon">
                                    <i class="bx bx-package"></i>
                                </div>
                                <h6 class="text-dark">Shipping Complete</h6>
                                <small class="text-secondary">When shipping is delivered</small>
                            </div>
                            <div class="flow-type-card {{ (isset($flow) && ($flow->flowType ?? '') === 'affiliate_earning') ? 'selected' : '' }} {{ isset($flow) ? 'disabled' : '' }}"
                                 data-type="affiliate_earning">
                                <div class="flow-type-card-icon">
                                    <i class="bx bx-dollar-circle"></i>
                                </div>
                                <h6 class="text-dark">Affiliate Earning</h6>
                                <small class="text-secondary">When affiliate earns commission</small>
                            </div>
                        </div>
                        @if(isset($flow))
                            <small class="text-secondary mt-2 d-block"><i class="bx bx-info-circle me-1"></i>Flow type cannot be changed after creation.</small>
                        @endif
                    </div>
                </div>

                <!-- Flow Info Section -->
                <div class="row mb-4">
                    <div class="col-md-5">
                        <label for="flowName" class="form-label text-dark">Flow Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="flowName" name="flowName"
                               placeholder="Enter flow name" value="{{ $flow->flowName ?? '' }}" required>
                    </div>
                    <div class="col-md-5">
                        <label for="flowDescription" class="form-label text-dark">Description</label>
                        <input type="text" class="form-control" id="flowDescription" name="flowDescription"
                               placeholder="Optional description" value="{{ $flow->flowDescription ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-dark">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="flowStatus" name="flowStatus"
                                   style="width: 3rem; height: 1.5rem;"
                                   {{ (isset($flow) && $flow->isActive) || !isset($flow) ? 'checked' : '' }}>
                            <label class="form-check-label ms-2 text-dark" for="flowStatus" id="flowStatusLabel">
                                @if(isset($flow))
                                    {{ $flow->isActive ? 'Active' : 'Inactive' }}
                                @else
                                    Active
                                @endif
                            </label>
                        </div>
                    </div>
                </div>

                <hr class="mb-4">

                <!-- Flow Builder -->
                <div class="flow-builder-container">
                    <!-- Left Sidebar: Draggable Elements -->
                    <div class="flow-sidebar">
                        <h6 class="text-dark mb-3"><i class="bx bx-cube me-1"></i>Flow Elements</h6>
                        <p class="text-secondary small mb-3">Drag elements to the canvas to build your flow.</p>

                        <div class="flow-element" draggable="true" data-node-type="delay">
                            <div class="flow-element-icon bg-warning text-white">
                                <i class="bx bx-time"></i>
                            </div>
                            <div class="flow-element-info">
                                <h6 class="text-dark">Delay</h6>
                                <small>Wait for days or minutes</small>
                            </div>
                        </div>

                        <div class="flow-element" draggable="true" data-node-type="schedule">
                            <div class="flow-element-icon bg-info text-white">
                                <i class="bx bx-calendar"></i>
                            </div>
                            <div class="flow-element-info">
                                <h6 class="text-dark">Schedule</h6>
                                <small>Execute at specific time</small>
                            </div>
                        </div>

                        <div class="flow-element" draggable="true" data-node-type="email">
                            <div class="flow-element-icon bg-primary text-white">
                                <i class="bx bx-envelope"></i>
                            </div>
                            <div class="flow-element-info">
                                <h6 class="text-dark">Email</h6>
                                <small>Send email with merge tags</small>
                            </div>
                        </div>

                        <div class="flow-element" draggable="true" data-node-type="send_sms">
                            <div class="flow-element-icon bg-success text-white">
                                <i class="bx bx-message-rounded-dots"></i>
                            </div>
                            <div class="flow-element-info">
                                <h6 class="text-dark">Send SMS</h6>
                                <small>Send SMS with merge tags</small>
                            </div>
                        </div>

                        <div class="flow-element" draggable="true" data-node-type="send_whatsapp">
                            <div class="flow-element-icon text-white" style="background-color: #25D366;">
                                <i class="bx bxl-whatsapp"></i>
                            </div>
                            <div class="flow-element-info">
                                <h6 class="text-dark">Send WhatsApp</h6>
                                <small>Send WhatsApp message</small>
                            </div>
                        </div>

                        <div class="flow-element" draggable="true" data-node-type="y_flow">
                            <div class="flow-element-icon bg-danger text-white">
                                <i class="bx bx-git-branch"></i>
                            </div>
                            <div class="flow-element-info">
                                <h6 class="text-dark">Y-Flow Split</h6>
                                <small>Divide into two paths</small>
                            </div>
                        </div>

                        <div class="flow-element" draggable="true" data-node-type="if_else">
                            <div class="flow-element-icon text-white" style="background-color: #0891B2;">
                                <i class="bx bx-git-compare"></i>
                            </div>
                            <div class="flow-element-info">
                                <h6 class="text-dark">If / Else</h6>
                                <small>Conditional branching</small>
                            </div>
                        </div>

                        <!-- Course Access: Only for Trigger Flow -->
                        <div class="flow-element flow-element-trigger-only" draggable="true" data-node-type="course_access">
                            <div class="flow-element-icon bg-secondary text-white">
                                <i class="bx bx-key"></i>
                            </div>
                            <div class="flow-element-info">
                                <h6 class="text-dark">Course Access</h6>
                                <small>Grant course access tag</small>
                            </div>
                        </div>

                        <!-- Remove Access: For both flows -->
                        <div class="flow-element" draggable="true" data-node-type="remove_access">
                            <div class="flow-element-icon bg-dark text-white">
                                <i class="bx bx-block"></i>
                            </div>
                            <div class="flow-element-info">
                                <h6 class="text-dark">Remove Access</h6>
                                <small>Remove course access</small>
                            </div>
                        </div>

                        <!-- AI Add to Referral -->
                        <div class="flow-element" draggable="true" data-node-type="ai_add_referral">
                            <div class="flow-element-icon text-white" style="background-color: #8B5CF6;">
                                <i class="bx bx-bot"></i>
                            </div>
                            <div class="flow-element-info">
                                <h6 class="text-dark">AI Add to Referral</h6>
                                <small>AI-powered referral assignment</small>
                            </div>
                        </div>

                        <!-- Add as Affiliate -->
                        <div class="flow-element" draggable="true" data-node-type="add_as_affiliate">
                            <div class="flow-element-icon text-white" style="background-color: #F59E0B;">
                                <i class="bx bx-user-plus"></i>
                            </div>
                            <div class="flow-element-info">
                                <h6 class="text-dark">Add as Affiliate</h6>
                                <small>Register as affiliate</small>
                            </div>
                        </div>

                        <!-- Course Subscription -->
                        <div class="flow-element" draggable="true" data-node-type="course_subscription">
                            <div class="flow-element-icon text-white" style="background-color: #EC4899;">
                                <i class="bx bx-book-reader"></i>
                            </div>
                            <div class="flow-element-info">
                                <h6 class="text-dark">Course Subscription</h6>
                                <small>Add/Remove course access</small>
                            </div>
                        </div>
                    </div>

                    <!-- Canvas Area -->
                    <div class="flow-canvas-wrapper">
                        <div class="flow-canvas" id="flowCanvas">
                            <svg class="flow-connections" id="flowConnections" style="position: absolute; top: 0; left: 0; width: 100%; min-height: 500px; pointer-events: none; z-index: 5;"></svg>

                            <div class="flow-canvas-empty" id="canvasEmpty">
                                <i class="bx bx-target-lock"></i>
                                <h5 class="mt-2 text-dark">Start Building Your Flow</h5>
                                <p class="text-secondary">Select a trigger tag above, then drag elements from the left panel</p>
                            </div>

                            <!-- Nodes will be added here dynamically -->
                        </div>

                        <!-- Properties Overlay -->
                        <div class="properties-overlay" id="propertiesOverlay"></div>

                        <!-- Properties Panel (floating above canvas) -->
                        <div class="properties-panel" id="propertiesPanel">
                            <div class="properties-panel-header">
                                <h6 class="mb-0 text-dark"><i class="bx bx-cog me-1"></i>Properties</h6>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="closeProperties">
                                    <i class="bx bx-x"></i>
                                </button>
                            </div>
                            <div class="properties-panel-body" id="propertiesPanelBody">
                                <!-- Properties form will be populated dynamically -->
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between">
                    <a href="{{ route('ecom-triggers') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back to Flows
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-danger" id="clearCanvas">
                            <i class="bx bx-trash me-1"></i>Clear Canvas
                        </button>
                        <button type="button" class="btn btn-primary" id="saveFlow">
                            <i class="bx bx-save me-1"></i>{{ isset($flow) ? 'Update' : 'Save' }} Flow
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Editor Modal -->
<div class="modal fade" id="emailEditorModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bx bx-envelope me-2"></i>Email Editor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Subject Row -->
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label text-dark">Email Subject</label>
                        <input type="text" class="form-control" id="emailSubject" placeholder="Enter email subject...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-dark">Insert Merge Tag</label>
                        <select class="form-select" id="mergeTagSelect">
                            <option value="">Select merge tag...</option>
                            @foreach($mergeTags as $tag => $description)
                                <option value="{!! htmlspecialchars($tag, ENT_QUOTES) !!}">{{ $description }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Email Body Editor -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label text-dark mb-0">Email Body</label>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active" id="visualModeBtn" title="Visual Editor">
                                <i class="bx bx-show me-1"></i>Visual
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="htmlModeBtn" title="HTML Code">
                                <i class="bx bx-code-alt me-1"></i>HTML
                            </button>
                        </div>
                    </div>

                    <!-- Formatting Toolbar (Visual Mode Only) -->
                    <div class="email-toolbar border rounded-top p-2 bg-light" id="emailToolbar">
                        <div class="btn-toolbar gap-1" role="toolbar">
                            <div class="btn-group btn-group-sm me-2" role="group">
                                <button type="button" class="btn btn-outline-secondary" data-command="bold" title="Bold">
                                    <i class="bx bx-bold"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-command="italic" title="Italic">
                                    <i class="bx bx-italic"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-command="underline" title="Underline">
                                    <i class="bx bx-underline"></i>
                                </button>
                            </div>
                            <div class="btn-group btn-group-sm me-2" role="group">
                                <button type="button" class="btn btn-outline-secondary" data-command="justifyLeft" title="Align Left">
                                    <i class="bx bx-align-left"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-command="justifyCenter" title="Align Center">
                                    <i class="bx bx-align-middle"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-command="justifyRight" title="Align Right">
                                    <i class="bx bx-align-right"></i>
                                </button>
                            </div>
                            <div class="btn-group btn-group-sm me-2" role="group">
                                <button type="button" class="btn btn-outline-secondary" data-command="insertUnorderedList" title="Bullet List">
                                    <i class="bx bx-list-ul"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-command="insertOrderedList" title="Numbered List">
                                    <i class="bx bx-list-ol"></i>
                                </button>
                            </div>
                            <div class="btn-group btn-group-sm me-2" role="group">
                                <button type="button" class="btn btn-outline-secondary" id="insertLinkBtn" title="Insert Link">
                                    <i class="bx bx-link"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="insertImageBtn" title="Insert Image URL">
                                    <i class="bx bx-image"></i>
                                </button>
                            </div>
                            <div class="btn-group btn-group-sm" role="group">
                                <select class="form-select form-select-sm" id="fontSizeSelect" style="width: auto;">
                                    <option value="">Size</option>
                                    <option value="1">Small</option>
                                    <option value="3">Normal</option>
                                    <option value="5">Large</option>
                                    <option value="7">X-Large</option>
                                </select>
                            </div>
                            <div class="btn-group btn-group-sm ms-2" role="group">
                                <input type="color" class="form-control form-control-color p-0" id="textColorPicker" value="#000000" title="Text Color" style="width: 30px; height: 30px;">
                            </div>
                        </div>
                    </div>

                    <!-- Visual Editor (contenteditable) -->
                    <div class="email-visual-editor border border-top-0 rounded-bottom p-3" id="emailVisualEditor"
                         contenteditable="true"
                         style="min-height: 300px; max-height: 400px; overflow-y: auto; background: #fff;">
                    </div>

                    <!-- HTML Code Editor (textarea) -->
                    <textarea class="form-control font-monospace d-none" id="emailHtmlEditor"
                              style="min-height: 300px; max-height: 400px; font-size: 0.875rem;"
                              placeholder="Enter HTML code here..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEmailContent">
                    <i class="bx bx-save me-1"></i>Save Email
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
$(document).ready(function() {
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Flow builder state
    const state = {
        nodes: [],
        connections: [],
        selectedNode: null,
        nodeIdCounter: 0,
        isEditing: {{ isset($flow) ? 'true' : 'false' }},
        flowId: {{ isset($flow) ? $flow->id : 'null' }},
        flowType: '{{ $flow->flowType ?? 'trigger' }}'
    };

    // Course access tags data
    const courseAccessTags = @json($courseAccessTags);

    // Ani-Senso courses data
    const aniSensoCourses = @json($courses ?? []);

    // Trigger tags data
    const triggerTags = @json($triggerTags);

    // Get current flow type from card selector
    function getFlowType() {
        return $('.flow-type-card.selected').data('type') || 'trigger';
    }

    // Setup flow type card selection
    function setupFlowTypeCards() {
        if (state.isEditing) return; // Don't allow changes when editing

        $('#flowTypeCards .flow-type-card').on('click', function() {
            if ($(this).hasClass('disabled')) return;

            const newFlowType = $(this).data('type');
            const currentFlowType = state.flowType;

            if (newFlowType === currentFlowType) return;

            // If there are nodes beyond the starting node, confirm change
            if (state.nodes.length > 1) {
                if (!confirm('Changing flow type will clear the canvas. Continue?')) {
                    return;
                }
            }

            // Update selection
            $('.flow-type-card').removeClass('selected');
            $(this).addClass('selected');

            // Update state
            state.flowType = newFlowType;

            // Clear canvas and create new starting node
            state.nodes = [];
            state.connections = [];
            $('.flow-node').remove();
            $('#flowConnections').empty();

            // Create appropriate starting node
            const startNode = createDefaultStartNode(newFlowType);
            state.nodes.push(startNode);

            // Center the node
            const $canvas = $('#flowCanvas');
            const canvasWidth = $canvas.width();
            const nodeWidth = 220;
            startNode.position.x = (canvasWidth / 2) - (nodeWidth / 2);

            renderNode(startNode);
            updateCanvasEmptyState();
            updateSidebarForFlowType(newFlowType);

            toastr.info(`Switched to ${getFlowTypeLabel(newFlowType)} flow.`);
        });
    }

    // Get flow type display label
    function getFlowTypeLabel(flowType) {
        const labels = {
            'trigger': 'Trigger',
            'expiration': 'Expiration',
            'order_not_complete': 'Order Not Complete',
            'shipping_complete': 'Shipping Complete',
            'affiliate_earning': 'Affiliate Earning'
        };
        return labels[flowType] || flowType;
    }

    // Update sidebar elements based on flow type
    function updateSidebarForFlowType(flowType) {
        // Course Access element should only show for Trigger flow
        // For all other flow types (expiration, order_not_complete, shipping_complete, affiliate_earning),
        // Course Tag is the starting node so hide it from sidebar
        if (flowType === 'trigger') {
            $('.flow-element-trigger-only').removeClass('d-none');
        } else {
            $('.flow-element-trigger-only').addClass('d-none');
        }
    }

    // Create default starting node based on flow type
    function createDefaultStartNode(flowType) {
        if (flowType === 'trigger') {
            return {
                id: 'node_trigger',
                type: 'trigger_tag',
                position: { x: 50, y: 50 },
                data: { tagId: '', tagName: '' }
            };
        } else if (flowType === 'expiration') {
            return {
                id: 'node_start',
                type: 'course_access_start',
                position: { x: 50, y: 50 },
                data: { tagId: '', tagName: '' }
            };
        } else {
            // For order_not_complete, shipping_complete, affiliate_earning
            // All use course_tag_start as the starting node
            return {
                id: 'node_start',
                type: 'course_tag_start',
                position: { x: 50, y: 50 },
                data: { tagId: '', tagName: '', flowType: flowType }
            };
        }
    }

    // Load existing flow data if editing
    @if(isset($flow) && $flow->flowData)
        const existingFlowData = @json($flow->flowData);
        if (existingFlowData && existingFlowData.nodes) {
            state.nodes = existingFlowData.nodes;
            state.connections = existingFlowData.connections || [];

            // IMPORTANT: Ensure all positions are numbers, not strings (prevents string concatenation bugs)
            state.nodes.forEach(node => {
                if (node.position) {
                    node.position.x = parseFloat(node.position.x) || 0;
                    node.position.y = parseFloat(node.position.y) || 0;
                }
            });

            // Calculate nodeIdCounter from the highest existing node ID to avoid conflicts
            let maxNodeId = 0;
            state.nodes.forEach(node => {
                // Try to extract number from node ID (handles node_1, node_2, etc.)
                const match = node.id.match(/(\d+)/);
                if (match) {
                    const idNum = parseInt(match[1], 10);
                    if (idNum > maxNodeId) maxNodeId = idNum;
                }
            });
            // Ensure nodeIdCounter is at least as high as the number of nodes
            maxNodeId = Math.max(maxNodeId, state.nodes.length);
            state.nodeIdCounter = existingFlowData.nodeIdCounter
                ? Math.max(existingFlowData.nodeIdCounter, maxNodeId)
                : maxNodeId;

            console.log('Loaded flow - nodeIdCounter set to:', state.nodeIdCounter, 'from nodes:', state.nodes.map(n => n.id));
        }
        // Ensure starting node exists based on flow type
        const startNodeTypes = ['trigger_tag', 'course_access_start', 'course_tag_start'];
        if (!state.nodes.find(n => startNodeTypes.includes(n.type))) {
            state.nodes.unshift(createDefaultStartNode(state.flowType));
        }
    @else
        // Will be created in init() after flow type is determined
    @endif

    // Initialize
    function init() {
        // Set initial flow type from state or card selection
        const flowType = state.isEditing ? state.flowType : getFlowType();
        state.flowType = flowType;

        // Update sidebar visibility
        updateSidebarForFlowType(flowType);

        // Create default node if not editing
        @if(!isset($flow))
            state.nodes = [createDefaultStartNode(flowType)];
        @endif

        // Setup flow type card selection (only for new flows)
        setupFlowTypeCards();

        setupDragAndDrop();
        setupEventListeners();
        setupMergeTagClicks();
        setupEmailEditor();
        setupStatusToggle();

        // Center the starting node if it's at default position
        const startingNodeTypes = ['trigger_tag', 'course_access_start', 'course_tag_start'];
        const startNode = state.nodes.find(n => startingNodeTypes.includes(n.type));
        if (startNode && startNode.position.x === 50 && startNode.position.y === 50) {
            const $canvas = $('#flowCanvas');
            const canvasWidth = $canvas.width();
            const nodeWidth = 220;
            startNode.position.x = (canvasWidth / 2) - (nodeWidth / 2);
        }

        renderNodes();
        updateCanvasEmptyState();

        // Re-render connections on window resize
        $(window).on('resize', function() {
            renderConnections();
        });

        // Re-render connections after a longer delay for initial load
        // This ensures all CSS and layout calculations are complete
        @if(isset($flow))
            setTimeout(() => {
                renderConnections();
            }, 300);
        @endif
    }

    // Email editor mode: 'visual' or 'html'
    let emailEditorMode = 'visual';

    // Setup email editor (HTML builder)
    function setupEmailEditor() {
        // Visual/HTML mode toggle
        $('#visualModeBtn').on('click', function() {
            if (emailEditorMode === 'visual') return;
            switchToVisualMode();
        });

        $('#htmlModeBtn').on('click', function() {
            if (emailEditorMode === 'html') return;
            switchToHtmlMode();
        });

        // Toolbar formatting commands
        $('#emailToolbar').on('click', '[data-command]', function(e) {
            e.preventDefault();
            const command = $(this).data('command');
            document.execCommand(command, false, null);
            $('#emailVisualEditor').focus();
        });

        // Font size select
        $('#fontSizeSelect').on('change', function() {
            const size = $(this).val();
            if (size) {
                document.execCommand('fontSize', false, size);
                $('#emailVisualEditor').focus();
            }
            $(this).val('');
        });

        // Text color picker
        $('#textColorPicker').on('input', function() {
            document.execCommand('foreColor', false, $(this).val());
            $('#emailVisualEditor').focus();
        });

        // Insert link button
        $('#insertLinkBtn').on('click', function(e) {
            e.preventDefault();
            const url = prompt('Enter URL:', 'https://');
            if (url) {
                document.execCommand('createLink', false, url);
                $('#emailVisualEditor').focus();
            }
        });

        // Insert image button
        $('#insertImageBtn').on('click', function(e) {
            e.preventDefault();
            const url = prompt('Enter Image URL:', 'https://');
            if (url) {
                document.execCommand('insertImage', false, url);
                $('#emailVisualEditor').focus();
            }
        });
    }

    // Switch to visual mode
    function switchToVisualMode() {
        emailEditorMode = 'visual';
        $('#visualModeBtn').addClass('active');
        $('#htmlModeBtn').removeClass('active');

        // Get HTML from textarea and put it in visual editor
        const html = $('#emailHtmlEditor').val();
        $('#emailVisualEditor').html(html);

        // Show visual editor, hide HTML editor
        $('#emailToolbar').removeClass('d-none');
        $('#emailVisualEditor').removeClass('d-none').addClass('border-top-0');
        $('#emailHtmlEditor').addClass('d-none');
    }

    // Switch to HTML mode
    function switchToHtmlMode() {
        emailEditorMode = 'html';
        $('#htmlModeBtn').addClass('active');
        $('#visualModeBtn').removeClass('active');

        // Get HTML from visual editor and put it in textarea
        const html = $('#emailVisualEditor').html();
        $('#emailHtmlEditor').val(html);

        // Hide visual editor, show HTML editor
        $('#emailToolbar').addClass('d-none');
        $('#emailVisualEditor').addClass('d-none');
        $('#emailHtmlEditor').removeClass('d-none');
    }

    // Get current email content (HTML)
    function getEmailContent() {
        if (emailEditorMode === 'visual') {
            return $('#emailVisualEditor').html();
        } else {
            return $('#emailHtmlEditor').val();
        }
    }

    // Set email content
    function setEmailContent(html) {
        $('#emailVisualEditor').html(html || '');
        $('#emailHtmlEditor').val(html || '');
    }

    // Setup status toggle
    function setupStatusToggle() {
        $('#flowStatus').on('change', function() {
            const isActive = $(this).is(':checked');
            $('#flowStatusLabel').text(isActive ? 'Active' : 'Inactive');
        });
    }

    // Get flow status
    function getFlowStatus() {
        return $('#flowStatus').is(':checked');
    }

    // Setup merge tag dropdown in email editor
    function setupMergeTagClicks() {
        // Handle merge tag dropdown selection
        $('#mergeTagSelect').on('change', function() {
            const tag = $(this).val();
            if (!tag) return;

            if (emailEditorMode === 'visual') {
                // Insert at cursor position in contenteditable
                document.execCommand('insertText', false, tag);
                $('#emailVisualEditor').focus();
            } else {
                // Insert at cursor position in textarea
                const $textarea = $('#emailHtmlEditor');
                const start = $textarea[0].selectionStart;
                const end = $textarea[0].selectionEnd;
                const text = $textarea.val();
                $textarea.val(text.substring(0, start) + tag + text.substring(end));
                $textarea[0].selectionStart = $textarea[0].selectionEnd = start + tag.length;
                $textarea.focus();
            }

            $(this).val(''); // Reset dropdown
            toastr.info('Merge tag inserted!');
        });
    }

    // Setup drag and drop
    function setupDragAndDrop() {
        // Draggable elements from sidebar
        $('.flow-element').on('dragstart', function(e) {
            $(this).addClass('dragging');
            e.originalEvent.dataTransfer.setData('nodeType', $(this).data('node-type'));
            e.originalEvent.dataTransfer.effectAllowed = 'copy';
        });

        $('.flow-element').on('dragend', function() {
            $(this).removeClass('dragging');
        });

        // Canvas drop zone
        const canvas = $('#flowCanvas');

        canvas.on('dragover', function(e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'copy';
            $(this).addClass('drag-over');
        });

        canvas.on('dragleave', function() {
            $(this).removeClass('drag-over');
        });

        canvas.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');

            const nodeType = e.originalEvent.dataTransfer.getData('nodeType');
            if (!nodeType) return;

            addNodeWithAutoSnap(nodeType);
        });
    }

    // Find the last node in the flow chain (node with no outgoing connections or the furthest down)
    function findLastNode() {
        if (state.nodes.length === 0) return null;

        // Find nodes that have no outgoing connections (leaf nodes)
        const nodesWithOutput = state.connections.map(c => c.source);
        const leafNodes = state.nodes.filter(n => !nodesWithOutput.includes(n.id));

        if (leafNodes.length === 0) {
            // All nodes have outputs, find the one with the largest Y position
            return state.nodes.reduce((prev, curr) =>
                (prev.position.y > curr.position.y) ? prev : curr
            );
        }

        // Return the leaf node with the largest Y position
        return leafNodes.reduce((prev, curr) =>
            (prev.position.y > curr.position.y) ? prev : curr
        );
    }

    // Add a new node with auto-snap below the last node
    function addNodeWithAutoSnap(type) {
        const lastNode = findLastNode();
        const $canvas = $('#flowCanvas');
        const canvasWidth = $canvas.width();
        const nodeWidth = 220; // Default node width

        // Center horizontally
        let x = (canvasWidth / 2) - (nodeWidth / 2);
        let y = 50;

        if (lastNode) {
            const $lastNode = $(`#${lastNode.id}`);
            const nodeHeight = $lastNode.outerHeight() || 80;

            // Position below the last node, keep centered
            x = (canvasWidth / 2) - (nodeWidth / 2);
            y = lastNode.position.y + nodeHeight + 60; // 60px gap between nodes
        }

        const nodeId = 'node_' + (++state.nodeIdCounter);
        const nodeConfig = getDefaultNodeConfig(type);

        const node = {
            id: nodeId,
            type: type,
            position: { x: x, y: y },
            data: nodeConfig
        };

        state.nodes.push(node);
        renderNode(node);

        // Debug: Verify node was rendered
        const $renderedNode = $(`#${nodeId}`);
        if (!$renderedNode.length) {
            console.error('Failed to render node:', nodeId, 'at position:', x, y);
            toastr.error('Failed to render node. Please try again.');
            return;
        }
        console.log('Node added:', nodeId, 'at position:', x, y, 'type:', type);

        // Auto-connect from last node if it exists and is not a y_flow or if_else (these need manual connection due to multiple outputs)
        if (lastNode && lastNode.type !== 'y_flow' && lastNode.type !== 'if_else') {
            // Check if last node doesn't already have an outgoing connection
            const hasConnection = state.connections.some(c => c.source === lastNode.id && c.type === 'default');
            if (!hasConnection) {
                state.connections.push({
                    source: lastNode.id,
                    target: nodeId,
                    type: 'default'
                });
                // Don't call renderConnections() here - wait for DOM to be ready
            }
        }

        updateCanvasEmptyState();

        // Expand canvas and render connections after DOM is ready
        setTimeout(() => {
            expandCanvasToFitNodes();
            renderConnections();

            // Scroll to the new node if it's outside the visible area
            const $newNode = $(`#${nodeId}`);
            if ($newNode.length) {
                const $wrapper = $('.flow-canvas-wrapper');
                const nodeTop = $newNode.position().top;
                const wrapperScrollTop = $wrapper.scrollTop();
                const wrapperHeight = $wrapper.height();

                // If node is below visible area, scroll to it
                if (nodeTop > wrapperScrollTop + wrapperHeight - 100) {
                    $wrapper.animate({
                        scrollTop: nodeTop - 100
                    }, 300);
                }
            }
        }, 100);
    }

    // Add a new node (without auto-snap, for programmatic use)
    function addNode(type, x, y) {
        const nodeId = 'node_' + (++state.nodeIdCounter);

        const nodeConfig = getDefaultNodeConfig(type);

        const node = {
            id: nodeId,
            type: type,
            position: { x: x, y: y },
            data: nodeConfig
        };

        state.nodes.push(node);
        renderNode(node);
        updateCanvasEmptyState();
    }

    // Get default config for node type
    function getDefaultNodeConfig(type) {
        switch(type) {
            case 'trigger_tag':
                return { tagId: '', tagName: '' };
            case 'course_access_start':
                return { tagId: '', tagName: '' };
            case 'course_tag_start':
                return { tagId: '', tagName: '', flowType: state.flowType };
            case 'delay':
                return { delayType: 'days', delayValue: 1 };
            case 'schedule':
                return { scheduleDate: '', scheduleTime: '' };
            case 'email':
                return { subject: '', body: '' };
            case 'send_sms':
                return { message: '' };
            case 'send_whatsapp':
                return { message: '' };
            case 'y_flow':
                return { label: 'Decision Split' };
            case 'if_else':
                return {
                    conditionType: 'has_tag',
                    conditionOperator: 'equals',
                    conditionValue: '',
                    conditionValueLabel: '',
                    storeId: '',
                    storeName: '',
                    tagId: '',
                    tagName: '',
                    orderTotal: '',
                    orderOperator: 'greater_than'
                };
            case 'course_access':
                return { tagId: '', tagName: '' };
            case 'remove_access':
                return { tagId: '', tagName: '' };
            case 'ai_add_referral':
                return { affiliateId: '', affiliateName: '' };
            case 'add_as_affiliate':
                return { storeId: '', storeName: '', commissionRate: '10' };
            case 'course_subscription':
                return {
                    action: 'add', // add or remove
                    courseId: '',
                    courseName: '',
                    durationType: 'days', // days or expire
                    durationDays: 30,
                    expireImmediately: false
                };
            default:
                return {};
        }
    }

    // Render a single node
    function renderNode(node) {
        const nodeHtml = createNodeHtml(node);
        $('#flowCanvas').append(nodeHtml);

        // Make node draggable
        const $node = $(`#${node.id}`);
        makeNodeDraggable($node, node);
    }

    // Create node HTML
    function createNodeHtml(node) {
        const icons = {
            'trigger_tag': 'bx-play-circle',
            'course_access_start': 'bx-time-five',
            'course_tag_start': 'bx-key',
            'delay': 'bx-time',
            'schedule': 'bx-calendar',
            'email': 'bx-envelope',
            'send_sms': 'bx-message-rounded-dots',
            'send_whatsapp': 'bxl-whatsapp',
            'y_flow': 'bx-git-branch',
            'if_else': 'bx-git-compare',
            'course_access': 'bx-key',
            'remove_access': 'bx-block',
            'ai_add_referral': 'bx-bot',
            'add_as_affiliate': 'bx-user-plus',
            'course_subscription': 'bx-book-reader'
        };

        // Dynamic title based on flow type for course_tag_start
        let dynamicTitle = '';
        if (node.type === 'course_tag_start') {
            const flowType = node.data.flowType || state.flowType;
            const flowLabels = {
                'order_not_complete': 'Order Not Complete',
                'shipping_complete': 'Shipping Complete',
                'affiliate_earning': 'Affiliate Earning'
            };
            dynamicTitle = flowLabels[flowType] || 'Course Tag';
        }

        const titles = {
            'trigger_tag': 'Trigger Tag',
            'course_access_start': 'Course Expiration',
            'course_tag_start': dynamicTitle || 'Course Tag',
            'delay': 'Delay',
            'schedule': 'Schedule',
            'email': 'Email',
            'send_sms': 'Send SMS',
            'send_whatsapp': 'Send WhatsApp',
            'y_flow': 'Y-Flow Split',
            'if_else': 'If / Else',
            'course_access': 'Course Access',
            'remove_access': 'Remove Access',
            'ai_add_referral': 'AI Add to Referral',
            'add_as_affiliate': 'Add as Affiliate',
            'course_subscription': 'Course Subscription'
        };

        const bodyContent = getNodeBodyContent(node);

        // Starting nodes only have output connector (no input)
        const startingNodeTypes = ['trigger_tag', 'course_access_start', 'course_tag_start'];
        let connectorsHtml = '';
        if (startingNodeTypes.includes(node.type)) {
            connectorsHtml = '<div class="flow-node-connector output"></div>';
        } else if (node.type === 'y_flow') {
            connectorsHtml = '<div class="flow-node-connector input"></div>';
            connectorsHtml += '<div class="flow-node-connector output-left" data-output="left"></div>';
            connectorsHtml += '<div class="flow-node-connector output-right" data-output="right"></div>';
        } else if (node.type === 'if_else') {
            connectorsHtml = '<div class="flow-node-connector input"></div>';
            connectorsHtml += '<div class="flow-node-connector output-left" data-output="left" title="YES - Condition Met"></div>';
            connectorsHtml += '<div class="flow-node-connector output-right" data-output="right" title="NO - Condition Not Met"></div>';
        } else {
            connectorsHtml = '<div class="flow-node-connector input"></div>';
            connectorsHtml += '<div class="flow-node-connector output"></div>';
        }

        // Starting nodes cannot be deleted
        const deleteBtn = startingNodeTypes.includes(node.type) ? '' :
            `<button type="button" class="btn btn-sm btn-outline-danger delete-node-btn" data-node-id="${node.id}" title="Delete">
                <i class="bx bx-trash"></i>
            </button>`;

        // Special class for starting nodes
        let specialClass = '';
        if (node.type === 'trigger_tag') {
            specialClass = 'start-node';
        } else if (node.type === 'course_access_start') {
            specialClass = 'expiration-start-node';
        } else if (node.type === 'course_tag_start') {
            const flowType = node.data.flowType || state.flowType;
            if (flowType === 'order_not_complete') {
                specialClass = 'order-start-node';
            } else if (flowType === 'shipping_complete') {
                specialClass = 'shipping-start-node';
            } else if (flowType === 'affiliate_earning') {
                specialClass = 'affiliate-start-node';
            }
        }

        return `
            <div class="flow-node node-type-${node.type} ${specialClass}" id="${node.id}" style="left: ${node.position.x}px; top: ${node.position.y}px;">
                <div class="flow-node-header">
                    <div class="node-icon"><i class="bx ${icons[node.type]}"></i></div>
                    <span class="node-title">${titles[node.type]}</span>
                    <div class="node-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary edit-node-btn" data-node-id="${node.id}" title="Edit">
                            <i class="bx bx-edit-alt"></i>
                        </button>
                        ${deleteBtn}
                    </div>
                </div>
                <div class="flow-node-body">${bodyContent}</div>
                ${connectorsHtml}
            </div>
        `;
    }

    // Get node body content
    function getNodeBodyContent(node) {
        switch(node.type) {
            case 'trigger_tag':
                if (node.data.tagName) {
                    return `<span class="badge bg-success text-white"><i class="bx bx-tag me-1"></i>${escapeHtml(node.data.tagName)}</span>`;
                }
                return '<span class="text-warning"><i class="bx bx-error-circle me-1"></i>Select a trigger tag</span>';
            case 'course_access_start':
                if (node.data.tagName) {
                    return `<span class="badge bg-warning text-dark"><i class="bx bx-key me-1"></i>${escapeHtml(node.data.tagName)}</span>`;
                }
                return '<span class="text-warning"><i class="bx bx-error-circle me-1"></i>Select course access tag</span>';
            case 'course_tag_start':
                if (node.data.tagName) {
                    const flowType = node.data.flowType || state.flowType;
                    let badgeClass = 'bg-primary';
                    if (flowType === 'order_not_complete') badgeClass = 'bg-danger';
                    else if (flowType === 'shipping_complete') badgeClass = 'bg-info';
                    else if (flowType === 'affiliate_earning') badgeClass = 'bg-primary';
                    return `<span class="badge ${badgeClass} text-white"><i class="bx bx-key me-1"></i>${escapeHtml(node.data.tagName)}</span>`;
                }
                return '<span class="text-warning"><i class="bx bx-error-circle me-1"></i>Select course tag</span>';
            case 'delay':
                return `<span class="text-dark">Wait ${node.data.delayValue || 1} ${node.data.delayType || 'days'}</span>`;
            case 'schedule':
                if (node.data.scheduleDate) {
                    return `<span class="text-dark">${node.data.scheduleDate} ${node.data.scheduleTime || ''}</span>`;
                }
                return '<span class="text-secondary">Not configured</span>';
            case 'email':
                if (node.data.subject) {
                    return `<span class="text-dark">${escapeHtml(node.data.subject.substring(0, 30))}${node.data.subject.length > 30 ? '...' : ''}</span>`;
                }
                return '<span class="text-secondary">No email configured</span>';
            case 'send_sms':
                if (node.data.message) {
                    return `<span class="text-dark">${escapeHtml(node.data.message.substring(0, 30))}${node.data.message.length > 30 ? '...' : ''}</span>`;
                }
                return '<span class="text-secondary">No SMS configured</span>';
            case 'send_whatsapp':
                if (node.data.message) {
                    return `<span class="text-dark">${escapeHtml(node.data.message.substring(0, 30))}${node.data.message.length > 30 ? '...' : ''}</span>`;
                }
                return '<span class="text-secondary">No message configured</span>';
            case 'y_flow':
                return '<span class="text-dark">Split into 2 paths</span>';
            case 'if_else':
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
                    'has_discount': 'Has Discount Applied'
                };
                const condLabel = conditionLabels[node.data.conditionType] || 'Condition';
                if (node.data.conditionValueLabel || node.data.tagName || node.data.storeName) {
                    const valueLabel = node.data.conditionValueLabel || node.data.tagName || node.data.storeName || node.data.conditionValue || '';
                    return `<span class="text-dark"><strong>IF</strong> ${condLabel}: ${escapeHtml(valueLabel.substring(0, 20))}${valueLabel.length > 20 ? '...' : ''}</span>`;
                }
                return `<span class="text-secondary">IF ${condLabel}...</span>`;
            case 'course_access':
                if (node.data.tagName) {
                    return `<span class="text-dark">${escapeHtml(node.data.tagName)}</span>`;
                }
                return '<span class="text-secondary">No tag selected</span>';
            case 'remove_access':
                if (node.data.tagName) {
                    return `<span class="badge bg-dark text-white"><i class="bx bx-block me-1"></i>${escapeHtml(node.data.tagName)}</span>`;
                }
                return '<span class="text-secondary">No tag selected</span>';
            case 'ai_add_referral':
                if (node.data.affiliateName) {
                    return `<span class="badge text-white" style="background-color: #8B5CF6;"><i class="bx bx-bot me-1"></i>${escapeHtml(node.data.affiliateName)}</span>`;
                }
                return '<span class="text-secondary">AI will assign referral</span>';
            case 'add_as_affiliate':
                if (node.data.storeName) {
                    return `<span class="badge text-white" style="background-color: #F59E0B;"><i class="bx bx-store me-1"></i>${escapeHtml(node.data.storeName)} (${node.data.commissionRate || 10}%)</span>`;
                }
                return '<span class="text-secondary">No store selected</span>';
            case 'course_subscription':
                if (node.data.courseName) {
                    const actionIcon = node.data.action === 'add' ? 'bx-plus-circle' : 'bx-minus-circle';
                    const actionLabel = node.data.action === 'add' ? 'Add' : 'Remove';
                    let durationText = '';
                    if (node.data.action === 'add') {
                        durationText = node.data.durationType === 'expire' ? ' (Expire Now)' : ` (${node.data.durationDays} days)`;
                    }
                    return `<span class="badge text-white" style="background-color: #EC4899;"><i class="bx ${actionIcon} me-1"></i>${actionLabel}: ${escapeHtml(node.data.courseName.substring(0, 15))}${node.data.courseName.length > 15 ? '...' : ''}${durationText}</span>`;
                }
                return '<span class="text-secondary">No course selected</span>';
            default:
                return '';
        }
    }

    // Make node draggable within canvas
    function makeNodeDraggable($node, node) {
        let isDragging = false;
        let startX, startY, startLeft, startTop;

        $node.find('.flow-node-header').on('mousedown', function(e) {
            if ($(e.target).closest('.node-actions').length) return;

            isDragging = true;
            startX = e.pageX;
            startY = e.pageY;
            startLeft = parseInt($node.css('left'));
            startTop = parseInt($node.css('top'));

            $(document).on('mousemove.nodeDrag', function(e) {
                if (!isDragging) return;

                const dx = e.pageX - startX;
                const dy = e.pageY - startY;

                const newLeft = Math.max(0, startLeft + dx);
                const newTop = Math.max(0, startTop + dy);

                $node.css({ left: newLeft + 'px', top: newTop + 'px' });
                node.position.x = newLeft;
                node.position.y = newTop;

                renderConnections();
            });

            $(document).on('mouseup.nodeDrag', function() {
                isDragging = false;
                $(document).off('.nodeDrag');
                // Expand canvas if node was dragged beyond current bounds
                expandCanvasToFitNodes();
            });
        });
    }

    // Select node
    function selectNode(nodeId) {
        $('.flow-node').removeClass('selected');
        $(`#${nodeId}`).addClass('selected');
        state.selectedNode = state.nodes.find(n => n.id === nodeId);
        showProperties(state.selectedNode);
    }

    // Show properties panel
    function showProperties(node) {
        if (!node) {
            closePropertiesPanel();
            return;
        }

        openPropertiesPanel();
        const $body = $('#propertiesPanelBody');

        let html = '';

        switch(node.type) {
            case 'trigger_tag':
                let triggerOptionsHtml = '<option value="">Select Trigger Tag...</option>';
                triggerTags.forEach(tag => {
                    const selected = node.data.tagId == tag.id ? 'selected' : '';
                    triggerOptionsHtml += `<option value="${tag.id}" ${selected}>${escapeHtml(tag.triggerTagName)}</option>`;
                });
                html = `
                    <div class="alert alert-success mb-3">
                        <small><i class="bx bx-play-circle me-1"></i>This is the starting point of your flow. Select the trigger tag that will activate this automation.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Trigger Tag <span class="text-danger">*</span></label>
                        <select class="form-select" id="propTriggerTag">${triggerOptionsHtml}</select>
                    </div>
                    <button type="button" class="btn btn-success btn-sm w-100" id="applyTriggerProps">
                        <i class="bx bx-check me-1"></i>Apply Trigger Tag
                    </button>
                `;
                break;

            case 'course_access_start':
                let expirationOptionsHtml = '<option value="">Select Course Access Tag...</option>';
                courseAccessTags.forEach(tag => {
                    const selected = node.data.tagId == tag.id ? 'selected' : '';
                    expirationOptionsHtml += `<option value="${tag.id}" ${selected}>${escapeHtml(tag.tagName)}</option>`;
                });
                html = `
                    <div class="alert alert-warning mb-3">
                        <small><i class="bx bx-time-five me-1"></i>This flow triggers when a course access expires. Select the course access tag to monitor.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Course Access Tag <span class="text-danger">*</span></label>
                        <select class="form-select" id="propExpirationTag">${expirationOptionsHtml}</select>
                    </div>
                    <button type="button" class="btn btn-warning btn-sm w-100" id="applyExpirationProps">
                        <i class="bx bx-check me-1"></i>Apply Course Tag
                    </button>
                `;
                break;

            case 'course_tag_start':
                const flowTypeForStart = node.data.flowType || state.flowType;
                const flowDescriptions = {
                    'order_not_complete': 'This flow triggers when an order is not completed (abandoned cart). Select the course tag to monitor.',
                    'shipping_complete': 'This flow triggers when shipping is marked as complete. Select the course tag to monitor.',
                    'affiliate_earning': 'This flow triggers when an affiliate earns a commission. Select the course tag to monitor.'
                };
                const alertClasses = {
                    'order_not_complete': 'alert-danger',
                    'shipping_complete': 'alert-info',
                    'affiliate_earning': 'alert-primary'
                };
                const btnClasses = {
                    'order_not_complete': 'btn-danger',
                    'shipping_complete': 'btn-info',
                    'affiliate_earning': 'btn-primary'
                };
                let courseTagOptionsHtml = '<option value="">Select Course Tag...</option>';
                courseAccessTags.forEach(tag => {
                    const selected = node.data.tagId == tag.id ? 'selected' : '';
                    courseTagOptionsHtml += `<option value="${tag.id}" ${selected}>${escapeHtml(tag.tagName)}</option>`;
                });
                html = `
                    <div class="alert ${alertClasses[flowTypeForStart] || 'alert-primary'} mb-3">
                        <small><i class="bx bx-key me-1"></i>${flowDescriptions[flowTypeForStart] || 'Select the course tag to monitor.'}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Course Tag <span class="text-danger">*</span></label>
                        <select class="form-select" id="propCourseTagStart">${courseTagOptionsHtml}</select>
                    </div>
                    <button type="button" class="${btnClasses[flowTypeForStart] || 'btn-primary'} btn btn-sm w-100" id="applyCourseTagStartProps">
                        <i class="bx bx-check me-1"></i>Apply Course Tag
                    </button>
                `;
                break;

            case 'delay':
                html = `
                    <div class="mb-3">
                        <label class="form-label text-dark">Delay Value</label>
                        <input type="number" class="form-control" id="propDelayValue" value="${node.data.delayValue || 1}" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Delay Type</label>
                        <select class="form-select" id="propDelayType">
                            <option value="minutes" ${node.data.delayType === 'minutes' ? 'selected' : ''}>Minutes</option>
                            <option value="days" ${node.data.delayType === 'days' ? 'selected' : ''}>Days</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm w-100" id="applyDelayProps">Apply</button>
                `;
                break;

            case 'schedule':
                html = `
                    <div class="mb-3">
                        <label class="form-label text-dark">Date</label>
                        <input type="date" class="form-control" id="propScheduleDate" value="${node.data.scheduleDate || ''}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Time</label>
                        <input type="time" class="form-control" id="propScheduleTime" value="${node.data.scheduleTime || ''}">
                    </div>
                    <button type="button" class="btn btn-primary btn-sm w-100" id="applyScheduleProps">Apply</button>
                `;
                break;

            case 'email':
                html = `
                    <div class="mb-3">
                        <label class="form-label text-dark">Subject</label>
                        <input type="text" class="form-control form-control-sm" id="propEmailSubject" value="${escapeHtml(node.data.subject || '')}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Body Preview</label>
                        <div class="border rounded p-2 bg-light" style="max-height: 100px; overflow: hidden; font-size: 0.75rem;">
                            ${escapeHtml(node.data.body || 'No content').substring(0, 150)}...
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm w-100" id="openEmailEditor">
                        <i class="bx bx-edit me-1"></i>Edit Email
                    </button>
                `;
                break;

            case 'send_sms':
                html = `
                    <div class="mb-3">
                        <label class="form-label text-dark">SMS Message</label>
                        <textarea class="form-control" id="propSmsMessage" rows="4" placeholder="Enter SMS message...">${escapeHtml(node.data.message || '')}</textarea>
                        <small class="text-secondary">Max 160 characters recommended. Merge tags: ${Object.keys(@json($mergeTags)).slice(0, 3).join(', ')}...</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Insert Merge Tag</label>
                        <select class="form-select form-select-sm" id="propSmsMergeTag">
                            <option value="">Select tag...</option>
                            @foreach($mergeTags as $tag => $description)
                                <option value="{!! htmlspecialchars($tag, ENT_QUOTES) !!}">{{ $description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-success btn-sm w-100" id="applySmsProps">
                        <i class="bx bx-check me-1"></i>Save SMS
                    </button>
                `;
                break;

            case 'send_whatsapp':
                html = `
                    <div class="mb-3">
                        <label class="form-label text-dark">WhatsApp Message</label>
                        <textarea class="form-control" id="propWhatsappMessage" rows="4" placeholder="Enter WhatsApp message...">${escapeHtml(node.data.message || '')}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Insert Merge Tag</label>
                        <select class="form-select form-select-sm" id="propWhatsappMergeTag">
                            <option value="">Select tag...</option>
                            @foreach($mergeTags as $tag => $description)
                                <option value="{!! htmlspecialchars($tag, ENT_QUOTES) !!}">{{ $description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-sm w-100" id="applyWhatsappProps" style="background-color: #25D366; color: #fff;">
                        <i class="bx bx-check me-1"></i>Save Message
                    </button>
                `;
                break;

            case 'course_access':
                let optionsHtml = '<option value="">Select Course Tag...</option>';
                courseAccessTags.forEach(tag => {
                    const selected = node.data.tagId == tag.id ? 'selected' : '';
                    optionsHtml += `<option value="${tag.id}" ${selected}>${escapeHtml(tag.tagName)}</option>`;
                });
                html = `
                    <div class="mb-3">
                        <label class="form-label text-dark">Course Access Tag</label>
                        <select class="form-select" id="propCourseTag">${optionsHtml}</select>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm w-100" id="applyCourseProps">Apply</button>
                `;
                break;

            case 'y_flow':
                html = `
                    <div class="alert alert-info mb-0">
                        <small><i class="bx bx-info-circle me-1"></i>Y-Flow splits the automation into two separate paths. Connect other nodes to the left and right outputs.</small>
                    </div>
                `;
                break;

            case 'if_else':
                // Build tag options
                let ifElseTagOptions = '<option value="">Select Tag...</option>';
                triggerTags.forEach(tag => {
                    const selected = node.data.tagId == tag.id ? 'selected' : '';
                    ifElseTagOptions += `<option value="${tag.id}" data-name="${escapeHtml(tag.triggerTagName)}" ${selected}>${escapeHtml(tag.triggerTagName)}</option>`;
                });

                // Build course access tag options
                let ifElseCourseOptions = '<option value="">Select Course Tag...</option>';
                courseAccessTags.forEach(tag => {
                    const selected = node.data.tagId == tag.id ? 'selected' : '';
                    ifElseCourseOptions += `<option value="${tag.id}" data-name="${escapeHtml(tag.tagName)}" ${selected}>${escapeHtml(tag.tagName)}</option>`;
                });

                // Build store options
                let ifElseStoreOptions = '<option value="">Select Store...</option>';
                @if(isset($stores))
                @foreach($stores as $store)
                ifElseStoreOptions += `<option value="{{ $store->id }}" data-name="{{ $store->storeName }}" ${node.data.storeId == '{{ $store->id }}' ? 'selected' : ''}>{{ $store->storeName }}</option>`;
                @endforeach
                @endif

                html = `
                    <div class="alert mb-3" style="background-color: rgba(8, 145, 178, 0.1); border-color: #0891B2;">
                        <small class="text-dark"><i class="bx bx-git-compare me-1" style="color: #0891B2;"></i>
                            <strong>YES path (left):</strong> Condition is true<br>
                            <strong>NO path (right):</strong> Condition is false
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Condition Type</label>
                        <select class="form-select" id="propIfElseConditionType">
                            <option value="has_tag" ${node.data.conditionType === 'has_tag' ? 'selected' : ''}>Has Trigger Tag</option>
                            <option value="not_has_tag" ${node.data.conditionType === 'not_has_tag' ? 'selected' : ''}>Does Not Have Trigger Tag</option>
                            <option value="store_in_order" ${node.data.conditionType === 'store_in_order' ? 'selected' : ''}>Store in Order</option>
                            <option value="has_course_access" ${node.data.conditionType === 'has_course_access' ? 'selected' : ''}>Has Course Access</option>
                            <option value="no_course_access" ${node.data.conditionType === 'no_course_access' ? 'selected' : ''}>Does Not Have Course Access</option>
                            <option value="order_total" ${node.data.conditionType === 'order_total' ? 'selected' : ''}>Order Total</option>
                            <option value="is_affiliate" ${node.data.conditionType === 'is_affiliate' ? 'selected' : ''}>Is Affiliate</option>
                            <option value="is_not_affiliate" ${node.data.conditionType === 'is_not_affiliate' ? 'selected' : ''}>Is Not Affiliate</option>
                            <option value="client_province" ${node.data.conditionType === 'client_province' ? 'selected' : ''}>Client Province</option>
                            <option value="has_discount" ${node.data.conditionType === 'has_discount' ? 'selected' : ''}>Has Discount Applied</option>
                            <option value="payment_method" ${node.data.conditionType === 'payment_method' ? 'selected' : ''}>Payment Method</option>
                        </select>
                    </div>

                    <!-- Tag Selection (for has_tag, not_has_tag) -->
                    <div class="mb-3 if-else-field" id="ifElseTagField" style="display: ${['has_tag', 'not_has_tag'].includes(node.data.conditionType) ? 'block' : 'none'};">
                        <label class="form-label text-dark">Select Trigger Tag</label>
                        <select class="form-select" id="propIfElseTag">${ifElseTagOptions}</select>
                    </div>

                    <!-- Course Access Selection (for has_course_access, no_course_access) -->
                    <div class="mb-3 if-else-field" id="ifElseCourseField" style="display: ${['has_course_access', 'no_course_access'].includes(node.data.conditionType) ? 'block' : 'none'};">
                        <label class="form-label text-dark">Select Course Tag</label>
                        <select class="form-select" id="propIfElseCourseTag">${ifElseCourseOptions}</select>
                    </div>

                    <!-- Store Selection (for store_in_order) -->
                    <div class="mb-3 if-else-field" id="ifElseStoreField" style="display: ${node.data.conditionType === 'store_in_order' ? 'block' : 'none'};">
                        <label class="form-label text-dark">Select Store</label>
                        <select class="form-select" id="propIfElseStore">${ifElseStoreOptions}</select>
                    </div>

                    <!-- Order Total (for order_total) -->
                    <div class="mb-3 if-else-field" id="ifElseOrderTotalField" style="display: ${node.data.conditionType === 'order_total' ? 'block' : 'none'};">
                        <label class="form-label text-dark">Order Total Condition</label>
                        <div class="input-group">
                            <select class="form-select" id="propIfElseOrderOperator" style="max-width: 150px;">
                                <option value="greater_than" ${node.data.orderOperator === 'greater_than' ? 'selected' : ''}>Greater than</option>
                                <option value="less_than" ${node.data.orderOperator === 'less_than' ? 'selected' : ''}>Less than</option>
                                <option value="equals" ${node.data.orderOperator === 'equals' ? 'selected' : ''}>Equals</option>
                                <option value="greater_equal" ${node.data.orderOperator === 'greater_equal' ? 'selected' : ''}>Greater or equal</option>
                                <option value="less_equal" ${node.data.orderOperator === 'less_equal' ? 'selected' : ''}>Less or equal</option>
                            </select>
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="propIfElseOrderTotal" value="${node.data.orderTotal || ''}" placeholder="0.00" step="0.01">
                        </div>
                    </div>

                    <!-- Province Selection (for client_province) -->
                    <div class="mb-3 if-else-field" id="ifElseProvinceField" style="display: ${node.data.conditionType === 'client_province' ? 'block' : 'none'};">
                        <label class="form-label text-dark">Province Name</label>
                        <input type="text" class="form-control" id="propIfElseProvince" value="${node.data.conditionValue || ''}" placeholder="e.g., Metro Manila">
                    </div>

                    <!-- Payment Method (for payment_method) -->
                    <div class="mb-3 if-else-field" id="ifElsePaymentField" style="display: ${node.data.conditionType === 'payment_method' ? 'block' : 'none'};">
                        <label class="form-label text-dark">Payment Method</label>
                        <select class="form-select" id="propIfElsePayment">
                            <option value="">Select Payment Method...</option>
                            <option value="cod" ${node.data.conditionValue === 'cod' ? 'selected' : ''}>Cash on Delivery (COD)</option>
                            <option value="gcash" ${node.data.conditionValue === 'gcash' ? 'selected' : ''}>GCash</option>
                            <option value="bank_transfer" ${node.data.conditionValue === 'bank_transfer' ? 'selected' : ''}>Bank Transfer</option>
                            <option value="credit_card" ${node.data.conditionValue === 'credit_card' ? 'selected' : ''}>Credit Card</option>
                            <option value="paypal" ${node.data.conditionValue === 'paypal' ? 'selected' : ''}>PayPal</option>
                        </select>
                    </div>

                    <!-- Info for boolean conditions -->
                    <div class="mb-3 if-else-field" id="ifElseBooleanInfo" style="display: ${['is_affiliate', 'is_not_affiliate', 'has_discount'].includes(node.data.conditionType) ? 'block' : 'none'};">
                        <div class="alert alert-secondary mb-0">
                            <small><i class="bx bx-info-circle me-1"></i>This condition doesn't require additional configuration.</small>
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm w-100 text-white" style="background-color: #0891B2;" id="applyIfElseProps">
                        <i class="bx bx-check me-1"></i>Apply Condition
                    </button>
                `;
                break;

            case 'remove_access':
                let removeOptionsHtml = '<option value="">Select Course Tag to Remove...</option>';
                courseAccessTags.forEach(tag => {
                    const selected = node.data.tagId == tag.id ? 'selected' : '';
                    removeOptionsHtml += `<option value="${tag.id}" ${selected}>${escapeHtml(tag.tagName)}</option>`;
                });
                html = `
                    <div class="alert alert-dark mb-3">
                        <small><i class="bx bx-block me-1"></i>This action will remove the selected course access from the client.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Course Access Tag to Remove</label>
                        <select class="form-select" id="propRemoveAccessTag">${removeOptionsHtml}</select>
                    </div>
                    <button type="button" class="btn btn-dark btn-sm w-100" id="applyRemoveAccessProps">
                        <i class="bx bx-check me-1"></i>Apply
                    </button>
                `;
                break;

            case 'ai_add_referral':
                html = `
                    <div class="alert mb-3" style="background-color: rgba(139, 92, 246, 0.1); border-color: #8B5CF6;">
                        <small class="text-dark"><i class="bx bx-bot me-1" style="color: #8B5CF6;"></i>AI will automatically assign the customer to an appropriate affiliate based on intelligent matching.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Assignment Mode</label>
                        <select class="form-select" id="propAiMode">
                            <option value="auto" ${(node.data.mode || 'auto') === 'auto' ? 'selected' : ''}>Automatic (AI decides)</option>
                            <option value="round_robin" ${node.data.mode === 'round_robin' ? 'selected' : ''}>Round Robin</option>
                            <option value="performance" ${node.data.mode === 'performance' ? 'selected' : ''}>Best Performance</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-sm w-100 text-white" style="background-color: #8B5CF6;" id="applyAiReferralProps">
                        <i class="bx bx-check me-1"></i>Apply
                    </button>
                `;
                break;

            case 'add_as_affiliate':
                let storeOptionsHtml = '<option value="">Select Store...</option>';
                @if(isset($stores))
                @foreach($stores as $store)
                storeOptionsHtml += `<option value="{{ $store->id }}" ${node.data.storeId == '{{ $store->id }}' ? 'selected' : ''}>{{ $store->storeName }}</option>`;
                @endforeach
                @endif
                html = `
                    <div class="alert mb-3" style="background-color: rgba(245, 158, 11, 0.1); border-color: #F59E0B;">
                        <small class="text-dark"><i class="bx bx-user-plus me-1" style="color: #F59E0B;"></i>Register the customer as an affiliate for the selected store.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Store</label>
                        <select class="form-select" id="propAffiliateStore">${storeOptionsHtml}</select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Commission Rate (%)</label>
                        <input type="number" class="form-control" id="propCommissionRate" value="${node.data.commissionRate || 10}" min="0" max="100" step="0.5">
                    </div>
                    <button type="button" class="btn btn-sm w-100 text-white" style="background-color: #F59E0B;" id="applyAddAffiliateProps">
                        <i class="bx bx-check me-1"></i>Apply
                    </button>
                `;
                break;

            case 'course_subscription':
                // Build course options
                let courseOptionsHtml = '<option value="">Select Course...</option>';
                aniSensoCourses.forEach(course => {
                    const selected = node.data.courseId == course.id ? 'selected' : '';
                    courseOptionsHtml += `<option value="${course.id}" ${selected}>${escapeHtml(course.courseName)}</option>`;
                });

                html = `
                    <div class="alert mb-3" style="background-color: rgba(236, 72, 153, 0.1); border-color: #EC4899;">
                        <small class="text-dark"><i class="bx bx-book-reader me-1" style="color: #EC4899;"></i>Manage Ani-Senso course subscriptions for the customer.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark">Action</label>
                        <select class="form-select" id="propCourseSubAction">
                            <option value="add" ${node.data.action === 'add' ? 'selected' : ''}>Add Subscription</option>
                            <option value="remove" ${node.data.action === 'remove' ? 'selected' : ''}>Remove Subscription</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark">Course</label>
                        <select class="form-select" id="propCourseSubCourse">${courseOptionsHtml}</select>
                    </div>

                    <div class="mb-3 course-sub-duration-field" id="courseSubDurationField" style="display: ${node.data.action === 'add' ? 'block' : 'none'};">
                        <label class="form-label text-dark">Duration Type</label>
                        <select class="form-select" id="propCourseSubDurationType">
                            <option value="days" ${node.data.durationType === 'days' ? 'selected' : ''}>Set Number of Days</option>
                            <option value="expire" ${node.data.durationType === 'expire' ? 'selected' : ''}>Set to Expire Immediately</option>
                        </select>
                    </div>

                    <div class="mb-3 course-sub-days-field" id="courseSubDaysField" style="display: ${node.data.action === 'add' && node.data.durationType === 'days' ? 'block' : 'none'};">
                        <label class="form-label text-dark">Number of Days</label>
                        <input type="number" class="form-control" id="propCourseSubDays" value="${node.data.durationDays || 30}" min="1" max="3650">
                        <small class="text-secondary">How many days the subscription will be valid</small>
                    </div>

                    <div class="mb-3 course-sub-expire-info" id="courseSubExpireInfo" style="display: ${node.data.action === 'add' && node.data.durationType === 'expire' ? 'block' : 'none'};">
                        <div class="alert alert-warning mb-0">
                            <small><i class="bx bx-error-circle me-1"></i>This will set the subscription to expire immediately (useful for revoking access).</small>
                        </div>
                    </div>

                    <div class="mb-3 course-sub-remove-info" id="courseSubRemoveInfo" style="display: ${node.data.action === 'remove' ? 'block' : 'none'};">
                        <div class="alert alert-danger mb-0">
                            <small><i class="bx bx-trash me-1"></i>This will completely remove the course subscription from the customer.</small>
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm w-100 text-white" style="background-color: #EC4899;" id="applyCourseSubProps">
                        <i class="bx bx-check me-1"></i>Apply
                    </button>
                `;
                break;
        }

        $body.html(html);
        setupPropertiesEvents(node);
    }

    // Setup properties panel events
    function setupPropertiesEvents(node) {
        $('#applyTriggerProps').on('click', function() {
            const $select = $('#propTriggerTag');
            if (!$select.val()) {
                toastr.error('Please select a trigger tag.');
                return;
            }
            node.data.tagId = $select.val();
            node.data.tagName = $select.find('option:selected').text();
            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('Trigger tag applied!');
        });

        $('#applyDelayProps').on('click', function() {
            node.data.delayValue = parseInt($('#propDelayValue').val()) || 1;
            node.data.delayType = $('#propDelayType').val();
            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('Delay settings applied!');
        });

        $('#applyScheduleProps').on('click', function() {
            node.data.scheduleDate = $('#propScheduleDate').val();
            node.data.scheduleTime = $('#propScheduleTime').val();
            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('Schedule settings applied!');
        });

        $('#openEmailEditor').on('click', function() {
            $('#emailSubject').val(node.data.subject || '');

            // Reset to visual mode and set content
            emailEditorMode = 'visual';
            $('#visualModeBtn').addClass('active');
            $('#htmlModeBtn').removeClass('active');
            $('#emailToolbar').removeClass('d-none');
            $('#emailVisualEditor').removeClass('d-none');
            $('#emailHtmlEditor').addClass('d-none');

            // Set content to both editors
            setEmailContent(node.data.body || '');

            $('#emailEditorModal').modal('show');
        });

        $('#applyCourseProps').on('click', function() {
            const $select = $('#propCourseTag');
            node.data.tagId = $select.val();
            node.data.tagName = $select.find('option:selected').text();
            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('Course access tag applied!');
        });

        // Expiration flow start node
        $('#applyExpirationProps').on('click', function() {
            const $select = $('#propExpirationTag');
            if (!$select.val()) {
                toastr.error('Please select a course access tag.');
                return;
            }
            node.data.tagId = $select.val();
            node.data.tagName = $select.find('option:selected').text();
            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('Course access tag applied!');
        });

        // Course tag start node (for order_not_complete, shipping_complete, affiliate_earning flows)
        $('#applyCourseTagStartProps').on('click', function() {
            const $select = $('#propCourseTagStart');
            if (!$select.val()) {
                toastr.error('Please select a course tag.');
                return;
            }
            node.data.tagId = $select.val();
            node.data.tagName = $select.find('option:selected').text();
            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('Course tag applied!');
        });

        // SMS properties
        $('#propSmsMergeTag').on('change', function() {
            const tag = $(this).val();
            if (tag) {
                const $textarea = $('#propSmsMessage');
                const start = $textarea[0].selectionStart;
                const text = $textarea.val();
                $textarea.val(text.substring(0, start) + tag + text.substring(start));
                $textarea.focus();
                $(this).val('');
            }
        });

        $('#applySmsProps').on('click', function() {
            node.data.message = $('#propSmsMessage').val();
            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('SMS message saved!');
        });

        // WhatsApp properties
        $('#propWhatsappMergeTag').on('change', function() {
            const tag = $(this).val();
            if (tag) {
                const $textarea = $('#propWhatsappMessage');
                const start = $textarea[0].selectionStart;
                const text = $textarea.val();
                $textarea.val(text.substring(0, start) + tag + text.substring(start));
                $textarea.focus();
                $(this).val('');
            }
        });

        $('#applyWhatsappProps').on('click', function() {
            node.data.message = $('#propWhatsappMessage').val();
            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('WhatsApp message saved!');
        });

        // Remove access properties
        $('#applyRemoveAccessProps').on('click', function() {
            const $select = $('#propRemoveAccessTag');
            node.data.tagId = $select.val();
            node.data.tagName = $select.find('option:selected').text();
            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('Remove access tag applied!');
        });

        // AI Add to Referral properties
        $('#applyAiReferralProps').on('click', function() {
            node.data.mode = $('#propAiMode').val();
            const modeLabels = {
                'auto': 'AI Auto-Assign',
                'round_robin': 'Round Robin',
                'performance': 'Best Performance'
            };
            node.data.affiliateName = modeLabels[node.data.mode] || 'AI Auto-Assign';
            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('AI referral settings applied!');
        });

        // Add as Affiliate properties
        $('#applyAddAffiliateProps').on('click', function() {
            const $select = $('#propAffiliateStore');
            node.data.storeId = $select.val();
            node.data.storeName = $select.find('option:selected').text();
            node.data.commissionRate = $('#propCommissionRate').val() || '10';
            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('Affiliate settings applied!');
        });

        // Course Subscription - Action change
        $('#propCourseSubAction').on('change', function() {
            const action = $(this).val();
            if (action === 'add') {
                $('#courseSubDurationField').show();
                $('#courseSubRemoveInfo').hide();
                // Show days field based on duration type
                const durationType = $('#propCourseSubDurationType').val();
                if (durationType === 'days') {
                    $('#courseSubDaysField').show();
                    $('#courseSubExpireInfo').hide();
                } else {
                    $('#courseSubDaysField').hide();
                    $('#courseSubExpireInfo').show();
                }
            } else {
                $('#courseSubDurationField').hide();
                $('#courseSubDaysField').hide();
                $('#courseSubExpireInfo').hide();
                $('#courseSubRemoveInfo').show();
            }
        });

        // Course Subscription - Duration type change
        $('#propCourseSubDurationType').on('change', function() {
            const durationType = $(this).val();
            if (durationType === 'days') {
                $('#courseSubDaysField').show();
                $('#courseSubExpireInfo').hide();
            } else {
                $('#courseSubDaysField').hide();
                $('#courseSubExpireInfo').show();
            }
        });

        // Course Subscription - Apply properties
        $('#applyCourseSubProps').on('click', function() {
            const action = $('#propCourseSubAction').val();
            const $courseSelect = $('#propCourseSubCourse');

            if (!$courseSelect.val()) {
                toastr.error('Please select a course.');
                return;
            }

            node.data.action = action;
            node.data.courseId = $courseSelect.val();
            node.data.courseName = $courseSelect.find('option:selected').text();

            if (action === 'add') {
                node.data.durationType = $('#propCourseSubDurationType').val();
                if (node.data.durationType === 'days') {
                    node.data.durationDays = parseInt($('#propCourseSubDays').val()) || 30;
                    node.data.expireImmediately = false;
                } else {
                    node.data.durationDays = 0;
                    node.data.expireImmediately = true;
                }
            } else {
                node.data.durationType = '';
                node.data.durationDays = 0;
                node.data.expireImmediately = false;
            }

            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('Course subscription settings applied!');
        });

        // If/Else condition type change - show/hide relevant fields
        $('#propIfElseConditionType').on('change', function() {
            const conditionType = $(this).val();

            // Hide all conditional fields first
            $('.if-else-field').hide();

            // Show relevant fields based on condition type
            switch(conditionType) {
                case 'has_tag':
                case 'does_not_have_tag':
                    $('#ifElseTagField').show();
                    break;
                case 'has_course_access':
                case 'no_course_access':
                    $('#ifElseCourseField').show();
                    break;
                case 'store_in_order':
                    $('#ifElseStoreField').show();
                    break;
                case 'order_total':
                    $('#ifElseOrderTotalField').show();
                    break;
                case 'client_province':
                    $('#ifElseProvinceField').show();
                    break;
                case 'payment_method':
                    $('#ifElsePaymentField').show();
                    break;
                case 'is_affiliate':
                case 'is_not_affiliate':
                case 'has_discount':
                    $('#ifElseBooleanInfo').show();
                    break;
            }
        });

        // If/Else apply properties
        $('#applyIfElseProps').on('click', function() {
            const conditionType = $('#propIfElseConditionType').val();

            if (!conditionType) {
                toastr.error('Please select a condition type.');
                return;
            }

            node.data.conditionType = conditionType;

            // Save condition-specific data
            switch(conditionType) {
                case 'has_tag':
                case 'does_not_have_tag':
                    const $tagSelect = $('#propIfElseTag');
                    if (!$tagSelect.val()) {
                        toastr.error('Please select a tag.');
                        return;
                    }
                    node.data.tagId = $tagSelect.val();
                    node.data.tagName = $tagSelect.find('option:selected').text();
                    node.data.conditionValue = node.data.tagId;
                    node.data.conditionValueLabel = node.data.tagName;
                    break;

                case 'has_course_access':
                case 'no_course_access':
                    const $courseSelect = $('#propIfElseCourseTag');
                    if (!$courseSelect.val()) {
                        toastr.error('Please select a course access tag.');
                        return;
                    }
                    node.data.tagId = $courseSelect.val();
                    node.data.tagName = $courseSelect.find('option:selected').text();
                    node.data.conditionValue = node.data.tagId;
                    node.data.conditionValueLabel = node.data.tagName;
                    break;

                case 'store_in_order':
                    const $storeSelect = $('#propIfElseStore');
                    if (!$storeSelect.val()) {
                        toastr.error('Please select a store.');
                        return;
                    }
                    node.data.storeId = $storeSelect.val();
                    node.data.storeName = $storeSelect.find('option:selected').text();
                    node.data.conditionValue = node.data.storeId;
                    node.data.conditionValueLabel = node.data.storeName;
                    break;

                case 'order_total':
                    const orderTotal = $('#propIfElseOrderTotal').val();
                    const orderOperator = $('#propIfElseOrderOperator').val();
                    if (!orderTotal) {
                        toastr.error('Please enter an order total amount.');
                        return;
                    }
                    node.data.orderTotal = orderTotal;
                    node.data.orderOperator = orderOperator;
                    const operatorLabels = {
                        'greater_than': '>',
                        'less_than': '<',
                        'equals': '=',
                        'greater_or_equal': '≥',
                        'less_or_equal': '≤'
                    };
                    node.data.conditionValue = orderTotal;
                    node.data.conditionValueLabel = `${operatorLabels[orderOperator] || '>'} ₱${parseFloat(orderTotal).toLocaleString()}`;
                    break;

                case 'client_province':
                    const province = $('#propIfElseProvince').val();
                    if (!province) {
                        toastr.error('Please enter a province name.');
                        return;
                    }
                    node.data.conditionValue = province;
                    node.data.conditionValueLabel = province;
                    break;

                case 'payment_method':
                    const $paymentSelect = $('#propIfElsePayment');
                    if (!$paymentSelect.val()) {
                        toastr.error('Please select a payment method.');
                        return;
                    }
                    node.data.conditionValue = $paymentSelect.val();
                    node.data.conditionValueLabel = $paymentSelect.find('option:selected').text();
                    break;

                case 'is_affiliate':
                case 'is_not_affiliate':
                case 'has_discount':
                    // Boolean conditions don't need additional values
                    node.data.conditionValue = '';
                    node.data.conditionValueLabel = '';
                    break;
            }

            updateNodeBodyWithAnimation(node);
            closePropertiesPanel();
            toastr.success('Condition applied!');
        });
    }

    // Close properties panel with animation
    function closePropertiesPanel() {
        $('#propertiesPanel').removeClass('active');
        $('#propertiesOverlay').removeClass('active');
        state.selectedNode = null;
        $('.flow-node').removeClass('selected');
    }

    // Open properties panel with overlay
    function openPropertiesPanel() {
        $('#propertiesPanel').addClass('active');
        $('#propertiesOverlay').addClass('active');
    }

    // Update node body content with pulse animation
    function updateNodeBodyWithAnimation(node) {
        const $node = $(`#${node.id}`);
        $node.find('.flow-node-body').html(getNodeBodyContent(node));

        // Trigger pulse animation
        $node.addClass('node-updating');
        setTimeout(() => {
            $node.removeClass('node-updating');
        }, 300);
    }

    // Update node body content (without animation)
    function updateNodeBody(node) {
        const $node = $(`#${node.id}`);
        $node.find('.flow-node-body').html(getNodeBodyContent(node));
    }

    // Render all nodes (for loading saved flow)
    function renderNodes() {
        state.nodes.forEach(node => renderNode(node));
        // Delay connection rendering to ensure nodes are fully rendered in DOM
        setTimeout(() => {
            expandCanvasToFitNodes();
            renderConnections();
        }, 100);
    }

    // Render connections
    function renderConnections() {
        const svg = $('#flowConnections');
        const $canvas = $('#flowCanvas');
        const canvasOffset = $canvas.offset();

        if (!canvasOffset) return; // Canvas not ready

        svg.empty();

        state.connections.forEach(conn => {
            const $source = $(`#${conn.source}`);
            const $target = $(`#${conn.target}`);

            if (!$source.length || !$target.length) return;

            // Get actual DOM positions relative to canvas
            const sourceOffset = $source.offset();
            const targetOffset = $target.offset();

            // Validate offsets exist
            if (!sourceOffset || !targetOffset) return;

            // Calculate positions relative to canvas
            const sourceX = (sourceOffset.left - canvasOffset.left) + ($source.outerWidth() / 2);
            const sourceY = (sourceOffset.top - canvasOffset.top) + $source.outerHeight();
            const targetX = (targetOffset.left - canvasOffset.left) + ($target.outerWidth() / 2);
            const targetY = (targetOffset.top - canvasOffset.top);

            // Validate positions are reasonable (within canvas bounds + buffer)
            const maxReasonableY = Math.max(2000, $canvas.height() + 500);
            if (sourceY < 0 || targetY < 0 || sourceY > maxReasonableY || targetY > maxReasonableY) {
                return; // Skip invalid positions - will be redrawn when DOM is ready
            }

            // Skip if target appears to be at origin (not yet positioned)
            if (targetY === 0 && targetX === 0) return;

            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.setAttribute('x1', sourceX);
            line.setAttribute('y1', sourceY);
            line.setAttribute('x2', targetX);
            line.setAttribute('y2', targetY);
            line.setAttribute('stroke', '#556ee6');
            line.setAttribute('stroke-width', '2');
            line.setAttribute('marker-end', 'url(#arrowhead)');

            svg.append(line);
        });

        // Add arrowhead marker if not exists
        if (!$('#arrowhead').length) {
            const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            defs.innerHTML = `
                <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                    <polygon points="0 0, 10 3.5, 0 7" fill="#556ee6" />
                </marker>
            `;
            svg.prepend(defs);
        }
    }

    // Update canvas empty state
    function updateCanvasEmptyState() {
        if (state.nodes.length > 0) {
            $('#canvasEmpty').hide();
        } else {
            $('#canvasEmpty').show();
        }
    }

    // Expand canvas to fit all nodes
    function expandCanvasToFitNodes() {
        let maxBottom = 500; // Minimum height
        let maxRight = 0;

        state.nodes.forEach(node => {
            const $node = $(`#${node.id}`);
            if ($node.length) {
                const nodeBottom = node.position.y + $node.outerHeight() + 100; // Add padding
                const nodeRight = node.position.x + $node.outerWidth() + 50;
                if (nodeBottom > maxBottom) maxBottom = nodeBottom;
                if (nodeRight > maxRight) maxRight = nodeRight;
            }
        });

        const $canvas = $('#flowCanvas');
        $canvas.css('min-height', maxBottom + 'px');
        $canvas.css('height', maxBottom + 'px');

        // Also update SVG size (both attribute and style)
        const $svg = $('#flowConnections');
        $svg.attr('height', maxBottom);
        $svg.css('height', maxBottom + 'px');

        console.log('Canvas expanded to height:', maxBottom);
    }

    // Setup general event listeners
    function setupEventListeners() {
        // Close properties panel
        $('#closeProperties').on('click', function() {
            closePropertiesPanel();
        });

        // Close properties when clicking overlay
        $('#propertiesOverlay').on('click', function() {
            closePropertiesPanel();
        });

        // Delete node
        $(document).on('click', '.delete-node-btn', function(e) {
            e.stopPropagation();
            const nodeId = $(this).data('node-id');
            deleteNode(nodeId);
        });

        // Edit node (open properties)
        $(document).on('click', '.edit-node-btn', function(e) {
            e.stopPropagation();
            const nodeId = $(this).data('node-id');
            selectNode(nodeId);
        });

        // Save email content
        $('#saveEmailContent').on('click', function() {
            if (state.selectedNode && state.selectedNode.type === 'email') {
                state.selectedNode.data.subject = $('#emailSubject').val();
                // Get content from HTML builder
                state.selectedNode.data.body = getEmailContent();
                updateNodeBodyWithAnimation(state.selectedNode);
                $('#emailEditorModal').modal('hide');
                closePropertiesPanel();
                toastr.success('Email content saved!');
            }
        });

        // Clear canvas (keep starting node based on flow type)
        $('#clearCanvas').on('click', function() {
            if (confirm('Are you sure you want to clear the canvas? This will remove all nodes except the starting node.')) {
                // Keep only the starting node based on flow type
                const startingNodeTypes = ['trigger_tag', 'course_access_start', 'course_tag_start'];
                const startNode = state.nodes.find(n => startingNodeTypes.includes(n.type));
                state.nodes = startNode ? [startNode] : [];
                state.connections = [];
                $('.flow-node').not('.node-type-trigger_tag, .node-type-course_access_start, .node-type-course_tag_start').remove();
                $('#flowConnections').empty();
                updateCanvasEmptyState();
                $('#propertiesPanel').removeClass('active');
                $('#propertiesOverlay').removeClass('active');
                toastr.success('Canvas cleared!');
            }
        });

        // Save flow
        $('#saveFlow').on('click', function() {
            saveFlow();
        });

        // Connection handling
        $(document).on('mousedown', '.flow-node-connector.output, .flow-node-connector.output-left, .flow-node-connector.output-right', function(e) {
            e.stopPropagation();
            const $connector = $(this);
            const $sourceNode = $connector.closest('.flow-node');
            const sourceId = $sourceNode.attr('id');
            const outputType = $connector.data('output') || 'default';

            // Start drawing a connection line
            startConnection(sourceId, outputType, e);
        });
    }

    // Start connection
    function startConnection(sourceId, outputType, e) {
        const $canvas = $('#flowCanvas');
        const canvasOffset = $canvas.offset();

        const tempLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        tempLine.setAttribute('id', 'tempConnection');
        tempLine.setAttribute('stroke', '#556ee6');
        tempLine.setAttribute('stroke-width', '2');
        tempLine.setAttribute('stroke-dasharray', '5,5');

        const $source = $(`#${sourceId}`);
        const sourceNode = state.nodes.find(n => n.id === sourceId);
        const startX = sourceNode.position.x + $source.width() / 2;
        const startY = sourceNode.position.y + $source.height();

        tempLine.setAttribute('x1', startX);
        tempLine.setAttribute('y1', startY);
        tempLine.setAttribute('x2', startX);
        tempLine.setAttribute('y2', startY);

        $('#flowConnections').append(tempLine);

        $(document).on('mousemove.connection', function(e) {
            const x = e.pageX - canvasOffset.left;
            const y = e.pageY - canvasOffset.top;
            tempLine.setAttribute('x2', x);
            tempLine.setAttribute('y2', y);
        });

        $(document).on('mouseup.connection', function(e) {
            $(document).off('.connection');
            $('#tempConnection').remove();

            // Check if dropped on a node input connector
            const $target = $(e.target);
            if ($target.hasClass('input')) {
                const targetId = $target.closest('.flow-node').attr('id');
                if (targetId && targetId !== sourceId) {
                    // Check if connection already exists
                    const exists = state.connections.some(c => c.source === sourceId && c.target === targetId);
                    if (!exists) {
                        state.connections.push({
                            source: sourceId,
                            target: targetId,
                            type: outputType
                        });
                        renderConnections();
                        toastr.success('Connection created!');
                    }
                }
            }
        });
    }

    // Delete node
    function deleteNode(nodeId) {
        if (!confirm('Delete this node?')) return;

        state.nodes = state.nodes.filter(n => n.id !== nodeId);
        state.connections = state.connections.filter(c => c.source !== nodeId && c.target !== nodeId);

        $(`#${nodeId}`).remove();
        renderConnections();
        updateCanvasEmptyState();

        if (state.selectedNode && state.selectedNode.id === nodeId) {
            state.selectedNode = null;
            $('#propertiesPanel').removeClass('active');
        }

        toastr.success('Node deleted!');
    }

    // Save flow
    function saveFlow() {
        const flowName = $('#flowName').val().trim();
        const flowDescription = $('#flowDescription').val().trim();
        const flowType = state.flowType;

        // Get starting node based on flow type
        let startTagId = '';
        if (flowType === 'trigger') {
            const triggerTagNode = state.nodes.find(n => n.type === 'trigger_tag');
            startTagId = triggerTagNode ? triggerTagNode.data.tagId : '';
        } else if (flowType === 'expiration') {
            const expirationNode = state.nodes.find(n => n.type === 'course_access_start');
            startTagId = expirationNode ? expirationNode.data.tagId : '';
        } else {
            // For order_not_complete, shipping_complete, affiliate_earning
            const courseTagNode = state.nodes.find(n => n.type === 'course_tag_start');
            startTagId = courseTagNode ? courseTagNode.data.tagId : '';
        }

        if (!flowName) {
            toastr.error('Please enter a flow name.');
            $('#flowName').focus();
            return;
        }

        if (!startTagId) {
            let errorMsg = 'Please select a tag in the starting node.';
            if (flowType === 'trigger') {
                errorMsg = 'Please select a trigger tag in the starting node.';
            } else if (flowType === 'expiration') {
                errorMsg = 'Please select a course access tag in the starting node.';
            } else {
                errorMsg = 'Please select a course tag in the starting node.';
            }
            toastr.error(errorMsg);
            return;
        }

        const flowData = {
            nodes: state.nodes,
            connections: state.connections,
            nodeIdCounter: state.nodeIdCounter
        };

        const $btn = $('#saveFlow');
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        const url = state.isEditing ? `/ecom-triggers-update?id=${state.flowId}` : '/ecom-triggers-store';
        const method = state.isEditing ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: {
                flowName: flowName,
                flowDescription: flowDescription,
                flowType: flowType,
                triggerTagId: startTagId,
                flowData: flowData,
                isActive: getFlowStatus(),
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (!state.isEditing) {
                        // Redirect to edit page for the new flow
                        window.location.href = `/ecom-triggers-edit?id=${response.flow.id}`;
                    }
                } else {
                    toastr.error(response.message || 'Failed to save flow.');
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

    // Escape HTML helper
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize the builder
    init();
});
</script>
@endsection
