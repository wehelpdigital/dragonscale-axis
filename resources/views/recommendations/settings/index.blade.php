@extends('layouts.master')

@section('title') Recommendation Settings @endsection

@section('css')
<style>
    .provider-card {
        border-left: 4px solid #6c757d;
        transition: box-shadow 0.2s;
    }
    .provider-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    .provider-card.claude { border-left-color: #D97706; }
    .provider-card.openai { border-left-color: #10A37F; }
    .provider-card.gemini { border-left-color: #4285F4; }

    .provider-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }
    .provider-icon.claude { background: linear-gradient(135deg, #D97706, #F59E0B); }
    .provider-icon.openai { background: linear-gradient(135deg, #10A37F, #34D399); }
    .provider-icon.gemini { background: linear-gradient(135deg, #4285F4, #60A5FA); }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }
    .empty-state i {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }

    .password-toggle {
        cursor: pointer;
    }

    .nav-tabs .nav-link {
        font-weight: 500;
    }
    .nav-tabs .nav-link.active {
        border-bottom: 2px solid #556ee6;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Recommendations @endslot
        @slot('title') Settings @endslot
    @endcomponent

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">
                <i class="bx bx-cog text-primary me-2"></i>Recommendation Settings
            </h4>
            <p class="text-secondary mb-0 mt-1">Configure AI providers and access management for recommendations</p>
        </div>
        <div class="card-body">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="api-tab" data-bs-toggle="tab" data-bs-target="#api-settings"
                            type="button" role="tab" aria-controls="api-settings" aria-selected="true">
                        <i class="bx bx-key me-1"></i>AI API Settings
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tags-tab" data-bs-toggle="tab" data-bs-target="#access-tags"
                            type="button" role="tab" aria-controls="access-tags" aria-selected="false">
                        <i class="bx bx-tag me-1"></i>Access Tags
                        <span class="badge bg-primary ms-1">{{ $accessTags->count() }}</span>
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="settingsTabsContent">
                <!-- Tab 1: AI API Settings -->
                <div class="tab-pane fade show active" id="api-settings" role="tabpanel" aria-labelledby="api-tab">
                    <div class="row">
                        <div class="col-12">
                            <p class="text-secondary mb-4">
                                Configure your AI provider API keys to enable recommendation generation.
                                At least one provider must be configured and enabled.
                            </p>

                            <div class="accordion" id="providersAccordion">
                                @foreach(['claude', 'openai', 'gemini'] as $provider)
                                    @php $setting = $settings[$provider]; @endphp
                                    <div class="accordion-item provider-card {{ $provider }} mb-3 border rounded">
                                        <h2 class="accordion-header" id="heading-{{ $provider }}">
                                            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapse-{{ $provider }}"
                                                    aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                    aria-controls="collapse-{{ $provider }}">
                                                <div class="d-flex align-items-center w-100">
                                                    <div class="provider-icon {{ $provider }} me-3">
                                                        <i class="{{ $providerIcons[$provider] }}"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0 text-dark">{{ $providerLabels[$provider] }}</h6>
                                                        <small class="text-secondary">
                                                            {{ $setting->hasApiKey() ? 'API Key: ' . $setting->masked_api_key : 'Not configured' }}
                                                        </small>
                                                    </div>
                                                    <div class="me-3">
                                                        {!! $setting->status_badge !!}
                                                        @if($setting->isDefault)
                                                            <span class="badge bg-primary ms-1">Default</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse-{{ $provider }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                             aria-labelledby="heading-{{ $provider }}" data-bs-parent="#providersAccordion">
                                            <div class="accordion-body">
                                                <form id="form-{{ $provider }}" class="provider-form" data-provider="{{ $provider }}">
                                                    @csrf
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">API Key</label>
                                                            <div class="input-group">
                                                                <input type="password" class="form-control api-key-input"
                                                                       name="apiKey" placeholder="Enter API key"
                                                                       autocomplete="off">
                                                                <button class="btn btn-outline-secondary password-toggle" type="button">
                                                                    <i class="bx bx-hide"></i>
                                                                </button>
                                                            </div>
                                                            <small class="text-secondary">
                                                                @if($setting->hasApiKey())
                                                                    Current: {{ $setting->masked_api_key }} (leave blank to keep)
                                                                @else
                                                                    No API key configured
                                                                @endif
                                                            </small>
                                                        </div>
                                                        @if($provider === 'openai')
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label">Organization ID (Optional)</label>
                                                                <input type="text" class="form-control" name="organizationId"
                                                                       value="{{ $setting->organizationId }}"
                                                                       placeholder="org-xxxxx">
                                                            </div>
                                                        @endif
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Default Model</label>
                                                            <select class="form-select" name="defaultModel">
                                                                <option value="">Select a model</option>
                                                                @foreach($modelsByProvider[$provider] as $modelId => $modelName)
                                                                    <option value="{{ $modelId }}"
                                                                        {{ $setting->defaultModel === $modelId ? 'selected' : '' }}>
                                                                        {{ $modelName }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Max Tokens</label>
                                                            <input type="number" class="form-control" name="maxTokens"
                                                                   value="{{ $setting->maxTokens ?? 4096 }}" min="1" max="200000">
                                                        </div>
                                                        <div class="col-md-3 mb-3">
                                                            <label class="form-label">Temperature</label>
                                                            <input type="number" class="form-control" name="temperature"
                                                                   value="{{ $setting->temperature ?? 0.7 }}"
                                                                   step="0.1" min="0" max="2">
                                                        </div>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" name="isActive"
                                                                       id="isActive-{{ $provider }}" value="1"
                                                                       {{ $setting->isActive ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="isActive-{{ $provider }}">
                                                                    Enable this provider
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="d-flex justify-content-between">
                                                        <div>
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="bx bx-save me-1"></i>Save Settings
                                                            </button>
                                                            <button type="button" class="btn btn-outline-secondary ms-2 test-connection-btn"
                                                                    data-provider="{{ $provider }}">
                                                                <i class="bx bx-plug me-1"></i>Test Connection
                                                            </button>
                                                        </div>
                                                        @if(!$setting->isDefault)
                                                            <button type="button" class="btn btn-outline-primary set-default-btn"
                                                                    data-provider="{{ $provider }}">
                                                                <i class="bx bx-star me-1"></i>Set as Default
                                                            </button>
                                                        @endif
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Access Tags -->
                <div class="tab-pane fade" id="access-tags" role="tabpanel" aria-labelledby="tags-tab">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="text-dark mb-1">Access Tags</h5>
                            <p class="text-secondary mb-0">Manage access tags for recommendation clients</p>
                        </div>
                        <button class="btn btn-primary" id="addTagBtn">
                            <i class="bx bx-plus me-1"></i>Add New Tag
                        </button>
                    </div>

                    @if($accessTags->isEmpty())
                        <div class="empty-state" id="emptyState">
                            <i class="bx bx-tag"></i>
                            <h5 class="text-dark">No Access Tags Found</h5>
                            <p class="text-secondary">Create your first access tag to manage client access to recommendations.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover" id="tagsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-dark">Tag Name</th>
                                        <th class="text-dark">Expiration Length</th>
                                        <th class="text-dark">Description</th>
                                        <th class="text-dark text-center" style="width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($accessTags as $tag)
                                        <tr data-tag-id="{{ $tag->id }}">
                                            <td class="text-dark">
                                                <i class="bx bx-tag text-primary me-1"></i>
                                                <strong>{{ $tag->tagName }}</strong>
                                            </td>
                                            <td class="text-dark">
                                                <span class="badge bg-info text-white">{{ $tag->expirationLength }} days</span>
                                                <small class="text-secondary ms-1">({{ $tag->expiration_length_human }})</small>
                                            </td>
                                            <td class="text-secondary">{{ $tag->description ?? '-' }}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary edit-tag-btn me-1"
                                                        data-tag-id="{{ $tag->id }}"
                                                        data-tag-name="{{ $tag->tagName }}"
                                                        data-tag-expiration="{{ $tag->expirationLength }}"
                                                        data-tag-description="{{ $tag->description }}">
                                                    <i class="bx bx-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-tag-btn"
                                                        data-tag-id="{{ $tag->id }}"
                                                        data-tag-name="{{ $tag->tagName }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Tag Add/Edit Modal -->
    <div class="modal fade" id="tagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tagModalTitle">
                        <i class="bx bx-tag text-primary me-2"></i>Add New Tag
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="tagForm">
                    <div class="modal-body">
                        <input type="hidden" id="tagId" name="tagId">
                        <div class="mb-3">
                            <label for="tagName" class="form-label">Tag Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tagName" name="tagName"
                                   placeholder="e.g. Premium Access, Monthly Plan" maxlength="255" required>
                        </div>
                        <div class="mb-3">
                            <label for="expirationLength" class="form-label">Expiration Length (Days) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="expirationLength" name="expirationLength"
                                   value="30" min="1" max="3650" required>
                            <small class="text-secondary">How many days until access expires (1-3650 days)</small>
                        </div>
                        <div class="mb-3">
                            <label for="tagDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="tagDescription" name="description"
                                      rows="3" maxlength="1000" placeholder="Optional description..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="saveTagBtn">
                            <i class="bx bx-save me-1"></i>Save Tag
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tag Delete Confirmation Modal -->
    <div class="modal fade" id="deleteTagModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark mb-0">Are you sure you want to delete the tag "<strong id="deleteTagName"></strong>"?</p>
                    <p class="text-secondary small mb-0 mt-2">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteTag">
                        <i class="bx bx-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Tab persistence via URL hash
    $(document).ready(function() {
        let hash = window.location.hash;
        if (hash) {
            $('button[data-bs-target="' + hash + '"]').tab('show');
        }

        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            window.location.hash = $(e.target).data('bs-target');
        });
    });

    // Password toggle
    $(document).on('click', '.password-toggle', function() {
        const input = $(this).siblings('.api-key-input');
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bx-hide').addClass('bx-show');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bx-show').addClass('bx-hide');
        }
    });

    // Save provider settings
    $('.provider-form').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const provider = form.data('provider');
        const $btn = form.find('button[type="submit"]');

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: '/recommendation-settings/' + provider,
            type: 'PUT',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save settings.', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Settings');
            }
        });
    });

    // Test connection
    $('.test-connection-btn').on('click', function() {
        const provider = $(this).data('provider');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Testing...');

        $.ajax({
            url: '/recommendation-settings/' + provider + '/test',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Connection Successful');
                } else {
                    toastr.error(response.message, 'Connection Failed');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Connection test failed.', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-plug me-1"></i>Test Connection');
            }
        });
    });

    // Set default provider
    $('.set-default-btn').on('click', function() {
        const provider = $(this).data('provider');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Setting...');

        $.ajax({
            url: '/recommendation-settings/' + provider + '/default',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success('Default provider set to ' + response.data.label, 'Success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to set default.', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-star me-1"></i>Set as Default');
            }
        });
    });

    // ==================== ACCESS TAGS ====================

    let tagToDelete = null;
    let isEditing = false;

    // Add new tag
    $('#addTagBtn').on('click', function() {
        isEditing = false;
        $('#tagModalTitle').html('<i class="bx bx-tag text-primary me-2"></i>Add New Tag');
        $('#tagForm')[0].reset();
        $('#tagId').val('');
        $('#expirationLength').val(30);
        $('#tagModal').modal('show');
    });

    // Edit tag
    $(document).on('click', '.edit-tag-btn', function() {
        isEditing = true;
        const $btn = $(this);

        $('#tagModalTitle').html('<i class="bx bx-edit text-primary me-2"></i>Edit Tag');
        $('#tagId').val($btn.data('tag-id'));
        $('#tagName').val($btn.data('tag-name'));
        $('#expirationLength').val($btn.data('tag-expiration'));
        $('#tagDescription').val($btn.data('tag-description') || '');
        $('#tagModal').modal('show');
    });

    // Save tag
    $('#tagForm').on('submit', function(e) {
        e.preventDefault();

        const tagId = $('#tagId').val();
        const url = isEditing
            ? '/recommendation-settings/access-tags/' + tagId
            : '/recommendation-settings/access-tags';
        const method = isEditing ? 'PUT' : 'POST';

        const $btn = $('#saveTagBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: url,
            type: method,
            data: {
                _token: '{{ csrf_token() }}',
                tagName: $('#tagName').val(),
                expirationLength: $('#expirationLength').val(),
                description: $('#tagDescription').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#tagModal').modal('hide');
                    toastr.success(response.message, 'Success');
                    // Reload with hash to stay on Access Tags tab
                    window.location.hash = '#access-tags';
                    location.reload();
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save tag.', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Tag');
            }
        });
    });

    // Delete tag button click
    $(document).on('click', '.delete-tag-btn', function() {
        tagToDelete = {
            id: $(this).data('tag-id'),
            name: $(this).data('tag-name')
        };
        $('#deleteTagName').text(tagToDelete.name);
        $('#deleteTagModal').modal('show');
    });

    // Confirm delete tag
    $('#confirmDeleteTag').on('click', function() {
        if (!tagToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: '/recommendation-settings/access-tags/' + tagToDelete.id,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#deleteTagModal').modal('hide');
                    toastr.success('Access tag deleted successfully!', 'Success');

                    // Remove row from table
                    $('tr[data-tag-id="' + tagToDelete.id + '"]').fadeOut(400, function() {
                        $(this).remove();
                        // Check if table is empty
                        if ($('#tagsTable tbody tr').length === 0) {
                            location.reload();
                        }
                    });

                    // Update badge count
                    const currentCount = parseInt($('#tags-tab .badge').text());
                    $('#tags-tab .badge').text(currentCount - 1);
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete tag.', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
                tagToDelete = null;
            }
        });
    });
</script>
@endsection
