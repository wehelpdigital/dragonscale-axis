@extends('layouts.master')

@section('title') KB Images Settings @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .upload-zone {
        border: 2px dashed #556ee6;
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background-color: rgba(85, 110, 230, 0.05);
    }
    .upload-zone:hover {
        border-color: #3c4ccf;
        background-color: rgba(85, 110, 230, 0.1);
    }
    .upload-zone.drag-over {
        border-color: #34c38f;
        background-color: rgba(52, 195, 143, 0.1);
    }
    .upload-zone i {
        font-size: 48px;
        color: #556ee6;
        margin-bottom: 15px;
    }
    .upload-zone h5 {
        color: #495057;
        margin-bottom: 8px;
    }
    .upload-zone p {
        color: #74788d;
        margin-bottom: 0;
    }
    .image-list-item {
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 12px 15px;
        margin-bottom: 10px;
        background-color: #fff;
        transition: all 0.2s ease;
    }
    .image-list-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .image-thumbnail {
        width: 60px;
        height: 60px;
        border-radius: 6px;
        object-fit: cover;
        border: 1px solid #e9ecef;
    }
    .api-key-input {
        font-family: monospace;
        letter-spacing: 0.5px;
    }
    .settings-card {
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .empty-state {
        padding: 60px 20px;
        text-align: center;
    }
    .empty-state i {
        font-size: 64px;
        color: #c3cbe4;
        margin-bottom: 20px;
    }
    .upload-progress {
        display: none;
    }
    .upload-progress.active {
        display: block;
    }
    .description-textarea {
        resize: vertical;
        min-height: 80px;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AI Technician @endslot
        @slot('title') KB Images Settings @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card settings-card">
                <div class="card-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#imagesManagement" role="tab">
                                <i class="bx bx-images me-1"></i>
                                <span class="d-none d-sm-inline">Images Management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#settings" role="tab">
                                <i class="bx bx-cog me-1"></i>
                                <span class="d-none d-sm-inline">Settings</span>
                            </a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content p-3 pt-4">
                        <!-- Tab 1: Images Management -->
                        <div class="tab-pane active" id="imagesManagement" role="tabpanel">
                            <!-- Upload Section - Collapsed by default -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-dark mb-0">
                                        <i class="bx bx-images me-1"></i> Uploaded Images
                                        <span class="badge bg-secondary ms-2">{{ $images->count() }}</span>
                                    </h5>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-soft-primary me-2" id="refreshListBtn">
                                            <i class="bx bx-refresh me-1"></i> Refresh
                                        </button>
                                        <button type="button" class="btn btn-primary" id="toggleUploadBtn">
                                            <i class="bx bx-plus me-1"></i> Upload Image
                                        </button>
                                    </div>
                                </div>

                                <!-- Upload Form - Hidden by default -->
                                <div id="uploadSection" style="display: none;">
                                    <div class="card bg-light border mb-4">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="text-dark mb-0">
                                                    <i class="bx bx-upload me-1"></i> Upload New Image
                                                </h6>
                                                <button type="button" class="btn btn-sm btn-soft-secondary" id="closeUploadBtn">
                                                    <i class="bx bx-x"></i>
                                                </button>
                                            </div>

                                            @if(!$settings->isConfigured())
                                                <div class="alert alert-warning py-2 mb-3">
                                                    <i class="bx bx-info-circle me-1"></i>
                                                    <strong>RAG not configured.</strong> Images will be saved locally only.
                                                    <a href="#settings" data-bs-toggle="tab" class="alert-link">Configure Pinecone</a> for automatic RAG indexing.
                                                </div>
                                            @else
                                                <div class="alert alert-success py-2 mb-3">
                                                    <i class="bx bx-check-circle me-1"></i>
                                                    <strong>RAG configured.</strong> Images will be automatically indexed in Pinecone after upload.
                                                </div>
                                            @endif

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="upload-zone" id="uploadZone">
                                                        <i class="bx bx-image-add"></i>
                                                        <h5 class="text-dark">Drop image here or click to upload</h5>
                                                        <p class="text-secondary">Supported: JPG, PNG, GIF, WebP (Max 10MB)</p>
                                                    </div>
                                                    <input type="file" id="fileInput" style="display: none;" accept=".jpg,.jpeg,.png,.gif,.webp">

                                                    <!-- Image Preview -->
                                                    <div id="imagePreview" class="mt-3" style="display: none;">
                                                        <img id="previewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                                                        <p class="text-dark mt-2 mb-0"><strong id="previewFileName"></strong></p>
                                                        <small class="text-secondary" id="previewFileSize"></small>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label for="imageDescription" class="form-label text-dark">
                                                            Description <span class="text-danger">*</span>
                                                        </label>
                                                        <textarea
                                                            class="form-control description-textarea"
                                                            id="imageDescription"
                                                            rows="4"
                                                            placeholder="Describe what this image shows. This description will be used for RAG context..."
                                                            required></textarea>
                                                        <small class="text-secondary">Minimum 10 characters. Be descriptive for better AI understanding.</small>
                                                    </div>
                                                    <button type="button" class="btn btn-primary" id="uploadBtn" disabled>
                                                        <i class="bx bx-cloud-upload me-1"></i> Upload Image
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Upload Progress with RAG Sync Steps -->
                                            <div class="upload-progress mt-3" id="uploadProgress">
                                                <div class="alert alert-light border mb-0">
                                                    <h6 class="text-dark mb-3" id="uploadStatus">
                                                        <i class="bx bx-loader-alt bx-spin me-2 text-primary"></i>
                                                        Uploading image...
                                                    </h6>
                                                    <div class="progress mb-3" style="height: 6px;">
                                                        <div class="progress-bar" id="uploadProgressBar" role="progressbar" style="width: 0%"></div>
                                                    </div>
                                                    <!-- RAG Sync Steps -->
                                                    <div class="small">
                                                        <div class="d-flex align-items-center mb-2" id="ragStep1">
                                                            <span class="me-2" id="ragStep1Icon"><i class="bx bx-circle text-secondary"></i></span>
                                                            <span class="text-secondary" id="ragStep1Text">Saving image locally</span>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-2" id="ragStep2">
                                                            <span class="me-2" id="ragStep2Icon"><i class="bx bx-circle text-secondary"></i></span>
                                                            <span class="text-secondary" id="ragStep2Text">Analyzing with Vision AI</span>
                                                        </div>
                                                        <div class="d-flex align-items-center mb-2" id="ragStep3">
                                                            <span class="me-2" id="ragStep3Icon"><i class="bx bx-circle text-secondary"></i></span>
                                                            <span class="text-secondary" id="ragStep3Text">Uploading to RAG (Pinecone)</span>
                                                        </div>
                                                        <div class="d-flex align-items-center" id="ragStep4">
                                                            <span class="me-2" id="ragStep4Icon"><i class="bx bx-circle text-secondary"></i></span>
                                                            <span class="text-secondary" id="ragStep4Text">Verifying index status</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Images List -->
                            <div>
                                <div id="imagesList">
                                    @if($images->isEmpty())
                                        <div class="empty-state" id="emptyState">
                                            <i class="bx bx-images"></i>
                                            <h5 class="text-dark">No images uploaded yet</h5>
                                            <p class="text-secondary mb-2">Upload your first image to build your visual knowledge base.</p>
                                            @if(!$settings->isConfigured())
                                                <div class="alert alert-warning d-inline-block mt-2">
                                                    <i class="bx bx-info-circle me-1"></i>
                                                    Configure <a href="#settings" data-bs-toggle="tab" class="alert-link">Pinecone Settings</a> to enable automatic RAG sync.
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        @foreach($images as $image)
                                            <div class="image-list-item" data-image-id="{{ $image->id }}">
                                                <div class="d-flex align-items-start">
                                                    <!-- Thumbnail -->
                                                    <img src="{{ $image->thumbnail_url }}"
                                                         alt="{{ $image->originalName }}"
                                                         class="image-thumbnail me-3">

                                                    <!-- Details -->
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <h6 class="mb-1 text-dark text-truncate" style="max-width: 400px;" title="{{ $image->originalName }}">
                                                                    {{ $image->originalName }}
                                                                </h6>
                                                                <p class="text-secondary mb-1 small">{{ $image->description_short }}</p>
                                                                <small class="text-secondary">
                                                                    {{ $image->file_size_human }} &bull; {{ $image->created_at->format('M d, Y H:i') }}
                                                                </small>
                                                            </div>
                                                            <div class="d-flex align-items-center">
                                                                <span class="badge {{ $image->pinecone_status_badge }} me-2">
                                                                    {{ $image->pinecone_status_display }}
                                                                </span>

                                                                @if($image->isPending())
                                                                    <button type="button" class="btn btn-sm btn-soft-success sync-btn me-1"
                                                                            data-image-id="{{ $image->id }}" title="Sync to Pinecone">
                                                                        <i class="bx bx-cloud-upload"></i>
                                                                    </button>
                                                                @endif

                                                                @if($image->isProcessing())
                                                                    <button type="button" class="btn btn-sm btn-soft-info refresh-status-btn me-1"
                                                                            data-image-id="{{ $image->id }}" title="Refresh Status">
                                                                        <i class="bx bx-sync"></i>
                                                                    </button>
                                                                @endif

                                                                @if($image->canRetry())
                                                                    <button type="button" class="btn btn-sm btn-soft-warning retry-btn me-1"
                                                                            data-image-id="{{ $image->id }}" title="Retry">
                                                                        <i class="bx bx-refresh"></i>
                                                                    </button>
                                                                @endif

                                                                <button type="button" class="btn btn-sm btn-soft-secondary edit-btn me-1"
                                                                        data-image-id="{{ $image->id }}"
                                                                        data-description="{{ $image->description }}"
                                                                        title="Edit Description">
                                                                    <i class="bx bx-edit"></i>
                                                                </button>

                                                                <button type="button" class="btn btn-sm btn-soft-danger delete-btn"
                                                                        data-image-id="{{ $image->id }}"
                                                                        data-image-name="{{ $image->originalName }}"
                                                                        title="Delete">
                                                                    <i class="bx bx-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        @if($image->pineconeStatus === 'failed' && $image->errorMessage)
                                                            <div class="mt-2">
                                                                <small class="text-danger">
                                                                    <i class="bx bx-error-circle me-1"></i>{{ $image->errorMessage }}
                                                                </small>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                        <!-- End Images Management Tab -->

                        <!-- Settings Tab -->
                        <div class="tab-pane" id="settings" role="tabpanel">
                            <div class="row justify-content-center">
                                <div class="col-lg-8">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <h5 class="text-dark mb-1">
                                                <i class="bx bx-key me-1"></i> Pinecone API Settings
                                            </h5>
                                            <p class="text-secondary mb-0">Configure your Pinecone API credentials for image indexing.</p>
                                        </div>
                                        <button type="button" class="btn btn-soft-info" id="testConnectionBtn">
                                            <i class="bx bx-link me-1"></i> Test Connection
                                        </button>
                                    </div>

                                    <form id="settingsForm">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="apiKey" class="form-label text-dark">API Key <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="password"
                                                       class="form-control api-key-input"
                                                       id="apiKey"
                                                       name="apiKey"
                                                       value="{{ $settings->apiKey }}"
                                                       placeholder="pcsk_xxxxxxxx..."
                                                       required>
                                                <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                                    <i class="bx bx-show"></i>
                                                </button>
                                            </div>
                                            <small class="text-secondary">Your Pinecone API key from the Pinecone console.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="indexName" class="form-label text-dark">Assistant/Index Name <span class="text-danger">*</span></label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="indexName"
                                                   name="indexName"
                                                   value="{{ $settings->indexName }}"
                                                   placeholder="e.g., kb-images"
                                                   required>
                                            <small class="text-secondary">The name of your Pinecone Assistant for image content.</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="indexHost" class="form-label text-dark">Index Host <span class="text-muted">(Optional)</span></label>
                                            <input type="text"
                                                   class="form-control"
                                                   id="indexHost"
                                                   name="indexHost"
                                                   value="{{ $settings->indexHost }}"
                                                   placeholder="e.g., https://your-index-xxxxxxx.svc.environment.pinecone.io">
                                            <small class="text-secondary">The host URL of your Pinecone index (if using direct index access).</small>
                                        </div>

                                        <div class="mb-4">
                                            <label for="email" class="form-label text-dark">Account Email <span class="text-muted">(Optional)</span></label>
                                            <input type="email"
                                                   class="form-control"
                                                   id="email"
                                                   name="email"
                                                   value="{{ $settings->email }}"
                                                   placeholder="e.g., your@email.com">
                                            <small class="text-secondary">Email associated with your Pinecone account for reference.</small>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary" id="saveSettingsBtn">
                                                <i class="bx bx-save me-1"></i> Save Settings
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- End Settings Tab -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">
                        <i class="bx bx-trash text-danger me-2"></i>Delete Image
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to delete <strong id="deleteImageName"></strong>?</p>
                    <p class="text-secondary mb-0">This will also remove it from Pinecone if indexed.</p>
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

    <!-- Edit Description Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">
                        <i class="bx bx-edit text-primary me-2"></i>Edit Description
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editDescription" class="form-label text-dark">Description</label>
                        <textarea class="form-control description-textarea" id="editDescription" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmEditBtn">
                        <i class="bx bx-save me-1"></i> Save
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
    const csrfToken = '{{ csrf_token() }}';
    let selectedFile = null;
    let imageToDelete = null;
    let imageToEdit = null;

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // ==================== UPLOAD SECTION TOGGLE ====================

    $('#toggleUploadBtn').on('click', function() {
        $('#uploadSection').slideDown(300);
        $(this).hide();
    });

    $('#closeUploadBtn').on('click', function() {
        $('#uploadSection').slideUp(300);
        $('#toggleUploadBtn').show();
        // Reset the form
        $('#fileInput').val('');
        selectedFile = null;
        $('#imagePreview').hide();
        $('#imageDescription').val('');
        $('#uploadBtn').prop('disabled', true);
    });

    // ==================== UPLOAD ZONE ====================

    const uploadZone = $('#uploadZone');
    const fileInput = $('#fileInput');

    uploadZone.on('click', function(e) {
        e.preventDefault();
        fileInput[0].click();
    });

    uploadZone.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    });

    uploadZone.on('dragleave', function() {
        $(this).removeClass('drag-over');
    });

    uploadZone.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });

    fileInput.on('change', function() {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });

    function handleFileSelect(file) {
        const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        const extension = file.name.split('.').pop().toLowerCase();

        if (!allowedExtensions.includes(extension)) {
            toastr.error('Unsupported file type. Please upload JPG, PNG, GIF, or WebP.', 'Error');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            toastr.error('File size must be less than 10MB.', 'Error');
            return;
        }

        selectedFile = file;

        const reader = new FileReader();
        reader.onload = function(e) {
            $('#previewImg').attr('src', e.target.result);
            $('#previewFileName').text(file.name);
            $('#previewFileSize').text(formatFileSize(file.size));
            $('#imagePreview').show();
        };
        reader.readAsDataURL(file);

        updateUploadButton();
    }

    function formatFileSize(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' bytes';
    }

    $('#imageDescription').on('input', updateUploadButton);

    function updateUploadButton() {
        const hasFile = selectedFile !== null;
        const hasDescription = $('#imageDescription').val().trim().length >= 10;
        $('#uploadBtn').prop('disabled', !(hasFile && hasDescription));
    }

    // ==================== UPLOAD ====================

    $('#uploadBtn').on('click', function() {
        if (!selectedFile) {
            toastr.error('Please select an image first.', 'Error');
            return;
        }

        const description = $('#imageDescription').val().trim();
        if (description.length < 10) {
            toastr.error('Description must be at least 10 characters.', 'Error');
            return;
        }

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('image', selectedFile);
        formData.append('description', description);

        // Reset and show progress UI
        $('#uploadProgress').addClass('active');
        $('#uploadProgressBar').css('width', '0%');
        $('#uploadBtn').prop('disabled', true);
        resetRagSteps();

        // Start step 1 - Saving locally
        $('#uploadStatus').html('<i class="bx bx-loader-alt bx-spin me-2 text-primary"></i>Uploading image...');
        setRagStepActive(1);

        $.ajax({
            url: '{{ route("ai-technician.kb-images-settings.upload") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        $('#uploadProgressBar').css('width', (percent * 0.25) + '%'); // 25% for upload phase

                        // When upload completes, show step 2 (Vision AI)
                        if (percent >= 100) {
                            setRagStepComplete(1, 'Image saved locally');
                            setRagStepActive(2);
                            $('#uploadProgressBar').css('width', '40%');
                            $('#uploadStatus').html('<i class="bx bx-loader-alt bx-spin me-2 text-primary"></i>Analyzing with Vision AI...');

                            // Simulate progress for Vision AI + RAG steps
                            setTimeout(function() {
                                $('#uploadProgressBar').css('width', '60%');
                                setRagStepActive(3);
                                $('#uploadStatus').html('<i class="bx bx-loader-alt bx-spin me-2 text-primary"></i>Uploading to RAG...');
                            }, 2000);
                        }
                    }
                });
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    // Check RAG sync status
                    if (response.data?.ragSynced) {
                        // Step 2: Vision AI (may have been skipped)
                        if (response.data?.hasAiAnalysis) {
                            setRagStepComplete(2, 'AI analyzed (' + (response.data?.aiProvider || 'Vision') + ')');
                        } else {
                            setRagStepSkipped(2, 'Vision AI not configured');
                        }

                        // Step 3: RAG upload complete
                        setRagStepComplete(3, 'Uploaded to RAG');
                        setRagStepActive(4);
                        $('#uploadStatus').html('<i class="bx bx-loader-alt bx-spin me-2 text-primary"></i>Verifying index status...');

                        setTimeout(function() {
                            const status = response.data?.pineconeStatus;
                            if (status === 'indexed') {
                                setRagStepComplete(4, 'Indexed successfully');
                                $('#uploadStatus').html('<i class="bx bx-check-circle me-2 text-success"></i>Image uploaded and indexed!');
                            } else if (status === 'processing') {
                                setRagStepComplete(4, 'Processing in Pinecone');
                                $('#uploadStatus').html('<i class="bx bx-check-circle me-2 text-success"></i>Image uploaded! RAG processing...');
                            } else {
                                setRagStepComplete(4, 'Upload complete');
                                $('#uploadStatus').html('<i class="bx bx-check-circle me-2 text-success"></i>Image uploaded!');
                            }

                            toastr.success(response.message, 'Success');
                            setTimeout(function() { location.reload(); }, 1500);
                        }, 500);
                    } else {
                        // RAG not configured or sync failed
                        setRagStepComplete(1, 'Image saved locally');
                        setRagStepSkipped(2, 'Vision AI skipped');
                        setRagStepSkipped(3, 'RAG not configured');
                        setRagStepSkipped(4, 'Skipped');
                        $('#uploadStatus').html('<i class="bx bx-check-circle me-2 text-warning"></i>Image saved (RAG not configured)');

                        toastr.warning(response.message, 'Partial Success');
                        setTimeout(function() { location.reload(); }, 2000);
                    }
                } else {
                    setRagStepError(1);
                    $('#uploadStatus').html('<i class="bx bx-error-circle me-2 text-danger"></i>Upload failed');
                    toastr.error(response.message || 'Upload failed', 'Error');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                setRagStepError(1);
                $('#uploadStatus').html('<i class="bx bx-error-circle me-2 text-danger"></i>Upload failed');

                if (xhr.status === 409 && response?.isDuplicate) {
                    toastr.warning(response.message, 'Duplicate Image');
                } else {
                    toastr.error(response?.message || 'Upload failed', 'Error');
                }
            },
            complete: function() {
                setTimeout(function() {
                    $('#uploadProgress').removeClass('active');
                    $('#uploadBtn').prop('disabled', false);
                    fileInput.val('');
                    selectedFile = null;
                }, 3000);
            }
        });
    });

    // RAG Steps UI helpers
    function resetRagSteps() {
        for (let i = 1; i <= 4; i++) {
            $(`#ragStep${i}Icon`).html('<i class="bx bx-circle text-secondary"></i>');
            $(`#ragStep${i}Text`).removeClass('text-dark text-success text-danger text-warning').addClass('text-secondary');
        }
        $('#ragStep1Text').text('Saving image locally');
        $('#ragStep2Text').text('Analyzing with Vision AI');
        $('#ragStep3Text').text('Uploading to RAG (Pinecone)');
        $('#ragStep4Text').text('Verifying index status');
    }

    function setRagStepActive(step) {
        $(`#ragStep${step}Icon`).html('<i class="bx bx-loader-alt bx-spin text-primary"></i>');
        $(`#ragStep${step}Text`).removeClass('text-secondary').addClass('text-dark');
    }

    function setRagStepComplete(step, text) {
        $(`#ragStep${step}Icon`).html('<i class="bx bx-check-circle text-success"></i>');
        $(`#ragStep${step}Text`).removeClass('text-secondary text-dark').addClass('text-success').text(text);
    }

    function setRagStepError(step) {
        $(`#ragStep${step}Icon`).html('<i class="bx bx-error-circle text-danger"></i>');
        $(`#ragStep${step}Text`).removeClass('text-secondary text-dark').addClass('text-danger').text('Failed');
    }

    function setRagStepSkipped(step, text) {
        $(`#ragStep${step}Icon`).html('<i class="bx bx-minus-circle text-warning"></i>');
        $(`#ragStep${step}Text`).removeClass('text-secondary text-dark').addClass('text-warning').text(text);
    }

    // ==================== SYNC TO PINECONE ====================

    $(document).on('click', '.sync-btn', function() {
        const imageId = $(this).data('image-id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-kb-images-settings/images") }}/' + imageId + '/upload-to-pinecone',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    location.reload();
                } else {
                    toastr.error(response.message, 'Error');
                    $btn.prop('disabled', false).html('<i class="bx bx-cloud-upload"></i>');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Sync failed', 'Error');
                $btn.prop('disabled', false).html('<i class="bx bx-cloud-upload"></i>');
            }
        });
    });

    // ==================== REFRESH STATUS ====================

    $(document).on('click', '.refresh-status-btn', function() {
        const imageId = $(this).data('image-id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-kb-images-settings/images") }}/' + imageId + '/refresh-status',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    if (response.data.status === 'indexed') {
                        location.reload();
                    }
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Refresh failed', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-sync"></i>');
            }
        });
    });

    // ==================== RETRY ====================

    $(document).on('click', '.retry-btn', function() {
        const imageId = $(this).data('image-id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-kb-images-settings/images") }}/' + imageId + '/retry',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    location.reload();
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Retry failed', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-refresh"></i>');
            }
        });
    });

    // ==================== DELETE ====================

    $(document).on('click', '.delete-btn', function() {
        imageToDelete = {
            id: $(this).data('image-id'),
            name: $(this).data('image-name')
        };
        $('#deleteImageName').text(imageToDelete.name);
        $('#deleteModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        if (!imageToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

        $.ajax({
            url: '{{ url("ai-technician-kb-images-settings/images") }}/' + imageToDelete.id,
            type: 'DELETE',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    $('#deleteModal').modal('hide');
                    $('[data-image-id="' + imageToDelete.id + '"]').fadeOut(400, function() {
                        $(this).remove();
                        if ($('.image-list-item').length === 0) {
                            $('#imagesList').html(`
                                <div class="empty-state">
                                    <i class="bx bx-images"></i>
                                    <h5 class="text-dark">No images uploaded yet</h5>
                                    <p class="text-secondary">Upload your first image to build your visual knowledge base.</p>
                                </div>
                            `);
                        }
                    });
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Delete failed', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
                imageToDelete = null;
            }
        });
    });

    // ==================== EDIT DESCRIPTION ====================

    $(document).on('click', '.edit-btn', function() {
        imageToEdit = {
            id: $(this).data('image-id'),
            description: $(this).data('description')
        };
        $('#editDescription').val(imageToEdit.description);
        $('#editModal').modal('show');
    });

    $('#confirmEditBtn').on('click', function() {
        if (!imageToEdit) return;

        const newDescription = $('#editDescription').val().trim();
        if (newDescription.length < 10) {
            toastr.error('Description must be at least 10 characters.', 'Error');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.ajax({
            url: '{{ url("ai-technician-kb-images-settings/images") }}/' + imageToEdit.id,
            type: 'PUT',
            data: {
                _token: csrfToken,
                description: newDescription
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    $('#editModal').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Update failed', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save');
                imageToEdit = null;
            }
        });
    });

    // ==================== SETTINGS FORM ====================

    // Toggle API Key visibility
    $('#toggleApiKey').on('click', function() {
        const $input = $('#apiKey');
        const $icon = $(this).find('i');

        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $icon.removeClass('bx-show').addClass('bx-hide');
        } else {
            $input.attr('type', 'password');
            $icon.removeClass('bx-hide').addClass('bx-show');
        }
    });

    // Save Settings
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#saveSettingsBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.ajax({
            url: '{{ route("ai-technician.kb-images-settings.settings.save") }}',
            type: 'POST',
            data: {
                _token: csrfToken,
                apiKey: $('#apiKey').val(),
                indexName: $('#indexName').val(),
                indexHost: $('#indexHost').val(),
                email: $('#email').val()
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    const firstError = Object.values(errors)[0];
                    toastr.error(Array.isArray(firstError) ? firstError[0] : firstError, 'Validation Error');
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Failed to save settings', 'Error');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Settings');
            }
        });
    });

    // Test Connection
    $('#testConnectionBtn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Testing...');

        $.ajax({
            url: '{{ route("ai-technician.kb-images-settings.settings.test") }}',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Connection Successful');
                } else {
                    toastr.error(response.message, 'Connection Failed');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Connection test failed', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-link me-1"></i> Test Connection');
            }
        });
    });

    // ==================== REFRESH LIST ====================

    $('#refreshListBtn').on('click', function() {
        location.reload();
    });
});
</script>
@endsection
