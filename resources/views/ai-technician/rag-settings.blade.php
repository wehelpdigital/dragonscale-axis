@extends('layouts.master')

@section('title') RAG Settings @endsection

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
    .file-list-item {
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 12px 15px;
        margin-bottom: 10px;
        background-color: #fff;
        transition: all 0.2s ease;
    }
    .file-list-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .file-icon {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .file-icon.pdf { background-color: rgba(244, 67, 54, 0.1); color: #f44336; }
    .file-icon.txt { background-color: rgba(33, 150, 243, 0.1); color: #2196f3; }
    .file-icon.doc { background-color: rgba(25, 118, 210, 0.1); color: #1976d2; }
    .file-icon.json { background-color: rgba(255, 152, 0, 0.1); color: #ff9800; }
    .file-icon.csv { background-color: rgba(76, 175, 80, 0.1); color: #4caf50; }
    .file-icon.default { background-color: rgba(158, 158, 158, 0.1); color: #9e9e9e; }
    .api-key-input {
        font-family: monospace;
        letter-spacing: 0.5px;
    }
    .connection-status {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    .connection-status.connected {
        background-color: rgba(52, 195, 143, 0.15);
        color: #34c38f;
    }
    .connection-status.disconnected {
        background-color: rgba(244, 106, 106, 0.15);
        color: #f46a6a;
    }
    .nav-tabs-custom .nav-link {
        font-weight: 500;
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
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AI Technician @endslot
        @slot('title') RAG Settings @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card settings-card">
                <div class="card-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#knowledgeBase" role="tab">
                                <i class="bx bx-data me-1"></i>
                                <span class="d-none d-sm-inline">Knowledge Base</span>
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
                        <!-- Knowledge Base Tab -->
                        <div class="tab-pane active" id="knowledgeBase" role="tabpanel">
                            <!-- Upload Section -->
                            <div class="mb-4">
                                <h5 class="text-dark mb-3">
                                    <i class="bx bx-upload me-1"></i> Upload Files
                                </h5>
                                <div class="upload-zone" id="uploadZone">
                                    <i class="bx bx-cloud-upload"></i>
                                    <h5 class="text-dark">Drop files here or click to upload</h5>
                                    <p class="text-secondary">Supported: PDF, TXT, MD, DOC, DOCX, JSON, CSV (Max 50MB)</p>
                                </div>
                                <input type="file" id="fileInput" style="display: none;" accept=".pdf,.txt,.md,.doc,.docx,.json,.csv">

                                <!-- Upload Progress -->
                                <div class="upload-progress mt-3" id="uploadProgress">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bx bx-loader-alt bx-spin me-2 text-primary"></i>
                                        <span class="text-dark" id="uploadFileName">Uploading...</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" id="uploadProgressBar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="bx bx-info-circle me-1"></i>
                                    Files are processed and indexed into Pinecone for RAG queries.
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Files List Section -->
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="text-dark mb-0">
                                        <i class="bx bx-folder-open me-1"></i> Indexed Files
                                    </h5>
                                    <button type="button" class="btn btn-sm btn-soft-primary" id="syncFilesBtn">
                                        <i class="bx bx-refresh me-1"></i> Sync
                                    </button>
                                </div>

                                <div id="filesList">
                                    @if($files->isEmpty())
                                        <div class="empty-state" id="emptyState">
                                            <i class="bx bx-folder-open"></i>
                                            <h5 class="text-dark">No files uploaded yet</h5>
                                            <p class="text-secondary">Upload your first file to build your knowledge base.</p>
                                        </div>
                                    @else
                                        @foreach($files as $file)
                                            <div class="file-list-item" data-file-id="{{ $file->id }}">
                                                <div class="d-flex align-items-center">
                                                    <div class="file-icon {{ strtolower(pathinfo($file->originalName, PATHINFO_EXTENSION)) }}">
                                                        <i class="bx bx-file"></i>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h6 class="mb-1 text-dark text-truncate" style="max-width: 500px;" title="{{ $file->originalName }}">
                                                            {{ $file->originalName }}
                                                        </h6>
                                                        <small class="text-secondary">
                                                            {{ $file->formatted_file_size }} &bull; {{ $file->created_at->format('M d, Y H:i') }}
                                                        </small>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge {{ $file->status_badge_class }} me-2">{{ $file->status_display }}</span>
                                                        @if($file->isProcessing())
                                                            <button type="button" class="btn btn-sm btn-soft-info refresh-file-btn me-1" data-file-id="{{ $file->id }}" title="Refresh Status">
                                                                <i class="bx bx-sync"></i>
                                                            </button>
                                                        @endif
                                                        @if($file->canRetry())
                                                            <button type="button" class="btn btn-sm btn-soft-warning retry-file-btn me-1" data-file-id="{{ $file->id }}" title="Retry">
                                                                <i class="bx bx-refresh"></i>
                                                            </button>
                                                        @endif
                                                        <button type="button" class="btn btn-sm btn-soft-danger delete-file-btn" data-file-id="{{ $file->id }}" data-file-name="{{ $file->originalName }}" title="Delete">
                                                            <i class="bx bx-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                @if($file->status === 'failed' && $file->errorMessage)
                                                    <div class="mt-2">
                                                        <small class="text-danger"><i class="bx bx-error-circle me-1"></i>{{ $file->errorMessage }}</small>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Settings Tab -->
                        <div class="tab-pane" id="settings" role="tabpanel">
                            <div class="row justify-content-center">
                                <div class="col-lg-8">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <h5 class="text-dark mb-1">
                                                <i class="bx bx-key me-1"></i> Pinecone API Settings
                                            </h5>
                                            <p class="text-secondary mb-0">Configure your Pinecone API credentials for RAG integration.</p>
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
                                                   placeholder="e.g., anisenso-dxaxis"
                                                   required>
                                            <small class="text-secondary">The name of your Pinecone Assistant for this knowledge base.</small>
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
                        <i class="bx bx-trash text-danger me-2"></i>Delete File
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to delete <strong id="deleteFileName"></strong>?</p>
                    <p class="text-secondary mb-0">This will also remove it from the Pinecone knowledge base.</p>
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
    // CSRF Token
    const csrfToken = '{{ csrf_token() }}';

    // File to delete
    let fileToDelete = null;

    // Toggle API Key visibility
    $('#toggleApiKey').on('click', function() {
        const input = $('#apiKey');
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bx-show').addClass('bx-hide');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bx-hide').addClass('bx-show');
        }
    });

    // Save Settings
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#saveSettingsBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.ajax({
            url: '{{ route("ai-technician.kb-docs-settings.store") }}',
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
                    toastr.error(response.message || 'Failed to save settings', 'Error');
                }
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.keys(errors).forEach(function(key) {
                        toastr.error(errors[key][0], 'Validation Error');
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred', 'Error');
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
            url: '{{ route("ai-technician.kb-docs-settings.test") }}',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Connection Successful');

                    if (response.data?.assistants) {
                        console.log('Found assistants:', response.data.assistants);
                    }
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

    // Upload Zone interactions
    const uploadZone = $('#uploadZone');
    const fileInput = $('#fileInput');

    uploadZone.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
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
            uploadFile(files[0]);
        }
    });

    fileInput.on('change', function() {
        if (this.files.length > 0) {
            uploadFile(this.files[0]);
        }
    });

    // Upload File function
    function uploadFile(file) {
        const allowedTypes = ['application/pdf', 'text/plain', 'text/markdown', 'application/msword',
                             'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                             'application/json', 'text/csv'];
        const allowedExtensions = ['pdf', 'txt', 'md', 'doc', 'docx', 'json', 'csv'];
        const extension = file.name.split('.').pop().toLowerCase();

        if (!allowedExtensions.includes(extension)) {
            toastr.error('Unsupported file type. Please upload PDF, TXT, MD, DOC, DOCX, JSON, or CSV files.', 'Error');
            return;
        }

        if (file.size > 50 * 1024 * 1024) {
            toastr.error('File size must be less than 50MB.', 'Error');
            return;
        }

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('file', file);

        // Show progress
        $('#uploadProgress').addClass('active');
        $('#uploadFileName').text('Uploading ' + file.name + '...');
        $('#uploadProgressBar').css('width', '0%');

        $.ajax({
            url: '{{ route("ai-technician.kb-docs-settings.upload") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        $('#uploadProgressBar').css('width', percent + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    // Refresh file list
                    location.reload();
                } else {
                    toastr.error(response.message || 'Upload failed', 'Error');
                }
            },
            error: function(xhr) {
                console.log('Upload error:', xhr.status, xhr.responseJSON);
                const response = xhr.responseJSON;
                if (xhr.status === 409 && response?.isDuplicate) {
                    // Duplicate file detected
                    toastr.warning(response.message, 'Duplicate File', {
                        timeOut: 6000,
                        extendedTimeOut: 2000
                    });
                } else {
                    const errorMsg = response?.message || 'Upload failed (Status: ' + xhr.status + ')';
                    toastr.error(errorMsg, 'Error', {
                        timeOut: 8000,
                        extendedTimeOut: 3000
                    });
                }
            },
            complete: function() {
                $('#uploadProgress').removeClass('active');
                fileInput.val('');
            }
        });
    }

    // Delete file
    $(document).on('click', '.delete-file-btn', function() {
        fileToDelete = {
            id: $(this).data('file-id'),
            name: $(this).data('file-name')
        };
        $('#deleteFileName').text(fileToDelete.name);
        $('#deleteModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        if (!fileToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

        $.ajax({
            url: '{{ url("ai-technician-rag-settings/files") }}/' + fileToDelete.id,
            type: 'DELETE',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    $('#deleteModal').modal('hide');
                    $('[data-file-id="' + fileToDelete.id + '"]').fadeOut(400, function() {
                        $(this).remove();
                        // Check if no files left
                        if ($('.file-list-item').length === 0) {
                            $('#filesList').html(`
                                <div class="empty-state" id="emptyState">
                                    <i class="bx bx-folder-open"></i>
                                    <h5 class="text-dark">No files uploaded yet</h5>
                                    <p class="text-secondary">Upload your first file to build your knowledge base.</p>
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
                fileToDelete = null;
            }
        });
    });

    // Retry failed file
    $(document).on('click', '.retry-file-btn', function() {
        const fileId = $(this).data('file-id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-rag-settings/files") }}/' + fileId + '/retry',
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

    // Refresh file status from Pinecone
    $(document).on('click', '.refresh-file-btn', function() {
        const fileId = $(this).data('file-id');
        const $btn = $(this);
        const $row = $btn.closest('.file-list-item');

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: '{{ url("ai-technician-rag-settings/files") }}/' + fileId + '/refresh',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    // Update badge
                    const $badge = $row.find('.badge');
                    $badge.removeClass().addClass('badge ' + response.data.statusBadgeClass + ' me-2');
                    $badge.text(response.data.statusDisplay);

                    // If now indexed, remove refresh button and reload
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

    // Sync files
    $('#syncFilesBtn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Syncing...');

        $.ajax({
            url: '{{ route("ai-technician.kb-docs-settings.sync") }}',
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    console.log('Pinecone files:', response.data);
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Sync failed', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-refresh me-1"></i> Sync');
            }
        });
    });

    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };
});
</script>
@endsection
