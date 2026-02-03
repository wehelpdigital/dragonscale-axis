@extends('layouts.master')

@section('title') {{ isset($flow) ? 'Edit' : 'Create' }} Reply Flow @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .flow-builder-container {
        display: flex;
        gap: 1rem;
        min-height: 550px;
        height: calc(100vh - 400px);
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
        height: calc(100vh - 400px);
    }

    .flow-canvas {
        width: 100%;
        height: 100%;
        min-width: 2000px;
        min-height: 1500px;
        padding: 2rem;
        position: relative;
        /* Grid background */
        background-image:
            linear-gradient(rgba(85, 110, 230, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(85, 110, 230, 0.05) 1px, transparent 1px);
        background-size: 20px 20px;
        background-position: -1px -1px;
    }

    .flow-canvas.drag-over {
        background-color: #e3e8f0;
        border-color: #556ee6;
    }

    /* Grid snap indicator */
    .grid-snap-indicator {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background: rgba(85, 110, 230, 0.85);
        color: #fff;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 11px;
        z-index: 50;
        display: flex;
        align-items: center;
        gap: 5px;
        pointer-events: none;
    }

    .grid-snap-indicator i {
        font-size: 13px;
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

    /* Branching connectors */
    .flow-node-connector.output-true {
        bottom: -7px;
        left: 30%;
        background: #34c38f;
    }

    .flow-node-connector.output-false {
        bottom: -7px;
        left: 70%;
        background: #f46a6a;
    }

    /* Multi-output connectors */
    .flow-node-connector.output-1 { bottom: -7px; left: 20%; }
    .flow-node-connector.output-2 { bottom: -7px; left: 40%; }
    .flow-node-connector.output-3 { bottom: -7px; left: 60%; }
    .flow-node-connector.output-4 { bottom: -7px; left: 80%; }

    .flow-node-connector:hover {
        transform: translateX(-50%) scale(1.3);
        box-shadow: 0 0 8px rgba(85, 110, 230, 0.6);
    }

    .flow-node-connector.connecting {
        background: #f1b44c !important;
        animation: pulse 0.8s infinite;
        transform: translateX(-50%) scale(1.3);
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

    /* Connection lines */
    .flow-connection {
        position: absolute;
        pointer-events: none;
        z-index: 5;
    }

    .flow-connection path {
        stroke: #556ee6;
        stroke-width: 2;
        fill: none;
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

    /* Properties panel as floating overlay */
    .properties-panel {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0.8);
        width: 400px;
        max-width: 95%;
        max-height: 80vh;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 0.75rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        opacity: 0;
        visibility: hidden;
        transition: transform 0.25s ease, opacity 0.25s ease, visibility 0.25s;
        z-index: 100;
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
        max-height: calc(80vh - 120px);
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

    /* Condition builder */
    .condition-row {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .condition-row select, .condition-row input {
        font-size: 0.8125rem;
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') AI Technician @endslot
@slot('li_2') Reply Flows @endslot
@slot('title') {{ isset($flow) ? 'Edit' : 'Create' }} Flow @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Flow Info Section -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label for="flowName" class="form-label text-dark">Flow Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="flowName" name="flowName"
                               placeholder="Enter flow name" value="{{ $flow->flowName ?? '' }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="flowDescription" class="form-label text-dark">Description</label>
                        <input type="text" class="form-control" id="flowDescription" name="flowDescription"
                               placeholder="Optional description" value="{{ $flow->flowDescription ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label for="flowPriority" class="form-label text-dark">Priority</label>
                        <input type="number" class="form-control" id="flowPriority" name="flowPriority"
                               value="{{ $flow->priority ?? 0 }}" min="0" max="100">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-dark">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="flowStatus" name="flowStatus"
                                   style="width: 3rem; height: 1.5rem;"
                                   {{ (isset($flow) && $flow->isActive) || !isset($flow) ? 'checked' : '' }}>
                            <label class="form-check-label ms-2 text-dark" for="flowStatus" id="flowStatusLabel">
                                {{ isset($flow) && !$flow->isActive ? 'Inactive' : 'Active' }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="flowIsDefault" name="flowIsDefault"
                                   {{ (isset($flow) && $flow->isDefault) ? 'checked' : '' }}>
                            <label class="form-check-label text-dark" for="flowIsDefault">
                                Set as default flow <small class="text-secondary">(used when no other flow matches)</small>
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
                        <p class="text-secondary small mb-3">Drag elements to the canvas to build your reply flow.</p>

                        @php
                            // Categories to show (hiding: source, processing, filter, response, action for now)
                            $visibleCategories = ['start', 'analysis', 'control', 'flow'];
                        @endphp
                        @foreach($nodeTypes as $categoryKey => $category)
                            @if(count($category['nodes']) > 0 && in_array($categoryKey, $visibleCategories))
                                <div class="flow-category">
                                    <div class="flow-category-header">
                                        <i class="bx {{ $category['icon'] }}"></i>
                                        {{ $category['label'] }}
                                    </div>
                                    @foreach($category['nodes'] as $nodeType => $nodeInfo)
                                        @if($nodeType !== 'start')
                                        <div class="flow-element" draggable="true" data-node-type="{{ $nodeType }}">
                                            <div class="flow-element-icon" style="background-color: {{ $nodeInfo['color'] }}">
                                                <i class="bx {{ $nodeInfo['icon'] }}"></i>
                                            </div>
                                            <div class="flow-element-info">
                                                <h6 class="text-dark">{{ $nodeInfo['label'] }}</h6>
                                                <small>{{ $nodeInfo['description'] }}</small>
                                            </div>
                                        </div>
                                        @endif
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
                                </defs>
                            </svg>

                            <div class="flow-canvas-empty" id="canvasEmpty">
                                <i class="bx bx-git-branch"></i>
                                <h5 class="mt-2 text-dark">Start Building Your Reply Flow</h5>
                                <p class="text-secondary">Drag elements from the left panel to design how AI processes and responds to queries</p>
                            </div>
                        </div>

                        <!-- Grid Snap Indicator -->
                        <div class="grid-snap-indicator">
                            <i class="bx bx-grid-alt"></i>
                            <span>Grid Snap: 20px</span>
                        </div>

                        <!-- Properties Overlay -->
                        <div class="properties-overlay" id="propertiesOverlay"></div>

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
                <div class="d-flex justify-content-between">
                    <a href="{{ route('ai-technician.reply-flows') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i>Back to Flows
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-info" id="autoAlignNodes" title="Auto-align all nodes vertically">
                            <i class="bx bx-align-middle me-1"></i>Auto-Align
                        </button>
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

    // Flatten node types for easier access
    const flatNodeTypes = {};
    Object.values(nodeTypes).forEach(category => {
        Object.entries(category.nodes).forEach(([type, info]) => {
            flatNodeTypes[type] = info;
        });
    });

    // Grid settings
    const GRID_SIZE = 20;
    const GRID_SNAP_ENABLED = true;

    // Snap value to grid
    function snapToGrid(value) {
        if (!GRID_SNAP_ENABLED) return value;
        return Math.round(value / GRID_SIZE) * GRID_SIZE;
    }

    // Flow builder state
    const state = {
        nodes: [],
        connections: [],
        selectedNode: null,
        nodeIdCounter: 0,
        isEditing: {{ isset($flow) ? 'true' : 'false' }},
        flowId: {{ isset($flow) ? $flow->id : 'null' }},
        isDragging: false,
        dragNode: null,
        dragOffset: { x: 0, y: 0 }
    };

    // Load existing flow data if editing
    @if(isset($flow) && $flow->flowData)
        const existingFlowData = @json($flow->flowData);
        if (existingFlowData && existingFlowData.nodes) {
            state.nodes = existingFlowData.nodes;
            state.connections = existingFlowData.connections || [];

            // Ensure positions are numbers and snapped to grid
            state.nodes.forEach(node => {
                if (node.position) {
                    node.position.x = snapToGrid(parseFloat(node.position.x) || 0);
                    node.position.y = snapToGrid(parseFloat(node.position.y) || 0);
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

    // Track if this is a new flow (no saved nodes or only has start node)
    const isNewFlow = state.nodes.length === 0;
    let needsCentering = isNewFlow;

    // Ensure start node exists
    if (!state.nodes.find(n => n.type === 'start')) {
        needsCentering = true;
        state.nodes.unshift({
            id: 'node_start',
            type: 'start',
            position: { x: -999, y: snapToGrid(80) }, // Use -999 as flag to center later
            data: {}
        });
    } else {
        // Check if start node needs centering (has placeholder position)
        const existingStart = state.nodes.find(n => n.type === 'start');
        if (existingStart && (existingStart.position.x < 0 || existingStart.position.x === 50)) {
            needsCentering = true;
        }
    }

    // Initialize
    function init() {
        setupDragAndDrop();
        setupEventListeners();
        updateCanvasEmptyState();
        setupStatusToggle();

        // Center start node horizontally if needed
        if (needsCentering) {
            // Use requestAnimationFrame + multiple fallbacks to ensure canvas is fully rendered
            requestAnimationFrame(() => {
                // Wait for layout to complete
                setTimeout(() => {
                    centerStartNode();
                }, 200);
            });
        } else {
            // Just render normally
            renderNodes();
            setTimeout(() => renderConnections(), 100);
        }
    }

    // Separate function to center start node - can be called after layout
    function centerStartNode() {
        const startNode = state.nodes.find(n => n.type === 'start');
        if (!startNode) {
            renderNodes();
            return;
        }

        // Use getBoundingClientRect for accurate dimensions
        const wrapper = document.querySelector('.flow-canvas-wrapper');
        let wrapperWidth = 800; // Default fallback

        if (wrapper) {
            const rect = wrapper.getBoundingClientRect();
            wrapperWidth = rect.width > 0 ? rect.width : 800;
        }

        // Also try jQuery as fallback
        if (wrapperWidth <= 0 || wrapperWidth === 800) {
            const $wrapper = $('.flow-canvas-wrapper');
            const jqWidth = $wrapper.innerWidth() || $wrapper.width() || $wrapper.parent().width();
            if (jqWidth > 0) {
                wrapperWidth = jqWidth;
            }
        }

        const nodeWidth = 220; // Approximate node width (slightly larger for padding)

        // Calculate center position and snap to grid
        // Use a fixed center X that works well with the visible area
        let centerX = Math.max(100, (wrapperWidth / 2) - (nodeWidth / 2));
        startNode.position.x = snapToGrid(centerX);
        startNode.position.y = snapToGrid(60); // Start a bit higher

        console.log('Centering start node - wrapper width:', wrapperWidth, 'node x:', startNode.position.x);

        // Render after centering
        renderNodes();
        setTimeout(() => {
            renderConnections();
            // Scroll canvas to show start node if needed
            if (wrapper) {
                wrapper.scrollLeft = 0;
                wrapper.scrollTop = 0;
            }
        }, 50);
    }

    // Get the center X position (for aligning new nodes)
    function getCenterX() {
        const startNode = state.nodes.find(n => n.type === 'start');
        if (startNode) {
            return startNode.position.x;
        }

        // Fallback: calculate from wrapper
        const wrapper = document.querySelector('.flow-canvas-wrapper');
        if (wrapper) {
            const rect = wrapper.getBoundingClientRect();
            return snapToGrid((rect.width / 2) - 110);
        }
        return snapToGrid(300); // Default fallback
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

            // Find the last node to auto-connect to
            const lastNode = findLastNodeInFlow();

            // Auto-snap position below the last node, aligned to center
            let x, y;
            if (lastNode) {
                // Align with center X (same as start node) for consistency
                x = getCenterX();
                y = lastNode.position.y + 140; // Position below with spacing
            } else {
                x = getCenterX();
                y = snapToGrid(60);
            }

            // Snap to grid
            x = snapToGrid(Math.max(0, x));
            y = snapToGrid(Math.max(0, y));

            addNode(nodeType, x, y, lastNode);
        });
    }

    // Find the last node in the flow (the one with no outgoing connections or the deepest one)
    function findLastNodeInFlow() {
        if (state.nodes.length === 0) return null;

        // Find nodes that have no outgoing connections
        const nodesWithOutgoing = new Set(state.connections.map(c => c.from));

        // Get nodes that don't have outgoing connections
        let candidateNodes = state.nodes.filter(n => !nodesWithOutgoing.has(n.id));

        // If all nodes have outgoing connections, just use the last added node
        if (candidateNodes.length === 0) {
            candidateNodes = state.nodes;
        }

        // Return the node with the highest Y position (lowest on canvas)
        return candidateNodes.reduce((lowest, node) => {
            if (!lowest || node.position.y > lowest.position.y) {
                return node;
            }
            return lowest;
        }, null);
    }

    // Add a new node with optional auto-connect
    function addNode(type, x, y, connectFromNode = null) {
        state.nodeIdCounter++;
        const nodeId = `node_${state.nodeIdCounter}`;

        // Ensure position is snapped to grid
        const node = {
            id: nodeId,
            type: type,
            position: { x: snapToGrid(x), y: snapToGrid(y) },
            data: getDefaultNodeData(type)
        };

        state.nodes.push(node);
        renderNode(node);
        updateCanvasEmptyState();

        // Auto-connect from the previous node if provided
        if (connectFromNode) {
            const typeInfo = flatNodeTypes[connectFromNode.type] || {};
            const fromConnector = typeInfo.hasBranching ? 'output-true' : 'output';

            // Only connect if the new node has an input
            const newTypeInfo = flatNodeTypes[type] || {};
            if (newTypeInfo.hasInput !== false) {
                state.connections.push({
                    from: connectFromNode.id,
                    to: nodeId,
                    fromConnector: fromConnector,
                    toConnector: 'input'
                });
                renderConnections();
            }
        }

        toastr.success(`Added ${flatNodeTypes[type]?.label || type} node`);
    }

    // Get default data for a node type
    function getDefaultNodeData(type) {
        const defaults = {
            'query': { analyzeIntent: true, extractEntities: true },
            'source_rag': { topK: 5, threshold: 0.7 },
            'source_online': { maxResults: 3, safeSearch: true },
            'source_website': { websiteIds: [], maxPages: 5 },
            'if_else': { condition: '', operator: 'equals', value: '' },
            'switch': { variable: '', cases: [] },
            'loop': { maxIterations: 10 },
            'history': { messageCount: 10, includeSystem: false },
            'context': { contextText: '' },
            'memory': { action: 'get', key: '' },
            'response': { systemPrompt: '', temperature: 0.7, maxTokens: 1000 },
            'template': { templateText: '' },
            'filter_topic': { topics: [], matchType: 'any' },
            'filter_keywords': { keywords: [], matchType: 'any', caseSensitive: false },
            'filter_sentiment': { sentiment: 'positive', threshold: 0.5 },
            'delay': { seconds: 1 },
            'webhook': { url: '', method: 'POST', headers: {} },
            'log': { message: '', level: 'info' },
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
        const multiOutput = typeInfo.multiOutput;

        let nodeHtml = `
            <div class="flow-node ${isStart ? 'start-node' : ''}" id="${node.id}"
                 style="left: ${node.position.x}px; top: ${node.position.y}px;">
                <div class="flow-node-header">
                    <div class="node-icon" style="background-color: ${typeInfo.color}">
                        <i class="bx ${typeInfo.icon}"></i>
                    </div>
                    <h6 class="node-title">${typeInfo.label}</h6>
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
                ${typeInfo.hasInput !== false ? '<div class="flow-node-connector input" data-connector="input"></div>' : ''}
                ${hasBranching ? `
                    <div class="flow-node-connector output-true" data-connector="output-true" title="True"></div>
                    <div class="flow-node-connector output-false" data-connector="output-false" title="False"></div>
                ` : multiOutput ? `
                    <div class="flow-node-connector output-1" data-connector="output-1"></div>
                    <div class="flow-node-connector output-2" data-connector="output-2"></div>
                ` : typeInfo.hasOutput !== false ? '<div class="flow-node-connector output" data-connector="output"></div>' : ''}
            </div>
        `;

        $('#flowCanvas').append(nodeHtml);

        // Setup node dragging
        const $node = $(`#${node.id}`);
        setupNodeDragging($node, node);

        // Setup connector click for connections
        setupConnectorHandlers($node, node);
    }

    // Get a summary text for the node body
    function getNodeSummary(node) {
        const data = node.data || {};
        switch (node.type) {
            case 'start': return 'Flow entry point';
            case 'query': return 'Analyze incoming query';
            case 'source_none': return 'No external data';
            case 'source_rag': return `RAG search (top ${data.topK || 5})`;
            case 'source_online': return `Web search (${data.maxResults || 3} results)`;
            case 'source_website': return data.websiteIds?.length ? `${data.websiteIds.length} websites` : 'Select websites';
            case 'if_else': return data.condition ? `If ${data.condition}` : 'Configure condition';
            case 'switch': return data.variable ? `Switch on ${data.variable}` : 'Configure switch';
            case 'loop': return `Max ${data.maxIterations || 10} iterations`;
            case 'history': return `Last ${data.messageCount || 10} messages`;
            case 'context': return data.contextText ? 'Custom context set' : 'Add context';
            case 'memory': return `${data.action || 'get'}: ${data.key || 'key'}`;
            case 'many_to_one': return 'Merge multiple inputs';
            case 'one_to_many': return 'Split to multiple outputs';
            case 'merge': return 'Wait and merge paths';
            case 'split': return 'Parallel processing';
            case 'response': return data.systemPrompt ? 'Custom prompt' : 'Default response';
            case 'template': return data.templateText ? 'Template set' : 'Configure template';
            case 'filter_topic': return data.topics?.length ? `${data.topics.length} topics` : 'Add topics';
            case 'filter_keywords': return data.keywords?.length ? `${data.keywords.length} keywords` : 'Add keywords';
            case 'filter_sentiment': return `${data.sentiment || 'positive'} (${data.threshold || 0.5})`;
            case 'delay': return `Wait ${data.seconds || 1}s`;
            case 'webhook': return data.url ? 'Webhook configured' : 'Configure webhook';
            case 'log': return data.level || 'info';
            default: return 'Configure node';
        }
    }

    // Setup node dragging
    function setupNodeDragging($node, nodeData) {
        $node.on('mousedown', function(e) {
            if ($(e.target).closest('.node-actions, .flow-node-connector').length) return;

            state.isDragging = true;
            state.dragNode = nodeData;
            state.dragOffset = {
                x: e.pageX - nodeData.position.x,
                y: e.pageY - nodeData.position.y
            };

            $node.css('z-index', 100);
        });
    }

    // Global mouse events for dragging
    $(document).on('mousemove', function(e) {
        if (!state.isDragging || !state.dragNode) return;

        const $canvas = $('#flowCanvas');
        const canvasOffset = $canvas.offset();

        let newX = e.pageX - state.dragOffset.x;
        let newY = e.pageY - state.dragOffset.y;

        // Constrain to canvas
        newX = Math.max(0, newX);
        newY = Math.max(0, newY);

        // Snap to grid
        newX = snapToGrid(newX);
        newY = snapToGrid(newY);

        state.dragNode.position.x = newX;
        state.dragNode.position.y = newY;

        $(`#${state.dragNode.id}`).css({
            left: newX + 'px',
            top: newY + 'px'
        });

        renderConnections();
    });

    $(document).on('mouseup', function() {
        if (state.isDragging && state.dragNode) {
            // Final snap to grid on mouse up
            state.dragNode.position.x = snapToGrid(state.dragNode.position.x);
            state.dragNode.position.y = snapToGrid(state.dragNode.position.y);

            $(`#${state.dragNode.id}`).css({
                left: state.dragNode.position.x + 'px',
                top: state.dragNode.position.y + 'px',
                'z-index': 10
            });
            renderConnections();
        }
        state.isDragging = false;
        state.dragNode = null;
    });

    // Setup connector handlers
    let connectionStart = null;

    function setupConnectorHandlers($node, nodeData) {
        $node.find('.flow-node-connector').on('click', function(e) {
            e.stopPropagation();
            const connectorType = $(this).data('connector');

            if (!connectionStart) {
                // Start connection
                if (connectorType.startsWith('output')) {
                    connectionStart = {
                        nodeId: nodeData.id,
                        connector: connectorType
                    };
                    $(this).addClass('connecting');
                    toastr.info('Click on an input connector to complete connection');
                }
            } else {
                // Complete connection
                if (connectorType === 'input' && connectionStart.nodeId !== nodeData.id) {
                    // Check if connection already exists
                    const exists = state.connections.some(c =>
                        c.from === connectionStart.nodeId &&
                        c.to === nodeData.id &&
                        c.fromConnector === connectionStart.connector
                    );

                    if (!exists) {
                        state.connections.push({
                            from: connectionStart.nodeId,
                            to: nodeData.id,
                            fromConnector: connectionStart.connector,
                            toConnector: 'input'
                        });
                        renderConnections();
                        toastr.success('Connection created');
                    }
                }

                // Reset
                $('.flow-node-connector').removeClass('connecting');
                connectionStart = null;
            }
        });
    }

    // Render connections
    function renderConnections() {
        const svg = document.getElementById('flowConnections');
        if (!svg) return;

        // Remove existing paths (but keep defs)
        const paths = svg.querySelectorAll('path');
        paths.forEach(p => p.remove());

        state.connections.forEach(conn => {
            const $fromNode = $(`#${conn.from}`);
            const $toNode = $(`#${conn.to}`);

            if (!$fromNode.length || !$toNode.length) return;

            const $fromConnector = $fromNode.find(`[data-connector="${conn.fromConnector}"]`);
            const $toConnector = $toNode.find(`[data-connector="input"]`);

            if (!$fromConnector.length || !$toConnector.length) return;

            const fromPos = getConnectorPosition($fromConnector);
            const toPos = getConnectorPosition($toConnector);

            // Draw bezier curve
            const midY = (fromPos.y + toPos.y) / 2;
            const pathD = `M ${fromPos.x} ${fromPos.y} C ${fromPos.x} ${midY}, ${toPos.x} ${midY}, ${toPos.x} ${toPos.y}`;

            // Create SVG path element properly using SVG namespace
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', pathD);
            path.setAttribute('stroke', '#556ee6');
            path.setAttribute('stroke-width', '2');
            path.setAttribute('fill', 'none');
            path.setAttribute('marker-end', 'url(#arrowhead)');
            svg.appendChild(path);
        });
    }

    function getConnectorPosition($connector) {
        const $canvas = $('#flowCanvas');
        const canvasOffset = $canvas.offset();
        const connectorOffset = $connector.offset();

        return {
            x: connectorOffset.left - canvasOffset.left + $connector.width() / 2,
            y: connectorOffset.top - canvasOffset.top + $connector.height() / 2
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

        // Auto-align nodes
        $('#autoAlignNodes').on('click', function() {
            realignAllNodes();
            toastr.success('Nodes aligned to center');
        });

        // Clear canvas
        $('#clearCanvas').on('click', function() {
            if (confirm('Are you sure you want to clear the canvas? This will remove all nodes except the start node.')) {
                clearCanvas();
            }
        });

        // Save flow
        $('#saveFlow').on('click', function() {
            saveFlow();
        });
    }

    // Delete node
    function deleteNode(nodeId) {
        // Remove from state
        state.nodes = state.nodes.filter(n => n.id !== nodeId);
        state.connections = state.connections.filter(c => c.from !== nodeId && c.to !== nodeId);

        // Remove from DOM
        $(`#${nodeId}`).remove();

        renderConnections();
        updateCanvasEmptyState();
        toastr.success('Node deleted');
    }

    // Clear canvas
    function clearCanvas() {
        // Keep only start node
        const startNode = state.nodes.find(n => n.type === 'start');
        state.nodes = startNode ? [startNode] : [];
        state.connections = [];

        // Remove all nodes except start
        $('.flow-node:not(.start-node)').remove();

        renderConnections();
        updateCanvasEmptyState();
        toastr.info('Canvas cleared');
    }

    // Open properties panel
    function openPropertiesPanel(node) {
        state.selectedNode = node;
        const typeInfo = flatNodeTypes[node.type] || { label: node.type };

        $('#propertiesPanelTitle').text(typeInfo.label + ' Properties');
        $('#propertiesPanelBody').html(getPropertiesForm(node));
        $('#propertiesPanel, #propertiesOverlay').addClass('active');
    }

    // Close properties panel
    function closePropertiesPanel() {
        if (state.selectedNode) {
            // Save properties before closing
            saveNodeProperties(state.selectedNode);
        }
        $('#propertiesPanel, #propertiesOverlay').removeClass('active');
        state.selectedNode = null;
    }

    // Get properties form HTML
    function getPropertiesForm(node) {
        const data = node.data || {};

        switch (node.type) {
            case 'query':
                return `
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="propAnalyzeIntent" ${data.analyzeIntent ? 'checked' : ''}>
                            <label class="form-check-label text-dark" for="propAnalyzeIntent">Analyze Intent</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="propExtractEntities" ${data.extractEntities ? 'checked' : ''}>
                            <label class="form-check-label text-dark" for="propExtractEntities">Extract Entities</label>
                        </div>
                    </div>
                `;

            case 'source_rag':
                return `
                    <div class="mb-3">
                        <label class="form-label">Top K Results</label>
                        <input type="number" class="form-control" id="propTopK" value="${data.topK || 5}" min="1" max="20">
                        <small class="form-text text-secondary">Number of relevant documents to retrieve</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Similarity Threshold</label>
                        <input type="number" class="form-control" id="propThreshold" value="${data.threshold || 0.7}" min="0" max="1" step="0.1">
                        <small class="form-text text-secondary">Minimum similarity score (0-1)</small>
                    </div>
                `;

            case 'source_online':
                return `
                    <div class="mb-3">
                        <label class="form-label">Max Results</label>
                        <input type="number" class="form-control" id="propMaxResults" value="${data.maxResults || 3}" min="1" max="10">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="propSafeSearch" ${data.safeSearch !== false ? 'checked' : ''}>
                            <label class="form-check-label text-dark" for="propSafeSearch">Safe Search</label>
                        </div>
                    </div>
                `;

            case 'if_else':
                return `
                    <div class="mb-3">
                        <label class="form-label">Variable/Field</label>
                        <input type="text" class="form-control" id="propCondition" value="${data.condition || ''}" placeholder="e.g., intent, sentiment">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Operator</label>
                        <select class="form-select" id="propOperator">
                            <option value="equals" ${data.operator === 'equals' ? 'selected' : ''}>Equals</option>
                            <option value="not_equals" ${data.operator === 'not_equals' ? 'selected' : ''}>Not Equals</option>
                            <option value="contains" ${data.operator === 'contains' ? 'selected' : ''}>Contains</option>
                            <option value="not_contains" ${data.operator === 'not_contains' ? 'selected' : ''}>Not Contains</option>
                            <option value="greater_than" ${data.operator === 'greater_than' ? 'selected' : ''}>Greater Than</option>
                            <option value="less_than" ${data.operator === 'less_than' ? 'selected' : ''}>Less Than</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value</label>
                        <input type="text" class="form-control" id="propValue" value="${data.value || ''}" placeholder="Value to compare">
                    </div>
                `;

            case 'history':
                return `
                    <div class="mb-3">
                        <label class="form-label">Message Count</label>
                        <input type="number" class="form-control" id="propMessageCount" value="${data.messageCount || 10}" min="1" max="50">
                        <small class="form-text text-secondary">Number of previous messages to include</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="propIncludeSystem" ${data.includeSystem ? 'checked' : ''}>
                            <label class="form-check-label text-dark" for="propIncludeSystem">Include System Messages</label>
                        </div>
                    </div>
                `;

            case 'context':
                return `
                    <div class="mb-3">
                        <label class="form-label">Context Text</label>
                        <textarea class="form-control" id="propContextText" rows="4" placeholder="Additional context for the AI...">${data.contextText || ''}</textarea>
                        <small class="form-text text-secondary">This context will be added to the AI's knowledge for this flow</small>
                    </div>
                `;

            case 'memory':
                return `
                    <div class="mb-3">
                        <label class="form-label">Action</label>
                        <select class="form-select" id="propMemoryAction">
                            <option value="get" ${data.action === 'get' ? 'selected' : ''}>Get</option>
                            <option value="set" ${data.action === 'set' ? 'selected' : ''}>Set</option>
                            <option value="delete" ${data.action === 'delete' ? 'selected' : ''}>Delete</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Key</label>
                        <input type="text" class="form-control" id="propMemoryKey" value="${data.key || ''}" placeholder="Memory key name">
                    </div>
                `;

            case 'response':
                return `
                    <div class="mb-3">
                        <label class="form-label">System Prompt</label>
                        <textarea class="form-control" id="propSystemPrompt" rows="4" placeholder="Custom system prompt...">${data.systemPrompt || ''}</textarea>
                        <small class="form-text text-secondary">Leave empty to use default behavior</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Temperature (${data.temperature || 0.7})</label>
                        <input type="range" class="form-range" id="propTemperature" value="${data.temperature || 0.7}" min="0" max="1" step="0.1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Max Tokens</label>
                        <input type="number" class="form-control" id="propMaxTokens" value="${data.maxTokens || 1000}" min="100" max="4000">
                    </div>
                `;

            case 'template':
                return `
                    <div class="mb-3">
                        <label class="form-label">Template</label>
                        <textarea class="form-control" id="propTemplateText" rows="6" placeholder="Response template with @{{variables}}...">${data.templateText || ''}</textarea>
                        <small class="form-text text-secondary">Use @{{variable}} for dynamic content</small>
                    </div>
                `;

            case 'filter_topic':
                return `
                    <div class="mb-3">
                        <label class="form-label">Topics (comma-separated)</label>
                        <input type="text" class="form-control" id="propTopics" value="${(data.topics || []).join(', ')}" placeholder="topic1, topic2, topic3">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Match Type</label>
                        <select class="form-select" id="propMatchType">
                            <option value="any" ${data.matchType === 'any' ? 'selected' : ''}>Any (OR)</option>
                            <option value="all" ${data.matchType === 'all' ? 'selected' : ''}>All (AND)</option>
                        </select>
                    </div>
                `;

            case 'filter_keywords':
                return `
                    <div class="mb-3">
                        <label class="form-label">Keywords (comma-separated)</label>
                        <input type="text" class="form-control" id="propKeywords" value="${(data.keywords || []).join(', ')}" placeholder="keyword1, keyword2">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Match Type</label>
                        <select class="form-select" id="propKeywordMatchType">
                            <option value="any" ${data.matchType === 'any' ? 'selected' : ''}>Any (OR)</option>
                            <option value="all" ${data.matchType === 'all' ? 'selected' : ''}>All (AND)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="propCaseSensitive" ${data.caseSensitive ? 'checked' : ''}>
                            <label class="form-check-label text-dark" for="propCaseSensitive">Case Sensitive</label>
                        </div>
                    </div>
                `;

            case 'filter_sentiment':
                return `
                    <div class="mb-3">
                        <label class="form-label">Sentiment</label>
                        <select class="form-select" id="propSentiment">
                            <option value="positive" ${data.sentiment === 'positive' ? 'selected' : ''}>Positive</option>
                            <option value="negative" ${data.sentiment === 'negative' ? 'selected' : ''}>Negative</option>
                            <option value="neutral" ${data.sentiment === 'neutral' ? 'selected' : ''}>Neutral</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Threshold (${data.threshold || 0.5})</label>
                        <input type="range" class="form-range" id="propSentimentThreshold" value="${data.threshold || 0.5}" min="0" max="1" step="0.1">
                    </div>
                `;

            case 'delay':
                return `
                    <div class="mb-3">
                        <label class="form-label">Delay (seconds)</label>
                        <input type="number" class="form-control" id="propDelaySeconds" value="${data.seconds || 1}" min="0" max="300" step="0.5">
                    </div>
                `;

            case 'webhook':
                return `
                    <div class="mb-3">
                        <label class="form-label">URL</label>
                        <input type="url" class="form-control" id="propWebhookUrl" value="${data.url || ''}" placeholder="https://...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Method</label>
                        <select class="form-select" id="propWebhookMethod">
                            <option value="POST" ${data.method === 'POST' ? 'selected' : ''}>POST</option>
                            <option value="GET" ${data.method === 'GET' ? 'selected' : ''}>GET</option>
                            <option value="PUT" ${data.method === 'PUT' ? 'selected' : ''}>PUT</option>
                        </select>
                    </div>
                `;

            case 'log':
                return `
                    <div class="mb-3">
                        <label class="form-label">Log Message</label>
                        <input type="text" class="form-control" id="propLogMessage" value="${data.message || ''}" placeholder="Message to log">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Level</label>
                        <select class="form-select" id="propLogLevel">
                            <option value="info" ${data.level === 'info' ? 'selected' : ''}>Info</option>
                            <option value="debug" ${data.level === 'debug' ? 'selected' : ''}>Debug</option>
                            <option value="warning" ${data.level === 'warning' ? 'selected' : ''}>Warning</option>
                            <option value="error" ${data.level === 'error' ? 'selected' : ''}>Error</option>
                        </select>
                    </div>
                `;

            default:
                return '<p class="text-secondary">No properties available for this node.</p>';
        }
    }

    // Save node properties
    function saveNodeProperties(node) {
        const data = node.data || {};

        switch (node.type) {
            case 'query':
                data.analyzeIntent = $('#propAnalyzeIntent').is(':checked');
                data.extractEntities = $('#propExtractEntities').is(':checked');
                break;

            case 'source_rag':
                data.topK = parseInt($('#propTopK').val()) || 5;
                data.threshold = parseFloat($('#propThreshold').val()) || 0.7;
                break;

            case 'source_online':
                data.maxResults = parseInt($('#propMaxResults').val()) || 3;
                data.safeSearch = $('#propSafeSearch').is(':checked');
                break;

            case 'if_else':
                data.condition = $('#propCondition').val();
                data.operator = $('#propOperator').val();
                data.value = $('#propValue').val();
                break;

            case 'history':
                data.messageCount = parseInt($('#propMessageCount').val()) || 10;
                data.includeSystem = $('#propIncludeSystem').is(':checked');
                break;

            case 'context':
                data.contextText = $('#propContextText').val();
                break;

            case 'memory':
                data.action = $('#propMemoryAction').val();
                data.key = $('#propMemoryKey').val();
                break;

            case 'response':
                data.systemPrompt = $('#propSystemPrompt').val();
                data.temperature = parseFloat($('#propTemperature').val()) || 0.7;
                data.maxTokens = parseInt($('#propMaxTokens').val()) || 1000;
                break;

            case 'template':
                data.templateText = $('#propTemplateText').val();
                break;

            case 'filter_topic':
                data.topics = $('#propTopics').val().split(',').map(t => t.trim()).filter(t => t);
                data.matchType = $('#propMatchType').val();
                break;

            case 'filter_keywords':
                data.keywords = $('#propKeywords').val().split(',').map(k => k.trim()).filter(k => k);
                data.matchType = $('#propKeywordMatchType').val();
                data.caseSensitive = $('#propCaseSensitive').is(':checked');
                break;

            case 'filter_sentiment':
                data.sentiment = $('#propSentiment').val();
                data.threshold = parseFloat($('#propSentimentThreshold').val()) || 0.5;
                break;

            case 'delay':
                data.seconds = parseFloat($('#propDelaySeconds').val()) || 1;
                break;

            case 'webhook':
                data.url = $('#propWebhookUrl').val();
                data.method = $('#propWebhookMethod').val();
                break;

            case 'log':
                data.message = $('#propLogMessage').val();
                data.level = $('#propLogLevel').val();
                break;
        }

        node.data = data;

        // Update node summary in DOM
        $(`#${node.id} .node-summary`).text(getNodeSummary(node));
    }

    // Setup status toggle
    function setupStatusToggle() {
        $('#flowStatus').on('change', function() {
            $('#flowStatusLabel').text($(this).is(':checked') ? 'Active' : 'Inactive');
        });
    }

    // Save flow
    function saveFlow() {
        const flowName = $('#flowName').val().trim();
        if (!flowName) {
            toastr.error('Flow name is required');
            $('#flowName').focus();
            return;
        }

        const $btn = $('#saveFlow');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        const flowData = {
            nodes: state.nodes,
            connections: state.connections,
            nodeIdCounter: state.nodeIdCounter
        };

        const data = {
            _token: '{{ csrf_token() }}',
            flowName: flowName,
            flowDescription: $('#flowDescription').val(),
            flowData: flowData,
            priority: parseInt($('#flowPriority').val()) || 0,
            isActive: $('#flowStatus').is(':checked') ? 1 : 0,
            isDefault: $('#flowIsDefault').is(':checked') ? 1 : 0
        };

        const url = state.isEditing ? `/ai-technician-reply-flows/${state.flowId}` : '/ai-technician-reply-flows';
        const method = state.isEditing ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: data,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (!state.isEditing && response.data.redirect) {
                        setTimeout(() => {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save flow.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> {{ isset($flow) ? "Update" : "Save" }} Flow');
            }
        });
    }

    // Initialize the builder
    init();

    // Fallback: Also trigger centering on window load (after all CSS/images)
    // This ensures the wrapper has its final dimensions
    if (needsCentering) {
        $(window).on('load', function() {
            // Only re-center if start node is still at a placeholder position
            const startNode = state.nodes.find(n => n.type === 'start');
            if (startNode && (startNode.position.x < 100 || startNode.position.x === 50)) {
                console.log('Window load fallback - re-centering all nodes');
                realignAllNodes();
            }
        });
    }

    // Re-align all nodes to be centered and vertically stacked
    function realignAllNodes() {
        const wrapper = document.querySelector('.flow-canvas-wrapper');
        if (!wrapper) return;

        const rect = wrapper.getBoundingClientRect();
        const nodeWidth = 220;
        const centerX = snapToGrid(Math.max(100, (rect.width / 2) - (nodeWidth / 2)));
        const verticalSpacing = 140;
        const startY = 60;

        // Sort nodes by their current Y position to maintain order
        const sortedNodes = [...state.nodes].sort((a, b) => a.position.y - b.position.y);

        // Re-position all nodes
        sortedNodes.forEach((node, index) => {
            node.position.x = centerX;
            node.position.y = snapToGrid(startY + (index * verticalSpacing));

            // Update DOM
            const $nodeEl = $(`#${node.id}`);
            if ($nodeEl.length) {
                $nodeEl.css({
                    left: node.position.x + 'px',
                    top: node.position.y + 'px'
                });
            }
        });

        renderConnections();

        // Scroll to top
        wrapper.scrollLeft = 0;
        wrapper.scrollTop = 0;
    }
});
</script>
@endsection
