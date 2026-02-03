@extends('layouts.master')

@section('title') Query Rules @endsection

@section('css')
<link rel="stylesheet" href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}">
<style>
    .rule-card {
        border-left: 4px solid #556ee6;
        transition: all 0.2s ease;
    }
    .rule-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
    .rule-card.disabled {
        border-left-color: #74788d;
        opacity: 0.7;
    }
    .rule-card.system-rule {
        border-left-color: #34c38f;
    }
    .rule-actions {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .rule-card:hover .rule-actions {
        opacity: 1;
    }
    .priority-badge {
        width: 32px;
        height: 32px;
        min-width: 32px;
        min-height: 32px;
        max-width: 32px;
        max-height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #556ee6;
        color: white;
        font-size: 0.8rem;
        font-weight: 600;
        flex-shrink: 0;
        aspect-ratio: 1;
        box-sizing: border-box;
    }
    .category-badge {
        font-size: 0.75rem;
        padding: 3px 8px;
    }
    .rule-prompt-preview {
        background-color: #f8f9fa;
        border-radius: 4px;
        padding: 10px 12px;
        font-size: 0.85rem;
        color: #495057;
        max-height: 80px;
        overflow: hidden;
        position: relative;
    }
    .rule-prompt-preview::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 30px;
        background: linear-gradient(transparent, #f8f9fa);
    }
    .empty-state {
        padding: 80px 20px;
        text-align: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
        border-radius: 8px;
    }
    .empty-state-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #e8f0fe;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
    }
    .empty-state-icon i {
        font-size: 2.5rem;
        color: #556ee6;
    }
    .stats-card {
        border-radius: 8px;
        padding: 16px 20px;
    }
    .compiled-preview {
        background-color: #1e1e1e;
        color: #d4d4d4;
        border-radius: 8px;
        padding: 16px;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        max-height: 400px;
        overflow-y: auto;
        white-space: pre-wrap;
    }
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') AI Technician @endslot
        @slot('title') Query Rules @endslot
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

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-primary bg-gradient text-white mb-0">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bx bx-list-ul" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ $totalRules }}</h3>
                        <p class="mb-0 small opacity-75">Total Rules</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-success bg-gradient text-white mb-0">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="bx bx-check-circle" style="font-size: 2rem; opacity: 0.7;"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h3 class="mb-0">{{ $enabledRules }}</h3>
                        <p class="mb-0 small opacity-75">Enabled Rules</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card stats-card bg-light mb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1 text-dark">Query Rules</h6>
                        <p class="mb-0 text-secondary small">Define instructions that guide AI responses in chat</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="previewCompiledBtn" title="Preview compiled rules">
                            <i class="bx bx-code-alt me-1"></i>Preview
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" id="resetDefaultsBtn" title="Reset to default rules">
                            <i class="bx bx-reset me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex gap-2 align-items-center">
                    <!-- Category Filter -->
                    <select class="form-select form-select-sm" id="categoryFilter" style="width: auto;">
                        <option value="">All Categories</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ $categoryFilter === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <!-- Enabled Filter -->
                    <select class="form-select form-select-sm" id="enabledFilter" style="width: auto;">
                        <option value="">All Status</option>
                        <option value="1" {{ $enabledFilter === '1' ? 'selected' : '' }}>Enabled Only</option>
                        <option value="0" {{ $enabledFilter === '0' ? 'selected' : '' }}>Disabled Only</option>
                    </select>
                </div>
                <a href="{{ route('ai-technician.query-rules.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> Add Rule
                </a>
            </div>
        </div>
    </div>

    <!-- Rules List -->
    <div class="row">
        <div class="col-12">
            @if($rules->isEmpty())
                <div class="card">
                    <div class="card-body empty-state">
                        <div class="empty-state-icon">
                            <i class="bx bx-list-check"></i>
                        </div>
                        <h5 class="text-dark mb-2">No Query Rules Found</h5>
                        <p class="text-secondary mb-4">Query rules help guide the AI to provide better, more consistent responses.</p>
                        <a href="{{ route('ai-technician.query-rules.create') }}" class="btn btn-primary btn-lg">
                            <i class="bx bx-plus me-1"></i> Create First Rule
                        </a>
                    </div>
                </div>
            @else
                <div id="rulesList">
                    @foreach($rules as $rule)
                        <div class="card rule-card mb-3 {{ !$rule->isEnabled ? 'disabled' : '' }} {{ $rule->isSystemRule ? 'system-rule' : '' }}" data-rule-id="{{ $rule->id }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-start flex-grow-1">
                                        <div class="priority-badge me-3" title="Priority: {{ $rule->priority }}">
                                            {{ $rule->priority }}
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
                                                <h5 class="mb-0 text-dark">{{ $rule->ruleName }}</h5>
                                                <span class="badge category-badge bg-info text-white">{{ $categories[$rule->ruleCategory] ?? $rule->ruleCategory }}</span>
                                                @if($rule->isSystemRule)
                                                    <span class="badge bg-success text-white">System</span>
                                                @endif
                                                @if(!$rule->isEnabled)
                                                    <span class="badge bg-secondary">Disabled</span>
                                                @endif
                                            </div>
                                            @if($rule->ruleDescription)
                                                <p class="text-secondary mb-2">{{ $rule->ruleDescription }}</p>
                                            @endif
                                            <div class="rule-prompt-preview">{{ Str::limit($rule->rulePrompt, 300) }}</div>
                                        </div>
                                    </div>
                                    <div class="rule-actions ms-3 d-flex align-items-center gap-2">
                                        <!-- Enable/Disable Toggle -->
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input toggle-rule" type="checkbox"
                                                   data-rule-id="{{ $rule->id }}"
                                                   {{ $rule->isEnabled ? 'checked' : '' }}
                                                   title="{{ $rule->isEnabled ? 'Click to disable' : 'Click to enable' }}">
                                        </div>
                                        <div class="btn-group">
                                            <a href="{{ route('ai-technician.query-rules.edit', ['id' => $rule->id]) }}"
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                            @if(!$rule->isSystemRule)
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-rule"
                                                        data-rule-id="{{ $rule->id }}"
                                                        data-rule-name="{{ $rule->ruleName }}" title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Delete Rule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to delete the rule "<strong id="deleteRuleName"></strong>"?</p>
                    <p class="text-secondary small mb-0">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                        <i class="bx bx-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Compiled Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-code-alt text-primary me-2"></i>Compiled Rules Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary small mb-3">This is how the enabled rules will be injected into AI queries:</p>
                    <div class="compiled-preview" id="compiledPreview">Loading...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="copyCompiledBtn">
                        <i class="bx bx-copy me-1"></i> Copy
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div class="modal fade" id="resetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-reset text-warning me-2"></i>Reset to Defaults</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to reset all rules to defaults?</p>
                    <p class="text-secondary small mb-0">This will delete all custom rules and recreate the default system rules.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="btnConfirmReset">
                        <i class="bx bx-reset me-1"></i> Reset
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
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    const previewModalEl = document.getElementById('previewModal');
    const previewModal = previewModalEl ? new bootstrap.Modal(previewModalEl) : null;
    const resetModalEl = document.getElementById('resetModal');
    const resetModal = resetModalEl ? new bootstrap.Modal(resetModalEl) : null;
    let ruleToDelete = null;

    // Filter handlers
    $('#categoryFilter, #enabledFilter').on('change', function() {
        const category = $('#categoryFilter').val();
        const enabled = $('#enabledFilter').val();
        let url = new URL(window.location.href);

        if (category) {
            url.searchParams.set('category', category);
        } else {
            url.searchParams.delete('category');
        }

        if (enabled !== '') {
            url.searchParams.set('enabled', enabled);
        } else {
            url.searchParams.delete('enabled');
        }

        window.location.href = url.toString();
    });

    // Toggle rule status
    $(document).on('change', '.toggle-rule', function() {
        const ruleId = $(this).data('rule-id');
        const checkbox = $(this);
        const card = checkbox.closest('.rule-card');

        $.ajax({
            url: `/ai-technician-query-rules/${ruleId}/toggle`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (response.data.isEnabled) {
                        card.removeClass('disabled');
                        card.find('.badge.bg-secondary').remove();
                    } else {
                        card.addClass('disabled');
                        const badges = card.find('.d-flex.align-items-center.mb-2');
                        if (!badges.find('.badge.bg-secondary').length) {
                            badges.append('<span class="badge bg-secondary">Disabled</span>');
                        }
                    }
                } else {
                    toastr.error(response.message);
                    checkbox.prop('checked', !checkbox.prop('checked'));
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to toggle rule status.');
                checkbox.prop('checked', !checkbox.prop('checked'));
            }
        });
    });

    // Open delete confirmation
    $(document).on('click', '.delete-rule', function() {
        ruleToDelete = {
            id: $(this).data('rule-id'),
            name: $(this).data('rule-name'),
            card: $(this).closest('.rule-card')
        };
        $('#deleteRuleName').text(ruleToDelete.name);
        deleteModal.show();
    });

    // Confirm delete
    $('#btnConfirmDelete').on('click', function() {
        if (!ruleToDelete) return;

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

        $.ajax({
            url: `/ai-technician-query-rules/${ruleToDelete.id}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    deleteModal.hide();
                    toastr.success(response.message);
                    ruleToDelete.card.fadeOut(400, function() {
                        $(this).remove();
                        if ($('#rulesList .rule-card').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete rule.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
                ruleToDelete = null;
            }
        });
    });

    // Preview compiled rules
    $('#previewCompiledBtn').on('click', function() {
        $('#compiledPreview').text('Loading...');
        previewModal.show();

        $.ajax({
            url: '/ai-technician-query-rules/compiled',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    if (response.data.isEmpty) {
                        $('#compiledPreview').text('(No enabled rules to compile)');
                    } else {
                        $('#compiledPreview').text(response.data.compiled);
                    }
                } else {
                    $('#compiledPreview').text('Error loading compiled rules.');
                }
            },
            error: function() {
                $('#compiledPreview').text('Failed to load compiled rules.');
            }
        });
    });

    // Copy compiled rules
    $('#copyCompiledBtn').on('click', function() {
        const text = $('#compiledPreview').text();
        navigator.clipboard.writeText(text).then(function() {
            toastr.success('Copied to clipboard!');
        }).catch(function() {
            toastr.error('Failed to copy.');
        });
    });

    // Reset to defaults
    $('#resetDefaultsBtn').on('click', function() {
        resetModal.show();
    });

    $('#btnConfirmReset').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Resetting...');

        $.ajax({
            url: '/ai-technician-query-rules/reset',
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
                toastr.error(xhr.responseJSON?.message || 'Failed to reset rules.');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bx bx-reset me-1"></i> Reset');
                resetModal.hide();
            }
        });
    });
});
</script>
@endsection
