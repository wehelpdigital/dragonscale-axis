@extends('layouts.master')

@section('title') Mail Settings @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .template-group-header td {
        background: #f0f2f7 !important;
        font-weight: 600;
        color: #2a3042;
        text-transform: uppercase;
        letter-spacing: .5px;
        font-size: 12px;
    }
    .template-key { font-family: monospace; font-size: 12px; color: #74788d; }
    .tags-hint {
        background: #f8f9fa;
        border: 1px dashed #ced4da;
        border-radius: 4px;
        padding: 8px 12px;
        font-family: monospace;
        font-size: 12px;
        color: #495057;
        word-break: break-all;
    }
    .form-switch .form-check-input { cursor: pointer; }
    #templateBodyHtml { font-family: monospace; font-size: 12px; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('title') Mail Settings @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h4 class="card-title mb-1 text-dark">Mail Settings</h4>
                            <p class="text-secondary mb-0">SMTP accounts and email templates per mail group (AniSystem client emails, Ani-Senso emails).</p>
                        </div>
                    </div>

                    {{-- Tabs --}}
                    <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'smtp' ? 'active' : '' }}" id="smtp-tab"
                                    data-bs-toggle="tab" data-bs-target="#smtp" type="button" role="tab">
                                <i class="bx bx-server me-1"></i> SMTP
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'templates' ? 'active' : '' }}" id="templates-tab"
                                    data-bs-toggle="tab" data-bs-target="#templates" type="button" role="tab">
                                <i class="bx bx-envelope me-1"></i> Email Templates
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-4">
                        {{-- ============ SMTP TAB ============ --}}
                        <div class="tab-pane fade {{ $activeTab === 'smtp' ? 'show active' : '' }}" id="smtp" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="mb-3" style="max-width: 320px;">
                                        <label class="form-label text-dark">Mail Group</label>
                                        <select class="form-select" id="smtpGroupSelect">
                                            @foreach($groups as $g)
                                                <option value="{{ $g }}" @selected($g === $group)>{{ $g }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-secondary">Each group has its own SMTP account. Emails to AniSystem clients use the 'AniSystem' group.</small>
                                    </div>

                                    <form id="smtpForm">
                                        <input type="hidden" name="groupKey" id="smtpGroupKey" value="{{ $group }}">
                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label for="smtpHost" class="form-label text-dark">SMTP Host <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="smtpHost" name="smtpHost"
                                                       value="{{ $smtpSettings->smtpHost ?? '' }}" placeholder="e.g., smtp.gmail.com">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="smtpPort" class="form-label text-dark">Port <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="smtpPort" name="smtpPort"
                                                       value="{{ $smtpSettings->smtpPort ?? 587 }}" placeholder="587">
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="smtpUsername" class="form-label text-dark">Username</label>
                                                <input type="text" class="form-control" id="smtpUsername" name="smtpUsername"
                                                       value="{{ $smtpSettings->smtpUsername ?? '' }}" placeholder="SMTP username">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="smtpPassword" class="form-label text-dark">Password</label>
                                                <input type="password" class="form-control" id="smtpPassword" name="smtpPassword"
                                                       autocomplete="new-password"
                                                       placeholder="{{ ($smtpSettings && $smtpSettings->smtpPassword) ? '•••••••• (leave blank to keep current)' : 'Enter password' }}">
                                                @if($smtpSettings && $smtpSettings->smtpPassword)
                                                    <small class="text-secondary">Leave blank to keep the current password.</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mb-3" style="max-width: 320px;">
                                            <label for="smtpEncryption" class="form-label text-dark">Encryption <span class="text-danger">*</span></label>
                                            <select class="form-select" id="smtpEncryption" name="smtpEncryption">
                                                <option value="tls" @selected(($smtpSettings->smtpEncryption ?? 'tls') === 'tls')>TLS (Recommended)</option>
                                                <option value="ssl" @selected(($smtpSettings->smtpEncryption ?? '') === 'ssl')>SSL</option>
                                                <option value="none" @selected(($smtpSettings->smtpEncryption ?? '') === 'none')>None</option>
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="smtpFromEmail" class="form-label text-dark">From Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="smtpFromEmail" name="smtpFromEmail"
                                                       value="{{ $smtpSettings->smtpFromEmail ?? '' }}" placeholder="no-reply@anisystem.test">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="smtpFromName" class="form-label text-dark">From Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="smtpFromName" name="smtpFromName"
                                                       value="{{ $smtpSettings->smtpFromName ?? $group }}" placeholder="AniSystem">
                                            </div>
                                        </div>

                                        @php
                                            $smtpConfigured = $smtpSettings && $smtpSettings->isConfigured();
                                            $smtpActive = $smtpSettings && $smtpSettings->isActive;
                                        @endphp

                                        <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="smtpIsActive" name="isActive" @checked($smtpActive)>
                                                <label class="form-check-label text-dark" for="smtpIsActive">SMTP Active</label>
                                            </div>
                                            @if($smtpSettings && $smtpSettings->isVerified)
                                                <span class="badge bg-success"><i class="bx bx-check me-1"></i>Verified{{ $smtpSettings->lastTestedAt ? ' — ' . $smtpSettings->lastTestedAt->format('M j, Y g:i A') : '' }}</span>
                                            @elseif($smtpSettings)
                                                <span class="badge bg-warning text-dark"><i class="bx bx-time me-1"></i>Not tested since last change</span>
                                            @endif
                                        </div>

                                        <button type="submit" class="btn btn-primary" id="saveSmtpBtn">
                                            <i class="bx bx-save me-1"></i> Save SMTP Settings
                                        </button>
                                    </form>

                                    <hr class="my-4">

                                    <h5 class="text-dark mb-2"><i class="bx bx-paper-plane me-1"></i>Send Test Email</h5>
                                    <div class="row g-2" style="max-width: 480px;">
                                        <div class="col-8">
                                            <input type="email" class="form-control" id="testEmail"
                                                   value="{{ $smtpSettings->smtpFromEmail ?? '' }}" placeholder="recipient@example.com">
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-primary w-100" id="testSmtpBtn">
                                                <i class="bx bx-send me-1"></i> Send Test
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-secondary d-block mt-1">Sending a successful test marks the SMTP settings verified.</small>
                                </div>
                            </div>
                        </div>

                        {{-- ============ TEMPLATES TAB ============ --}}
                        <div class="tab-pane fade {{ $activeTab === 'templates' ? 'show active' : '' }}" id="templates" role="tabpanel">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <p class="text-secondary mb-0">Templates use <code>@{{tag}}</code> placeholders replaced at send time. Grouped per mail group.</p>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="reloadTemplatesBtn">
                                    <i class="bx bx-refresh me-1"></i> Reload
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="templatesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Template</th>
                                            <th>Key</th>
                                            <th>Subject</th>
                                            <th style="width: 90px;">Active</th>
                                            <th>Updated</th>
                                            <th class="text-end" style="width: 180px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="templatesTbody">
                                        <tr><td colspan="6" class="text-center text-secondary py-4"><i class="bx bx-loader-alt bx-spin me-1"></i>Loading templates...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit template modal --}}
    <div class="modal fade" id="editTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-edit me-2 text-primary"></i>Edit Template <span class="template-key ms-2" id="editTemplateKeyLabel"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="templateForm">
                        <input type="hidden" id="templateId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="templateName" class="form-label text-dark">Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="templateName">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Group</label>
                                <input type="text" class="form-control" id="templateGroup" readonly disabled>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="templateSubject" class="form-label text-dark">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="templateSubject">
                        </div>
                        <div class="mb-3">
                            <label for="templateBodyHtml" class="form-label text-dark">Body (HTML) <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="templateBodyHtml" rows="16"></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label text-dark mb-1">Available Tags</label>
                            <div class="tags-hint" id="templateTagsHint">—</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveTemplateBtn">
                        <i class="bx bx-save me-1"></i> Save Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Send test template modal --}}
    <div class="modal fade" id="testTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-paper-plane me-2 text-primary"></i>Send Test — <span id="testTemplateName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="testTemplateId">
                    <label for="testTemplateEmail" class="form-label text-dark">Send rendered sample to:</label>
                    <input type="email" class="form-control" id="testTemplateEmail" placeholder="recipient@example.com">
                    <small class="text-secondary">The template is rendered with sample data and sent through its group's SMTP settings.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="sendTestTemplateBtn">
                        <i class="bx bx-send me-1"></i> Send Test
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
    toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: 3000 };

    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    /* ---------------- SMTP tab ---------------- */

    // Switching group reloads the page with that group's settings.
    $('#smtpGroupSelect').on('change', function() {
        window.location = '{{ route('anisenso-mail-settings.index') }}?tab=smtp&group=' + encodeURIComponent($(this).val());
    });

    $('#smtpForm').on('submit', function(e) {
        e.preventDefault();
        const $btn = $('#saveSmtpBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.post('{{ route('anisenso-mail-settings.smtp.save') }}', {
            groupKey: $('#smtpGroupKey').val(),
            smtpHost: $('#smtpHost').val(),
            smtpPort: $('#smtpPort').val(),
            smtpUsername: $('#smtpUsername').val(),
            smtpPassword: $('#smtpPassword').val(),
            smtpEncryption: $('#smtpEncryption').val(),
            smtpFromEmail: $('#smtpFromEmail').val(),
            smtpFromName: $('#smtpFromName').val(),
            isActive: $('#smtpIsActive').is(':checked') ? 1 : 0
        }).done(function(res) {
            if (res.success) {
                toastr.success(res.message);
                $('#smtpPassword').val('');
            } else {
                toastr.error(res.message || 'Failed to save.');
            }
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to save SMTP settings.');
        }).always(function() {
            $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save SMTP Settings');
        });
    });

    $('#testSmtpBtn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Sending...');

        $.post('{{ route('anisenso-mail-settings.smtp.test') }}', {
            groupKey: $('#smtpGroupKey').val(),
            testEmail: $('#testEmail').val()
        }).done(function(res) {
            if (res.success) toastr.success(res.message);
            else toastr.error(res.message || 'Test failed.');
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Test email failed.');
        }).always(function() {
            $btn.prop('disabled', false).html('<i class="bx bx-send me-1"></i> Send Test');
        });
    });

    /* ---------------- Templates tab ---------------- */

    let templatesCache = {};

    function loadTemplates() {
        $('#templatesTbody').html('<tr><td colspan="6" class="text-center text-secondary py-4"><i class="bx bx-loader-alt bx-spin me-1"></i>Loading templates...</td></tr>');
        $.get('{{ route('anisenso-mail-settings.templates') }}', function(res) {
            if (!res.success) {
                $('#templatesTbody').html('<tr><td colspan="6" class="text-center text-danger py-4">' + esc(res.message) + '</td></tr>');
                return;
            }
            renderTemplates(res.data.templates || []);
        }).fail(function(xhr) {
            $('#templatesTbody').html('<tr><td colspan="6" class="text-center text-danger py-4">' + esc(xhr.responseJSON?.message || 'Failed to load templates.') + '</td></tr>');
        });
    }

    function renderTemplates(templates) {
        templatesCache = {};
        if (!templates.length) {
            $('#templatesTbody').html('<tr><td colspan="6" class="text-center text-secondary py-4">No templates found.</td></tr>');
            return;
        }

        let html = '';
        let currentGroup = null;
        templates.forEach(function(t) {
            templatesCache[t.id] = t;
            if (t.groupKey !== currentGroup) {
                currentGroup = t.groupKey;
                html += '<tr class="template-group-header"><td colspan="6"><i class="bx bx-folder me-1"></i>' + esc(currentGroup) + '</td></tr>';
            }
            html += '<tr data-id="' + t.id + '">' +
                '<td class="fw-semibold text-dark">' + esc(t.templateName) + '</td>' +
                '<td><span class="template-key">' + esc(t.templateKey) + '</span></td>' +
                '<td class="text-dark">' + esc(t.subject) + '</td>' +
                '<td><div class="form-check form-switch mb-0">' +
                    '<input class="form-check-input template-toggle" type="checkbox" data-id="' + t.id + '"' + (t.isActive ? ' checked' : '') + '>' +
                '</div></td>' +
                '<td><small class="text-secondary">' + esc(t.updatedAt || '—') + '</small></td>' +
                '<td class="text-end">' +
                    '<button type="button" class="btn btn-sm btn-outline-primary edit-template-btn me-1" data-id="' + t.id + '"><i class="bx bx-edit"></i> Edit</button>' +
                    '<button type="button" class="btn btn-sm btn-outline-info test-template-btn" data-id="' + t.id + '" title="Send test with sample data"><i class="bx bx-paper-plane"></i></button>' +
                '</td>' +
            '</tr>';
        });
        $('#templatesTbody').html(html);
    }

    loadTemplates();
    $('#reloadTemplatesBtn').on('click', loadTemplates);

    // Edit modal
    $('#templatesTable').on('click', '.edit-template-btn', function() {
        const t = templatesCache[$(this).data('id')];
        if (!t) return;
        $('#templateId').val(t.id);
        $('#editTemplateKeyLabel').text(t.templateKey);
        $('#templateName').val(t.templateName);
        $('#templateGroup').val(t.groupKey);
        $('#templateSubject').val(t.subject);
        $('#templateBodyHtml').val(t.bodyHtml);
        $('#templateTagsHint').text(t.availableTags || 'No tag list recorded for this template.');
        $('#editTemplateModal').modal('show');
    });

    $('#saveTemplateBtn').on('click', function() {
        const id = $('#templateId').val();
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.ajax({
            url: '{{ url('/anisenso-mail-settings/templates') }}/' + id,
            type: 'PUT',
            data: {
                templateName: $('#templateName').val(),
                subject: $('#templateSubject').val(),
                bodyHtml: $('#templateBodyHtml').val()
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#editTemplateModal').modal('hide');
                    loadTemplates();
                } else {
                    toastr.error(res.message || 'Failed to save template.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save template.');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Template');
            }
        });
    });

    // Active toggle
    $('#templatesTable').on('change', '.template-toggle', function() {
        const id = $(this).data('id');
        const $toggle = $(this);
        $.post('{{ url('/anisenso-mail-settings/templates') }}/' + id + '/toggle')
            .done(function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    if (templatesCache[id]) templatesCache[id].isActive = res.data.isActive;
                } else {
                    toastr.error(res.message || 'Failed to toggle.');
                    $toggle.prop('checked', !$toggle.prop('checked'));
                }
            })
            .fail(function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to toggle template.');
                $toggle.prop('checked', !$toggle.prop('checked'));
            });
    });

    // Send test
    $('#templatesTable').on('click', '.test-template-btn', function() {
        const t = templatesCache[$(this).data('id')];
        if (!t) return;
        $('#testTemplateId').val(t.id);
        $('#testTemplateName').text(t.templateName);
        $('#testTemplateEmail').val('');
        $('#testTemplateModal').modal('show');
    });

    $('#sendTestTemplateBtn').on('click', function() {
        const id = $('#testTemplateId').val();
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Sending...');

        $.post('{{ url('/anisenso-mail-settings/templates') }}/' + id + '/test', {
            testEmail: $('#testTemplateEmail').val()
        }).done(function(res) {
            if (res.success) {
                toastr.success(res.message);
                $('#testTemplateModal').modal('hide');
            } else {
                toastr.error(res.message || 'Test failed.');
            }
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to send test email.');
        }).always(function() {
            $btn.prop('disabled', false).html('<i class="bx bx-send me-1"></i> Send Test');
        });
    });
});
</script>
@endsection
