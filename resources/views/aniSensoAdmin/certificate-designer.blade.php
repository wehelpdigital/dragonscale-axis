@extends('layouts.master')

@section('title') Certificate Designer - {{ $course->courseName }} @endsection

@section('css')
<!-- Toastr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
    /* Designer Layout */
    .designer-container {
        display: flex;
        height: calc(100vh - 180px);
        min-height: 600px;
    }

    /* Left Toolbar */
    .designer-toolbar {
        width: 60px;
        background: #2a3042;
        display: flex;
        flex-direction: column;
        padding: 10px 5px;
        gap: 5px;
    }

    .toolbar-btn {
        width: 50px;
        height: 50px;
        border: none;
        background: transparent;
        color: #a6b0cf;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .toolbar-btn:hover {
        background: #32394e;
        color: #fff;
    }

    .toolbar-btn.active {
        background: #556ee6;
        color: #fff;
    }

    .toolbar-btn i {
        font-size: 20px;
        margin-bottom: 2px;
    }

    .toolbar-divider {
        height: 1px;
        background: #32394e;
        margin: 10px 5px;
    }

    /* Canvas Area */
    .designer-canvas-area {
        flex: 1;
        background: #1a1d29;
        overflow: auto;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .canvas-wrapper {
        background: #fff;
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        position: relative;
    }

    #certificateCanvas {
        display: block;
    }

    /* Right Panel */
    .designer-panel {
        width: 300px;
        background: #fff;
        border-left: 1px solid #e9ecef;
        overflow-y: auto;
    }

    .panel-section {
        padding: 15px;
        border-bottom: 1px solid #e9ecef;
    }

    .panel-section-title {
        font-size: 12px;
        font-weight: 600;
        color: #495057;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .property-row {
        margin-bottom: 10px;
    }

    .property-label {
        font-size: 11px;
        color: #6c757d;
        margin-bottom: 4px;
    }

    /* Font Selector */
    .font-select {
        font-size: 13px;
    }

    /* Color Picker */
    .color-picker-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .color-preview {
        width: 30px;
        height: 30px;
        border-radius: 4px;
        border: 1px solid #ced4da;
        cursor: pointer;
    }

    /* Asset Library */
    .asset-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    .asset-item {
        aspect-ratio: 1;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s;
    }

    .asset-item:hover {
        border-color: #556ee6;
    }

    .asset-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Placeholder buttons */
    .placeholder-btn {
        display: block;
        width: 100%;
        padding: 8px 12px;
        margin-bottom: 8px;
        background: #f8f9fa;
        border: 1px dashed #ced4da;
        border-radius: 6px;
        text-align: left;
        font-size: 12px;
        color: #495057;
        cursor: pointer;
        transition: all 0.2s;
    }

    .placeholder-btn:hover {
        background: #e9ecef;
        border-color: #556ee6;
    }

    .placeholder-btn code {
        color: #556ee6;
        font-size: 11px;
    }

    /* Zoom Controls */
    .zoom-controls {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #2a3042;
        padding: 8px 15px;
        border-radius: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
        z-index: 100;
    }

    .zoom-controls button {
        background: none;
        border: none;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        padding: 5px;
    }

    .zoom-controls button:hover {
        color: #556ee6;
    }

    .zoom-level {
        color: #fff;
        font-size: 13px;
        min-width: 50px;
        text-align: center;
    }

    /* Header Actions */
    .designer-header {
        background: #fff;
        border-bottom: 1px solid #e9ecef;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .designer-title {
        font-size: 16px;
        font-weight: 600;
        color: #495057;
    }

    .designer-actions {
        display: flex;
        gap: 10px;
    }

    /* Status Toggle */
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-indicator.active {
        background: #d4edda;
        color: #155724;
    }

    .status-indicator.inactive {
        background: #f8d7da;
        color: #721c24;
    }
</style>
@endsection

@section('content')
    <!-- Designer Header -->
    <div class="designer-header">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('anisenso-courses') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>Back
            </a>
            <div>
                <span class="designer-title">
                    <i class="bx bx-award text-primary me-2"></i>Certificate Designer
                </span>
                <small class="text-muted d-block">{{ $course->courseName }}</small>
            </div>
        </div>
        <div class="designer-actions">
            <div id="certificateStatus" class="status-indicator {{ $template->isActive ? 'active' : 'inactive' }}">
                <i class="bx {{ $template->isActive ? 'bx-check-circle' : 'bx-x-circle' }}"></i>
                <span>{{ $template->isActive ? 'Active' : 'Inactive' }}</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-info" id="toggleStatusBtn">
                <i class="bx bx-power-off me-1"></i>Toggle Status
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="previewBtn">
                <i class="bx bx-show me-1"></i>Preview
            </button>
            <button type="button" class="btn btn-sm btn-outline-success" id="exportPdfBtn">
                <i class="bx bx-download me-1"></i>Export PDF
            </button>
            <button type="button" class="btn btn-sm btn-primary" id="saveTemplateBtn">
                <i class="bx bx-save me-1"></i>Save Template
            </button>
        </div>
    </div>

    <!-- Designer Container -->
    <div class="designer-container">
        <!-- Left Toolbar -->
        <div class="designer-toolbar">
            <button type="button" class="toolbar-btn" id="addTextBtn" title="Add Text">
                <i class="bx bx-text"></i>
                <span>Text</span>
            </button>
            <button type="button" class="toolbar-btn" id="addHeadingBtn" title="Add Heading">
                <i class="bx bx-heading"></i>
                <span>Heading</span>
            </button>
            <button type="button" class="toolbar-btn" id="addImageBtn" title="Add Image">
                <i class="bx bx-image"></i>
                <span>Image</span>
            </button>
            <button type="button" class="toolbar-btn" id="addShapeBtn" title="Add Shape">
                <i class="bx bx-shape-square"></i>
                <span>Shape</span>
            </button>
            <button type="button" class="toolbar-btn" id="addLineBtn" title="Add Line">
                <i class="bx bx-minus"></i>
                <span>Line</span>
            </button>

            <div class="toolbar-divider"></div>

            <button type="button" class="toolbar-btn" id="bringForwardBtn" title="Bring Forward">
                <i class="bx bx-chevrons-up"></i>
                <span>Forward</span>
            </button>
            <button type="button" class="toolbar-btn" id="sendBackwardBtn" title="Send Backward">
                <i class="bx bx-chevrons-down"></i>
                <span>Back</span>
            </button>

            <div class="toolbar-divider"></div>

            <button type="button" class="toolbar-btn" id="deleteObjectBtn" title="Delete">
                <i class="bx bx-trash"></i>
                <span>Delete</span>
            </button>
        </div>

        <!-- Canvas Area -->
        <div class="designer-canvas-area">
            <div class="canvas-wrapper" id="canvasWrapper">
                <canvas id="certificateCanvas"></canvas>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="designer-panel">
            <!-- Template Settings -->
            <div class="panel-section">
                <div class="panel-section-title">Template Settings</div>
                <div class="property-row">
                    <label class="property-label">Certificate Name</label>
                    <input type="text" class="form-control form-control-sm" id="certificateName"
                           value="{{ $template->certificateName }}">
                </div>
                <div class="property-row">
                    <label class="property-label">Paper Size</label>
                    <select class="form-select form-select-sm" id="paperSize">
                        <option value="letter" {{ $template->paperSize == 'letter' ? 'selected' : '' }}>Letter (8.5" x 11")</option>
                        <option value="a4" {{ $template->paperSize == 'a4' ? 'selected' : '' }}>A4</option>
                    </select>
                </div>
                <div class="property-row">
                    <label class="property-label">Orientation</label>
                    <select class="form-select form-select-sm" id="orientation">
                        <option value="landscape" {{ $template->orientation == 'landscape' ? 'selected' : '' }}>Landscape</option>
                        <option value="portrait" {{ $template->orientation == 'portrait' ? 'selected' : '' }}>Portrait</option>
                    </select>
                </div>
                <div class="property-row">
                    <label class="property-label">Background Color</label>
                    <div class="color-picker-wrapper">
                        <input type="color" class="color-preview" id="backgroundColor"
                               value="{{ $template->backgroundColor }}">
                        <input type="text" class="form-control form-control-sm" id="backgroundColorText"
                               value="{{ $template->backgroundColor }}" style="width: 100px;">
                    </div>
                </div>
                <div class="property-row">
                    <label class="property-label">Background Image</label>
                    <input type="file" class="form-control form-control-sm" id="backgroundImageInput"
                           accept="image/*">
                    @if($template->backgroundImage)
                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="removeBackgroundBtn">
                        <i class="bx bx-x me-1"></i>Remove Background
                    </button>
                    @endif
                </div>
            </div>

            <!-- Object Properties (shown when object selected) -->
            <div class="panel-section" id="objectPropertiesPanel" style="display: none;">
                <div class="panel-section-title">Object Properties</div>

                <!-- Text Properties -->
                <div id="textProperties">
                    <div class="property-row">
                        <label class="property-label">Font Family</label>
                        <select class="form-select form-select-sm font-select" id="fontFamily">
                            <option value="Arial">Arial</option>
                            <option value="Times New Roman">Times New Roman</option>
                            <option value="Georgia">Georgia</option>
                            <option value="Verdana">Verdana</option>
                            <option value="Courier New">Courier New</option>
                            <option value="Impact">Impact</option>
                            <option value="Comic Sans MS">Comic Sans MS</option>
                            <option value="Trebuchet MS">Trebuchet MS</option>
                            <option value="Palatino Linotype">Palatino Linotype</option>
                            <option value="Lucida Sans Unicode">Lucida Sans Unicode</option>
                        </select>
                    </div>
                    <div class="property-row">
                        <label class="property-label">Font Size</label>
                        <input type="number" class="form-control form-control-sm" id="fontSize"
                               min="8" max="200" value="24">
                    </div>
                    <div class="property-row">
                        <label class="property-label">Font Style</label>
                        <div class="btn-group btn-group-sm w-100">
                            <button type="button" class="btn btn-outline-secondary" id="boldBtn">
                                <i class="bx bx-bold"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="italicBtn">
                                <i class="bx bx-italic"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="underlineBtn">
                                <i class="bx bx-underline"></i>
                            </button>
                        </div>
                    </div>
                    <div class="property-row">
                        <label class="property-label">Text Align</label>
                        <div class="btn-group btn-group-sm w-100">
                            <button type="button" class="btn btn-outline-secondary" id="alignLeftBtn">
                                <i class="bx bx-align-left"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="alignCenterBtn">
                                <i class="bx bx-align-middle"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="alignRightBtn">
                                <i class="bx bx-align-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="property-row">
                        <label class="property-label">Text Color</label>
                        <div class="color-picker-wrapper">
                            <input type="color" class="color-preview" id="textColor" value="#000000">
                            <input type="text" class="form-control form-control-sm" id="textColorText"
                                   value="#000000" style="width: 100px;">
                        </div>
                    </div>
                </div>

                <!-- Shape Properties -->
                <div id="shapeProperties" style="display: none;">
                    <div class="property-row">
                        <label class="property-label">Fill Color</label>
                        <div class="color-picker-wrapper">
                            <input type="color" class="color-preview" id="fillColor" value="#556ee6">
                            <input type="text" class="form-control form-control-sm" id="fillColorText"
                                   value="#556ee6" style="width: 100px;">
                        </div>
                    </div>
                    <div class="property-row">
                        <label class="property-label">Stroke Color</label>
                        <div class="color-picker-wrapper">
                            <input type="color" class="color-preview" id="strokeColor" value="#000000">
                            <input type="text" class="form-control form-control-sm" id="strokeColorText"
                                   value="#000000" style="width: 100px;">
                        </div>
                    </div>
                    <div class="property-row">
                        <label class="property-label">Stroke Width</label>
                        <input type="number" class="form-control form-control-sm" id="strokeWidth"
                               min="0" max="20" value="1">
                    </div>
                </div>

                <!-- Common Properties -->
                <div class="property-row">
                    <label class="property-label">Opacity</label>
                    <input type="range" class="form-range" id="objectOpacity" min="0" max="1" step="0.1" value="1">
                </div>
            </div>

            <!-- Placeholders -->
            <div class="panel-section">
                <div class="panel-section-title">Dynamic Placeholders</div>
                <p class="text-muted small mb-3">Click to add dynamic text fields</p>
                @foreach($placeholders as $placeholder => $description)
                <button type="button" class="placeholder-btn" data-placeholder="{{ $placeholder }}">
                    <code>{{ $placeholder }}</code>
                    <br><small class="text-muted">{{ $description }}</small>
                </button>
                @endforeach
            </div>

            <!-- Assets Library -->
            <div class="panel-section">
                <div class="panel-section-title">
                    Assets Library
                    <button type="button" class="btn btn-sm btn-outline-primary float-end" id="uploadAssetBtn">
                        <i class="bx bx-upload"></i>
                    </button>
                </div>
                <input type="file" id="assetUploadInput" accept="image/*" style="display: none;">
                <div class="asset-grid" id="assetGrid">
                    @foreach($assets as $asset)
                    <div class="asset-item" data-url="{{ asset($asset->assetPath) }}" title="{{ $asset->assetName }}">
                        <img src="{{ asset($asset->assetPath) }}" alt="{{ $asset->assetName }}">
                    </div>
                    @endforeach
                    @if($assets->isEmpty())
                    <p class="text-muted small col-span-3">No assets uploaded yet</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Zoom Controls -->
    <div class="zoom-controls">
        <button type="button" id="zoomOutBtn"><i class="bx bx-minus"></i></button>
        <span class="zoom-level" id="zoomLevel">100%</span>
        <button type="button" id="zoomInBtn"><i class="bx bx-plus"></i></button>
        <button type="button" id="zoomFitBtn"><i class="bx bx-fullscreen"></i></button>
    </div>

    <!-- Hidden input for image upload -->
    <input type="file" id="imageUploadInput" accept="image/*" style="display: none;">
@endsection

@section('script')
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<!-- Fabric.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.1/fabric.min.js"></script>
<!-- jsPDF for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
const courseId = {{ $course->id }};
const csrfToken = '{{ csrf_token() }}';
let canvas;
let currentZoom = 1;
let canvasDimensions = @json($dimensions);

// Initialize Fabric.js canvas
document.addEventListener('DOMContentLoaded', function() {
    // Configure Toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    initCanvas();
    loadTemplate();
    bindEvents();
});

function initCanvas() {
    canvas = new fabric.Canvas('certificateCanvas', {
        width: canvasDimensions.width,
        height: canvasDimensions.height,
        backgroundColor: '{{ $template->backgroundColor }}',
        preserveObjectStacking: true
    });

    // Set initial background if exists
    @if($template->backgroundImage)
    fabric.Image.fromURL('{{ asset($template->backgroundImage) }}', function(img) {
        canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
            scaleX: canvas.width / img.width,
            scaleY: canvas.height / img.height
        });
    });
    @endif

    // Object selection event
    canvas.on('selection:created', updatePropertiesPanel);
    canvas.on('selection:updated', updatePropertiesPanel);
    canvas.on('selection:cleared', hidePropertiesPanel);
    canvas.on('object:modified', updatePropertiesPanel);
}

function loadTemplate() {
    const templateData = @json($template->templateData);
    if (templateData && templateData.objects && templateData.objects.length > 0) {
        canvas.loadFromJSON(templateData, function() {
            canvas.renderAll();
        });
    }
}

function bindEvents() {
    // Add Text
    $('#addTextBtn').on('click', function() {
        const text = new fabric.IText('Click to edit text', {
            left: canvasDimensions.width / 2 - 100,
            top: canvasDimensions.height / 2,
            fontFamily: 'Arial',
            fontSize: 24,
            fill: '#000000',
            originX: 'center',
            originY: 'center'
        });
        canvas.add(text);
        canvas.setActiveObject(text);
        canvas.renderAll();
    });

    // Add Heading
    $('#addHeadingBtn').on('click', function() {
        const heading = new fabric.IText('CERTIFICATE', {
            left: canvasDimensions.width / 2,
            top: 100,
            fontFamily: 'Times New Roman',
            fontSize: 48,
            fontWeight: 'bold',
            fill: '#333333',
            originX: 'center',
            originY: 'center'
        });
        canvas.add(heading);
        canvas.setActiveObject(heading);
        canvas.renderAll();
    });

    // Add Image
    $('#addImageBtn').on('click', function() {
        $('#imageUploadInput').click();
    });

    $('#imageUploadInput').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                fabric.Image.fromURL(event.target.result, function(img) {
                    img.scaleToWidth(200);
                    img.set({
                        left: canvasDimensions.width / 2,
                        top: canvasDimensions.height / 2,
                        originX: 'center',
                        originY: 'center'
                    });
                    canvas.add(img);
                    canvas.setActiveObject(img);
                    canvas.renderAll();
                });
            };
            reader.readAsDataURL(file);
        }
    });

    // Add Shape (Rectangle)
    $('#addShapeBtn').on('click', function() {
        const rect = new fabric.Rect({
            left: canvasDimensions.width / 2 - 75,
            top: canvasDimensions.height / 2 - 50,
            width: 150,
            height: 100,
            fill: '#556ee6',
            stroke: '#000000',
            strokeWidth: 1,
            rx: 5,
            ry: 5
        });
        canvas.add(rect);
        canvas.setActiveObject(rect);
        canvas.renderAll();
    });

    // Add Line
    $('#addLineBtn').on('click', function() {
        const line = new fabric.Line([100, canvasDimensions.height / 2, canvasDimensions.width - 100, canvasDimensions.height / 2], {
            stroke: '#c9a227',
            strokeWidth: 2
        });
        canvas.add(line);
        canvas.setActiveObject(line);
        canvas.renderAll();
    });

    // Bring Forward / Send Backward
    $('#bringForwardBtn').on('click', function() {
        const obj = canvas.getActiveObject();
        if (obj) {
            canvas.bringForward(obj);
            canvas.renderAll();
        }
    });

    $('#sendBackwardBtn').on('click', function() {
        const obj = canvas.getActiveObject();
        if (obj) {
            canvas.sendBackwards(obj);
            canvas.renderAll();
        }
    });

    // Delete Object
    $('#deleteObjectBtn').on('click', deleteSelectedObject);

    // Keyboard delete
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Delete' || e.key === 'Backspace') {
            if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                deleteSelectedObject();
            }
        }
    });

    // Placeholders
    $('.placeholder-btn').on('click', function() {
        const placeholder = $(this).data('placeholder');
        const text = new fabric.IText(placeholder, {
            left: canvasDimensions.width / 2,
            top: canvasDimensions.height / 2,
            fontFamily: 'Arial',
            fontSize: 28,
            fill: '#000000',
            originX: 'center',
            originY: 'center'
        });
        canvas.add(text);
        canvas.setActiveObject(text);
        canvas.renderAll();
    });

    // Asset click
    $(document).on('click', '.asset-item', function() {
        const url = $(this).data('url');
        fabric.Image.fromURL(url, function(img) {
            img.scaleToWidth(150);
            img.set({
                left: canvasDimensions.width / 2,
                top: canvasDimensions.height / 2,
                originX: 'center',
                originY: 'center'
            });
            canvas.add(img);
            canvas.setActiveObject(img);
            canvas.renderAll();
        }, { crossOrigin: 'anonymous' });
    });

    // Upload Asset
    $('#uploadAssetBtn').on('click', function() {
        $('#assetUploadInput').click();
    });

    $('#assetUploadInput').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('asset', file);
            formData.append('assetType', 'image');
            formData.append('_token', csrfToken);

            $.ajax({
                url: `/anisenso-courses/${courseId}/certificate/assets`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        const asset = response.asset;
                        const html = `<div class="asset-item" data-url="${asset.url}" title="${asset.name}">
                            <img src="${asset.url}" alt="${asset.name}">
                        </div>`;
                        $('#assetGrid').prepend(html);
                        toastr.success('Asset uploaded successfully');
                    }
                },
                error: function() {
                    toastr.error('Failed to upload asset');
                }
            });
        }
    });

    // Text Properties
    $('#fontFamily').on('change', function() {
        const obj = canvas.getActiveObject();
        if (obj && obj.type === 'i-text') {
            obj.set('fontFamily', $(this).val());
            canvas.renderAll();
        }
    });

    $('#fontSize').on('change', function() {
        const obj = canvas.getActiveObject();
        if (obj && obj.type === 'i-text') {
            obj.set('fontSize', parseInt($(this).val()));
            canvas.renderAll();
        }
    });

    $('#boldBtn').on('click', function() {
        const obj = canvas.getActiveObject();
        if (obj && obj.type === 'i-text') {
            const isBold = obj.fontWeight === 'bold';
            obj.set('fontWeight', isBold ? 'normal' : 'bold');
            $(this).toggleClass('active', !isBold);
            canvas.renderAll();
        }
    });

    $('#italicBtn').on('click', function() {
        const obj = canvas.getActiveObject();
        if (obj && obj.type === 'i-text') {
            const isItalic = obj.fontStyle === 'italic';
            obj.set('fontStyle', isItalic ? 'normal' : 'italic');
            $(this).toggleClass('active', !isItalic);
            canvas.renderAll();
        }
    });

    $('#underlineBtn').on('click', function() {
        const obj = canvas.getActiveObject();
        if (obj && obj.type === 'i-text') {
            obj.set('underline', !obj.underline);
            $(this).toggleClass('active', obj.underline);
            canvas.renderAll();
        }
    });

    // Text Alignment
    $('#alignLeftBtn').on('click', function() {
        setTextAlign('left');
    });

    $('#alignCenterBtn').on('click', function() {
        setTextAlign('center');
    });

    $('#alignRightBtn').on('click', function() {
        setTextAlign('right');
    });

    // Color pickers
    $('#textColor, #textColorText').on('change input', function() {
        const color = $(this).val();
        $('#textColor').val(color);
        $('#textColorText').val(color);
        const obj = canvas.getActiveObject();
        if (obj && obj.type === 'i-text') {
            obj.set('fill', color);
            canvas.renderAll();
        }
    });

    $('#fillColor, #fillColorText').on('change input', function() {
        const color = $(this).val();
        $('#fillColor').val(color);
        $('#fillColorText').val(color);
        const obj = canvas.getActiveObject();
        if (obj && (obj.type === 'rect' || obj.type === 'circle')) {
            obj.set('fill', color);
            canvas.renderAll();
        }
    });

    $('#strokeColor, #strokeColorText').on('change input', function() {
        const color = $(this).val();
        $('#strokeColor').val(color);
        $('#strokeColorText').val(color);
        const obj = canvas.getActiveObject();
        if (obj) {
            obj.set('stroke', color);
            canvas.renderAll();
        }
    });

    $('#strokeWidth').on('change', function() {
        const obj = canvas.getActiveObject();
        if (obj) {
            obj.set('strokeWidth', parseInt($(this).val()));
            canvas.renderAll();
        }
    });

    $('#objectOpacity').on('input', function() {
        const obj = canvas.getActiveObject();
        if (obj) {
            obj.set('opacity', parseFloat($(this).val()));
            canvas.renderAll();
        }
    });

    // Background Color
    $('#backgroundColor, #backgroundColorText').on('change input', function() {
        const color = $(this).val();
        $('#backgroundColor').val(color);
        $('#backgroundColorText').val(color);
        canvas.setBackgroundColor(color, canvas.renderAll.bind(canvas));
    });

    // Background Image Upload
    $('#backgroundImageInput').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const formData = new FormData();
            formData.append('background', file);
            formData.append('_token', csrfToken);

            $.ajax({
                url: `/anisenso-courses/${courseId}/certificate/background`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        fabric.Image.fromURL(response.backgroundUrl, function(img) {
                            canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                                scaleX: canvas.width / img.width,
                                scaleY: canvas.height / img.height
                            });
                        });
                        toastr.success('Background uploaded');
                    }
                },
                error: function() {
                    toastr.error('Failed to upload background');
                }
            });
        }
    });

    // Remove Background
    $('#removeBackgroundBtn').on('click', function() {
        $.ajax({
            url: `/anisenso-courses/${courseId}/certificate/background`,
            type: 'DELETE',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    canvas.setBackgroundImage(null, canvas.renderAll.bind(canvas));
                    $('#removeBackgroundBtn').hide();
                    toastr.success('Background removed');
                }
            }
        });
    });

    // Paper size / Orientation change
    $('#paperSize, #orientation').on('change', updateCanvasSize);

    // Zoom Controls
    $('#zoomInBtn').on('click', function() {
        setZoom(currentZoom + 0.1);
    });

    $('#zoomOutBtn').on('click', function() {
        setZoom(currentZoom - 0.1);
    });

    $('#zoomFitBtn').on('click', function() {
        setZoom(1);
    });

    // Save Template
    $('#saveTemplateBtn').on('click', saveTemplate);

    // Export PDF
    $('#exportPdfBtn').on('click', exportToPdf);

    // Preview
    $('#previewBtn').on('click', function() {
        // Open canvas as image in new tab
        const dataUrl = canvas.toDataURL({
            format: 'png',
            quality: 1,
            multiplier: 2
        });
        const win = window.open();
        win.document.write('<img src="' + dataUrl + '" style="max-width: 100%;"/>');
    });

    // Toggle Status
    $('#toggleStatusBtn').on('click', function() {
        $.ajax({
            url: `/anisenso-courses/${courseId}/certificate/toggle-status`,
            type: 'PUT',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    const $indicator = $('#certificateStatus');
                    if (response.isActive) {
                        $indicator.removeClass('inactive').addClass('active');
                        $indicator.html('<i class="bx bx-check-circle"></i><span>Active</span>');
                    } else {
                        $indicator.removeClass('active').addClass('inactive');
                        $indicator.html('<i class="bx bx-x-circle"></i><span>Inactive</span>');
                    }
                    toastr.success(response.message);
                }
            },
            error: function() {
                toastr.error('Failed to toggle status');
            }
        });
    });
}

function deleteSelectedObject() {
    const obj = canvas.getActiveObject();
    if (obj) {
        canvas.remove(obj);
        canvas.discardActiveObject();
        canvas.renderAll();
    }
}

function setTextAlign(align) {
    const obj = canvas.getActiveObject();
    if (obj && obj.type === 'i-text') {
        obj.set('textAlign', align);
        canvas.renderAll();
    }
}

function updatePropertiesPanel() {
    const obj = canvas.getActiveObject();
    if (!obj) {
        hidePropertiesPanel();
        return;
    }

    $('#objectPropertiesPanel').show();

    if (obj.type === 'i-text') {
        $('#textProperties').show();
        $('#shapeProperties').hide();

        $('#fontFamily').val(obj.fontFamily || 'Arial');
        $('#fontSize').val(obj.fontSize || 24);
        $('#textColor').val(obj.fill || '#000000');
        $('#textColorText').val(obj.fill || '#000000');
        $('#boldBtn').toggleClass('active', obj.fontWeight === 'bold');
        $('#italicBtn').toggleClass('active', obj.fontStyle === 'italic');
        $('#underlineBtn').toggleClass('active', obj.underline === true);
    } else if (obj.type === 'rect' || obj.type === 'circle' || obj.type === 'line') {
        $('#textProperties').hide();
        $('#shapeProperties').show();

        $('#fillColor').val(obj.fill || '#556ee6');
        $('#fillColorText').val(obj.fill || '#556ee6');
        $('#strokeColor').val(obj.stroke || '#000000');
        $('#strokeColorText').val(obj.stroke || '#000000');
        $('#strokeWidth').val(obj.strokeWidth || 1);
    } else {
        $('#textProperties').hide();
        $('#shapeProperties').hide();
    }

    $('#objectOpacity').val(obj.opacity || 1);
}

function hidePropertiesPanel() {
    $('#objectPropertiesPanel').hide();
}

function updateCanvasSize() {
    const paperSize = $('#paperSize').val();
    const orientation = $('#orientation').val();

    // Get new dimensions
    const baseDimensions = {
        'letter': { width: 816, height: 1056 },
        'a4': { width: 794, height: 1123 }
    };

    let dims = baseDimensions[paperSize] || baseDimensions['letter'];

    if (orientation === 'landscape') {
        canvasDimensions = { width: dims.height, height: dims.width };
    } else {
        canvasDimensions = dims;
    }

    canvas.setWidth(canvasDimensions.width);
    canvas.setHeight(canvasDimensions.height);
    canvas.renderAll();
}

function setZoom(zoom) {
    zoom = Math.max(0.25, Math.min(2, zoom));
    currentZoom = zoom;

    $('#canvasWrapper').css('transform', `scale(${zoom})`);
    $('#canvasWrapper').css('transform-origin', 'center center');
    $('#zoomLevel').text(Math.round(zoom * 100) + '%');
}

function saveTemplate() {
    const $btn = $('#saveTemplateBtn');
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

    try {
        const templateData = canvas.toJSON(['selectable', 'hasControls']);
        const jsonString = JSON.stringify(templateData);

        $.ajax({
            url: `/anisenso-courses/${courseId}/certificate`,
            type: 'PUT',
            timeout: 30000, // 30 second timeout
            data: {
                _token: csrfToken,
                certificateName: $('#certificateName').val(),
                paperSize: $('#paperSize').val(),
                orientation: $('#orientation').val(),
                backgroundColor: $('#backgroundColor').val(),
                templateData: jsonString,
                isActive: $('#certificateStatus').hasClass('active') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Certificate template saved successfully!');
                } else {
                    toastr.error(response.message || 'Failed to save template');
                }
            },
            error: function(xhr, status, error) {
                console.error('Save error:', status, error, xhr.responseText);
                let errorMsg = 'Error saving template';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (status === 'timeout') {
                    errorMsg = 'Request timed out. Template data may be too large.';
                } else if (xhr.status === 422) {
                    errorMsg = 'Validation error. Please check your input.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Server error. Please try again.';
                }
                toastr.error(errorMsg);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Template');
            }
        });
    } catch (e) {
        console.error('Save template error:', e);
        toastr.error('Error preparing template data: ' + e.message);
        $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Template');
    }
}

function exportToPdf() {
    const $btn = $('#exportPdfBtn');
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Exporting...');

    try {
        const { jsPDF } = window.jspdf;

        // Determine orientation and size
        const orientation = $('#orientation').val();
        const paperSize = $('#paperSize').val() === 'a4' ? 'a4' : 'letter';

        const pdf = new jsPDF({
            orientation: orientation,
            unit: 'in',
            format: paperSize
        });

        // Get canvas as high-resolution image
        const dataUrl = canvas.toDataURL({
            format: 'png',
            quality: 1,
            multiplier: 3 // Higher quality for PDF
        });

        // Calculate dimensions
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();

        // Add image to PDF
        pdf.addImage(dataUrl, 'PNG', 0, 0, pageWidth, pageHeight);

        // Save PDF
        pdf.save('certificate_' + courseId + '.pdf');

        toastr.success('PDF exported successfully!');
    } catch (error) {
        console.error('PDF export error:', error);
        toastr.error('Failed to export PDF');
    }

    $btn.prop('disabled', false).html('<i class="bx bx-download me-1"></i>Export PDF');
}
</script>
@endsection
