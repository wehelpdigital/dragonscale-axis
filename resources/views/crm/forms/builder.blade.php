@extends('layouts.master')

@section('title') {{ $mode === 'edit' ? 'Edit Form' : 'Create Form' }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
/* Builder Layout */
.builder-container {
    display: flex;
    gap: 1rem;
    min-height: calc(100vh - 200px);
}
.elements-panel {
    width: 260px;
    flex-shrink: 0;
}
.canvas-panel {
    flex: 1;
    min-width: 0;
}

/* Elements Panel */
.element-category {
    margin-bottom: 1rem;
}
.element-category-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #74788d;
    margin-bottom: 0.5rem;
    padding: 0 0.5rem;
}
.element-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 0.75rem;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    margin-bottom: 0.375rem;
    cursor: grab;
    transition: all 0.15s ease;
}
.element-item:hover {
    border-color: #556ee6;
    background: #f8f9ff;
}
.element-item:active {
    cursor: grabbing;
}
.element-item i {
    font-size: 1.125rem;
    color: #556ee6;
    margin-right: 0.625rem;
}
.element-item span {
    font-size: 0.8125rem;
    color: #495057;
}

/* Canvas */
.form-canvas {
    min-height: 400px;
    background: #fff;
    border: 2px dashed #ced4da;
    border-radius: 0.5rem;
    padding: 1.5rem;
    transition: all 0.2s ease;
}
.form-canvas.drag-over {
    border-color: #556ee6;
    background: #f8f9ff;
}
.form-canvas.has-elements {
    border-style: solid;
    border-color: #e9ecef;
}
.canvas-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 300px;
    color: #74788d;
}
.canvas-empty i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #ced4da;
}

/* Form Elements in Canvas */
.form-element {
    position: relative;
    padding: 1rem;
    margin-bottom: 0.75rem;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
}
.form-element:hover {
    border-color: #556ee6;
    box-shadow: 0 0 0 3px rgba(85, 110, 230, 0.1);
}
.form-element.selected {
    border-color: #556ee6;
    box-shadow: 0 0 0 3px rgba(85, 110, 230, 0.2);
}
.form-element.dragging {
    opacity: 0.5;
}
.element-actions {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: none;
    gap: 0.25rem;
}
.form-element:hover .element-actions {
    display: flex;
}
.element-actions .btn {
    padding: 0.2rem 0.4rem;
    font-size: 0.75rem;
}
.element-drag-handle {
    position: absolute;
    left: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: #ced4da;
    cursor: grab;
    display: none;
}
.form-element:hover .element-drag-handle {
    display: block;
}

/* Properties Offcanvas */
.offcanvas-properties {
    width: 380px !important;
}
.property-group {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}
.property-group:last-child {
    border-bottom: none;
}
.property-group-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #74788d;
    margin-bottom: 0.75rem;
}
.property-row {
    margin-bottom: 0.75rem;
}
.property-row:last-child {
    margin-bottom: 0;
}

/* Options Editor */
.options-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.option-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}
.option-item input {
    flex: 1;
}

/* Width selector */
.width-selector {
    display: flex;
    gap: 0.25rem;
}
.width-selector .btn {
    flex: 1;
    padding: 0.375rem;
    font-size: 0.75rem;
}
.width-selector .btn.active {
    background: #556ee6;
    color: #fff;
    border-color: #556ee6;
}

/* Trigger Flow - Drag Drop Canvas */
.trigger-builder-container {
    display: flex;
    gap: 1rem;
    min-height: 500px;
}
.trigger-sidebar {
    width: 240px;
    flex-shrink: 0;
}
.trigger-action-item {
    padding: 0.625rem 0.875rem;
    margin-bottom: 0.5rem;
    background: #fff;
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    cursor: grab;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.625rem;
}
.trigger-action-item:hover {
    border-color: #556ee6;
    box-shadow: 0 2px 8px rgba(85, 110, 230, 0.15);
}
.trigger-action-item:active {
    cursor: grabbing;
}
.trigger-action-item.dragging {
    opacity: 0.5;
}
.trigger-action-icon {
    width: 32px;
    height: 32px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.trigger-action-info h6 {
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 600;
}
.trigger-action-info small {
    color: #6c757d;
    font-size: 0.6875rem;
}
.trigger-canvas-wrapper {
    flex-grow: 1;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 0.5rem;
    position: relative;
    overflow: auto;
    min-height: 450px;
}
.trigger-canvas {
    min-width: 100%;
    min-height: 450px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.trigger-canvas-empty {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    color: #6c757d;
    pointer-events: none; /* Allow drops through empty state */
}
.trigger-canvas-empty i {
    font-size: 3rem;
    opacity: 0.3;
}
.trigger-canvas.drag-over {
    background: #e8eeff !important;
    border-color: #556ee6 !important;
    border-style: solid !important;
}
/* Flow Nodes - Centered vertical layout */
#triggerNodes {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    width: 100%;
}
.trigger-node {
    position: relative;
    min-width: 280px;
    max-width: 320px;
    background: #fff;
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    z-index: 10;
    animation: nodeDropIn 0.3s ease;
}
.trigger-node.selected {
    border-color: #556ee6;
    box-shadow: 0 0 0 3px rgba(85, 110, 230, 0.25);
}
.trigger-node-header {
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #e9ecef;
    border-radius: 0.5rem 0.5rem 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
}
.trigger-node-header .node-icon {
    width: 26px;
    height: 26px;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
}
.trigger-node-header .node-title {
    flex-grow: 1;
    font-size: 0.75rem;
    font-weight: 600;
    margin: 0;
}
.trigger-node-body {
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
    color: #6c757d;
}
.trigger-node-connector {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #556ee6;
    border: 2px solid #fff;
    position: absolute;
    z-index: 20;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.trigger-node-connector.output {
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
}
.trigger-node-connector.input {
    top: -6px;
    left: 50%;
    transform: translateX(-50%);
}
/* Start node */
.trigger-node.start-node {
    border-color: #34c38f;
    cursor: default;
}
.trigger-node.start-node .trigger-node-header {
    background: linear-gradient(135deg, #34c38f 0%, #28a879 100%);
    color: #fff;
}
/* Node type colors */
.node-type-send_email .node-icon { background: #556ee6; color: #fff; }
.node-type-notify_admin .node-icon { background: #f46a6a; color: #fff; }
.node-type-webhook .node-icon { background: #50a5f1; color: #fff; }
.node-type-create_lead .node-icon { background: #34c38f; color: #fff; }
.node-type-delay .node-icon { background: #f1b44c; color: #fff; }
.node-type-add_course_access .node-icon { background: #7b5ea7; color: #fff; }
/* Connection lines - vertical flow */
.trigger-connector-line {
    width: 2px;
    height: 20px;
    background: #556ee6;
    margin: 0 auto;
}
@keyframes nodeDropIn {
    0% { opacity: 0; transform: scale(0.8); }
    100% { opacity: 1; transform: scale(1); }
}
/* Email builder modal */
.email-visual-editor {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    font-size: 14px;
    line-height: 1.6;
}
.email-visual-editor:focus {
    outline: none;
    border-color: #556ee6 !important;
}
.email-toolbar .btn {
    border-color: #ced4da;
}
.email-toolbar .btn:hover {
    background-color: #e9ecef;
}
.merge-tag-item {
    cursor: pointer;
    padding: 0.375rem 0.5rem;
    border-radius: 0.25rem;
}
.merge-tag-item:hover {
    background: #f3f6f9;
}
.merge-tag-item code {
    font-size: 0.6875rem;
}

/* Tabs */
.nav-tabs-custom .nav-link {
    border: none;
    background: none;
    color: #74788d;
    padding: 0.75rem 1rem;
}
.nav-tabs-custom .nav-link.active {
    color: #556ee6;
    border-bottom: 2px solid #556ee6;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') CRM @endslot
    @slot('li_2') <a href="{{ route('crm-forms') }}">Forms</a> @endslot
    @slot('title') {{ $mode === 'edit' ? 'Edit Form' : 'Create Form' }} @endslot
@endcomponent

<!-- Form Header -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('crm-forms') }}" class="btn btn-soft-secondary">
                        <i class="bx bx-arrow-back"></i>
                    </a>
                    <input type="text" class="form-control form-control-lg border-0 ps-0" id="formName"
                        placeholder="Form Name" value="{{ $form->formName ?? '' }}" style="font-weight: 600; font-size: 1.25rem;">
                </div>
            </div>
            <div class="col-md-6 text-end">
                <div class="d-flex justify-content-end gap-2">
                    <select class="form-select" id="formStatus" style="width: auto;">
                        <option value="draft" {{ ($form->formStatus ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ ($form->formStatus ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($form->formStatus ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <button type="button" class="btn btn-soft-secondary" data-bs-toggle="modal" data-bs-target="#settingsModal">
                        <i class="bx bx-cog"></i>
                    </button>
                    <button type="button" class="btn btn-primary" id="saveFormBtn">
                        <i class="bx bx-save me-1"></i>Save Form
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#builderTab" role="tab">
            <i class="bx bx-layout me-1"></i>Form Builder
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#triggerTab" role="tab">
            <i class="bx bx-git-branch me-1"></i>Trigger Flow
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#apiTab" role="tab">
            <i class="bx bx-code-alt me-1"></i>API
        </a>
    </li>
</ul>

<div class="tab-content">
    <!-- Builder Tab -->
    <div class="tab-pane fade show active" id="builderTab" role="tabpanel">
        <div class="builder-container">
            <!-- Elements Panel -->
            <div class="elements-panel">
                <div class="card mb-0 h-100">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0 small"><i class="bx bx-category me-2"></i>Form Elements</h6>
                    </div>
                    <div class="card-body p-2" style="max-height: calc(100vh - 320px); overflow-y: auto;">
                        @foreach($formElements as $category => $elements)
                        <div class="element-category">
                            <div class="element-category-title">{{ ucfirst($category) }}</div>
                            @foreach($elements as $element)
                            <div class="element-item" draggable="true"
                                data-type="{{ $element['type'] }}"
                                data-defaults="{{ json_encode($element['defaults']) }}">
                                <i class="bx {{ $element['icon'] }}"></i>
                                <span>{{ $element['name'] }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Canvas Panel -->
            <div class="canvas-panel">
                <div class="card mb-0 h-100">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0 small"><i class="bx bx-layout me-2"></i>Form Canvas</h6>
                        <span class="badge bg-soft-secondary text-secondary" id="elementCount">0 elements</span>
                    </div>
                    <div class="card-body">
                        <div class="form-canvas" id="formCanvas">
                            <div class="canvas-empty" id="canvasEmpty">
                                <i class="bx bx-pointer"></i>
                                <p class="mb-1 text-dark">Drag and drop elements here</p>
                                <small class="text-secondary">or click an element to add it</small>
                            </div>
                            <div class="canvas-elements row" id="canvasElements"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trigger Tab -->
    <div class="tab-pane fade" id="triggerTab" role="tabpanel">
        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 small"><i class="bx bx-git-branch me-2"></i>Trigger Flow</h6>
                <span class="badge bg-soft-info text-info">Runs on form submission</span>
            </div>
            <div class="card-body">
                <div class="trigger-builder-container">
                    <!-- Actions Sidebar -->
                    <div class="trigger-sidebar">
                        <h6 class="text-dark mb-2 small"><i class="bx bx-cube me-1"></i>Actions</h6>
                        <p class="text-secondary small mb-3">Drag actions to the canvas</p>

                        <div class="trigger-action-item" draggable="true" data-action-type="send_email">
                            <div class="trigger-action-icon bg-primary text-white">
                                <i class="bx bx-envelope"></i>
                            </div>
                            <div class="trigger-action-info">
                                <h6 class="text-dark">Send Email</h6>
                                <small>Email with merge tags</small>
                            </div>
                        </div>

                        <div class="trigger-action-item" draggable="true" data-action-type="notify_admin">
                            <div class="trigger-action-icon bg-danger text-white">
                                <i class="bx bx-bell"></i>
                            </div>
                            <div class="trigger-action-info">
                                <h6 class="text-dark">Notify Admin</h6>
                                <small>Send notification</small>
                            </div>
                        </div>

                        <div class="trigger-action-item" draggable="true" data-action-type="webhook">
                            <div class="trigger-action-icon bg-info text-white">
                                <i class="bx bx-link-external"></i>
                            </div>
                            <div class="trigger-action-info">
                                <h6 class="text-dark">Webhook</h6>
                                <small>Send data to URL</small>
                            </div>
                        </div>

                        <div class="trigger-action-item" draggable="true" data-action-type="create_lead">
                            <div class="trigger-action-icon bg-success text-white">
                                <i class="bx bx-user-plus"></i>
                            </div>
                            <div class="trigger-action-info">
                                <h6 class="text-dark">Create Lead</h6>
                                <small>Add to CRM leads</small>
                            </div>
                        </div>

                        <div class="trigger-action-item" draggable="true" data-action-type="delay">
                            <div class="trigger-action-icon bg-warning text-white">
                                <i class="bx bx-time"></i>
                            </div>
                            <div class="trigger-action-info">
                                <h6 class="text-dark">Delay</h6>
                                <small>Wait before next action</small>
                            </div>
                        </div>

                        <div class="trigger-action-item" draggable="true" data-action-type="add_course_access">
                            <div class="trigger-action-icon" style="background: #7b5ea7;">
                                <i class="bx bx-id-card text-white"></i>
                            </div>
                            <div class="trigger-action-info">
                                <h6 class="text-dark">Add Course Access</h6>
                                <small>Grant access tag</small>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="bg-soft-secondary p-2 rounded small">
                            <strong class="text-dark d-block mb-1"><i class="bx bx-info-circle me-1"></i>Tips</strong>
                            <ul class="text-secondary ps-3 mb-0" style="font-size: 0.6875rem;">
                                <li>Use merge tags in emails</li>
                                <li>Actions run top to bottom</li>
                                <li>Webhook receives JSON</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Canvas Area -->
                    <div class="trigger-canvas-wrapper">
                        <div class="trigger-canvas" id="triggerCanvas">
                            <div class="trigger-canvas-empty" id="triggerCanvasEmpty">
                                <i class="bx bx-git-branch d-block mb-2"></i>
                                <p class="mb-1 text-dark">Drag actions here</p>
                                <small class="text-secondary">Build your automation flow</small>
                            </div>
                            <!-- Nodes will be rendered here -->
                            <div id="triggerNodes"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Tab -->
    <div class="tab-pane fade" id="apiTab" role="tabpanel">
        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 small"><i class="bx bx-code-alt me-2"></i>API Integration</h6>
                @if($form)
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="apiEnabledToggle" {{ ($form->apiEnabled ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label small" for="apiEnabledToggle">API Enabled</label>
                </div>
                @endif
            </div>
            <div class="card-body">
                @if($form)
                <!-- API Key Section -->
                <div class="row mb-4">
                    <div class="col-lg-8">
                        <label class="form-label text-dark">API Key</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" id="apiKeyDisplay"
                                   value="{{ $form->apiKey ?? 'Not generated yet' }}" readonly
                                   style="{{ $form->apiKey ? '' : 'color: #6c757d; font-style: italic;' }}">
                            <button class="btn btn-outline-secondary" type="button" id="copyApiKeyBtn" title="Copy to clipboard">
                                <i class="bx bx-copy"></i>
                            </button>
                            <button class="btn btn-primary" type="button" id="generateApiKeyBtn">
                                <i class="bx bx-refresh me-1"></i>{{ $form->apiKey ? 'Regenerate' : 'Generate' }}
                            </button>
                        </div>
                        <small class="text-warning"><i class="bx bx-error me-1"></i>Keep this key secret. Regenerating will invalidate the old key.</small>
                    </div>
                </div>

                <hr>

                <!-- API Documentation -->
                <h6 class="text-dark mb-3"><i class="bx bx-book me-1"></i>API Documentation</h6>

                <div class="row">
                    <div class="col-lg-6">
                        <!-- Endpoint Info -->
                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body py-3">
                                <div class="mb-2">
                                    <span class="badge bg-success me-2">GET</span>
                                    <code id="apiEndpointUrl" class="text-dark">{{ $form->apiUrl ?? url('/api/forms/[slug]/submit') }}</code>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" id="copyEndpointBtn">
                                    <i class="bx bx-copy me-1"></i>Copy URL
                                </button>
                            </div>
                        </div>

                        <!-- Parameters -->
                        <h6 class="text-dark small mb-2">Query Parameters</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-3">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-dark">Parameter</th>
                                        <th class="text-dark">Type</th>
                                        <th class="text-dark">Required</th>
                                    </tr>
                                </thead>
                                <tbody id="apiParamsTable">
                                    <tr>
                                        <td><code>api_key</code></td>
                                        <td>string</td>
                                        <td><span class="badge bg-danger">Yes</span></td>
                                    </tr>
                                    <!-- Dynamic parameters will be added here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <!-- Interactive Tester -->
                        <h6 class="text-dark small mb-2">Test API Request</h6>
                        <div class="card border mb-3">
                            <div class="card-body py-2">
                                <div class="mb-2" id="apiTestInputs">
                                    <!-- Dynamic inputs will be rendered here -->
                                </div>
                                <button class="btn btn-sm btn-success" id="buildApiUrlBtn">
                                    <i class="bx bx-link me-1"></i>Build URL
                                </button>
                            </div>
                        </div>

                        <!-- Generated URL -->
                        <h6 class="text-dark small mb-2">Generated URL</h6>
                        <div class="bg-dark text-white p-2 rounded mb-3" style="font-size: 0.75rem;">
                            <code id="generatedApiUrl" class="text-success" style="word-break: break-all;">
                                Click "Build URL" to generate
                            </code>
                        </div>

                        <button class="btn btn-sm btn-outline-primary" id="copyGeneratedUrlBtn">
                            <i class="bx bx-copy me-1"></i>Copy Generated URL
                        </button>
                        <a href="#" id="testInBrowserBtn" target="_blank" class="btn btn-sm btn-outline-success ms-1">
                            <i class="bx bx-link-external me-1"></i>Test in Browser
                        </a>
                    </div>
                </div>

                @else
                <div class="alert alert-info">
                    <i class="bx bx-info-circle me-2"></i>
                    Save the form first to access API settings.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Email Builder Modal -->
<!-- API Key Confirmation Modal -->
<div class="modal fade" id="apiKeyConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="apiKeyModalTitle">
                    <i class="bx bx-key me-2 text-primary" id="apiKeyModalIcon"></i>Generate API Key?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="apiKeyModalMessage">
                <p class="mb-0">Generate a new API key to enable API access for this form.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmApiKeyBtn">
                    <i class="bx bx-key me-1"></i>Generate
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="emailBuilderModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bx bx-envelope me-2"></i>Email Builder</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- To Email -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-dark">To Email <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="emailTo" placeholder="recipient@email.com or use merge tag">
                        <small class="text-secondary">Use a merge tag like <code>@{{field_email}}</code> for dynamic email</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-dark">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="emailSubject" placeholder="Email subject...">
                    </div>
                </div>

                <!-- Merge Tags -->
                <div class="mb-3">
                    <label class="form-label text-dark">Insert Merge Tag</label>
                    <div class="d-flex flex-wrap gap-2" id="mergeTagsContainer">
                        <!-- Merge tags will be populated dynamically -->
                        <span class="text-secondary small">Add form fields to see available merge tags</span>
                    </div>
                </div>

                <!-- Email Body Editor -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label text-dark mb-0">Email Body</label>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active" id="emailVisualModeBtn">
                                <i class="bx bx-show me-1"></i>Visual
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="emailHtmlModeBtn">
                                <i class="bx bx-code-alt me-1"></i>HTML
                            </button>
                        </div>
                    </div>

                    <!-- Formatting Toolbar -->
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
                                <button type="button" class="btn btn-outline-secondary" id="emailInsertLinkBtn" title="Insert Link">
                                    <i class="bx bx-link"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="emailInsertImageBtn" title="Insert Image">
                                    <i class="bx bx-image"></i>
                                </button>
                            </div>
                            <div class="btn-group btn-group-sm" role="group">
                                <select class="form-select form-select-sm" id="emailFontSize" style="width: auto;">
                                    <option value="">Size</option>
                                    <option value="1">Small</option>
                                    <option value="3">Normal</option>
                                    <option value="5">Large</option>
                                    <option value="7">X-Large</option>
                                </select>
                            </div>
                            <div class="btn-group btn-group-sm ms-2" role="group">
                                <input type="color" class="form-control form-control-color p-0" id="emailTextColor" value="#000000" title="Text Color" style="width: 28px; height: 28px;">
                            </div>
                        </div>
                    </div>

                    <!-- Visual Editor -->
                    <div class="email-visual-editor border border-top-0 rounded-bottom p-3" id="emailVisualEditor"
                         contenteditable="true"
                         style="min-height: 250px; max-height: 350px; overflow-y: auto; background: #fff;">
                    </div>

                    <!-- HTML Editor -->
                    <textarea class="form-control font-monospace d-none" id="emailHtmlEditor"
                              style="min-height: 250px; max-height: 350px; font-size: 0.875rem;"
                              placeholder="Enter HTML code..."></textarea>
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

<!-- Properties Offcanvas -->
<div class="offcanvas offcanvas-end offcanvas-properties" tabindex="-1" id="propertiesOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title"><i class="bx bx-slider me-2"></i>Properties</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body" id="propertiesPanel">
        <!-- Properties will be rendered here -->
    </div>
</div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-cog me-2"></i>Form Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-dark">Form Description</label>
                    <textarea class="form-control" id="formDescription" rows="2" placeholder="Optional description...">{{ $form->formDescription ?? '' }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Success Message</label>
                    <textarea class="form-control" id="successMessage" rows="2" placeholder="Thank you for your submission!">{{ $form->formSettings['successMessage'] ?? 'Thank you for your submission!' }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Redirect URL (optional)</label>
                    <input type="url" class="form-control" id="redirectUrl" placeholder="https://..." value="{{ $form->formSettings['redirectUrl'] ?? '' }}">
                    <small class="text-secondary">Leave empty to show success message instead</small>
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
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(document).ready(function() {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Form state
    const formId = {{ $form->id ?? 'null' }};
    const mode = '{{ $mode }}';
    let formElements = @json($form->formElements ?? []);
    let triggerFlow = @json($form->triggerFlow ?? []);
    let selectedElement = null;
    let elementIdCounter = formElements.length > 0 ?
        Math.max(...formElements.map(e => parseInt((e.id || '').replace('field_', '')) || 0)) + 1 : 1;
    let triggerIdCounter = triggerFlow.length > 0 ?
        Math.max(...triggerFlow.map(t => parseInt((t.id || '').replace('trigger_', '')) || 0)) + 1 : 1;

    const propertiesOffcanvas = new bootstrap.Offcanvas(document.getElementById('propertiesOffcanvas'));

    // Initialize form builder
    renderCanvas();
    updateElementCount();

    // Drag and drop from elements panel
    $('.element-item').on('dragstart', function(e) {
        e.originalEvent.dataTransfer.setData('type', $(this).data('type'));
        e.originalEvent.dataTransfer.setData('defaults', JSON.stringify($(this).data('defaults')));
    });

    // Click to add element
    $('.element-item').on('click', function() {
        const type = $(this).data('type');
        const defaults = $(this).data('defaults');
        addElement(type, defaults);
    });

    // Canvas drop zone
    const canvas = document.getElementById('formCanvas');

    canvas.addEventListener('dragover', function(e) {
        e.preventDefault();
        canvas.classList.add('drag-over');
    });

    canvas.addEventListener('dragleave', function(e) {
        canvas.classList.remove('drag-over');
    });

    canvas.addEventListener('drop', function(e) {
        e.preventDefault();
        canvas.classList.remove('drag-over');

        const type = e.dataTransfer.getData('type');
        const defaults = JSON.parse(e.dataTransfer.getData('defaults') || '{}');

        if (type) {
            addElement(type, defaults);
        }
    });

    // Add element to form
    function addElement(type, defaults) {
        const element = {
            id: 'field_' + elementIdCounter++,
            type: type,
            ...defaults
        };

        formElements.push(element);
        renderCanvas();
        selectElement(element.id);
        updateElementCount();
    }

    // Render canvas
    function renderCanvas() {
        const container = $('#canvasElements');
        const empty = $('#canvasEmpty');
        const canvasEl = $('#formCanvas');

        if (formElements.length === 0) {
            empty.show();
            container.empty();
            canvasEl.removeClass('has-elements');
            return;
        }

        empty.hide();
        canvasEl.addClass('has-elements');
        container.empty();

        formElements.forEach((element, index) => {
            const html = renderElement(element, index);
            container.append(html);
        });

        initSortable();
        bindElementEvents();
    }

    // Render single element
    function renderElement(element, index) {
        const isSelected = selectedElement === element.id;
        let preview = '';

        switch (element.type) {
            case 'text':
            case 'email':
            case 'phone':
            case 'number':
                preview = `
                    <label class="form-label text-dark">${escapeHtml(element.label || 'Text Field')}${element.required ? ' <span class="text-danger">*</span>' : ''}</label>
                    <input type="${element.type === 'phone' ? 'tel' : element.type}" class="form-control" placeholder="${escapeHtml(element.placeholder || '')}" disabled>
                `;
                break;

            case 'textarea':
                preview = `
                    <label class="form-label text-dark">${escapeHtml(element.label || 'Text Area')}${element.required ? ' <span class="text-danger">*</span>' : ''}</label>
                    <textarea class="form-control" rows="${element.rows || 4}" placeholder="${escapeHtml(element.placeholder || '')}" disabled></textarea>
                `;
                break;

            case 'select':
                const options = (element.options || []).map(opt => `<option>${escapeHtml(opt)}</option>`).join('');
                preview = `
                    <label class="form-label text-dark">${escapeHtml(element.label || 'Dropdown')}${element.required ? ' <span class="text-danger">*</span>' : ''}</label>
                    <select class="form-select" disabled>
                        <option value="">${escapeHtml(element.placeholder || 'Choose...')}</option>
                        ${options}
                    </select>
                `;
                break;

            case 'radio':
                const radioOptions = (element.options || []).map((opt, i) => `
                    <div class="form-check ${element.inline ? 'form-check-inline' : ''}">
                        <input class="form-check-input" type="radio" disabled>
                        <label class="form-check-label text-dark">${escapeHtml(opt)}</label>
                    </div>
                `).join('');
                preview = `
                    <label class="form-label text-dark">${escapeHtml(element.label || 'Radio')}${element.required ? ' <span class="text-danger">*</span>' : ''}</label>
                    ${radioOptions}
                `;
                break;

            case 'checkbox':
                const checkboxOptions = (element.options || []).map((opt, i) => `
                    <div class="form-check ${element.inline ? 'form-check-inline' : ''}">
                        <input class="form-check-input" type="checkbox" disabled>
                        <label class="form-check-label text-dark">${escapeHtml(opt)}</label>
                    </div>
                `).join('');
                preview = `
                    <label class="form-label text-dark">${escapeHtml(element.label || 'Checkboxes')}${element.required ? ' <span class="text-danger">*</span>' : ''}</label>
                    ${checkboxOptions}
                `;
                break;

            case 'single_checkbox':
                preview = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" disabled>
                        <label class="form-check-label text-dark">${escapeHtml(element.label || 'Checkbox')}${element.required ? ' <span class="text-danger">*</span>' : ''}</label>
                    </div>
                `;
                break;

            case 'date':
                preview = `
                    <label class="form-label text-dark">${escapeHtml(element.label || 'Date')}${element.required ? ' <span class="text-danger">*</span>' : ''}</label>
                    <input type="date" class="form-control" disabled>
                `;
                break;

            case 'time':
                preview = `
                    <label class="form-label text-dark">${escapeHtml(element.label || 'Time')}${element.required ? ' <span class="text-danger">*</span>' : ''}</label>
                    <input type="time" class="form-control" disabled>
                `;
                break;

            case 'file':
                preview = `
                    <label class="form-label text-dark">${escapeHtml(element.label || 'File Upload')}${element.required ? ' <span class="text-danger">*</span>' : ''}</label>
                    <input type="file" class="form-control" disabled>
                    <small class="text-secondary">Max size: ${element.maxSize || 5}MB</small>
                `;
                break;

            case 'heading':
                const HeadingTag = element.size || 'h4';
                preview = `<${HeadingTag} class="text-dark mb-0">${escapeHtml(element.text || 'Heading')}</${HeadingTag}>`;
                break;

            case 'paragraph':
                preview = `<p class="text-secondary mb-0">${escapeHtml(element.text || 'Paragraph text')}</p>`;
                break;

            case 'divider':
                preview = `<hr class="my-2">`;
                break;

            case 'hidden':
                preview = `<div class="text-secondary small"><i class="bx bx-hide me-1"></i>Hidden: ${escapeHtml(element.label || 'Hidden Field')}</div>`;
                break;

            case 'image':
                const imgSizeMap = { small: '25%', medium: '50%', large: '75%', full: '100%' };
                const imgSize = imgSizeMap[element.imageSize] || '50%';
                const imgPosition = element.imagePosition || 'center';
                const imgAlign = imgPosition === 'left' ? 'start' : (imgPosition === 'right' ? 'end' : 'center');
                preview = `
                    <div class="p-3 bg-light rounded d-flex justify-content-${imgAlign}">
                        <div style="width: ${imgSize}; max-width: 100%;">
                            ${element.imageUrl ?
                                `<img src="${escapeHtml(element.imageUrl)}" class="img-fluid rounded" style="max-height: 150px; width: 100%; object-fit: contain;">` :
                                '<div class="text-center py-4"><i class="bx bx-image text-secondary" style="font-size: 3rem;"></i><p class="text-secondary small mb-0 mt-2">Upload an image</p></div>'
                            }
                            ${element.caption ? `<p class="text-secondary small mb-0 mt-2 text-center">${escapeHtml(element.caption)}</p>` : ''}
                        </div>
                    </div>
                `;
                break;

            case 'video':
                preview = `
                    <div class="text-center p-3 bg-light rounded">
                        <i class="bx bx-play-circle text-danger" style="font-size: 3rem;"></i>
                        <p class="text-secondary small mb-0 mt-2">${element.videoUrl ? 'YouTube/Video Embed' : 'Add video URL in properties'}</p>
                    </div>
                `;
                break;

            case 'submit_button':
                preview = `
                    <button type="button" class="btn" style="background-color: ${element.buttonColor || '#556ee6'}; color: #fff; pointer-events: none;">
                        ${escapeHtml(element.buttonText || 'Submit')}
                    </button>
                `;
                break;
        }

        return `
            <div class="form-element ${element.width || 'col-12'} ${isSelected ? 'selected' : ''}"
                data-id="${element.id}" data-index="${index}">
                <div class="element-drag-handle">
                    <i class="bx bx-grip-vertical"></i>
                </div>
                <div class="element-actions">
                    <button type="button" class="btn btn-soft-info btn-sm duplicate-element" title="Duplicate">
                        <i class="bx bx-copy"></i>
                    </button>
                    <button type="button" class="btn btn-soft-danger btn-sm delete-element" title="Delete">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
                <div class="element-preview">
                    ${preview}
                </div>
            </div>
        `;
    }

    // Initialize sortable
    function initSortable() {
        const container = document.getElementById('canvasElements');
        if (container.children.length === 0) return;

        let draggedItem = null;

        $(container).find('.form-element').each(function() {
            this.draggable = true;

            this.addEventListener('dragstart', function(e) {
                draggedItem = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            this.addEventListener('dragend', function() {
                this.classList.remove('dragging');
                draggedItem = null;
                updateElementOrder();
            });

            this.addEventListener('dragover', function(e) {
                e.preventDefault();
                if (draggedItem && draggedItem !== this) {
                    const rect = this.getBoundingClientRect();
                    const midY = rect.top + rect.height / 2;
                    if (e.clientY < midY) {
                        container.insertBefore(draggedItem, this);
                    } else {
                        container.insertBefore(draggedItem, this.nextSibling);
                    }
                }
            });
        });
    }

    // Update element order after drag
    function updateElementOrder() {
        const newOrder = [];
        $('#canvasElements .form-element').each(function() {
            const id = $(this).data('id');
            const element = formElements.find(e => e.id === id);
            if (element) newOrder.push(element);
        });
        formElements = newOrder;
    }

    // Bind element events
    function bindElementEvents() {
        // Select element
        $('.form-element').on('click', function(e) {
            if (!$(e.target).closest('.element-actions').length) {
                selectElement($(this).data('id'));
            }
        });

        // Duplicate element
        $('.duplicate-element').on('click', function(e) {
            e.stopPropagation();
            const id = $(this).closest('.form-element').data('id');
            duplicateElement(id);
        });

        // Delete element
        $('.delete-element').on('click', function(e) {
            e.stopPropagation();
            const id = $(this).closest('.form-element').data('id');
            deleteElement(id);
        });
    }

    // Select element and show properties
    function selectElement(id) {
        selectedElement = id;
        $('.form-element').removeClass('selected');
        $(`.form-element[data-id="${id}"]`).addClass('selected');
        renderProperties(id);
        propertiesOffcanvas.show();
    }

    // Duplicate element
    function duplicateElement(id) {
        const element = formElements.find(e => e.id === id);
        if (element) {
            const newElement = {
                ...JSON.parse(JSON.stringify(element)),
                id: 'field_' + elementIdCounter++
            };
            const index = formElements.findIndex(e => e.id === id);
            formElements.splice(index + 1, 0, newElement);
            renderCanvas();
            selectElement(newElement.id);
            updateElementCount();
            toastr.success('Element duplicated');
        }
    }

    // Delete element
    function deleteElement(id) {
        formElements = formElements.filter(e => e.id !== id);
        if (selectedElement === id) {
            selectedElement = null;
            propertiesOffcanvas.hide();
        }
        renderCanvas();
        updateElementCount();
        toastr.success('Element deleted');
    }

    // Render properties panel
    function renderProperties(id) {
        const element = formElements.find(e => e.id === id);
        if (!element) return;

        let html = '';

        // Type-specific properties
        switch (element.type) {
            case 'submit_button':
                html = `
                    <div class="property-group">
                        <div class="property-group-title">Submit Button</div>
                        <div class="property-row">
                            <label class="form-label text-dark small">Button Text</label>
                            <input type="text" class="form-control form-control-sm" id="prop-buttonText" value="${escapeHtml(element.buttonText || 'Submit')}">
                        </div>
                        <div class="property-row">
                            <label class="form-label text-dark small">Button Color</label>
                            <input type="color" class="form-control form-control-color" id="prop-buttonColor" value="${element.buttonColor || '#556ee6'}">
                        </div>
                    </div>
                `;
                break;

            case 'image':
                html = `
                    <div class="property-group">
                        <div class="property-group-title">Image Upload</div>
                        <div class="property-row">
                            <label class="form-label text-dark small">Upload Image</label>
                            <input type="file" class="form-control form-control-sm" id="prop-imageUpload" accept="image/*">
                            <div id="uploadProgress" class="progress mt-2" style="display: none; height: 5px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                            </div>
                        </div>
                        ${element.imageUrl ? `
                        <div class="property-row">
                            <div class="position-relative">
                                <img src="${escapeHtml(element.imageUrl)}" class="img-fluid rounded" style="max-height: 120px;">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" id="removeImageBtn" style="margin: 4px;">
                                    <i class="bx bx-x"></i>
                                </button>
                            </div>
                        </div>
                        ` : ''}
                        <div class="property-row">
                            <label class="form-label text-dark small">Caption (optional)</label>
                            <input type="text" class="form-control form-control-sm" id="prop-caption" value="${escapeHtml(element.caption || '')}">
                        </div>
                    </div>
                    <div class="property-group">
                        <div class="property-group-title">Display Options</div>
                        <div class="property-row">
                            <label class="form-label text-dark small">Image Size</label>
                            <select class="form-select form-select-sm" id="prop-imageSize">
                                <option value="small" ${element.imageSize === 'small' ? 'selected' : ''}>Small (25%)</option>
                                <option value="medium" ${(element.imageSize === 'medium' || !element.imageSize) ? 'selected' : ''}>Medium (50%)</option>
                                <option value="large" ${element.imageSize === 'large' ? 'selected' : ''}>Large (75%)</option>
                                <option value="full" ${element.imageSize === 'full' ? 'selected' : ''}>Full Width (100%)</option>
                            </select>
                        </div>
                        <div class="property-row">
                            <label class="form-label text-dark small">Position</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-sm ${element.imagePosition === 'left' ? 'btn-primary' : 'btn-outline-secondary'} position-btn" data-position="left">
                                    <i class="bx bx-align-left"></i>
                                </button>
                                <button type="button" class="btn btn-sm ${(element.imagePosition === 'center' || !element.imagePosition) ? 'btn-primary' : 'btn-outline-secondary'} position-btn" data-position="center">
                                    <i class="bx bx-align-middle"></i>
                                </button>
                                <button type="button" class="btn btn-sm ${element.imagePosition === 'right' ? 'btn-primary' : 'btn-outline-secondary'} position-btn" data-position="right">
                                    <i class="bx bx-align-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                break;

            case 'video':
                html = `
                    <div class="property-group">
                        <div class="property-group-title">Video</div>
                        <div class="property-row">
                            <label class="form-label text-dark small">Video URL</label>
                            <input type="url" class="form-control form-control-sm" id="prop-videoUrl" value="${escapeHtml(element.videoUrl || '')}" placeholder="YouTube or embed URL">
                            <small class="text-secondary">Paste YouTube URL or embed link</small>
                        </div>
                    </div>
                `;
                break;

            case 'heading':
                html = `
                    <div class="property-group">
                        <div class="property-group-title">Heading</div>
                        <div class="property-row">
                            <label class="form-label text-dark small">Text</label>
                            <input type="text" class="form-control form-control-sm" id="prop-text" value="${escapeHtml(element.text || '')}">
                        </div>
                        <div class="property-row">
                            <label class="form-label text-dark small">Size</label>
                            <select class="form-select form-select-sm" id="prop-size">
                                <option value="h2" ${element.size === 'h2' ? 'selected' : ''}>Large (H2)</option>
                                <option value="h3" ${element.size === 'h3' ? 'selected' : ''}>Medium (H3)</option>
                                <option value="h4" ${(element.size === 'h4' || !element.size) ? 'selected' : ''}>Normal (H4)</option>
                                <option value="h5" ${element.size === 'h5' ? 'selected' : ''}>Small (H5)</option>
                            </select>
                        </div>
                    </div>
                `;
                break;

            case 'paragraph':
                html = `
                    <div class="property-group">
                        <div class="property-group-title">Paragraph</div>
                        <div class="property-row">
                            <label class="form-label text-dark small">Text</label>
                            <textarea class="form-control form-control-sm" id="prop-text" rows="3">${escapeHtml(element.text || '')}</textarea>
                        </div>
                    </div>
                `;
                break;

            case 'hidden':
                html = `
                    <div class="property-group">
                        <div class="property-group-title">Hidden Field</div>
                        <div class="property-row">
                            <label class="form-label text-dark small">Field Name</label>
                            <input type="text" class="form-control form-control-sm" id="prop-label" value="${escapeHtml(element.label || '')}">
                        </div>
                        <div class="property-row">
                            <label class="form-label text-dark small">Default Value</label>
                            <input type="text" class="form-control form-control-sm" id="prop-value" value="${escapeHtml(element.value || '')}">
                        </div>
                    </div>
                `;
                break;

            case 'divider':
                html = `<p class="text-secondary small">No properties for divider element.</p>`;
                break;

            default:
                // Standard field properties
                html = `
                    <div class="property-group">
                        <div class="property-group-title">Basic</div>
                        <div class="property-row">
                            <label class="form-label text-dark small">Label</label>
                            <input type="text" class="form-control form-control-sm" id="prop-label" value="${escapeHtml(element.label || '')}">
                        </div>
                        ${['text', 'email', 'phone', 'number', 'textarea', 'select'].includes(element.type) ? `
                        <div class="property-row">
                            <label class="form-label text-dark small">Placeholder</label>
                            <input type="text" class="form-control form-control-sm" id="prop-placeholder" value="${escapeHtml(element.placeholder || '')}">
                        </div>
                        ` : ''}
                        <div class="property-row">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="prop-required" ${element.required ? 'checked' : ''}>
                                <label class="form-check-label text-dark small" for="prop-required">Required field</label>
                            </div>
                        </div>
                    </div>
                `;

                // Type-specific additions
                if (element.type === 'textarea') {
                    html += `
                        <div class="property-group">
                            <div class="property-group-title">Text Area</div>
                            <div class="property-row">
                                <label class="form-label text-dark small">Rows</label>
                                <input type="number" class="form-control form-control-sm" id="prop-rows" value="${element.rows || 4}" min="2" max="20">
                            </div>
                        </div>
                    `;
                }

                if (element.type === 'number') {
                    html += `
                        <div class="property-group">
                            <div class="property-group-title">Number</div>
                            <div class="property-row">
                                <label class="form-label text-dark small">Min Value</label>
                                <input type="number" class="form-control form-control-sm" id="prop-min" value="${element.min ?? ''}">
                            </div>
                            <div class="property-row">
                                <label class="form-label text-dark small">Max Value</label>
                                <input type="number" class="form-control form-control-sm" id="prop-max" value="${element.max ?? ''}">
                            </div>
                        </div>
                    `;
                }

                if (['select', 'radio', 'checkbox'].includes(element.type)) {
                    html += `
                        <div class="property-group">
                            <div class="property-group-title">Options</div>
                            <div class="property-row">
                                <ul class="options-list" id="optionsList">
                                    ${(element.options || []).map((opt, i) => `
                                        <li class="option-item">
                                            <input type="text" class="form-control form-control-sm option-input" value="${escapeHtml(opt)}" data-index="${i}">
                                            <button type="button" class="btn btn-soft-danger btn-sm btn-remove-option" data-index="${i}">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </li>
                                    `).join('')}
                                </ul>
                                <button type="button" class="btn btn-soft-primary btn-sm w-100" id="addOptionBtn">
                                    <i class="bx bx-plus me-1"></i>Add Option
                                </button>
                            </div>
                            ${['radio', 'checkbox'].includes(element.type) ? `
                            <div class="property-row">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="prop-inline" ${element.inline ? 'checked' : ''}>
                                    <label class="form-check-label text-dark small" for="prop-inline">Display inline</label>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    `;
                }

                if (element.type === 'file') {
                    html += `
                        <div class="property-group">
                            <div class="property-group-title">File Upload</div>
                            <div class="property-row">
                                <label class="form-label text-dark small">Accepted File Types</label>
                                <input type="text" class="form-control form-control-sm" id="prop-accept" value="${escapeHtml(element.accept || '.pdf,.doc,.docx,.jpg,.png')}">
                            </div>
                            <div class="property-row">
                                <label class="form-label text-dark small">Max Size (MB)</label>
                                <input type="number" class="form-control form-control-sm" id="prop-maxSize" value="${element.maxSize || 5}" min="1" max="50">
                            </div>
                        </div>
                    `;
                }
        }

        // Width property (for most elements)
        if (!['hidden', 'submit_button'].includes(element.type)) {
            html += `
                <div class="property-group">
                    <div class="property-group-title">Layout</div>
                    <div class="property-row">
                        <label class="form-label text-dark small">Width</label>
                        <div class="width-selector">
                            <button type="button" class="btn btn-outline-secondary width-btn ${element.width === 'col-6' ? 'active' : ''}" data-width="col-6">50%</button>
                            <button type="button" class="btn btn-outline-secondary width-btn ${(element.width === 'col-12' || !element.width) ? 'active' : ''}" data-width="col-12">100%</button>
                        </div>
                    </div>
                </div>
            `;
        }

        $('#propertiesPanel').html(html);
        bindPropertyEvents(element.id);
    }

    // Bind property change events
    function bindPropertyEvents(elementId) {
        const updateElement = (key, value) => {
            const element = formElements.find(e => e.id === elementId);
            if (element) {
                element[key] = value;
                renderCanvas();
            }
        };

        $('#prop-label').on('input', function() { updateElement('label', $(this).val()); });
        $('#prop-placeholder').on('input', function() { updateElement('placeholder', $(this).val()); });
        $('#prop-required').on('change', function() { updateElement('required', $(this).is(':checked')); });
        $('#prop-text').on('input', function() { updateElement('text', $(this).val()); });
        $('#prop-size').on('change', function() { updateElement('size', $(this).val()); });
        $('#prop-rows').on('input', function() { updateElement('rows', parseInt($(this).val()) || 4); });
        $('#prop-min').on('input', function() { updateElement('min', $(this).val() ? parseFloat($(this).val()) : null); });
        $('#prop-max').on('input', function() { updateElement('max', $(this).val() ? parseFloat($(this).val()) : null); });
        $('#prop-accept').on('input', function() { updateElement('accept', $(this).val()); });
        $('#prop-maxSize').on('input', function() { updateElement('maxSize', parseInt($(this).val()) || 5); });
        $('#prop-inline').on('change', function() { updateElement('inline', $(this).is(':checked')); });
        $('#prop-value').on('input', function() { updateElement('value', $(this).val()); });
        $('#prop-buttonText').on('input', function() { updateElement('buttonText', $(this).val()); });
        $('#prop-buttonColor').on('input', function() { updateElement('buttonColor', $(this).val()); });
        $('#prop-caption').on('input', function() { updateElement('caption', $(this).val()); });
        $('#prop-videoUrl').on('input', function() { updateElement('videoUrl', $(this).val()); });
        $('#prop-imageSize').on('change', function() { updateElement('imageSize', $(this).val()); });

        // Image position buttons
        $('.position-btn').on('click', function() {
            $('.position-btn').removeClass('btn-primary').addClass('btn-outline-secondary');
            $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
            updateElement('imagePosition', $(this).data('position'));
        });

        // Image upload
        $('#prop-imageUpload').on('change', function() {
            const file = this.files[0];
            if (!file) return;

            // Validate file type
            if (!file.type.startsWith('image/')) {
                toastr.error('Please select an image file');
                return;
            }

            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                toastr.error('Image size must be less than 5MB');
                return;
            }

            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', '{{ csrf_token() }}');

            $('#uploadProgress').show();

            $.ajax({
                url: '/crm-forms-upload-image',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        updateElement('imageUrl', response.imageUrl);
                        renderProperties(elementId);
                        toastr.success('Image uploaded successfully');
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to upload image');
                },
                complete: function() {
                    $('#uploadProgress').hide();
                    $('#prop-imageUpload').val('');
                }
            });
        });

        // Remove image button
        $('#removeImageBtn').on('click', function() {
            updateElement('imageUrl', '');
            renderProperties(elementId);
            toastr.success('Image removed');
        });

        // Width buttons
        $('.width-btn').on('click', function() {
            $('.width-btn').removeClass('active');
            $(this).addClass('active');
            updateElement('width', $(this).data('width'));
        });

        // Options management
        $(document).off('input', '.option-input').on('input', '.option-input', function() {
            const element = formElements.find(e => e.id === elementId);
            if (element) {
                const index = $(this).data('index');
                element.options[index] = $(this).val();
            }
        });

        $(document).off('click', '.btn-remove-option').on('click', '.btn-remove-option', function() {
            const element = formElements.find(e => e.id === elementId);
            if (element && element.options.length > 1) {
                const index = $(this).data('index');
                element.options.splice(index, 1);
                renderProperties(elementId);
                renderCanvas();
            } else {
                toastr.warning('At least one option is required');
            }
        });

        $('#addOptionBtn').off('click').on('click', function() {
            const element = formElements.find(e => e.id === elementId);
            if (element) {
                element.options = element.options || [];
                element.options.push('Option ' + (element.options.length + 1));
                renderProperties(elementId);
                renderCanvas();
            }
        });
    }

    // Update element count
    function updateElementCount() {
        const count = formElements.length;
        $('#elementCount').text(count + ' element' + (count !== 1 ? 's' : ''));
    }

    // ============ TRIGGER FLOW - CANVAS BASED ============

    let selectedNode = null;
    let editingEmailNode = null;
    let emailEditorMode = 'visual';
    const emailBuilderModal = new bootstrap.Modal(document.getElementById('emailBuilderModal'));
    const apiKeyModal = new bootstrap.Modal(document.getElementById('apiKeyConfirmModal'));

    // Initialize trigger canvas
    initTriggerCanvas();
    renderTriggerNodes();
    setupEmailBuilder();

    function initTriggerCanvas() {
        // Use native JS for drag events (more reliable)
        const triggerActions = document.querySelectorAll('.trigger-action-item');
        const triggerCanvas = document.getElementById('triggerCanvas');

        // Setup drag events on sidebar items
        triggerActions.forEach(item => {
            item.addEventListener('dragstart', function(e) {
                this.classList.add('dragging');
                e.dataTransfer.setData('text/plain', this.dataset.actionType);
                e.dataTransfer.effectAllowed = 'copy';
            });

            item.addEventListener('dragend', function() {
                this.classList.remove('dragging');
            });

            // Click to add
            item.addEventListener('click', function() {
                const actionType = this.dataset.actionType;
                const nodeCount = triggerFlow.length;
                addTriggerNode(actionType, 60 + (nodeCount * 20), 60 + (nodeCount * 80));
            });
        });

        // Canvas drop zone events
        triggerCanvas.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.dataTransfer.dropEffect = 'copy';
            this.classList.add('drag-over');
        });

        triggerCanvas.addEventListener('dragleave', function(e) {
            e.preventDefault();
            // Only remove class if leaving the canvas itself
            if (e.target === this) {
                this.classList.remove('drag-over');
            }
        });

        triggerCanvas.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');

            const actionType = e.dataTransfer.getData('text/plain');
            if (!actionType) return;

            // Calculate position relative to canvas
            const canvasRect = this.getBoundingClientRect();
            const scrollLeft = this.parentElement.scrollLeft || 0;
            const scrollTop = this.parentElement.scrollTop || 0;
            const x = e.clientX - canvasRect.left + scrollLeft - 100;
            const y = e.clientY - canvasRect.top + scrollTop - 30;

            addTriggerNode(actionType, Math.max(20, x), Math.max(20, y));
        });

        // Re-init when tab is shown (important for hidden elements)
        $('a[href="#triggerTab"]').on('shown.bs.tab', function() {
            renderTriggerNodes();
        });
    }

    function addTriggerNode(type, x, y) {
        const node = {
            id: 'trigger_' + (++triggerIdCounter),
            type: type,
            position: { x: x, y: y },
            config: getDefaultTriggerConfig(type)
        };
        triggerFlow.push(node);
        renderTriggerNodes();
        toastr.success('Action added');
    }

    function getDefaultTriggerConfig(type) {
        switch (type) {
            case 'send_email':
                return { to: '', subject: 'New Form Submission', body: '' };
            case 'notify_admin':
                return { email: '', message: 'New submission received' };
            case 'webhook':
                return { url: '', method: 'POST' };
            case 'create_lead':
                return { source: 'form', status: 'new' };
            case 'delay':
                return { value: 1, unit: 'hours' };
            case 'add_course_access':
                return { tagId: '', tagName: '' };
            default:
                return {};
        }
    }

    // Access tags data
    const accessTags = @json($accessTags ?? []);

    // Lead fields for mapping
    const leadFields = {
        standard: @json(\App\Models\CrmLead::IMPORTABLE_FIELDS),
        custom: []
    };

    // Load custom fields
    $.get('{{ route("crm-forms.lead-fields") }}', function(response) {
        if (response.success) {
            leadFields.custom = response.data.custom || [];
        }
    });

    function renderTriggerNodes() {
        const container = $('#triggerNodes');
        const emptyState = $('#triggerCanvasEmpty');

        if (triggerFlow.length === 0) {
            emptyState.show();
            container.empty();
            return;
        }

        emptyState.hide();
        container.empty();

        // Add start node
        container.append(`
            <div class="trigger-node start-node">
                <div class="trigger-node-header">
                    <div class="node-icon bg-transparent"><i class="bx bx-play text-white"></i></div>
                    <span class="node-title text-white">Form Submitted</span>
                </div>
                <div class="trigger-node-body">When form is submitted</div>
            </div>
        `);

        // Render action nodes with connector lines
        triggerFlow.forEach((node, index) => {
            container.append('<div class="trigger-connector-line"></div>');
            container.append(renderTriggerNode(node, index));
        });

        bindTriggerNodeEvents();
    }

    function renderTriggerNode(node, index) {
        const typeInfo = {
            'send_email': { name: 'Send Email', icon: 'bx-envelope', desc: node.config?.to || 'Click to configure' },
            'notify_admin': { name: 'Notify Admin', icon: 'bx-bell', desc: node.config?.email || 'Admin notification' },
            'webhook': { name: 'Webhook', icon: 'bx-link-external', desc: node.config?.url ? (new URL(node.config.url).hostname) : 'Click to configure' },
            'create_lead': { name: 'Create Lead', icon: 'bx-user-plus', desc: 'Source: ' + (node.config?.source || 'form') },
            'delay': { name: 'Delay', icon: 'bx-time', desc: (node.config?.value || 1) + ' ' + (node.config?.unit || 'hours') },
            'add_course_access': { name: 'Add Course Access', icon: 'bx-id-card', desc: node.config?.tagName || 'Click to configure' }
        };

        const info = typeInfo[node.type] || { name: node.type, icon: 'bx-cog', desc: '' };
        const isSelected = selectedNode === node.id;

        return `
            <div class="trigger-node node-type-${node.type} ${isSelected ? 'selected' : ''}" data-node-id="${node.id}">
                <div class="trigger-node-header">
                    <div class="node-icon"><i class="bx ${info.icon}"></i></div>
                    <span class="node-title text-dark">${info.name}</span>
                    <div class="node-actions">
                        <button type="button" class="btn btn-soft-primary btn-sm edit-node" title="Edit"><i class="bx bx-edit-alt"></i></button>
                        <button type="button" class="btn btn-soft-danger btn-sm delete-node" title="Delete"><i class="bx bx-trash"></i></button>
                    </div>
                </div>
                <div class="trigger-node-body">${escapeHtml(info.desc)}</div>
            </div>
        `;
    }

    function bindTriggerNodeEvents() {
        // Edit node
        $('.edit-node').off('click').on('click', function(e) {
            e.stopPropagation();
            const nodeId = $(this).closest('.trigger-node').data('node-id');
            editTriggerNode(nodeId);
        });

        // Delete node
        $('.delete-node').off('click').on('click', function(e) {
            e.stopPropagation();
            const nodeId = $(this).closest('.trigger-node').data('node-id');
            deleteTriggerNode(nodeId);
        });

        // Double-click to edit
        $('.trigger-node:not(.start-node)').off('dblclick').on('dblclick', function() {
            editTriggerNode($(this).data('node-id'));
        });
    }

    function editTriggerNode(nodeId) {
        const node = triggerFlow.find(n => n.id === nodeId);
        if (!node) return;

        if (node.type === 'send_email') {
            openEmailBuilder(node);
        } else {
            openNodeEditor(node);
        }
    }

    function deleteTriggerNode(nodeId) {
        triggerFlow = triggerFlow.filter(n => n.id !== nodeId);
        renderTriggerNodes();
        toastr.success('Action removed');
    }

    function openNodeEditor(node) {
        let html = '';
        switch (node.type) {
            case 'notify_admin':
                html = `
                    <div class="mb-3">
                        <label class="form-label text-dark">Admin Email (optional)</label>
                        <input type="email" class="form-control" id="nodeConfigEmail" value="${escapeHtml(node.config?.email || '')}" placeholder="Leave empty for form owner">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Message</label>
                        <textarea class="form-control" id="nodeConfigMessage" rows="3">${escapeHtml(node.config?.message || '')}</textarea>
                    </div>
                `;
                break;
            case 'webhook':
                html = `
                    <div class="mb-3">
                        <label class="form-label text-dark">Webhook URL</label>
                        <input type="url" class="form-control" id="nodeConfigUrl" value="${escapeHtml(node.config?.url || '')}" placeholder="https://...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Method</label>
                        <select class="form-select" id="nodeConfigMethod">
                            <option value="POST" ${node.config?.method === 'POST' ? 'selected' : ''}>POST</option>
                            <option value="GET" ${node.config?.method === 'GET' ? 'selected' : ''}>GET</option>
                        </select>
                    </div>
                `;
                break;
            case 'create_lead':
                // Build form fields for mapping
                const formFieldOptions = formElements
                    .filter(el => el.id && !['heading', 'paragraph', 'divider', 'submit_button', 'image', 'video'].includes(el.type))
                    .map(el => `<option value="${escapeHtml(el.id)}">${escapeHtml(el.label || el.id)}</option>`)
                    .join('');

                // Build lead fields options
                const leadFieldOptions = Object.entries(leadFields.standard || {})
                    .map(([key, info]) => `<option value="${escapeHtml(key)}">${escapeHtml(info.label)}</option>`)
                    .join('');

                // Build custom field options
                const customFieldOptions = (leadFields.custom || [])
                    .map(name => `<option value="custom:${escapeHtml(name)}">[Custom] ${escapeHtml(name)}</option>`)
                    .join('');

                // Render existing mappings
                const existingMappings = node.config?.fieldMappings || [];
                const mappingsHtml = existingMappings.map((m, i) => `
                    <div class="field-mapping-row d-flex gap-2 mb-2" data-index="${i}">
                        <select class="form-select form-select-sm mapping-form-field" style="flex: 1;">
                            <option value="">Form Field...</option>
                            ${formFieldOptions.replace(`value="${escapeHtml(m.formField)}"`, `value="${escapeHtml(m.formField)}" selected`)}
                        </select>
                        <span class="align-self-center"><i class="bx bx-right-arrow-alt text-muted"></i></span>
                        <select class="form-select form-select-sm mapping-lead-field" style="flex: 1;">
                            <option value="">Lead Field...</option>
                            <optgroup label="Standard Fields">
                                ${leadFieldOptions.replace(`value="${escapeHtml(m.leadField)}"`, `value="${escapeHtml(m.leadField)}" selected`)}
                            </optgroup>
                            <optgroup label="Custom Fields">
                                ${customFieldOptions.replace(`value="${escapeHtml(m.leadField)}"`, `value="${escapeHtml(m.leadField)}" selected`)}
                                <option value="custom:__new__">+ Add New Custom Field</option>
                            </optgroup>
                        </select>
                        <button type="button" class="btn btn-sm btn-soft-danger remove-mapping"><i class="bx bx-trash"></i></button>
                    </div>
                `).join('');

                html = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-dark">Lead Source</label>
                            <input type="text" class="form-control" id="nodeConfigSource" value="${escapeHtml(node.config?.source || 'form')}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark">Initial Status</label>
                            <select class="form-select" id="nodeConfigStatus">
                                <option value="new" ${node.config?.status === 'new' ? 'selected' : ''}>New</option>
                                <option value="contacted" ${node.config?.status === 'contacted' ? 'selected' : ''}>Contacted</option>
                                <option value="qualified" ${node.config?.status === 'qualified' ? 'selected' : ''}>Qualified</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <h6 class="text-dark mb-3"><i class="bx bx-link me-1"></i>Field Mapping</h6>
                    <p class="text-secondary small mb-3">Map form fields to lead fields. Unmapped fields will be ignored.</p>

                    <div id="fieldMappingsContainer">
                        ${mappingsHtml || '<div class="text-muted small mb-2">No mappings yet. Click "Add Mapping" to start.</div>'}
                    </div>

                    <button type="button" class="btn btn-sm btn-soft-primary" id="addMappingBtn">
                        <i class="bx bx-plus me-1"></i>Add Mapping
                    </button>

                    <div class="mt-3 p-2 bg-soft-info rounded small">
                        <i class="bx bx-info-circle me-1"></i>
                        <strong>Tip:</strong> Map <code>email</code> field to identify duplicate leads.
                        Custom fields will be stored in lead's custom data.
                    </div>
                `;
                break;
            case 'delay':
                html = `
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label text-dark">Delay Value</label>
                            <input type="number" class="form-control" id="nodeConfigValue" value="${node.config?.value || 1}" min="1">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label text-dark">Unit</label>
                            <select class="form-select" id="nodeConfigUnit">
                                <option value="minutes" ${node.config?.unit === 'minutes' ? 'selected' : ''}>Minutes</option>
                                <option value="hours" ${node.config?.unit === 'hours' ? 'selected' : ''}>Hours</option>
                                <option value="days" ${node.config?.unit === 'days' ? 'selected' : ''}>Days</option>
                            </select>
                        </div>
                    </div>
                `;
                break;
            case 'add_course_access':
                const tagOptions = accessTags.map(tag =>
                    `<option value="${tag.id}" data-name="${escapeHtml(tag.tagName)}" ${node.config?.tagId == tag.id ? 'selected' : ''}>${escapeHtml(tag.tagName)} (${tag.expirationLength} days)</option>`
                ).join('');
                html = `
                    <div class="mb-3">
                        <label class="form-label text-dark">Select Access Tag</label>
                        <select class="form-select" id="nodeConfigTagId">
                            <option value="">Choose an access tag...</option>
                            ${tagOptions}
                        </select>
                        ${accessTags.length === 0 ? '<small class="text-warning">No access tags found. Create tags in Ani-Senso Course settings.</small>' : ''}
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="bx bx-info-circle me-1"></i>
                        This will grant the selected course access tag to the form submitter based on their email address.
                    </div>
                `;
                break;
        }

        // Show in a simple modal or alert
        const modalHtml = `
            <div class="modal fade" id="nodeEditorModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bx bx-cog me-2"></i>Configure Action</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">${html}</div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="saveNodeConfig">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#nodeEditorModal').remove();
        $('body').append(modalHtml);

        const nodeModal = new bootstrap.Modal(document.getElementById('nodeEditorModal'));
        nodeModal.show();

        // Add mapping button handler
        $('#addMappingBtn').on('click', function() {
            const formFieldOptions = formElements
                .filter(el => el.id && !['heading', 'paragraph', 'divider', 'submit_button', 'image', 'video'].includes(el.type))
                .map(el => `<option value="${escapeHtml(el.id)}">${escapeHtml(el.label || el.id)}</option>`)
                .join('');

            const leadFieldOptions = Object.entries(leadFields.standard || {})
                .map(([key, info]) => `<option value="${escapeHtml(key)}">${escapeHtml(info.label)}</option>`)
                .join('');

            const customFieldOptions = (leadFields.custom || [])
                .map(name => `<option value="custom:${escapeHtml(name)}">[Custom] ${escapeHtml(name)}</option>`)
                .join('');

            const newIndex = $('.field-mapping-row').length;
            const newRow = `
                <div class="field-mapping-row d-flex gap-2 mb-2" data-index="${newIndex}">
                    <select class="form-select form-select-sm mapping-form-field" style="flex: 1;">
                        <option value="">Form Field...</option>
                        ${formFieldOptions}
                    </select>
                    <span class="align-self-center"><i class="bx bx-right-arrow-alt text-muted"></i></span>
                    <select class="form-select form-select-sm mapping-lead-field" style="flex: 1;">
                        <option value="">Lead Field...</option>
                        <optgroup label="Standard Fields">${leadFieldOptions}</optgroup>
                        <optgroup label="Custom Fields">
                            ${customFieldOptions}
                            <option value="custom:__new__">+ Add New Custom Field</option>
                        </optgroup>
                    </select>
                    <button type="button" class="btn btn-sm btn-soft-danger remove-mapping"><i class="bx bx-trash"></i></button>
                </div>
            `;
            $('#fieldMappingsContainer .text-muted').remove();
            $('#fieldMappingsContainer').append(newRow);
        });

        // Remove mapping handler
        $(document).on('click', '.remove-mapping', function() {
            $(this).closest('.field-mapping-row').remove();
            if ($('.field-mapping-row').length === 0) {
                $('#fieldMappingsContainer').html('<div class="text-muted small mb-2">No mappings yet. Click "Add Mapping" to start.</div>');
            }
        });

        // Handle new custom field option
        $(document).on('change', '.mapping-lead-field', function() {
            if ($(this).val() === 'custom:__new__') {
                const customName = prompt('Enter custom field name:');
                if (customName && customName.trim()) {
                    const safeName = customName.trim().replace(/[^a-zA-Z0-9_]/g, '_');
                    // Add to custom fields list
                    if (!leadFields.custom.includes(safeName)) {
                        leadFields.custom.push(safeName);
                    }
                    // Add option and select it
                    $(this).find('optgroup:last').prepend(`<option value="custom:${escapeHtml(safeName)}">[Custom] ${escapeHtml(safeName)}</option>`);
                    $(this).val('custom:' + safeName);
                } else {
                    $(this).val('');
                }
            }
        });

        $('#saveNodeConfig').on('click', function() {
            switch (node.type) {
                case 'notify_admin':
                    node.config.email = $('#nodeConfigEmail').val();
                    node.config.message = $('#nodeConfigMessage').val();
                    break;
                case 'webhook':
                    node.config.url = $('#nodeConfigUrl').val();
                    node.config.method = $('#nodeConfigMethod').val();
                    break;
                case 'create_lead':
                    node.config.source = $('#nodeConfigSource').val();
                    node.config.status = $('#nodeConfigStatus').val();
                    // Collect field mappings
                    node.config.fieldMappings = [];
                    $('.field-mapping-row').each(function() {
                        const formField = $(this).find('.mapping-form-field').val();
                        const leadField = $(this).find('.mapping-lead-field').val();
                        if (formField && leadField && leadField !== 'custom:__new__') {
                            node.config.fieldMappings.push({ formField, leadField });
                        }
                    });
                    break;
                case 'delay':
                    node.config.value = parseInt($('#nodeConfigValue').val()) || 1;
                    node.config.unit = $('#nodeConfigUnit').val();
                    break;
                case 'add_course_access':
                    const selectedTag = $('#nodeConfigTagId option:selected');
                    node.config.tagId = $('#nodeConfigTagId').val();
                    node.config.tagName = selectedTag.data('name') || '';
                    break;
            }
            nodeModal.hide();
            renderTriggerNodes();
            toastr.success('Action updated');
        });
    }

    // ============ EMAIL BUILDER ============

    function setupEmailBuilder() {
        // Visual/HTML mode toggle
        $('#emailVisualModeBtn').on('click', function() {
            if (emailEditorMode === 'visual') return;
            switchToEmailVisualMode();
        });

        $('#emailHtmlModeBtn').on('click', function() {
            if (emailEditorMode === 'html') return;
            switchToEmailHtmlMode();
        });

        // Toolbar commands
        $('#emailToolbar').on('click', '[data-command]', function(e) {
            e.preventDefault();
            document.execCommand($(this).data('command'), false, null);
            $('#emailVisualEditor').focus();
        });

        $('#emailFontSize').on('change', function() {
            if ($(this).val()) {
                document.execCommand('fontSize', false, $(this).val());
                $('#emailVisualEditor').focus();
            }
            $(this).val('');
        });

        $('#emailTextColor').on('input', function() {
            document.execCommand('foreColor', false, $(this).val());
            $('#emailVisualEditor').focus();
        });

        $('#emailInsertLinkBtn').on('click', function(e) {
            e.preventDefault();
            const url = prompt('Enter URL:', 'https://');
            if (url) document.execCommand('createLink', false, url);
        });

        $('#emailInsertImageBtn').on('click', function(e) {
            e.preventDefault();
            const url = prompt('Enter Image URL:', 'https://');
            if (url) document.execCommand('insertImage', false, url);
        });

        // Save email content
        $('#saveEmailContent').on('click', function() {
            if (!editingEmailNode) return;

            editingEmailNode.config.to = $('#emailTo').val();
            editingEmailNode.config.subject = $('#emailSubject').val();
            editingEmailNode.config.body = emailEditorMode === 'visual' ?
                $('#emailVisualEditor').html() : $('#emailHtmlEditor').val();

            emailBuilderModal.hide();
            editingEmailNode = null;
            renderTriggerNodes();
            toastr.success('Email saved');
        });
    }

    function openEmailBuilder(node) {
        editingEmailNode = node;

        // Populate fields
        $('#emailTo').val(node.config?.to || '');
        $('#emailSubject').val(node.config?.subject || 'New Form Submission');
        $('#emailVisualEditor').html(node.config?.body || '');
        $('#emailHtmlEditor').val(node.config?.body || '');

        // Reset to visual mode
        switchToEmailVisualMode();

        // Populate merge tags
        updateMergeTags();

        emailBuilderModal.show();
    }

    function switchToEmailVisualMode() {
        emailEditorMode = 'visual';
        $('#emailVisualModeBtn').addClass('active');
        $('#emailHtmlModeBtn').removeClass('active');
        $('#emailVisualEditor').html($('#emailHtmlEditor').val());
        $('#emailToolbar').removeClass('d-none');
        $('#emailVisualEditor').removeClass('d-none').addClass('border-top-0');
        $('#emailHtmlEditor').addClass('d-none');
    }

    function switchToEmailHtmlMode() {
        emailEditorMode = 'html';
        $('#emailHtmlModeBtn').addClass('active');
        $('#emailVisualModeBtn').removeClass('active');
        $('#emailHtmlEditor').val($('#emailVisualEditor').html());
        $('#emailToolbar').addClass('d-none');
        $('#emailVisualEditor').addClass('d-none');
        $('#emailHtmlEditor').removeClass('d-none');
    }

    function updateMergeTags() {
        const container = $('#mergeTagsContainer');
        container.empty();

        // Add system tags
        const systemTags = [
            { tag: '@{{submission_id}}', label: 'Submission ID' },
            { tag: '@{{submission_date}}', label: 'Submission Date' },
            { tag: '@{{submitter_email}}', label: 'Submitter Email' },
            { tag: '@{{submitter_name}}', label: 'Submitter Name' }
        ];

        // Add form field tags
        const fieldTags = formElements
            .filter(el => el.id && !['heading', 'paragraph', 'divider', 'submit_button', 'image', 'video'].includes(el.type))
            .map(el => ({
                tag: `@{{${el.id}}}`,
                label: el.label || el.id
            }));

        const allTags = [...systemTags, ...fieldTags];

        if (allTags.length === 0) {
            container.html('<span class="text-secondary small">No merge tags available</span>');
            return;
        }

        allTags.forEach(item => {
            container.append(`
                <button type="button" class="btn btn-soft-secondary btn-sm merge-tag-btn" data-tag="${item.tag}">
                    <code>${item.tag}</code>
                    <span class="ms-1 text-secondary">${escapeHtml(item.label)}</span>
                </button>
            `);
        });

        // Click to insert merge tag
        $('.merge-tag-btn').on('click', function() {
            const tag = $(this).data('tag');
            if (emailEditorMode === 'visual') {
                document.execCommand('insertText', false, tag);
                $('#emailVisualEditor').focus();
            } else {
                const textarea = $('#emailHtmlEditor')[0];
                const start = textarea.selectionStart;
                const text = $(textarea).val();
                $(textarea).val(text.substring(0, start) + tag + text.substring(textarea.selectionEnd));
                textarea.selectionStart = textarea.selectionEnd = start + tag.length;
                $(textarea).focus();
            }
            toastr.info('Merge tag inserted');
        });
    }

    // ============ SAVE FORM ============

    // Get form settings
    function getFormSettings() {
        return {
            successMessage: $('#successMessage').val() || 'Thank you!',
            redirectUrl: $('#redirectUrl').val() || ''
        };
    }

    // Save form
    $('#saveFormBtn').on('click', function() {
        const formName = $('#formName').val().trim();
        if (!formName) {
            toastr.error('Please enter a form name');
            $('#formName').focus();
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        const data = {
            _token: '{{ csrf_token() }}',
            formName: formName,
            formDescription: $('#formDescription').val(),
            formStatus: $('#formStatus').val(),
            formElements: formElements,
            formSettings: getFormSettings(),
            triggerFlow: triggerFlow
        };

        const url = mode === 'edit' ? '/crm-forms-update?id=' + formId : '/crm-forms-store';
        const method = mode === 'edit' ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (response.redirect && mode === 'create') {
                        window.location.href = response.redirect;
                    }
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.values(errors).forEach(err => toastr.error(err[0]));
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Failed to save form');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Form');
            }
        });
    });

    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============ API TAB ============

    @if($form)
    // Initialize API tab
    initApiTab();

    function initApiTab() {
        updateApiParams();
        updateApiTestInputs();

        // Toggle API enabled
        $('#apiEnabledToggle').on('change', function() {
            $.post('{{ route("crm-forms.toggle-api") }}', {
                _token: '{{ csrf_token() }}',
                id: {{ $form->id ?? 0 }}
            }, function(response) {
                if (response.success) {
                    toastr.success(response.message);
                }
            }).fail(function() {
                toastr.error('Failed to toggle API');
                $('#apiEnabledToggle').prop('checked', !$('#apiEnabledToggle').is(':checked'));
            });
        });

        // Generate API key
        $('#generateApiKeyBtn').on('click', function() {
            const hasExistingKey = $('#apiKeyDisplay').val() !== 'Not generated yet';

            // Update modal content based on whether key exists
            $('#apiKeyModalTitle').text(hasExistingKey ? 'Regenerate API Key?' : 'Generate API Key?');
            $('#apiKeyModalIcon').removeClass('bx-key bx-error-circle text-primary text-warning')
                .addClass(hasExistingKey ? 'bx-error-circle text-warning' : 'bx-key text-primary');
            $('#apiKeyModalMessage').html(hasExistingKey
                ? '<p class="text-danger mb-2"><i class="bx bx-error me-1"></i>This will invalidate the existing API key.</p><p class="text-muted small mb-0">Any integrations using the current key will stop working immediately.</p>'
                : '<p class="mb-0">Generate a new API key to enable API access for this form.</p>');
            $('#confirmApiKeyBtn').removeClass('btn-primary btn-danger')
                .addClass(hasExistingKey ? 'btn-danger' : 'btn-primary')
                .html(hasExistingKey ? '<i class="bx bx-refresh me-1"></i>Regenerate' : '<i class="bx bx-key me-1"></i>Generate');

            apiKeyModal.show();
        });

        // Confirm API key generation
        $('#confirmApiKeyBtn').on('click', function() {
            const $btn = $(this);
            const $genBtn = $('#generateApiKeyBtn');
            $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Processing...');

            $.post('{{ route("crm-forms.generate-api-key") }}', {
                _token: '{{ csrf_token() }}',
                id: {{ $form->id ?? 0 }}
            }, function(response) {
                if (response.success) {
                    $('#apiKeyDisplay').val(response.apiKey).css({'color': '', 'font-style': ''});
                    toastr.success(response.message);
                    apiKeyModal.hide();
                }
            }).fail(function() {
                toastr.error('Failed to generate API key');
            }).always(function() {
                $btn.prop('disabled', false).html('<i class="bx bx-key me-1"></i>Generate');
                $genBtn.html('<i class="bx bx-refresh me-1"></i>Regenerate');
            });
        });

        // Copy API key
        $('#copyApiKeyBtn').on('click', function() {
            const apiKey = $('#apiKeyDisplay').val();
            if (!apiKey || apiKey === 'Not generated yet') {
                toastr.warning('Generate an API key first');
                return;
            }
            copyToClipboard(apiKey, 'API key copied!');
        });

        // Copy endpoint URL
        $('#copyEndpointBtn').on('click', function() {
            const url = $('#apiEndpointUrl').text().trim();
            copyToClipboard(url, 'Endpoint URL copied!');
        });

        // Helper function for clipboard copy
        function copyToClipboard(text, successMsg) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    toastr.success(successMsg);
                }).catch(function() {
                    fallbackCopy(text, successMsg);
                });
            } else {
                fallbackCopy(text, successMsg);
            }
        }

        function fallbackCopy(text, successMsg) {
            const temp = $('<textarea>').val(text).css({position: 'fixed', left: '-9999px'}).appendTo('body');
            temp[0].select();
            document.execCommand('copy');
            temp.remove();
            toastr.success(successMsg);
        }

        // Build API URL
        $('#buildApiUrlBtn').on('click', function() {
            const url = buildApiUrl();
            $('#generatedApiUrl').text(url);
            $('#testInBrowserBtn').attr('href', url);
        });

        // Copy generated URL
        $('#copyGeneratedUrlBtn').on('click', function() {
            const url = $('#generatedApiUrl').text().trim();
            if (!url || url === "Click \"Build URL\" to generate") {
                toastr.warning('Please build the URL first');
                return;
            }
            copyToClipboard(url, 'URL copied!');
        });

        // Re-render when form elements change
        $(document).on('formElementsUpdated', function() {
            updateApiParams();
            updateApiTestInputs();
        });
    }

    function updateApiParams() {
        const $tbody = $('#apiParamsTable');
        // Keep api_key row, clear others
        $tbody.find('tr:not(:first)').remove();

        formElements.forEach(el => {
            if (!el.id || ['heading', 'paragraph', 'divider', 'submit_button', 'image', 'video'].includes(el.type)) return;

            const required = el.required ? '<span class="badge bg-danger">Yes</span>' : '<span class="badge bg-secondary">No</span>';
            $tbody.append(`
                <tr>
                    <td><code>${escapeHtml(el.id)}</code></td>
                    <td>${escapeHtml(el.type)}</td>
                    <td>${required}</td>
                </tr>
            `);
        });
    }

    function updateApiTestInputs() {
        const $container = $('#apiTestInputs');
        $container.empty();

        // API key input
        $container.append(`
            <div class="mb-2">
                <label class="form-label small text-dark mb-1">api_key <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-sm api-test-input" data-param="api_key"
                       value="${escapeHtml($('#apiKeyDisplay').val() !== 'Not generated yet' ? $('#apiKeyDisplay').val() : '')}"
                       placeholder="Your API key">
            </div>
        `);

        // Form field inputs
        formElements.forEach(el => {
            if (!el.id || ['heading', 'paragraph', 'divider', 'submit_button', 'image', 'video', 'hidden'].includes(el.type)) return;

            const required = el.required ? '<span class="text-danger">*</span>' : '';
            let input = '';

            if (el.type === 'select' && el.options) {
                const options = el.options.map(opt => `<option value="${escapeHtml(opt)}">${escapeHtml(opt)}</option>`).join('');
                input = `<select class="form-select form-select-sm api-test-input" data-param="${escapeHtml(el.id)}">
                    <option value="">Select...</option>${options}</select>`;
            } else if (el.type === 'checkbox' && el.options) {
                input = `<input type="text" class="form-control form-control-sm api-test-input" data-param="${escapeHtml(el.id)}"
                         placeholder="Comma-separated values">`;
            } else if (el.type === 'textarea') {
                input = `<textarea class="form-control form-control-sm api-test-input" data-param="${escapeHtml(el.id)}"
                          rows="2" placeholder="${escapeHtml(el.placeholder || '')}"></textarea>`;
            } else {
                const inputType = el.type === 'email' ? 'email' : (el.type === 'number' ? 'number' : 'text');
                input = `<input type="${inputType}" class="form-control form-control-sm api-test-input" data-param="${escapeHtml(el.id)}"
                         placeholder="${escapeHtml(el.placeholder || '')}">`;
            }

            $container.append(`
                <div class="mb-2">
                    <label class="form-label small text-dark mb-1">${escapeHtml(el.label || el.id)} ${required}</label>
                    ${input}
                </div>
            `);
        });
    }

    function buildApiUrl() {
        let url = '{{ $form->apiUrl ?? "" }}';
        const params = [];

        $('.api-test-input').each(function() {
            const param = $(this).data('param');
            const value = $(this).val();
            if (value) {
                params.push(encodeURIComponent(param) + '=' + encodeURIComponent(value));
            }
        });

        if (params.length > 0) {
            url += '?' + params.join('&');
        }

        return url;
    }
    @endif
});
</script>
@endsection
