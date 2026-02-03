@extends('layouts.master')

@section('title') Form Triggers @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
.trigger-card {
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}
.trigger-card:hover {
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}
.trigger-status-badge {
    font-size: 0.75rem;
}
.trigger-stats {
    font-size: 0.8125rem;
    color: #74788d;
}
.empty-triggers {
    padding: 4rem 2rem;
    text-align: center;
}
.empty-triggers i {
    font-size: 4rem;
    color: #ced4da;
}
.action-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}
.action-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    color: #495057;
}
.action-badge i {
    margin-right: 0.25rem;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') CRM @endslot
    @slot('li_2') <a href="{{ route('crm-forms') }}">Forms</a> @endslot
    @slot('title') Triggers: {{ $form->formName }} @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-1">
                        <i class="bx bx-git-branch me-2"></i>{{ $form->formName }}
                    </h4>
                    <p class="text-secondary mb-0 small">Automation triggers for this form</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('crm-forms.edit', ['id' => $form->id]) }}" class="btn btn-soft-primary">
                        <i class="bx bx-edit me-1"></i>Edit Form
                    </a>
                    <a href="{{ route('crm-forms.submissions', ['id' => $form->id]) }}" class="btn btn-soft-info">
                        <i class="bx bx-list-check me-1"></i>Submissions
                    </a>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#triggerModal" onclick="openCreateModal()">
                        <i class="bx bx-plus me-1"></i>Add Trigger
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if($triggers->count() > 0)
                <div class="row">
                    @foreach($triggers as $trigger)
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card trigger-card h-100 mb-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title text-dark mb-0">{{ $trigger->triggerName }}</h6>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input trigger-toggle" type="checkbox" data-id="{{ $trigger->id }}" {{ $trigger->triggerStatus === 'active' ? 'checked' : '' }}>
                                    </div>
                                </div>

                                @if($trigger->triggerDescription)
                                <p class="text-secondary small mb-2">{{ Str::limit($trigger->triggerDescription, 80) }}</p>
                                @endif

                                <div class="mb-2">
                                    <span class="badge bg-soft-info text-info">
                                        <i class="bx bx-play-circle me-1"></i>{{ $trigger->triggerEvent === 'on_submit' ? 'On Submit' : 'On Status Change' }}
                                    </span>
                                </div>

                                @if(!empty($trigger->triggerFlow))
                                <div class="action-preview">
                                    @foreach($trigger->triggerFlow as $step)
                                    @php
                                        $actions = \App\Models\CrmFormTrigger::getAvailableActions();
                                        $action = $actions[$step['type']] ?? null;
                                    @endphp
                                    @if($action)
                                    <span class="action-badge">
                                        <i class="bx {{ $action['icon'] }}" style="color: {{ $action['color'] }}"></i>
                                        {{ $action['name'] }}
                                    </span>
                                    @endif
                                    @endforeach
                                </div>
                                @else
                                <p class="text-secondary small mb-0 fst-italic">No actions configured</p>
                                @endif

                                <hr class="my-3">

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="trigger-stats">
                                        <i class="bx bx-check-circle me-1"></i>{{ $trigger->executionCount }} runs
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button type="button" class="btn btn-soft-primary btn-sm edit-trigger" data-trigger="{{ json_encode($trigger) }}">
                                            <i class="bx bx-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-soft-danger btn-sm delete-trigger" data-id="{{ $trigger->id }}" data-name="{{ $trigger->triggerName }}">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="empty-triggers">
                    <i class="bx bx-git-branch d-block mb-3"></i>
                    <h5 class="text-dark">No triggers yet</h5>
                    <p class="text-secondary mb-4">Create triggers to automate actions when forms are submitted.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#triggerModal" onclick="openCreateModal()">
                        <i class="bx bx-plus me-1"></i>Create Your First Trigger
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Trigger Modal -->
<div class="modal fade" id="triggerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="triggerModalTitle"><i class="bx bx-git-branch me-2"></i>Create Trigger</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="triggerId">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-dark">Trigger Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="triggerName" placeholder="e.g., Send notification email">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-dark">Event <span class="text-danger">*</span></label>
                            <select class="form-select" id="triggerEvent">
                                <option value="on_submit">On Form Submit</option>
                                <option value="on_status_change">On Status Change</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control" id="triggerDescription" rows="2" placeholder="Optional description..."></textarea>
                </div>

                <hr>

                <h6 class="text-dark mb-3"><i class="bx bx-list-ul me-2"></i>Actions</h6>
                <p class="text-secondary small mb-3">Add actions that will be executed when this trigger fires.</p>

                <div id="actionsList" class="mb-3">
                    <!-- Actions will be added here -->
                </div>

                <div class="dropdown">
                    <button class="btn btn-soft-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bx bx-plus me-1"></i>Add Action
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item add-action" href="#" data-type="send_email"><i class="bx bx-envelope me-2 text-primary"></i>Send Email</a></li>
                        <li><a class="dropdown-item add-action" href="#" data-type="notify_admin"><i class="bx bx-bell me-2 text-danger"></i>Notify Admin</a></li>
                        <li><a class="dropdown-item add-action" href="#" data-type="webhook"><i class="bx bx-link-external me-2 text-info"></i>Webhook</a></li>
                        <li><a class="dropdown-item add-action" href="#" data-type="create_lead"><i class="bx bx-user-plus me-2 text-success"></i>Create Lead</a></li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <div class="form-check form-switch me-auto">
                    <input class="form-check-input" type="checkbox" id="triggerStatus" checked>
                    <label class="form-check-label text-dark" for="triggerStatus">Active</label>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveTriggerBtn">
                    <i class="bx bx-save me-1"></i>Save Trigger
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Delete Trigger</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete <strong id="deleteTriggerName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
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

    const formId = {{ $form->id }};
    let currentActions = [];
    let actionIdCounter = 0;

    // Form fields for variable insertion
    const formFields = @json($form->formElements ?? []);
    const fieldOptions = formFields
        .filter(f => f.label && !['heading', 'paragraph', 'divider'].includes(f.type))
        .map(f => ({ id: f.id, label: f.label }));

    // Open create modal
    window.openCreateModal = function() {
        $('#triggerId').val('');
        $('#triggerModalTitle').html('<i class="bx bx-git-branch me-2"></i>Create Trigger');
        $('#triggerName').val('');
        $('#triggerDescription').val('');
        $('#triggerEvent').val('on_submit');
        $('#triggerStatus').prop('checked', true);
        currentActions = [];
        renderActions();
    };

    // Edit trigger
    $('.edit-trigger').on('click', function() {
        const trigger = $(this).data('trigger');
        $('#triggerId').val(trigger.id);
        $('#triggerModalTitle').html('<i class="bx bx-git-branch me-2"></i>Edit Trigger');
        $('#triggerName').val(trigger.triggerName);
        $('#triggerDescription').val(trigger.triggerDescription || '');
        $('#triggerEvent').val(trigger.triggerEvent);
        $('#triggerStatus').prop('checked', trigger.triggerStatus === 'active');
        currentActions = trigger.triggerFlow || [];
        renderActions();
        $('#triggerModal').modal('show');
    });

    // Add action
    $('.add-action').on('click', function(e) {
        e.preventDefault();
        const type = $(this).data('type');
        const action = {
            id: 'action_' + (++actionIdCounter),
            type: type,
            config: getDefaultConfig(type)
        };
        currentActions.push(action);
        renderActions();
    });

    // Get default config for action type
    function getDefaultConfig(type) {
        switch (type) {
            case 'send_email':
                return { to: '', subject: 'New Form Submission', body: '' };
            case 'notify_admin':
                return { message: 'New submission received' };
            case 'webhook':
                return { url: '', method: 'POST' };
            case 'create_lead':
                return { source: 'form', status: 'new' };
            default:
                return {};
        }
    }

    // Render actions list
    function renderActions() {
        const container = $('#actionsList');
        container.empty();

        if (currentActions.length === 0) {
            container.html('<p class="text-secondary fst-italic">No actions added yet</p>');
            return;
        }

        currentActions.forEach((action, index) => {
            container.append(renderActionCard(action, index));
        });

        bindActionEvents();
    }

    // Render single action card
    function renderActionCard(action, index) {
        let configHtml = '';

        switch (action.type) {
            case 'send_email':
                configHtml = `
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small text-dark">To Email</label>
                            <input type="email" class="form-control form-control-sm action-config" data-key="to" value="${escapeHtml(action.config.to || '')}" placeholder="recipient@email.com or @{{field_id}}">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small text-dark">Subject</label>
                            <input type="text" class="form-control form-control-sm action-config" data-key="subject" value="${escapeHtml(action.config.subject || '')}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-dark">Body</label>
                            <textarea class="form-control form-control-sm action-config" data-key="body" rows="3" placeholder="Use @{{field_id}} to insert field values">${escapeHtml(action.config.body || '')}</textarea>
                        </div>
                    </div>
                `;
                break;

            case 'notify_admin':
                configHtml = `
                    <div class="mb-2">
                        <label class="form-label small text-dark">Message</label>
                        <textarea class="form-control form-control-sm action-config" data-key="message" rows="2">${escapeHtml(action.config.message || '')}</textarea>
                    </div>
                `;
                break;

            case 'webhook':
                configHtml = `
                    <div class="row">
                        <div class="col-md-8 mb-2">
                            <label class="form-label small text-dark">URL</label>
                            <input type="url" class="form-control form-control-sm action-config" data-key="url" value="${escapeHtml(action.config.url || '')}" placeholder="https://...">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label small text-dark">Method</label>
                            <select class="form-select form-select-sm action-config" data-key="method">
                                <option value="POST" ${action.config.method === 'POST' ? 'selected' : ''}>POST</option>
                                <option value="GET" ${action.config.method === 'GET' ? 'selected' : ''}>GET</option>
                            </select>
                        </div>
                    </div>
                `;
                break;

            case 'create_lead':
                configHtml = `
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small text-dark">Lead Source</label>
                            <input type="text" class="form-control form-control-sm action-config" data-key="source" value="${escapeHtml(action.config.source || 'form')}">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small text-dark">Initial Status</label>
                            <select class="form-select form-select-sm action-config" data-key="status">
                                <option value="new" ${action.config.status === 'new' ? 'selected' : ''}>New</option>
                                <option value="contacted" ${action.config.status === 'contacted' ? 'selected' : ''}>Contacted</option>
                                <option value="qualified" ${action.config.status === 'qualified' ? 'selected' : ''}>Qualified</option>
                            </select>
                        </div>
                    </div>
                `;
                break;
        }

        const typeLabels = {
            'send_email': { name: 'Send Email', icon: 'bx-envelope', color: '#556ee6' },
            'notify_admin': { name: 'Notify Admin', icon: 'bx-bell', color: '#e83e8c' },
            'webhook': { name: 'Webhook', icon: 'bx-link-external', color: '#50a5f1' },
            'create_lead': { name: 'Create Lead', icon: 'bx-user-plus', color: '#34c38f' }
        };

        const info = typeLabels[action.type] || { name: action.type, icon: 'bx-cog', color: '#74788d' };

        return `
            <div class="card mb-2" data-action-id="${action.id}">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <div class="d-flex align-items-center">
                        <i class="bx ${info.icon} me-2" style="color: ${info.color}; font-size: 1.25rem;"></i>
                        <span class="fw-medium text-dark">${info.name}</span>
                    </div>
                    <button type="button" class="btn btn-soft-danger btn-sm remove-action" data-id="${action.id}">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
                <div class="card-body py-2">
                    ${configHtml}
                </div>
            </div>
        `;
    }

    // Bind action events
    function bindActionEvents() {
        // Update config on change
        $('.action-config').on('input change', function() {
            const actionId = $(this).closest('[data-action-id]').data('action-id');
            const key = $(this).data('key');
            const value = $(this).val();

            const action = currentActions.find(a => a.id === actionId);
            if (action) {
                action.config[key] = value;
            }
        });

        // Remove action
        $('.remove-action').on('click', function() {
            const actionId = $(this).data('id');
            currentActions = currentActions.filter(a => a.id !== actionId);
            renderActions();
        });
    }

    // Save trigger
    $('#saveTriggerBtn').on('click', function() {
        const triggerName = $('#triggerName').val().trim();
        if (!triggerName) {
            toastr.error('Please enter a trigger name');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        const triggerId = $('#triggerId').val();
        const data = {
            _token: '{{ csrf_token() }}',
            triggerName: triggerName,
            triggerDescription: $('#triggerDescription').val(),
            triggerEvent: $('#triggerEvent').val(),
            triggerStatus: $('#triggerStatus').is(':checked') ? 'active' : 'inactive',
            triggerFlow: currentActions
        };

        const url = triggerId ? '/crm-forms-triggers-update?formId=' + formId + '&triggerId=' + triggerId : '/crm-forms-triggers-store?formId=' + formId;
        const method = triggerId ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $('#triggerModal').modal('hide');
                    toastr.success(response.message);
                    location.reload();
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save trigger');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Trigger');
            }
        });
    });

    // Toggle trigger status
    $('.trigger-toggle').on('change', function() {
        const triggerId = $(this).data('id');
        $.ajax({
            url: '/crm-forms-triggers-toggle?formId=' + formId + '&triggerId=' + triggerId,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success('Trigger status updated');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to update status');
                location.reload();
            }
        });
    });

    // Delete trigger
    let triggerToDelete = null;

    $('.delete-trigger').on('click', function() {
        triggerToDelete = $(this).data('id');
        $('#deleteTriggerName').text($(this).data('name'));
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function() {
        if (!triggerToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: '/crm-forms-triggers-delete?formId=' + formId + '&triggerId=' + triggerToDelete,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    toastr.success('Trigger deleted');
                    location.reload();
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete');
            },
            complete: function() {
                $btn.prop('disabled', false).html('Delete');
                triggerToDelete = null;
            }
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endsection
