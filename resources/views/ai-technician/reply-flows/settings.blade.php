@extends('layouts.master')

@section('title') Reply Flow Settings @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .flow-builder-container {
        display: flex;
        gap: 1rem;
        min-height: 550px;
        height: calc(100vh - 350px);
    }

    /* Sidebar with draggable elements */
    .flow-sidebar {
        width: 280px;
        flex-shrink: 0;
        max-height: calc(100vh - 250px);
        overflow-y: auto;
    }

    .flow-category {
        margin-bottom: 1rem;
    }

    .flow-category-header {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #74788d;
        margin-bottom: 0.5rem;
        padding-left: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .flow-element {
        padding: 0.6rem 0.75rem;
        margin-bottom: 0.4rem;
        background: #fff;
        border: 2px solid #e9ecef;
        border-radius: 0.5rem;
        cursor: grab;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.6rem;
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
        width: 32px;
        height: 32px;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
        color: #fff;
    }

    .flow-element-info h6 {
        margin: 0;
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .flow-element-info small {
        color: #6c757d;
        font-size: 0.7rem;
        line-height: 1.2;
    }

    /* Canvas area */
    .flow-canvas-wrapper {
        flex-grow: 1;
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
        border-radius: 0.5rem;
        position: relative;
        overflow: auto;
        min-height: 550px;
        height: calc(100vh - 350px);
    }

    .flow-canvas {
        width: 100%;
        height: 100%;
        min-width: 100%;
        min-height: 100%;
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
        min-width: 200px;
        max-width: 280px;
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
        border-radius: 0.375rem 0.375rem 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .flow-node-header .node-icon {
        width: 26px;
        height: 26px;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        color: #fff;
    }

    .flow-node-number {
        position: absolute;
        top: -10px;
        left: -10px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #495057;
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        z-index: 25;
    }

    .flow-node.start-node .flow-node-number {
        background: #34c38f;
    }

    .flow-node-header .node-title {
        flex-grow: 1;
        font-size: 0.75rem;
        font-weight: 600;
        margin: 0;
        color: #495057;
    }

    .flow-node-header .node-actions {
        display: flex;
        gap: 0.25rem;
    }

    .flow-node-header .node-actions button {
        padding: 0.125rem 0.375rem;
        font-size: 0.7rem;
    }

    .flow-node-body {
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
        color: #495057;
    }

    .flow-node-body .node-summary {
        color: #6c757d;
        font-style: italic;
    }

    .flow-node-connector {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #556ee6;
        border: 2px solid #fff;
        position: absolute;
        cursor: crosshair;
        z-index: 20;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .flow-node-connector.output {
        bottom: -7px;
        left: 50%;
        transform: translateX(-50%);
    }

    .flow-node-connector.input {
        top: -7px;
        left: 50%;
        transform: translateX(-50%);
    }

    /* Branching connectors for If/Else nodes */
    .flow-node-connector.output-yes {
        bottom: -7px;
        left: 30%;
        background: #34c38f;
    }

    .flow-node-connector.output-no {
        bottom: -7px;
        left: 70%;
        background: #f46a6a;
    }

    .flow-node-connector:hover {
        transform: translateX(-50%) scale(1.3);
        box-shadow: 0 0 8px rgba(85, 110, 230, 0.6);
    }

    .flow-node-connector.connecting {
        background: #f1b44c !important;
        animation: pulse 0.8s infinite;
        transform: translateX(-50%) scale(1.3);
    }

    .flow-node-connector.valid-target {
        background: #34c38f !important;
        transform: translateX(-50%) scale(1.4);
        box-shadow: 0 0 10px rgba(52, 195, 143, 0.8);
    }

    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(241, 180, 76, 0.7); }
        50% { box-shadow: 0 0 0 8px rgba(241, 180, 76, 0); }
    }

    /* Start node special styling */
    .flow-node.start-node {
        border-color: #34c38f;
    }

    .flow-node.start-node .flow-node-header {
        background: linear-gradient(135deg, #34c38f 0%, #28a879 100%);
        color: #fff;
    }

    .flow-node.start-node .flow-node-header .node-title {
        color: #fff;
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

    /* Properties panel as floating overlay - fixed to viewport */
    .properties-panel {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.8);
        width: 480px;
        max-width: 95%;
        max-height: 85vh;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 0.75rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        opacity: 0;
        visibility: hidden;
        transition: transform 0.25s ease, opacity 0.25s ease, visibility 0.25s;
        z-index: 1050;
        overflow: hidden;
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
        max-height: calc(85vh - 120px);
        overflow-y: auto;
    }

    .properties-panel-body .form-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #495057;
    }

    .properties-panel-body .form-text {
        font-size: 0.75rem;
    }

    /* Overlay backdrop when properties panel is open - fixed to viewport */
    .properties-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.3);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.25s ease, visibility 0.25s;
        z-index: 1040;
    }

    .properties-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    /* Node drop animation */
    .flow-node {
        animation: nodeDropIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
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

    /* Merge fields buttons */
    .merge-fields-group {
        display: flex;
        flex-wrap: wrap;
        gap: 0.375rem;
        margin-bottom: 0.5rem;
    }

    .merge-field-btn {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
    }

    /* Status toggle styling */
    .status-toggle-wrapper {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .status-badge {
        font-size: 0.8125rem;
        padding: 0.375rem 0.75rem;
    }

    /* Clickable connections */
    .flow-connection {
        cursor: pointer;
        pointer-events: stroke;
        transition: stroke-width 0.2s, filter 0.2s;
    }

    .flow-connection:hover {
        stroke-width: 4px !important;
        filter: drop-shadow(0 0 4px rgba(0, 0, 0, 0.3));
    }

    .flow-connection.selected {
        stroke-width: 4px !important;
        filter: drop-shadow(0 0 6px rgba(244, 106, 106, 0.8));
    }

    /* Connection delete tooltip */
    .connection-delete-tooltip {
        position: absolute;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 0.5rem;
        z-index: 200;
        display: none;
    }

    .connection-delete-tooltip.show {
        display: block;
    }

    .connection-delete-tooltip button {
        white-space: nowrap;
    }

    /* Canvas toolbar - floating draggable */
    .canvas-toolbar {
        position: absolute;
        bottom: 1rem;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        z-index: 50;
        background: #fff;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
        border: 1px solid #e9ecef;
        user-select: none;
    }

    .canvas-toolbar.dragging {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        opacity: 0.95;
    }

    .canvas-toolbar .toolbar-drag-handle {
        cursor: grab;
        padding: 0 0.25rem;
        color: #adb5bd;
        display: flex;
        align-items: center;
    }

    .canvas-toolbar .toolbar-drag-handle:hover {
        color: #556ee6;
    }

    .canvas-toolbar .toolbar-drag-handle:active {
        cursor: grabbing;
    }

    .canvas-toolbar .toolbar-divider {
        width: 1px;
        height: 28px;
        background: #dee2e6;
        margin: 0 0.25rem;
    }

    /* Pan mode section */
    .canvas-toolbar .pan-section {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        color: #495057;
    }

    .canvas-toolbar .pan-section i {
        font-size: 1rem;
        color: #556ee6;
    }

    .canvas-toolbar .pan-section .form-check {
        margin-bottom: 0;
    }

    .canvas-toolbar .pan-section .form-check-input {
        margin-top: 0;
    }

    .canvas-toolbar .pan-section .form-check-label {
        font-weight: 500;
    }

    .canvas-toolbar .pan-section small {
        font-size: 0.7rem;
    }

    /* Zoom section */
    .canvas-toolbar .zoom-section {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .canvas-toolbar .zoom-section .btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .canvas-toolbar .zoom-level {
        text-align: center;
        font-size: 0.75rem;
        font-weight: 600;
        color: #495057;
        padding: 0 0.5rem;
        min-width: 45px;
    }

    /* Canvas zoom transform */
    .flow-canvas {
        transform-origin: 0 0;
        transition: transform 0.1s ease-out;
    }

    .flow-canvas-wrapper {
        overflow: auto;
    }

    /* Canvas grab/pan cursor */
    .flow-canvas-wrapper.grabbable {
        cursor: grab;
    }

    .flow-canvas-wrapper.grabbing {
        cursor: grabbing !important;
        user-select: none;
    }

    .flow-canvas-wrapper.grabbing .flow-canvas {
        pointer-events: none;
    }

    .flow-canvas-wrapper.grabbing .flow-node {
        pointer-events: none;
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') AI Technician @endslot
@slot('title') Reply Flow Settings @endslot
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

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Header Section -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="card-title mb-1 text-dark">Reply Flow Configuration</h5>
                        <p class="text-secondary mb-0">Configure how AI processes and responds to chat queries using a visual flow builder.</p>
                    </div>
                    <div class="status-toggle-wrapper">
                        <span class="text-dark me-2">Flow Status:</span>
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="flowStatus"
                                   style="width: 3rem; height: 1.5rem;"
                                   {{ $flow->isActive ? 'checked' : '' }}>
                        </div>
                        <span class="badge status-badge {{ $flow->isActive ? 'bg-success' : 'bg-secondary' }}" id="statusBadge">
                            {{ $flow->isActive ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                <hr class="mb-4">

                <!-- Flow Builder -->
                <div class="flow-builder-container">
                    <!-- Left Sidebar: Draggable Elements -->
                    <div class="flow-sidebar">
                        <h6 class="text-dark mb-3"><i class="bx bx-cube me-1"></i>Flow Elements</h6>
                        <p class="text-secondary small mb-3">Drag elements to the canvas to build your reply flow.</p>

                        @foreach($nodeTypes as $categoryKey => $category)
                            @if(count($category['nodes']) > 0 && $categoryKey !== 'start')
                                <div class="flow-category">
                                    <div class="flow-category-header">
                                        <i class="bx {{ $category['icon'] }}"></i>
                                        {{ $category['label'] }}
                                    </div>
                                    @foreach($category['nodes'] as $nodeType => $nodeInfo)
                                        <div class="flow-element" draggable="true" data-node-type="{{ $nodeType }}">
                                            <div class="flow-element-icon" style="background-color: {{ $nodeInfo['color'] }}">
                                                <i class="bx {{ $nodeInfo['icon'] }}"></i>
                                            </div>
                                            <div class="flow-element-info">
                                                <h6 class="text-dark">{{ $nodeInfo['label'] }}</h6>
                                                <small>{{ $nodeInfo['description'] }}</small>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Canvas Area -->
                    <div class="flow-canvas-wrapper">
                        <div class="flow-canvas" id="flowCanvas">
                            <svg class="flow-connections" id="flowConnections" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 5; overflow: visible;">
                                <defs>
                                    <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                        <polygon points="0 0, 10 3.5, 0 7" fill="#556ee6" />
                                    </marker>
                                    <marker id="arrowhead-green" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                        <polygon points="0 0, 10 3.5, 0 7" fill="#34c38f" />
                                    </marker>
                                    <marker id="arrowhead-red" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                                        <polygon points="0 0, 10 3.5, 0 7" fill="#f46a6a" />
                                    </marker>
                                </defs>
                            </svg>

                            <div class="flow-canvas-empty" id="canvasEmpty">
                                <i class="bx bx-git-branch"></i>
                                <h5 class="mt-2 text-dark">Start Building Your Reply Flow</h5>
                                <p class="text-secondary">Drag elements from the left panel to design how AI processes and responds to queries</p>
                            </div>
                        </div>

                        <!-- Properties Overlay -->
                        <div class="properties-overlay" id="propertiesOverlay"></div>

                        <!-- Canvas Toolbar (Pan + Zoom) - Draggable -->
                        <div class="canvas-toolbar" id="canvasToolbar">
                            <!-- Drag Handle -->
                            <div class="toolbar-drag-handle" id="toolbarDragHandle" title="Drag to move toolbar">
                                <i class="bx bx-grid-vertical"></i>
                            </div>

                            <div class="toolbar-divider"></div>

                            <!-- Pan Mode Section -->
                            <div class="pan-section">
                                <i class="bx bx-move"></i>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="panModeToggle">
                                    <label class="form-check-label text-dark" for="panModeToggle">Pan</label>
                                </div>
                                <small class="text-secondary">(Space)</small>
                            </div>

                            <div class="toolbar-divider"></div>

                            <!-- Zoom Section -->
                            <div class="zoom-section">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomOut" title="Zoom Out (Ctrl+-)">
                                    <i class="bx bx-minus"></i>
                                </button>
                                <div class="zoom-level" id="zoomLevel">100%</div>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomIn" title="Zoom In (Ctrl++)">
                                    <i class="bx bx-plus"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomReset" title="Reset Zoom (Ctrl+0)">
                                    <i class="bx bx-reset"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomFit" title="Fit to View">
                                    <i class="bx bx-fullscreen"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Connection Delete Tooltip -->
                        <div class="connection-delete-tooltip" id="connectionDeleteTooltip">
                            <button type="button" class="btn btn-sm btn-danger" id="deleteConnectionBtn">
                                <i class="bx bx-trash me-1"></i>Remove Connection
                            </button>
                        </div>

                        <!-- Properties Panel -->
                        <div class="properties-panel" id="propertiesPanel">
                            <div class="properties-panel-header">
                                <h6 class="mb-0 text-dark"><i class="bx bx-cog me-1"></i><span id="propertiesPanelTitle">Properties</span></h6>
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
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-warning" id="resetFlow">
                        <i class="bx bx-reset me-1"></i>Reset to Default
                    </button>
                    <button type="button" class="btn btn-primary" id="saveFlow">
                        <i class="bx bx-save me-1"></i>Save Flow
                    </button>
                </div>
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
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Node type definitions from PHP
    const nodeTypes = @json($nodeTypes);
    const mergeFields = @json($mergeFields);
    const aiApis = @json($aiApis);

    // Flatten node types for easier access
    const flatNodeTypes = {};
    Object.values(nodeTypes).forEach(category => {
        Object.entries(category.nodes).forEach(([type, info]) => {
            flatNodeTypes[type] = info;
        });
    });

    // Flow builder state
    const state = {
        nodes: [],
        connections: [],
        selectedNode: null,
        selectedConnection: null,
        nodeIdCounter: 0,
        flowId: {{ $flow->id }},
        isDragging: false,
        dragNode: null,
        dragOffset: { x: 0, y: 0 },
        // Connection dragging state
        isConnecting: false,
        connectionStart: null,
        tempConnectionLine: null,
        // Zoom state
        zoom: 1,
        minZoom: 0.25,
        maxZoom: 2,
        zoomStep: 0.1,
        // Pan state
        isPanMode: false,
        isPanning: false,
        panStart: { x: 0, y: 0 },
        scrollStart: { x: 0, y: 0 }
    };

    // Load existing flow data
    @if($flow->flowData)
        const existingFlowData = @json($flow->flowData);
        if (existingFlowData && existingFlowData.nodes) {
            state.nodes = existingFlowData.nodes;
            state.connections = existingFlowData.connections || [];

            // Ensure positions are numbers
            state.nodes.forEach(node => {
                if (node.position) {
                    node.position.x = parseFloat(node.position.x) || 0;
                    node.position.y = parseFloat(node.position.y) || 0;
                }
            });

            // Calculate nodeIdCounter
            let maxNodeId = 0;
            state.nodes.forEach(node => {
                const match = node.id.match(/(\d+)/);
                if (match) {
                    const idNum = parseInt(match[1], 10);
                    if (idNum > maxNodeId) maxNodeId = idNum;
                }
            });
            state.nodeIdCounter = Math.max(maxNodeId, state.nodes.length);
        }
    @endif

    // Track if we need to center the start node
    let needsCentering = false;

    // Ensure start node exists
    if (!state.nodes.find(n => n.type === 'start')) {
        needsCentering = true;
        state.nodes.unshift({
            id: 'node_start',
            type: 'start',
            position: { x: -999, y: 80 }, // Placeholder - will be centered in init()
            data: {}
        });
    } else {
        // Check if existing start node needs centering (was saved before fix)
        const existingStart = state.nodes.find(n => n.type === 'start');
        if (existingStart && (existingStart.position.x < 0 || existingStart.position.x === 300)) {
            needsCentering = true;
        }
    }

    // Initialize
    function init() {
        setupDragAndDrop();
        setupEventListeners();
        setupZoomControls();
        setupPanControls();
        setupDraggableToolbar();
        updateCanvasEmptyState();
        setupStatusToggle();

        // Center start node horizontally if needed
        if (needsCentering) {
            // Use requestAnimationFrame + timeout to ensure layout is complete
            requestAnimationFrame(() => {
                setTimeout(() => {
                    centerStartNode();
                }, 150);
            });
        } else {
            // Just render normally
            renderNodes();
            setTimeout(() => renderConnections(), 100);
        }
    }

    // Function to center the start node
    function centerStartNode() {
        const startNode = state.nodes.find(n => n.type === 'start');
        if (!startNode) {
            renderNodes();
            setTimeout(() => renderConnections(), 100);
            return;
        }

        // Get canvas wrapper width (the visible area)
        const wrapper = document.querySelector('.flow-canvas-wrapper');
        let wrapperWidth = 800; // Default fallback

        if (wrapper) {
            const rect = wrapper.getBoundingClientRect();
            wrapperWidth = rect.width > 0 ? rect.width : 800;
        }

        // Also try jQuery as fallback
        if (wrapperWidth <= 0 || wrapperWidth === 800) {
            const $wrapper = $('.flow-canvas-wrapper');
            const jqWidth = $wrapper.innerWidth() || $wrapper.width();
            if (jqWidth > 0) {
                wrapperWidth = jqWidth;
            }
        }

        const nodeWidth = 200; // Approximate node width
        const padding = 32; // Canvas padding (2rem)

        // Calculate center position accounting for canvas padding
        startNode.position.x = Math.max(50, ((wrapperWidth - padding * 2) / 2) - (nodeWidth / 2));
        startNode.position.y = 80;

        console.log('Centering start node - wrapper width:', wrapperWidth, 'node x:', startNode.position.x);

        // Render after centering
        renderNodes();
        setTimeout(() => renderConnections(), 100);
    }

    // Setup drag and drop
    function setupDragAndDrop() {
        // Sidebar elements drag
        $('.flow-element').on('dragstart', function(e) {
            const nodeType = $(this).data('node-type');
            e.originalEvent.dataTransfer.setData('nodeType', nodeType);
            $(this).addClass('dragging');
        });

        $('.flow-element').on('dragend', function() {
            $(this).removeClass('dragging');
        });

        // Canvas drop zone
        const $canvas = $('#flowCanvas');

        $canvas.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('drag-over');
        });

        $canvas.on('dragleave', function() {
            $(this).removeClass('drag-over');
        });

        $canvas.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('drag-over');

            const nodeType = e.originalEvent.dataTransfer.getData('nodeType');
            if (!nodeType || !flatNodeTypes[nodeType]) return;

            // Get drop position relative to canvas
            const canvasRect = this.getBoundingClientRect();
            const x = e.originalEvent.clientX - canvasRect.left - 100; // Center the node
            const y = e.originalEvent.clientY - canvasRect.top - 30;

            addNode(nodeType, Math.max(0, x), Math.max(0, y));
        });
    }

    // Add a new node
    function addNode(type, x, y) {
        state.nodeIdCounter++;
        const nodeId = `node_${state.nodeIdCounter}`;

        const node = {
            id: nodeId,
            type: type,
            position: { x: x, y: y },
            data: getDefaultNodeData(type)
        };

        state.nodes.push(node);
        renderNode(node);
        updateCanvasEmptyState();

        toastr.success(`Added ${flatNodeTypes[type]?.label || type} node`);
    }

    // Get node number (1-based index)
    function getNodeNumber(nodeId) {
        const index = state.nodes.findIndex(n => n.id === nodeId);
        return index + 1;
    }

    // Get node label with number
    function getNodeLabel(nodeId) {
        const node = state.nodes.find(n => n.id === nodeId);
        if (!node) return 'Unknown';
        const typeInfo = flatNodeTypes[node.type] || { label: node.type };
        const number = getNodeNumber(nodeId);
        // Use custom name if set, otherwise use default type label
        const displayName = node.data?.customName || typeInfo.label;
        return `#${number} ${displayName}`;
    }

    // Get incoming connections to a node (elements connected TO this node)
    function getIncomingConnections(nodeId) {
        return state.connections
            .filter(c => c.to === nodeId)
            .map(c => ({
                nodeId: c.from,
                connector: c.fromConnector,
                label: getNodeLabel(c.from)
            }));
    }

    // Get default data for a node type
    function getDefaultNodeData(type) {
        const defaults = {
            'query': { queryText: '', aiApiId: '' },
            'rag_docs_query': { queryText: '', topK: 5, threshold: 0.7 },
            'rag_websites_query': { queryText: '', topK: 5, threshold: 0.7 },
            'rag_images_query': { queryText: '', topK: 5, threshold: 0.7 },
            'online_query': { queryText: '', aiApiId: '', maxResults: 3 },
            'api_query': { queryText: '', aiApiId: '', apiEndpoint: '', method: 'POST' },
            'if_else_image': { queryText: '', aiApiId: '' },
            'if_else_query': { queryText: '', aiApiId: '' },
            'personality': { personalityText: '', sampleConversations: '' },
            'thinking_reply': { queryText: '', aiApiId: '', staticReplies: [] },
            'output': { outputType: 'response', responseTemplate: '' },
        };
        return defaults[type] || {};
    }

    // Render all nodes
    function renderNodes() {
        state.nodes.forEach(node => renderNode(node));
    }

    // Render a single node
    function renderNode(node) {
        const typeInfo = flatNodeTypes[node.type] || { label: node.type, icon: 'bx-cube', color: '#556ee6' };
        const isStart = node.type === 'start';
        const hasBranching = typeInfo.hasBranching;

        // Calculate node number based on position in array
        const nodeNumber = getNodeNumber(node.id);

        // Use custom name if set, otherwise use default type label
        const displayName = node.data?.customName || typeInfo.label;

        let connectorsHtml = '';
        if (typeInfo.hasInput !== false && !isStart) {
            connectorsHtml += '<div class="flow-node-connector input" data-connector="input"></div>';
        }
        if (hasBranching) {
            connectorsHtml += `
                <div class="flow-node-connector output-yes" data-connector="output-yes" title="Yes"></div>
                <div class="flow-node-connector output-no" data-connector="output-no" title="No"></div>
            `;
        } else if (typeInfo.hasOutput !== false) {
            connectorsHtml += '<div class="flow-node-connector output" data-connector="output"></div>';
        }

        let nodeHtml = `
            <div class="flow-node ${isStart ? 'start-node' : ''}" id="${node.id}"
                 style="left: ${node.position.x}px; top: ${node.position.y}px;" data-node-number="${nodeNumber}">
                <div class="flow-node-number">${nodeNumber}</div>
                <div class="flow-node-header">
                    <div class="node-icon" style="background-color: ${typeInfo.color}">
                        <i class="bx ${typeInfo.icon}"></i>
                    </div>
                    <h6 class="node-title"><span>${displayName}</span></h6>
                    <div class="node-actions">
                        ${!isStart ? `
                            <button type="button" class="btn btn-sm btn-outline-primary edit-node" data-node-id="${node.id}" title="Edit">
                                <i class="bx bx-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-node" data-node-id="${node.id}" title="Delete">
                                <i class="bx bx-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
                <div class="flow-node-body">
                    <span class="node-summary">${getNodeSummary(node)}</span>
                </div>
                ${connectorsHtml}
            </div>
        `;

        $('#flowCanvas').append(nodeHtml);

        const $node = $(`#${node.id}`);
        setupNodeDragging($node, node);
        setupConnectorHandlers($node, node);
    }

    // Get a summary text for the node body
    function getNodeSummary(node) {
        const data = node.data || {};
        const apiName = data.aiApiId ? (aiApis.find(a => a.id == data.aiApiId)?.apiName || 'API set') : 'No API selected';

        switch (node.type) {
            case 'start': return 'Flow entry point - receives user message';
            case 'query': return data.queryText ? 'Query configured' : 'Configure query text';
            case 'rag_docs_query': return `Docs RAG (top ${data.topK || 5})`;
            case 'rag_websites_query': return `Websites RAG (top ${data.topK || 5})`;
            case 'rag_images_query': return `Images RAG (top ${data.topK || 5})`;
            case 'online_query': return `Web search (${data.maxResults || 3} results)`;
            case 'api_query': return data.apiEndpoint ? 'API configured' : 'Configure API endpoint';
            case 'if_else_image': return data.queryText ? 'Image analysis configured' : 'Check if message has images';
            case 'if_else_query': return data.queryText ? 'Condition set' : 'Configure yes/no question';
            case 'personality':
                const hasConversations = data.sampleConversations?.trim()?.length > 0;
                return data.personalityText ? (hasConversations ? 'Personality & samples set' : 'Personality set') : 'Define AI personality';
            case 'thinking_reply':
                const replyCount = data.staticReplies?.length || 0;
                return data.queryText ? `Configured (${replyCount} static)` : 'Set thinking responses';
            case 'blocker':
                return data.scopeQuery ? 'Scope check configured' : 'Define allowed topics';
            case 'output': return data.responseTemplate ? 'Output configured' : 'End of flow';
            default: return 'Configure node';
        }
    }

    // Setup node dragging
    function setupNodeDragging($node, nodeData) {
        $node.on('mousedown', function(e) {
            if ($(e.target).closest('.node-actions, .flow-node-connector').length) return;

            const $canvas = $('#flowCanvas');
            const canvasOffset = $canvas.offset();

            state.isDragging = true;
            state.dragNode = nodeData;

            // Calculate offset accounting for zoom
            const mouseX = (e.pageX - canvasOffset.left) / state.zoom;
            const mouseY = (e.pageY - canvasOffset.top) / state.zoom;

            state.dragOffset = {
                x: mouseX - nodeData.position.x,
                y: mouseY - nodeData.position.y
            };

            $node.css('z-index', 100);
        });
    }

    // Setup connector handlers for drag-to-connect
    function setupConnectorHandlers($node, nodeData) {
        $node.find('.flow-node-connector').on('mousedown', function(e) {
            e.stopPropagation();
            e.preventDefault();

            const connectorType = $(this).data('connector');
            const $connector = $(this);

            // Start connection from output connectors
            if (connectorType.startsWith('output') || connectorType === 'input') {
                state.isConnecting = true;
                state.connectionStart = {
                    nodeId: nodeData.id,
                    connector: connectorType,
                    element: $connector,
                    isOutput: connectorType.startsWith('output')
                };
                $connector.addClass('connecting');

                // Create temporary line
                const pos = getConnectorPosition($connector);
                state.tempConnectionLine = {
                    startX: pos.x,
                    startY: pos.y,
                    endX: pos.x,
                    endY: pos.y
                };
            }
        });

        // Highlight connectors on hover during connection
        $node.find('.flow-node-connector').on('mouseenter', function() {
            if (state.isConnecting && state.connectionStart) {
                const connectorType = $(this).data('connector');
                const isValidTarget = (state.connectionStart.isOutput && connectorType === 'input') ||
                                     (!state.connectionStart.isOutput && connectorType.startsWith('output'));

                if (isValidTarget && state.connectionStart.nodeId !== nodeData.id) {
                    $(this).addClass('valid-target');
                }
            }
        });

        $node.find('.flow-node-connector').on('mouseleave', function() {
            $(this).removeClass('valid-target');
        });

        // Complete connection on mouseup over a connector
        $node.find('.flow-node-connector').on('mouseup', function(e) {
            if (!state.isConnecting || !state.connectionStart) return;

            const connectorType = $(this).data('connector');
            const startIsOutput = state.connectionStart.isOutput;

            // Determine from/to based on which connector was dragged from
            let fromNodeId, toNodeId, fromConnector, toConnector;

            if (startIsOutput && connectorType === 'input') {
                // Dragged from output to input
                fromNodeId = state.connectionStart.nodeId;
                toNodeId = nodeData.id;
                fromConnector = state.connectionStart.connector;
                toConnector = 'input';
            } else if (!startIsOutput && connectorType.startsWith('output')) {
                // Dragged from input to output (reverse)
                fromNodeId = nodeData.id;
                toNodeId = state.connectionStart.nodeId;
                fromConnector = connectorType;
                toConnector = 'input';
            } else {
                return; // Invalid connection
            }

            // Don't connect to self
            if (fromNodeId === toNodeId) return;

            // Check for exact duplicate connection
            const exists = state.connections.some(c =>
                c.from === fromNodeId &&
                c.to === toNodeId &&
                c.fromConnector === fromConnector &&
                c.toConnector === toConnector
            );

            if (!exists) {
                state.connections.push({
                    from: fromNodeId,
                    to: toNodeId,
                    fromConnector: fromConnector,
                    toConnector: toConnector
                });
                renderConnections();
                toastr.success('Connection created');
            }
        });
    }

    // Global mouse move for connection dragging
    $(document).on('mousemove', function(e) {
        // Handle node dragging
        if (state.isDragging && state.dragNode) {
            const $canvas = $('#flowCanvas');
            const canvasOffset = $canvas.offset();

            // Account for zoom when calculating position
            let newX = (e.pageX - canvasOffset.left) / state.zoom - state.dragOffset.x;
            let newY = (e.pageY - canvasOffset.top) / state.zoom - state.dragOffset.y;

            newX = Math.max(0, newX);
            newY = Math.max(0, newY);

            state.dragNode.position.x = newX;
            state.dragNode.position.y = newY;

            $(`#${state.dragNode.id}`).css({
                left: newX + 'px',
                top: newY + 'px'
            });

            renderConnections();
        }

        // Handle connection dragging
        if (state.isConnecting && state.tempConnectionLine) {
            const $canvas = $('#flowCanvas');
            const canvasOffset = $canvas.offset();

            // Account for zoom when calculating position
            state.tempConnectionLine.endX = (e.pageX - canvasOffset.left) / state.zoom;
            state.tempConnectionLine.endY = (e.pageY - canvasOffset.top) / state.zoom;

            renderConnections();
        }
    });

    // Global mouse up to cancel connection or finish node drag
    $(document).on('mouseup', function(e) {
        // Finish node dragging
        if (state.isDragging && state.dragNode) {
            $(`#${state.dragNode.id}`).css('z-index', 10);
            state.isDragging = false;
            state.dragNode = null;
        }

        // Cancel connection if not dropped on valid target
        if (state.isConnecting) {
            $('.flow-node-connector').removeClass('connecting valid-target');
            state.isConnecting = false;
            state.connectionStart = null;
            state.tempConnectionLine = null;
            renderConnections();
        }
    });

    // Render connections
    function renderConnections() {
        const svg = document.getElementById('flowConnections');
        if (!svg) return;

        const paths = svg.querySelectorAll('path');
        paths.forEach(p => p.remove());

        // Render existing connections
        state.connections.forEach((conn, index) => {
            const $fromNode = $(`#${conn.from}`);
            const $toNode = $(`#${conn.to}`);

            if (!$fromNode.length || !$toNode.length) return;

            const $fromConnector = $fromNode.find(`[data-connector="${conn.fromConnector}"]`);
            const $toConnector = $toNode.find(`[data-connector="input"]`);

            if (!$fromConnector.length || !$toConnector.length) return;

            const fromPos = getConnectorPosition($fromConnector);
            const toPos = getConnectorPosition($toConnector);

            const midY = (fromPos.y + toPos.y) / 2;
            const pathD = `M ${fromPos.x} ${fromPos.y} C ${fromPos.x} ${midY}, ${toPos.x} ${midY}, ${toPos.x} ${toPos.y}`;

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', pathD);
            path.setAttribute('data-connection-index', index);
            path.classList.add('flow-connection');

            // Color based on connector type
            if (conn.fromConnector === 'output-yes') {
                path.setAttribute('stroke', '#34c38f');
                path.setAttribute('marker-end', 'url(#arrowhead-green)');
            } else if (conn.fromConnector === 'output-no') {
                path.setAttribute('stroke', '#f46a6a');
                path.setAttribute('marker-end', 'url(#arrowhead-red)');
            } else {
                path.setAttribute('stroke', '#556ee6');
                path.setAttribute('marker-end', 'url(#arrowhead)');
            }

            path.setAttribute('stroke-width', '2');
            path.setAttribute('fill', 'none');

            // Mark as selected if this is the selected connection
            if (state.selectedConnection === index) {
                path.classList.add('selected');
            }

            svg.appendChild(path);
        });

        // Render temporary connection line while dragging
        if (state.isConnecting && state.tempConnectionLine) {
            const { startX, startY, endX, endY } = state.tempConnectionLine;
            const midY = (startY + endY) / 2;
            const pathD = `M ${startX} ${startY} C ${startX} ${midY}, ${endX} ${midY}, ${endX} ${endY}`;

            const tempPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            tempPath.setAttribute('d', pathD);
            tempPath.setAttribute('stroke', '#f1b44c');
            tempPath.setAttribute('stroke-width', '2');
            tempPath.setAttribute('stroke-dasharray', '5,5');
            tempPath.setAttribute('fill', 'none');
            tempPath.classList.add('temp-connection');
            svg.appendChild(tempPath);
        }
    }

    function getConnectorPosition($connector) {
        const $canvas = $('#flowCanvas');
        const canvasOffset = $canvas.offset();
        const connectorOffset = $connector.offset();

        // Account for zoom when calculating connector positions
        return {
            x: (connectorOffset.left - canvasOffset.left) / state.zoom + ($connector.width() / 2) / state.zoom,
            y: (connectorOffset.top - canvasOffset.top) / state.zoom + ($connector.height() / 2) / state.zoom
        };
    }

    // Update canvas empty state
    function updateCanvasEmptyState() {
        if (state.nodes.length > 1) {
            $('#canvasEmpty').hide();
        } else {
            $('#canvasEmpty').show();
        }
    }

    // Setup event listeners
    function setupEventListeners() {
        // Edit node
        $(document).on('click', '.edit-node', function(e) {
            e.stopPropagation();
            const nodeId = $(this).data('node-id');
            const node = state.nodes.find(n => n.id === nodeId);
            if (node) openPropertiesPanel(node);
        });

        // Delete node
        $(document).on('click', '.delete-node', function(e) {
            e.stopPropagation();
            const nodeId = $(this).data('node-id');
            deleteNode(nodeId);
        });

        // Close properties panel
        $('#closeProperties, #propertiesOverlay').on('click', function() {
            closePropertiesPanel();
        });

        // Reset flow
        $('#resetFlow').on('click', function() {
            if (confirm('Are you sure you want to reset the flow to default? This will remove all nodes except the start node.')) {
                resetFlow();
            }
        });

        // Save flow
        $('#saveFlow').on('click', function() {
            saveFlow();
        });

        // Connection click handler
        $(document).on('click', '.flow-connection', function(e) {
            e.stopPropagation();
            const index = parseInt($(this).attr('data-connection-index'));
            selectConnection(index, e);
        });

        // Delete connection button
        $('#deleteConnectionBtn').on('click', function() {
            if (state.selectedConnection !== null) {
                deleteConnection(state.selectedConnection);
            }
        });

        // Hide tooltip when clicking elsewhere
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.flow-connection, #connectionDeleteTooltip').length) {
                hideConnectionTooltip();
            }
        });

        // Hide tooltip on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                hideConnectionTooltip();
            }
            // Delete selected connection with Delete key
            if (e.key === 'Delete' && state.selectedConnection !== null) {
                deleteConnection(state.selectedConnection);
            }
        });
    }

    // Select a connection and show delete tooltip
    function selectConnection(index, event) {
        state.selectedConnection = index;
        renderConnections();

        // Position and show the tooltip
        const $tooltip = $('#connectionDeleteTooltip');
        const $canvas = $('#flowCanvas');
        const canvasOffset = $canvas.offset();

        // Position tooltip near the click
        const tooltipX = event.pageX - canvasOffset.left + 10;
        const tooltipY = event.pageY - canvasOffset.top - 10;

        $tooltip.css({
            left: tooltipX + 'px',
            top: tooltipY + 'px'
        }).addClass('show');
    }

    // Hide connection delete tooltip
    function hideConnectionTooltip() {
        state.selectedConnection = null;
        $('#connectionDeleteTooltip').removeClass('show');
        renderConnections();
    }

    // Delete a connection
    function deleteConnection(index) {
        if (index >= 0 && index < state.connections.length) {
            state.connections.splice(index, 1);
            hideConnectionTooltip();
            renderConnections();
            toastr.success('Connection removed');
        }
    }

    // ==================== ZOOM FUNCTIONS ====================

    // Apply zoom to canvas
    function applyZoom() {
        const $canvas = $('#flowCanvas');
        $canvas.css('transform', `scale(${state.zoom})`);
        $('#zoomLevel').text(Math.round(state.zoom * 100) + '%');

        // Adjust canvas size to maintain proper scrolling
        const baseWidth = Math.max(1200, getCanvasContentWidth() + 200);
        const baseHeight = Math.max(800, getCanvasContentHeight() + 200);
        $canvas.css({
            'min-width': baseWidth + 'px',
            'min-height': baseHeight + 'px'
        });

        renderConnections();
    }

    // Get the width needed to contain all nodes
    function getCanvasContentWidth() {
        let maxX = 0;
        state.nodes.forEach(node => {
            const nodeRight = node.position.x + 280; // node width
            if (nodeRight > maxX) maxX = nodeRight;
        });
        return maxX;
    }

    // Get the height needed to contain all nodes
    function getCanvasContentHeight() {
        let maxY = 0;
        state.nodes.forEach(node => {
            const nodeBottom = node.position.y + 150; // approximate node height
            if (nodeBottom > maxY) maxY = nodeBottom;
        });
        return maxY;
    }

    // Zoom in
    function zoomIn() {
        if (state.zoom < state.maxZoom) {
            state.zoom = Math.min(state.maxZoom, state.zoom + state.zoomStep);
            state.zoom = Math.round(state.zoom * 100) / 100; // Round to 2 decimals
            applyZoom();
        }
    }

    // Zoom out
    function zoomOut() {
        if (state.zoom > state.minZoom) {
            state.zoom = Math.max(state.minZoom, state.zoom - state.zoomStep);
            state.zoom = Math.round(state.zoom * 100) / 100;
            applyZoom();
        }
    }

    // Reset zoom to 100%
    function zoomReset() {
        state.zoom = 1;
        applyZoom();
    }

    // Fit all nodes in view
    function zoomFit() {
        if (state.nodes.length <= 1) {
            zoomReset();
            return;
        }

        const $wrapper = $('.flow-canvas-wrapper');
        const wrapperWidth = $wrapper.width() - 40; // padding
        const wrapperHeight = $wrapper.height() - 40;

        const contentWidth = getCanvasContentWidth();
        const contentHeight = getCanvasContentHeight();

        if (contentWidth === 0 || contentHeight === 0) {
            zoomReset();
            return;
        }

        const scaleX = wrapperWidth / contentWidth;
        const scaleY = wrapperHeight / contentHeight;
        let newZoom = Math.min(scaleX, scaleY);

        // Clamp to min/max
        newZoom = Math.max(state.minZoom, Math.min(state.maxZoom, newZoom));
        state.zoom = Math.round(newZoom * 100) / 100;

        applyZoom();

        // Scroll to top-left
        $wrapper.scrollTop(0).scrollLeft(0);
    }

    // Setup zoom event handlers
    function setupZoomControls() {
        // Zoom buttons
        $('#zoomIn').on('click', zoomIn);
        $('#zoomOut').on('click', zoomOut);
        $('#zoomReset').on('click', zoomReset);
        $('#zoomFit').on('click', zoomFit);

        // Mouse wheel zoom (works without Ctrl key)
        $('.flow-canvas-wrapper').on('wheel', function(e) {
            e.preventDefault();
            if (e.originalEvent.deltaY < 0) {
                zoomIn();
            } else {
                zoomOut();
            }
        });

        // Keyboard shortcuts for zoom
        $(document).on('keydown', function(e) {
            // Only when not in input/textarea
            if ($(e.target).is('input, textarea, select')) return;

            if (e.ctrlKey || e.metaKey) {
                if (e.key === '=' || e.key === '+') {
                    e.preventDefault();
                    zoomIn();
                } else if (e.key === '-') {
                    e.preventDefault();
                    zoomOut();
                } else if (e.key === '0') {
                    e.preventDefault();
                    zoomReset();
                }
            }
        });
    }

    // ==================== TOOLBAR DRAGGING ====================

    // Setup draggable toolbar
    function setupDraggableToolbar() {
        const $toolbar = $('#canvasToolbar');
        const $handle = $('#toolbarDragHandle');
        const $wrapper = $('.flow-canvas-wrapper');

        let isDragging = false;
        let dragOffset = { x: 0, y: 0 };

        $handle.on('mousedown', function(e) {
            e.preventDefault();
            isDragging = true;
            $toolbar.addClass('dragging');

            const toolbarRect = $toolbar[0].getBoundingClientRect();
            const wrapperRect = $wrapper[0].getBoundingClientRect();

            // Calculate offset from mouse to toolbar top-left
            dragOffset = {
                x: e.clientX - toolbarRect.left,
                y: e.clientY - toolbarRect.top
            };

            // Convert to scroll-aware position (includes scroll offset)
            const scrollLeft = $wrapper.scrollLeft();
            const scrollTop = $wrapper.scrollTop();
            const currentLeft = toolbarRect.left - wrapperRect.left + scrollLeft;
            const currentTop = toolbarRect.top - wrapperRect.top + scrollTop;

            $toolbar.css({
                'left': currentLeft + 'px',
                'top': currentTop + 'px',
                'bottom': 'auto',
                'transform': 'none'
            });
        });

        $(document).on('mousemove.toolbar', function(e) {
            if (!isDragging) return;

            const wrapperRect = $wrapper[0].getBoundingClientRect();
            const toolbarWidth = $toolbar.outerWidth();
            const toolbarHeight = $toolbar.outerHeight();
            const scrollLeft = $wrapper.scrollLeft();
            const scrollTop = $wrapper.scrollTop();

            // Calculate new position relative to wrapper (including scroll)
            let newLeft = e.clientX - wrapperRect.left + scrollLeft - dragOffset.x;
            let newTop = e.clientY - wrapperRect.top + scrollTop - dragOffset.y;

            // Get the total scrollable area
            const totalWidth = $wrapper[0].scrollWidth;
            const totalHeight = $wrapper[0].scrollHeight;

            // Constrain to total scrollable area bounds
            newLeft = Math.max(10, Math.min(newLeft, totalWidth - toolbarWidth - 10));
            newTop = Math.max(10, Math.min(newTop, totalHeight - toolbarHeight - 10));

            $toolbar.css({
                'left': newLeft + 'px',
                'top': newTop + 'px'
            });
        });

        $(document).on('mouseup.toolbar', function() {
            if (isDragging) {
                isDragging = false;
                $toolbar.removeClass('dragging');
            }
        });
    }

    // ==================== PAN FUNCTIONS ====================

    // Setup pan/grab controls
    function setupPanControls() {
        const $wrapper = $('.flow-canvas-wrapper');

        // Pan mode toggle
        $('#panModeToggle').on('change', function() {
            state.isPanMode = $(this).is(':checked');
            updatePanCursor();
        });

        // Space key to temporarily enable pan mode
        $(document).on('keydown', function(e) {
            if (e.code === 'Space' && !$(e.target).is('input, textarea, select')) {
                e.preventDefault();
                if (!state.isPanMode) {
                    state.isPanMode = true;
                    updatePanCursor();
                }
            }
        });

        $(document).on('keyup', function(e) {
            if (e.code === 'Space' && !$('#panModeToggle').is(':checked')) {
                state.isPanMode = false;
                updatePanCursor();
            }
        });

        // Mouse down on wrapper to start panning
        $wrapper.on('mousedown', function(e) {
            // Only pan if in pan mode and clicking on empty canvas area (not on nodes)
            if (state.isPanMode && ($(e.target).is('.flow-canvas, .flow-canvas-wrapper') || $(e.target).closest('.flow-canvas-empty').length)) {
                e.preventDefault();
                state.isPanning = true;
                state.panStart = { x: e.clientX, y: e.clientY };
                state.scrollStart = { x: $wrapper.scrollLeft(), y: $wrapper.scrollTop() };
                $wrapper.addClass('grabbing');
            }
        });

        // Mouse move for panning
        $(document).on('mousemove.pan', function(e) {
            if (state.isPanning) {
                const dx = e.clientX - state.panStart.x;
                const dy = e.clientY - state.panStart.y;

                $wrapper.scrollLeft(state.scrollStart.x - dx);
                $wrapper.scrollTop(state.scrollStart.y - dy);
            }
        });

        // Mouse up to stop panning
        $(document).on('mouseup.pan', function() {
            if (state.isPanning) {
                state.isPanning = false;
                $wrapper.removeClass('grabbing');
            }
        });

        // Middle mouse button to pan (always works, regardless of pan mode)
        $wrapper.on('mousedown', function(e) {
            if (e.which === 2) { // Middle mouse button
                e.preventDefault();
                state.isPanning = true;
                state.panStart = { x: e.clientX, y: e.clientY };
                state.scrollStart = { x: $wrapper.scrollLeft(), y: $wrapper.scrollTop() };
                $wrapper.addClass('grabbing');
            }
        });
    }

    // Update cursor based on pan mode
    function updatePanCursor() {
        const $wrapper = $('.flow-canvas-wrapper');
        if (state.isPanMode) {
            $wrapper.addClass('grabbable');
        } else {
            $wrapper.removeClass('grabbable');
        }
    }

    // Delete node
    function deleteNode(nodeId) {
        state.nodes = state.nodes.filter(n => n.id !== nodeId);
        state.connections = state.connections.filter(c => c.from !== nodeId && c.to !== nodeId);

        $(`#${nodeId}`).remove();

        renderConnections();
        updateCanvasEmptyState();
        toastr.success('Node deleted');
    }

    // Reset flow
    function resetFlow() {
        const $btn = $('#resetFlow');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Resetting...');

        $.ajax({
            url: '/ai-technician-reply-flow/reset',
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
                toastr.error(xhr.responseJSON?.message || 'Failed to reset flow.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-reset me-1"></i> Reset to Default');
            }
        });
    }

    // Open properties panel
    function openPropertiesPanel(node) {
        state.selectedNode = node;
        const typeInfo = flatNodeTypes[node.type] || { label: node.type };

        $('#propertiesPanelTitle').text(typeInfo.label + ' Properties');
        $('#propertiesPanelBody').html(getPropertiesForm(node));
        $('#propertiesPanel, #propertiesOverlay').addClass('active');

        // Setup merge field buttons
        setupMergeFieldButtons();

        // Setup static reply handlers for thinking_reply node
        if (node.type === 'thinking_reply') {
            setupStaticReplyHandlers();
        }
    }

    // Setup handlers for static replies
    function setupStaticReplyHandlers() {
        // Add static reply
        $(document).off('click', '#addStaticReply').on('click', '#addStaticReply', function() {
            const container = $('#staticRepliesContainer');

            // Remove "no replies" message if present
            $('#noStaticReplies').remove();

            const newReply = `
                <div class="input-group mb-2 static-reply-item">
                    <input type="text" class="form-control static-reply-input" placeholder="e.g., Let me check on that...">
                    <button type="button" class="btn btn-outline-danger remove-static-reply">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            `;
            container.append(newReply);
        });

        // Remove static reply
        $(document).off('click', '.remove-static-reply').on('click', '.remove-static-reply', function() {
            $(this).closest('.static-reply-item').remove();

            // Show "no replies" message if empty
            if ($('#staticRepliesContainer .static-reply-item').length === 0) {
                $('#staticRepliesContainer').html('<p class="text-secondary text-center py-2 mb-0" id="noStaticReplies"><i class="bx bx-info-circle me-1"></i>No static replies added</p>');
            }
        });
    }

    // Close properties panel
    function closePropertiesPanel() {
        if (state.selectedNode) {
            saveNodeProperties(state.selectedNode);
            const typeInfo = flatNodeTypes[state.selectedNode.type] || { label: state.selectedNode.type };
            const displayName = state.selectedNode.data?.customName || typeInfo.label;
            toastr.success(`${displayName} settings saved`, 'Element Updated');
        }
        $('#propertiesPanel, #propertiesOverlay').removeClass('active');
        state.selectedNode = null;
    }

    // Setup merge field buttons
    function setupMergeFieldButtons() {
        $(document).off('click', '.merge-field-btn').on('click', '.merge-field-btn', function(e) {
            e.preventDefault();
            const field = $(this).data('field');

            // Find the textarea - could be in the same .mb-3 container or in the next one
            let $textarea = $(this).closest('.mb-3').find('textarea');
            if (!$textarea.length) {
                // For dropdown items, look in the parent properties body
                $textarea = $('#propertiesPanelBody').find('textarea').first();
            }

            if ($textarea.length && field) {
                const cursorPos = $textarea[0].selectionStart || $textarea.val().length;
                const textBefore = $textarea.val().substring(0, cursorPos);
                const textAfter = $textarea.val().substring(cursorPos);
                $textarea.val(textBefore + field + textAfter);
                $textarea.focus();
                $textarea[0].selectionStart = $textarea[0].selectionEnd = cursorPos + field.length;
            }
        });
    }

    // Get merge fields HTML
    function getMergeFieldsHtml() {
        // Get incoming connections for the currently selected node
        const incomingConnections = state.selectedNode ? getIncomingConnections(state.selectedNode.id) : [];

        let html = '<div class="merge-fields-group">';

        // Previous Element Output - show as dropdown if there are incoming connections
        if (incomingConnections.length > 0) {
            html += `
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-link me-1"></i>Previous Output
                    </button>
                    <ul class="dropdown-menu">
            `;
            incomingConnections.forEach(conn => {
                const field = '@{{output_' + conn.nodeId + '}}';
                html += '<li><a class="dropdown-item merge-field-btn" href="#" data-field="' + field + '">' + conn.label + '</a></li>';
            });
            html += `
                    </ul>
                </div>
            `;
        } else {
            html += `<button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Connect elements to use their output"><i class="bx bx-link me-1"></i>Previous Output</button>`;
        }

        // User Message button
        html += `<button type="button" class="btn btn-outline-info btn-sm merge-field-btn" data-field="@{{user_message}}" title="User Message">User Message</button>`;

        // Chat History button
        html += `<button type="button" class="btn btn-outline-info btn-sm merge-field-btn" data-field="@{{chat_history}}" title="Chat History">Chat History</button>`;

        html += '</div>';
        return html;
    }

    // Get element name input HTML
    function getElementNameHtml(customName) {
        return `
            <div class="mb-3">
                <label class="form-label"><i class="bx bx-rename me-1"></i>Element Name</label>
                <input type="text" class="form-control" id="propCustomName" value="${customName || ''}" placeholder="Enter a custom name for this element...">
                <small class="form-text text-secondary">Custom name will appear in merge field dropdown</small>
            </div>
            <hr class="my-3">
        `;
    }

    // Get AI API selector HTML
    function getAiApiSelectorHtml(selectedId) {
        let html = `
            <div class="mb-3">
                <label class="form-label">AI API</label>
                <select class="form-select" id="propAiApiId">
                    <option value="">Select AI API...</option>
        `;
        aiApis.forEach(api => {
            const label = api.provider_label + (api.defaultModel ? ` (${api.defaultModel})` : '');
            html += `<option value="${api.id}" ${api.id == selectedId ? 'selected' : ''}>${label}</option>`;
        });
        html += `
                </select>
                <small class="form-text text-secondary">Select which AI API to use for this query</small>
            </div>
        `;
        return html;
    }

    // Get properties form HTML
    function getPropertiesForm(node) {
        const data = node.data || {};

        switch (node.type) {
            case 'query':
                return `
                    ${getElementNameHtml(data.customName)}
                    ${getAiApiSelectorHtml(data.aiApiId)}
                    <div class="mb-3">
                        <label class="form-label">Query Text</label>
                        ${getMergeFieldsHtml()}
                        <textarea class="form-control" id="propQueryText" rows="4" placeholder="Enter your query text. Use merge fields to include dynamic content...">${data.queryText || ''}</textarea>
                        <small class="form-text text-secondary">This query will be sent to the AI for processing</small>
                    </div>
                `;

            case 'rag_docs_query':
                return `
                    ${getElementNameHtml(data.customName)}
                    <div class="mb-3">
                        <label class="form-label">Search Query</label>
                        ${getMergeFieldsHtml()}
                        <textarea class="form-control" id="propQueryText" rows="4" placeholder="Enter what to search in the Documents RAG...">${data.queryText || ''}</textarea>
                        <small class="form-text text-secondary">This will search the <strong>Documents</strong> knowledge base and return matching content</small>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Top K Results</label>
                                <input type="number" class="form-control" id="propTopK" value="${data.topK || 5}" min="1" max="20">
                                <small class="form-text text-secondary">Number of results to retrieve</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Similarity Threshold</label>
                                <input type="number" class="form-control" id="propThreshold" value="${data.threshold || 0.7}" min="0" max="1" step="0.1">
                                <small class="form-text text-secondary">Minimum relevance score (0-1)</small>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="bx bx-info-circle me-1"></i>
                        Configure your Documents RAG in <a href="/ai-technician-kb-docs-settings" target="_blank">Knowledgebase → Docs</a>
                    </div>
                `;

            case 'rag_websites_query':
                return `
                    ${getElementNameHtml(data.customName)}
                    <div class="mb-3">
                        <label class="form-label">Search Query</label>
                        ${getMergeFieldsHtml()}
                        <textarea class="form-control" id="propQueryText" rows="4" placeholder="Enter what to search in the Websites RAG...">${data.queryText || ''}</textarea>
                        <small class="form-text text-secondary">This will search the <strong>Websites</strong> knowledge base and return matching content</small>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Top K Results</label>
                                <input type="number" class="form-control" id="propTopK" value="${data.topK || 5}" min="1" max="20">
                                <small class="form-text text-secondary">Number of results to retrieve</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Similarity Threshold</label>
                                <input type="number" class="form-control" id="propThreshold" value="${data.threshold || 0.7}" min="0" max="1" step="0.1">
                                <small class="form-text text-secondary">Minimum relevance score (0-1)</small>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="bx bx-info-circle me-1"></i>
                        Configure your Websites RAG in <a href="/ai-technician-kb-websites-settings" target="_blank">Knowledgebase → Websites</a>
                    </div>
                `;

            case 'rag_images_query':
                return `
                    ${getElementNameHtml(data.customName)}
                    <div class="mb-3">
                        <label class="form-label">Search Query</label>
                        ${getMergeFieldsHtml()}
                        <textarea class="form-control" id="propQueryText" rows="4" placeholder="Enter what to search in the Images RAG...">${data.queryText || ''}</textarea>
                        <small class="form-text text-secondary">This will search the <strong>Images</strong> knowledge base and return matching content</small>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Top K Results</label>
                                <input type="number" class="form-control" id="propTopK" value="${data.topK || 5}" min="1" max="20">
                                <small class="form-text text-secondary">Number of results to retrieve</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Similarity Threshold</label>
                                <input type="number" class="form-control" id="propThreshold" value="${data.threshold || 0.7}" min="0" max="1" step="0.1">
                                <small class="form-text text-secondary">Minimum relevance score (0-1)</small>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="bx bx-info-circle me-1"></i>
                        Configure your Images RAG in <a href="/ai-technician-kb-images-settings" target="_blank">Knowledgebase → Images</a>
                    </div>
                `;

            // Legacy - keeping for backward compatibility
            case 'website_query':
                return `
                    ${getElementNameHtml(data.customName)}
                    ${getAiApiSelectorHtml(data.aiApiId)}
                    <div class="mb-3">
                        <label class="form-label">Query Text</label>
                        ${getMergeFieldsHtml()}
                        <textarea class="form-control" id="propQueryText" rows="4" placeholder="Enter your website query text...">${data.queryText || ''}</textarea>
                        <small class="form-text text-secondary">This query will search configured websites</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Website Selection</label>
                        <p class="text-secondary small mb-0">Website selection will be available in a future update. Currently uses all active websites.</p>
                    </div>
                `;

            case 'online_query':
                return `
                    ${getElementNameHtml(data.customName)}
                    ${getAiApiSelectorHtml(data.aiApiId)}
                    <div class="mb-3">
                        <label class="form-label">Query Text</label>
                        ${getMergeFieldsHtml()}
                        <textarea class="form-control" id="propQueryText" rows="4" placeholder="Enter your online search query...">${data.queryText || ''}</textarea>
                        <small class="form-text text-secondary">This query will search the internet</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Max Results</label>
                        <input type="number" class="form-control" id="propMaxResults" value="${data.maxResults || 3}" min="1" max="10">
                    </div>
                `;

            case 'api_query':
                return `
                    ${getElementNameHtml(data.customName)}
                    ${getAiApiSelectorHtml(data.aiApiId)}
                    <div class="mb-3">
                        <label class="form-label">Query Text</label>
                        ${getMergeFieldsHtml()}
                        <textarea class="form-control" id="propQueryText" rows="4" placeholder="Enter query to process API response...">${data.queryText || ''}</textarea>
                        <small class="form-text text-secondary">This query will process the API response</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">API Endpoint</label>
                        <input type="url" class="form-control" id="propApiEndpoint" value="${data.apiEndpoint || ''}" placeholder="https://api.example.com/endpoint">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">HTTP Method</label>
                        <select class="form-select" id="propApiMethod">
                            <option value="GET" ${data.method === 'GET' ? 'selected' : ''}>GET</option>
                            <option value="POST" ${data.method === 'POST' ? 'selected' : ''}>POST</option>
                            <option value="PUT" ${data.method === 'PUT' ? 'selected' : ''}>PUT</option>
                        </select>
                    </div>
                `;

            case 'if_else_image':
                return `
                    ${getElementNameHtml(data.customName)}
                    <div class="alert alert-info mb-3">
                        <i class="bx bx-info-circle me-1"></i>
                        This node checks if the user's message contains images.
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <p class="text-dark mb-1"><strong><span class="text-success">Yes path:</span></strong></p>
                            <small class="text-secondary">Message contains images - AI will analyze them</small>
                        </div>
                        <div class="col-6">
                            <p class="text-dark mb-1"><strong><span class="text-danger">No path:</span></strong></p>
                            <small class="text-secondary">Message contains no images</small>
                        </div>
                    </div>
                    <hr>
                    <h6 class="text-dark mb-3"><i class="bx bx-image-alt me-1"></i>Image Analysis (Yes Path)</h6>
                    ${getAiApiSelectorHtml(data.aiApiId)}
                    <div class="mb-3">
                        <label class="form-label">Analysis Query</label>
                        ${getMergeFieldsHtml()}
                        <textarea class="form-control" id="propQueryText" rows="4" placeholder="Enter the query to analyze the uploaded images...&#10;&#10;Example: Describe what you see in this image. What objects, people, or text are visible?">${data.queryText || ''}</textarea>
                        <small class="form-text text-secondary">This query will be sent to the AI along with the uploaded images for analysis</small>
                    </div>
                `;

            case 'if_else_query':
                return `
                    ${getElementNameHtml(data.customName)}
                    ${getAiApiSelectorHtml(data.aiApiId)}
                    <div class="mb-3">
                        <label class="form-label">Yes/No Question</label>
                        ${getMergeFieldsHtml()}
                        <textarea class="form-control" id="propQueryText" rows="4" placeholder="Enter a yes/no question for the AI to decide...">${data.queryText || ''}</textarea>
                        <small class="form-text text-secondary">The AI will analyze and decide Yes or No based on this question</small>
                    </div>
                    <div class="alert alert-secondary mb-0">
                        <small>
                            <strong>Yes path:</strong> AI determines the answer is Yes<br>
                            <strong>No path:</strong> AI determines the answer is No
                        </small>
                    </div>
                `;

            case 'thinking_reply':
                const staticReplies = data.staticReplies || [];
                let staticRepliesHtml = '';
                staticReplies.forEach((reply, index) => {
                    staticRepliesHtml += `
                        <div class="input-group mb-2 static-reply-item" data-index="${index}">
                            <input type="text" class="form-control static-reply-input" value="${reply}" placeholder="e.g., Let me check on that...">
                            <button type="button" class="btn btn-outline-danger remove-static-reply" data-index="${index}">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    `;
                });

                return `
                    ${getElementNameHtml(data.customName)}
                    <div class="alert alert-warning mb-3">
                        <i class="bx bx-time-five me-1"></i>
                        Configure what the AI says while processing the user's request. This gives users feedback that the AI is working on their query.
                    </div>

                    <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#thinkingAiTab" role="tab">
                                <i class="bx bx-bot me-1"></i>AI Generated
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#thinkingStaticTab" role="tab">
                                <i class="bx bx-list-ul me-1"></i>Static Replies
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="thinkingAiTab" role="tabpanel">
                            ${getAiApiSelectorHtml(data.aiApiId)}
                            <div class="mb-3">
                                <label class="form-label">Query for Thinking Response</label>
                                ${getMergeFieldsHtml()}
                                <textarea class="form-control" id="propQueryText" rows="4" placeholder="Generate a brief, friendly message to let the user know you're working on their request...&#10;&#10;Example: Based on the user's message, generate a short (1-2 sentences) thinking message. Be friendly and acknowledge what they asked about.">${data.queryText || ''}</textarea>
                                <small class="form-text text-secondary">The AI will generate a contextual "thinking" response based on this query</small>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="thinkingStaticTab" role="tabpanel">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Static Thinking Replies</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addStaticReply">
                                        <i class="bx bx-plus me-1"></i>Add Reply
                                    </button>
                                </div>
                                <small class="form-text text-secondary mb-3 d-block">Add static replies that will be randomly selected (used as fallback if AI query is empty)</small>
                                <div id="staticRepliesContainer">
                                    ${staticRepliesHtml || '<p class="text-secondary text-center py-2 mb-0" id="noStaticReplies"><i class="bx bx-info-circle me-1"></i>No static replies added</p>'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;

            case 'personality':
                return `
                    ${getElementNameHtml(data.customName)}
                    <div class="alert alert-info mb-3">
                        <i class="bx bx-info-circle me-1"></i>
                        Define the AI's personality and provide sample conversations to guide its behavior.
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bx bx-user-voice me-1"></i>AI Personality</label>
                        <textarea class="form-control" id="propPersonalityText" rows="4" placeholder="Describe the AI's personality, tone, and behavior...&#10;&#10;Example: You are a friendly and helpful customer service assistant. You speak in a warm, professional tone and always try to resolve issues efficiently. You use simple language and avoid technical jargon unless necessary.">${data.personalityText || ''}</textarea>
                        <small class="form-text text-secondary">Describe how the AI should behave, its tone, style, and any specific instructions</small>
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                        <label class="form-label"><i class="bx bx-conversation me-1"></i>Sample Conversations</label>
                        <textarea class="form-control" id="propSampleConversations" rows="8" placeholder="Write example conversations to train the AI...&#10;&#10;Example:&#10;User: What are your business hours?&#10;Assistant: We're open Monday to Friday, 9 AM to 6 PM. Is there anything specific I can help you with?&#10;&#10;User: I want to return a product&#10;Assistant: I'd be happy to help with your return! Could you please provide your order number so I can look up the details?">${data.sampleConversations || ''}</textarea>
                        <small class="form-text text-secondary">Write example user/assistant conversations to show the AI how to respond. Use "User:" and "Assistant:" prefixes.</small>
                    </div>
                `;

            case 'blocker':
                return `
                    ${getElementNameHtml(data.customName)}
                    <div class="alert alert-danger mb-3">
                        <i class="bx bx-shield-quarter me-1"></i>
                        <strong>Gate Keeper:</strong> This element checks if the user's question is within the allowed scope BEFORE processing. If blocked, the flow stops and returns the block message.
                    </div>
                    ${getAiApiSelectorHtml(data.aiApiId)}
                    <div class="mb-3">
                        <label class="form-label"><i class="bx bx-target-lock me-1"></i>Scope Definition</label>
                        ${getMergeFieldsHtml()}
                        <textarea class="form-control" id="propScopeQuery" rows="5" placeholder="Define what topics/questions are ALLOWED. The AI will check if the user's message matches this scope.&#10;&#10;Example:&#10;Is this question related to:&#10;- Agriculture and farming&#10;- Pest management&#10;- Fertilizer application&#10;- Crop health&#10;&#10;Answer YES if the question is about any of these topics, otherwise NO.">${data.scopeQuery || ''}</textarea>
                        <small class="form-text text-secondary">Define the allowed topics. AI will answer YES (allow) or NO (block) based on this criteria.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bx bx-block me-1"></i>Block Message</label>
                        <textarea class="form-control" id="propBlockMessage" rows="3" placeholder="Message to show when the question is blocked...&#10;&#10;Example: Pasensya na po, ang tanong mo ay hindi sakop ng aking expertise. Ako po ay isang AI assistant para sa agriculture at farming topics lamang. 🙏">${data.blockMessage || ''}</textarea>
                        <small class="form-text text-secondary">This message is returned when the user's question is outside the allowed scope</small>
                    </div>
                `;

            case 'output':
                return `
                    ${getElementNameHtml(data.customName)}
                    <div class="alert alert-success mb-3">
                        <i class="bx bx-check-circle me-1"></i>
                        This is the end point of the flow. The response will be sent to the user.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Output Type</label>
                        <select class="form-select" id="propOutputType">
                            <option value="response" ${data.outputType === 'response' ? 'selected' : ''}>Send Response</option>
                            <option value="silent" ${data.outputType === 'silent' ? 'selected' : ''}>Silent (No Response)</option>
                        </select>
                        <small class="form-text text-secondary">Choose how this output behaves</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">AI API <span class="text-danger">*</span></label>
                        <select class="form-select" id="propAiApiId">
                            <option value="">-- No AI (Return template as-is) --</option>
                            ${aiApis.map(api => `<option value="${api.id}" ${data.aiApiId == api.id ? 'selected' : ''}>${api.provider_label}${api.defaultModel ? ' (' + api.defaultModel + ')' : ''}</option>`).join('')}
                        </select>
                        <small class="form-text text-secondary">Select an AI to process the template and generate the final response</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Response Template</label>
                        ${getMergeFieldsHtml()}
                        <textarea class="form-control" id="propResponseTemplate" rows="6" placeholder="Build your final prompt using merge fields. The AI will process this to generate the response.">${data.responseTemplate || ''}</textarea>
                        <small class="form-text text-secondary">Use merge fields to combine outputs from previous nodes. The AI will process this prompt.</small>
                    </div>
                `;

            default:
                return '<p class="text-secondary">No properties available for this node.</p>';
        }
    }

    // Save node properties
    function saveNodeProperties(node) {
        const data = node.data || {};

        // Save custom name for all node types (except start)
        if (node.type !== 'start') {
            const customName = $('#propCustomName').val()?.trim();
            data.customName = customName || null;
        }

        switch (node.type) {
            case 'query':
                data.aiApiId = $('#propAiApiId').val();
                data.queryText = $('#propQueryText').val();
                break;

            case 'rag_docs_query':
            case 'rag_websites_query':
            case 'rag_images_query':
                data.queryText = $('#propQueryText').val();
                data.topK = parseInt($('#propTopK').val()) || 5;
                data.threshold = parseFloat($('#propThreshold').val()) || 0.7;
                break;

            // Legacy - keeping for backward compatibility
            case 'website_query':
                data.aiApiId = $('#propAiApiId').val();
                data.queryText = $('#propQueryText').val();
                break;

            case 'online_query':
                data.aiApiId = $('#propAiApiId').val();
                data.queryText = $('#propQueryText').val();
                data.maxResults = parseInt($('#propMaxResults').val()) || 3;
                break;

            case 'api_query':
                data.aiApiId = $('#propAiApiId').val();
                data.queryText = $('#propQueryText').val();
                data.apiEndpoint = $('#propApiEndpoint').val();
                data.method = $('#propApiMethod').val();
                break;

            case 'if_else_image':
                data.aiApiId = $('#propAiApiId').val();
                data.queryText = $('#propQueryText').val();
                break;

            case 'if_else_query':
                data.aiApiId = $('#propAiApiId').val();
                data.queryText = $('#propQueryText').val();
                break;

            case 'thinking_reply':
                data.aiApiId = $('#propAiApiId').val();
                data.queryText = $('#propQueryText').val();
                // Collect static replies
                const staticReplies = [];
                $('#staticRepliesContainer .static-reply-input').each(function() {
                    const reply = $(this).val()?.trim();
                    if (reply) {
                        staticReplies.push(reply);
                    }
                });
                data.staticReplies = staticReplies;
                break;

            case 'personality':
                data.personalityText = $('#propPersonalityText').val();
                data.sampleConversations = $('#propSampleConversations').val();
                break;

            case 'blocker':
                data.aiApiId = $('#propAiApiId').val();
                data.scopeQuery = $('#propScopeQuery').val();
                data.blockMessage = $('#propBlockMessage').val();
                break;

            case 'output':
                data.outputType = $('#propOutputType').val();
                data.aiApiId = $('#propAiApiId').val();
                data.responseTemplate = $('#propResponseTemplate').val();
                break;
        }

        node.data = data;
        // Update the node header to show custom name if set
        const typeInfo = flatNodeTypes[node.type] || { label: node.type };
        const displayName = data.customName || typeInfo.label;
        $(`#${node.id} .node-title span`).text(displayName);
        $(`#${node.id} .node-summary`).text(getNodeSummary(node));
    }

    // Setup status toggle
    function setupStatusToggle() {
        $('#flowStatus').on('change', function() {
            const isActive = $(this).is(':checked');

            $.ajax({
                url: '/ai-technician-reply-flow/toggle',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        $('#statusBadge')
                            .removeClass('bg-success bg-secondary')
                            .addClass(response.data.isActive ? 'bg-success' : 'bg-secondary')
                            .text(response.data.isActive ? 'Active' : 'Inactive');
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                        // Revert toggle
                        $('#flowStatus').prop('checked', !isActive);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to toggle status.');
                    // Revert toggle
                    $('#flowStatus').prop('checked', !isActive);
                }
            });
        });
    }

    // Save flow
    function saveFlow() {
        const $btn = $('#saveFlow');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        const flowData = {
            nodes: state.nodes,
            connections: state.connections,
            nodeIdCounter: state.nodeIdCounter
        };

        $.ajax({
            url: '/ai-technician-reply-flow/save',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                flowData: flowData,
                isActive: $('#flowStatus').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save flow.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Flow');
            }
        });
    }

    // Initialize the builder
    init();

    // Fallback: Re-center on window load if still not properly positioned
    if (needsCentering) {
        $(window).on('load', function() {
            const startNode = state.nodes.find(n => n.type === 'start');
            if (startNode) {
                const wrapper = document.querySelector('.flow-canvas-wrapper');
                if (wrapper) {
                    const rect = wrapper.getBoundingClientRect();
                    if (rect.width > 0) {
                        const nodeWidth = 200;
                        const padding = 32;
                        const expectedX = Math.max(50, ((rect.width - padding * 2) / 2) - (nodeWidth / 2));

                        // Only update if significantly different (more than 50px off)
                        if (Math.abs(startNode.position.x - expectedX) > 50) {
                            startNode.position.x = expectedX;
                            const $startNodeEl = $('#node_start, #' + startNode.id);
                            if ($startNodeEl.length) {
                                $startNodeEl.css('left', startNode.position.x + 'px');
                                renderConnections();
                                console.log('Window load fallback - re-centered to x:', startNode.position.x);
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endsection
