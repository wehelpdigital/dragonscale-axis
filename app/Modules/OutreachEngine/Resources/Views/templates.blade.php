@extends('layouts.master')

@section('title') Email Templates @endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.placeholder-btn {
    border-radius: 20px !important;
    padding: 3px 10px !important;
    font-size: 11px !important;
    letter-spacing: 0.3px !important;
    line-height: 1.4 !important;
}

.template-subject {
    max-width: 420px;
    white-space: normal;
}

.preview-body {
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    background-color: #fff;
    color: #212529;
    padding: 1rem;
    max-height: 55vh;
    overflow-y: auto;
}

/* TinyMCE floats its dialogs and menus at the document root; without this they
   sit under the Bootstrap modal backdrop and cannot be clicked. */
.tox-tinymce-aux,
.tox-silver-sink {
    z-index: 1090 !important;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') Lead Finder @endslot
@slot('title') Email Templates @endslot
@endcomponent

@php
    // Explicit payload: the editor and the preview both need the raw template bodies,
    // and dumping whole models into the page would ship columns the browser has no
    // business seeing.
    $templatePayload = $templates->map(function ($template) {
        return [
            'id' => (int) $template->id,
            'name' => (string) $template->name,
            'subjectTemplate' => (string) $template->subjectTemplate,
            'bodyTemplate' => (string) $template->bodyTemplate,
            'bodyPreview' => $template->body_preview,
            'isActive' => (bool) $template->isActive,
            'sendOrder' => (int) $template->sendOrder,
            'timesUsed' => (int) $template->timesUsed,
        ];
    })->values();
@endphp

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h4 class="card-title mb-1">Email Templates</h4>
                        <p class="text-secondary mb-0">
                            The queue walks the active templates in send order, one per prospect.
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary" id="newTemplateBtn">
                        <i class="bx bx-plus me-1"></i>New template
                    </button>
                </div>

                @if(!$hasRealLead)
                    <div class="alert alert-info" role="alert">
                        <i class="bx bx-info-circle me-2"></i>
                        <span class="text-dark">
                            No leads yet, so previews render against a sample business until your first scrape lands.
                        </span>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table align-middle table-bordered table-striped mb-0" id="templates-table">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 90px;">Order</th>
                                <th>Name</th>
                                <th>Subject</th>
                                <th class="text-center" style="width: 110px;">Active</th>
                                <th class="text-center" style="width: 110px;">Times used</th>
                                <th class="text-center" style="width: 230px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="templatesBody">
                            <!-- Rows are rendered by renderTable() so one function owns the row markup -->
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Create / Edit Template Modal -->
<div class="modal fade" id="templateModal" tabindex="-1" aria-labelledby="templateModalLabel"
     aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalLabel">
                    <i class="bx bx-envelope text-primary me-2"></i>New Template
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="templateId">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="templateName" class="form-label text-dark">
                            Template name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="templateName" maxlength="190"
                               placeholder="e.g. First touch - website offer">
                        <div class="form-text text-body-secondary">Only you see this.</div>
                    </div>
                    <div class="col-md-3">
                        <label for="templateSendOrder" class="form-label text-dark">Send order</label>
                        <input type="number" class="form-control" id="templateSendOrder" min="1" max="9999" step="1">
                        <div class="form-text text-body-secondary">Lower goes out first.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-dark d-block">Status</label>
                        <div class="form-check form-switch form-switch-md mt-2">
                            <input class="form-check-input" type="checkbox" id="templateIsActive" checked>
                            <label class="form-check-label text-dark" for="templateIsActive">In rotation</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="templateSubject" class="form-label text-dark">
                            Subject line <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="templateSubject" maxlength="500"
                               placeholder="A quick idea for {business_name}">
                    </div>

                    <div class="col-12">
                        <label class="form-label text-dark mb-1">Placeholders</label>
                        <div class="d-flex flex-wrap gap-1 mb-2" id="placeholderPalette">
                            @foreach($placeholders as $token => $label)
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary placeholder-btn"
                                        data-token="{{ $token }}"
                                        title="{{ $label }}">{{ $token }}</button>
                            @endforeach
                        </div>
                        <div class="form-text text-body-secondary">
                            Click one to drop it where your cursor is - in the subject line or in the body,
                            whichever you touched last.
                        </div>
                    </div>

                    <div class="col-12">
                        <label for="templateBody" class="form-label text-dark">
                            Email body <span class="text-danger">*</span>
                        </label>
                        <textarea id="templateBody" class="form-control" rows="14"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-outline-info" id="previewFromEditorBtn">
                    <i class="bx bx-show me-1"></i>Preview
                </button>
                <button type="button" class="btn btn-primary" id="saveTemplateBtn">
                    <i class="bx bx-save me-1"></i>Save template
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="bx bx-show text-info me-2"></i>Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="previewRephrase"
                               {{ $settings->hasLlm() ? '' : 'disabled' }}>
                        <label class="form-check-label text-dark" for="previewRephrase">
                            AI rephrase
                        </label>
                        <div class="form-text text-body-secondary mb-0">
                            @if($settings->hasLlm())
                                Varies the subject and opening sentence, exactly as a real send would.
                            @else
                                Add an AI provider and API key in Settings to try a rephrased version.
                            @endif
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="refreshPreviewBtn">
                        <i class="bx bx-refresh me-1"></i>Re-render
                    </button>
                </div>

                <div id="previewMeta" class="mb-2"></div>

                <div class="mb-2">
                    <span class="text-secondary">Subject:</span>
                    <span class="text-dark fw-semibold" id="previewSubject"></span>
                </div>

                <div class="preview-body" id="previewBody"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete <strong id="deleteItemName" class="text-dark"></strong>?</p>
                <p class="text-secondary mb-0">Emails already sent keep their own copy, so your history stays intact.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="bx bx-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- TinyMCE -->
<script src="{{ URL::asset('build/libs/tinymce/tinymce.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    var CSRF_TOKEN = '{{ csrf_token() }}';
    var TEMPLATE_STORE_URL = '{{ route('outreach.templates.store') }}';
    var TEMPLATE_UPDATE_URL = '{{ route('outreach.templates.update') }}';
    var TEMPLATE_DELETE_URL = '{{ route('outreach.templates.destroy') }}';
    var TEMPLATE_TOGGLE_URL = '{{ route('outreach.templates.toggle') }}';
    var PREVIEW_LEAD_ID = {{ $previewLead ? (int) $previewLead->id : 'null' }};

    var templates = @json($templatePayload);

    /** What the preview modal is currently rendering - editor draft or a saved row. */
    var previewSource = null;

    /** Which field the placeholder palette should write into. */
    var lastFocusedField = 'body';

    /** Body waiting for the editor: TinyMCE only exists after the modal has been shown. */
    var pendingBody = '';

    var editorReady = false;

    function escapeHtml(value) {
        return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
    }

    function findTemplate(id) {
        id = parseInt(id, 10);

        for (var i = 0; i < templates.length; i++) {
            if (templates[i].id === id) {
                return templates[i];
            }
        }

        return null;
    }

    /** Insert or replace the saved copy, then redraw. */
    function upsertTemplate(template) {
        var existing = findTemplate(template.id);

        if (existing) {
            $.extend(existing, template);
        } else {
            templates.push(template);
        }

        renderTable();
    }

    function removeTemplate(id) {
        id = parseInt(id, 10);
        templates = $.grep(templates, function(template) {
            return template.id !== id;
        });

        renderTable();
    }

    // ==================== TABLE ====================

    function renderTable() {
        var $body = $('#templatesBody');

        if (!templates.length) {
            $body.html(
                '<tr><td colspan="6">' +
                '<div class="text-center py-4">' +
                '<i class="mdi mdi-email-plus-outline text-secondary" style="font-size: 2.5rem;"></i>' +
                '<p class="text-dark mt-2 mb-1">No templates yet.</p>' +
                '<small class="text-secondary">Write one and the queue has something to send.</small>' +
                '</div></td></tr>'
            );
            return;
        }

        // Same order the send pipeline uses, so the screen reads like the rotation.
        templates.sort(function(a, b) {
            return a.sendOrder === b.sendOrder ? a.id - b.id : a.sendOrder - b.sendOrder;
        });

        var html = '';

        $.each(templates, function(index, template) {
            var name = escapeHtml(template.name);
            var checked = template.isActive ? ' checked' : '';
            var preview = template.bodyPreview
                ? '<br><small class="text-secondary">' + escapeHtml(template.bodyPreview) + '</small>'
                : '';

            html += '<tr data-template-id="' + template.id + '">' +
                '<td class="text-center"><span class="badge bg-light text-dark">' + escapeHtml(template.sendOrder) + '</span></td>' +
                '<td><strong class="text-dark">' + name + '</strong>' + preview + '</td>' +
                '<td class="text-dark template-subject">' + escapeHtml(template.subjectTemplate) + '</td>' +
                '<td class="text-center">' +
                    '<div class="form-check form-switch d-inline-block">' +
                    '<input class="form-check-input template-toggle" type="checkbox" role="switch"' +
                    ' data-template-id="' + template.id + '"' +
                    ' data-template-name="' + name + '"' + checked + '>' +
                    '</div>' +
                '</td>' +
                '<td class="text-center text-dark">' + escapeHtml(template.timesUsed) + '</td>' +
                '<td class="text-center">' +
                    '<div class="d-flex flex-wrap gap-1 justify-content-center">' +
                    '<button type="button" class="btn btn-sm btn-outline-info template-preview-btn"' +
                    ' data-template-id="' + template.id + '"><i class="bx bx-show me-1"></i>Preview</button>' +
                    '<button type="button" class="btn btn-sm btn-outline-success template-edit-btn"' +
                    ' data-template-id="' + template.id + '"><i class="bx bx-edit me-1"></i>Edit</button>' +
                    '<button type="button" class="btn btn-sm btn-outline-danger template-delete-btn"' +
                    ' data-template-id="' + template.id + '"' +
                    ' data-template-name="' + name + '"><i class="bx bx-trash me-1"></i>Delete</button>' +
                    '</div>' +
                '</td>' +
                '</tr>';
        });

        $body.html(html);
    }

    renderTable();

    // ==================== EDITOR ====================

    /**
     * TinyMCE is booted on first open, never at page load: the app's layout script moves
     * every .modal to <body> after this file runs, and re-parenting a live editor iframe
     * wipes what is inside it.
     */
    function bootEditor() {
        if (editorReady) {
            var editor = tinymce.get('templateBody');

            if (editor) {
                editor.setContent(pendingBody || '');
            }

            return;
        }

        editorReady = true;

        tinymce.init({
            selector: '#templateBody',
            height: 420,
            menubar: false,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
                'searchreplace', 'visualblocks', 'code', 'fullscreen', 'table', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic underline forecolor | ' +
                'alignleft aligncenter alignright | bullist numlist outdent indent | ' +
                'removeformat | link | code fullscreen',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; color: #212529; }',
            skin: 'oxide',
            branding: false,
            promotion: false,
            relative_urls: false,
            remove_script_host: false,
            convert_urls: false,
            setup: function(editor) {
                editor.on('focus', function() {
                    lastFocusedField = 'body';
                });
            },
            init_instance_callback: function(editor) {
                editor.setContent(pendingBody || '');
            }
        });
    }

    function editorContent() {
        var editor = tinymce.get('templateBody');

        return editor ? editor.getContent() : $('#templateBody').val();
    }

    // Bootstrap's focus trap steals clicks from TinyMCE's floating dialogs (link, code,
    // colour picker). Letting those focus events through is the standard fix.
    $(document).on('focusin', function(e) {
        if ($(e.target).closest('.tox-tinymce-aux, .tox-dialog, .tox-silver-sink').length) {
            e.stopImmediatePropagation();
        }
    });

    $('#templateSubject').on('focus click keyup', function() {
        lastFocusedField = 'subject';
    });

    $('#templateModal').on('shown.bs.modal', function() {
        bootEditor();
    });

    $('#newTemplateBtn').on('click', function() {
        $('#templateModalLabel').html('<i class="bx bx-envelope text-primary me-2"></i>New Template');
        $('#templateId').val('');
        $('#templateName').val('');
        $('#templateSubject').val('');
        $('#templateSendOrder').val(nextSendOrder());
        $('#templateIsActive').prop('checked', true);

        pendingBody = '';
        lastFocusedField = 'body';

        $('#templateModal').modal('show');
    });

    /** One past the highest order in use, so a new template lands at the end of the rotation. */
    function nextSendOrder() {
        var highest = 0;

        $.each(templates, function(index, template) {
            if (template.sendOrder > highest) {
                highest = template.sendOrder;
            }
        });

        return highest + 1;
    }

    $('#templatesBody').on('click', '.template-edit-btn', function() {
        var template = findTemplate($(this).data('template-id'));

        if (!template) {
            return;
        }

        $('#templateModalLabel').html('<i class="bx bx-edit text-success me-2"></i>Edit Template');
        $('#templateId').val(template.id);
        $('#templateName').val(template.name);
        $('#templateSubject').val(template.subjectTemplate);
        $('#templateSendOrder').val(template.sendOrder);
        $('#templateIsActive').prop('checked', template.isActive);

        pendingBody = template.bodyTemplate || '';
        lastFocusedField = 'body';

        $('#templateModal').modal('show');
    });

    // ==================== PLACEHOLDER PALETTE ====================

    $('#placeholderPalette').on('click', '.placeholder-btn', function() {
        var token = $(this).data('token');

        if (lastFocusedField === 'subject') {
            insertIntoInput($('#templateSubject')[0], token);
            return;
        }

        var editor = tinymce.get('templateBody');

        if (editor) {
            editor.execCommand('mceInsertContent', false, token);
            editor.focus();
            return;
        }

        // Editor not up yet (modal still opening): append to the raw textarea instead.
        $('#templateBody').val($('#templateBody').val() + token);
    });

    /** Drop text at the caret of a plain input, leaving the caret after what was inserted. */
    function insertIntoInput(input, text) {
        if (!input) {
            return;
        }

        var start = input.selectionStart;
        var end = input.selectionEnd;
        var value = input.value || '';

        if (start === null || start === undefined) {
            input.value = value + text;
            return;
        }

        input.value = value.substring(0, start) + text + value.substring(end);
        input.selectionStart = input.selectionEnd = start + text.length;
        input.focus();
    }

    // ==================== SAVE ====================

    $('#saveTemplateBtn').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        var id = $('#templateId').val();

        var payload = {
            _token: CSRF_TOKEN,
            name: $('#templateName').val(),
            subjectTemplate: $('#templateSubject').val(),
            bodyTemplate: editorContent(),
            isActive: $('#templateIsActive').is(':checked') ? 1 : 0,
            sendOrder: $('#templateSendOrder').val()
        };

        // The id rides in the body now that the update route has no path segment.
        if (id) payload.id = id;

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: id ? TEMPLATE_UPDATE_URL : TEMPLATE_STORE_URL,
            type: 'POST',
            data: payload,
            success: function(response) {
                if (!response.success) {
                    toastr.error(response.message || 'The template could not be saved.', 'Error!', { timeOut: 5000 });
                    return;
                }

                upsertTemplate(response.data);
                $('#templateModal').modal('hide');
                toastr.success(response.message, 'Success!');
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'An error occurred while saving the template.';
                toastr.error(message, 'Error!', { timeOut: 5000 });
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ==================== ACTIVE TOGGLE ====================

    $('#templatesBody').on('change', '.template-toggle', function() {
        var $input = $(this);
        var id = $input.data('template-id');

        $input.prop('disabled', true);

        $.ajax({
            url: TEMPLATE_TOGGLE_URL,
            type: 'POST',
            data: { _token: CSRF_TOKEN, id: id },
            success: function(response) {
                if (!response.success) {
                    // Put the switch back where the server says it is.
                    $input.prop('checked', !$input.is(':checked'));
                    toastr.error(response.message || 'The template could not be updated.', 'Error!', { timeOut: 5000 });
                    return;
                }

                var template = findTemplate(id);

                if (template) {
                    template.isActive = response.data.isActive;
                }

                $input.prop('checked', response.data.isActive);
                toastr.success(response.message, 'Success!');
            },
            error: function(xhr) {
                $input.prop('checked', !$input.is(':checked'));

                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'An error occurred while updating the template.';
                toastr.error(message, 'Error!', { timeOut: 5000 });
            },
            complete: function() {
                $input.prop('disabled', false);
            }
        });
    });

    // ==================== PREVIEW ====================

    $('#previewFromEditorBtn').on('click', function() {
        previewSource = {
            name: $('#templateName').val() || 'Preview',
            subjectTemplate: $('#templateSubject').val(),
            bodyTemplate: editorContent()
        };

        openPreview();
    });

    $('#templatesBody').on('click', '.template-preview-btn', function() {
        var template = findTemplate($(this).data('template-id'));

        if (!template) {
            return;
        }

        previewSource = {
            name: template.name,
            subjectTemplate: template.subjectTemplate,
            bodyTemplate: template.bodyTemplate
        };

        openPreview();
    });

    function openPreview() {
        $('#previewRephrase').prop('checked', false);
        $('#previewModal').modal('show');
        runPreview();
    }

    $('#refreshPreviewBtn').on('click', function() {
        runPreview();
    });

    $('#previewRephrase').on('change', function() {
        runPreview();
    });

    function runPreview() {
        if (!previewSource) {
            return;
        }

        var wantsRephrase = $('#previewRephrase').is(':checked');

        $('#previewMeta').html('<span class="text-secondary"><i class="bx bx-loader-alt bx-spin me-1"></i>Rendering...</span>');
        $('#previewSubject').text('');
        $('#previewBody').html('');

        var payload = {
            _token: CSRF_TOKEN,
            name: previewSource.name,
            subjectTemplate: previewSource.subjectTemplate,
            bodyTemplate: previewSource.bodyTemplate,
            rephrase: wantsRephrase ? 1 : 0
        };

        // Omitted rather than sent empty: with no leads yet the controller picks its own
        // sample business, and 'nullable|integer' has nothing to argue with.
        if (PREVIEW_LEAD_ID) {
            payload.leadId = PREVIEW_LEAD_ID;
        }

        $.ajax({
            url: "{{ route('outreach.templates.preview') }}",
            type: 'POST',
            data: payload,
            success: function(response) {
                if (!response.success) {
                    $('#previewMeta').html(
                        '<div class="alert alert-warning mb-0"><i class="bx bx-error-circle me-2"></i>' +
                        escapeHtml(response.message || 'The preview could not be rendered.') + '</div>'
                    );
                    return;
                }

                var data = response.data || {};
                var meta = data.usedSampleLead
                    ? '<span class="badge bg-warning text-dark">Sample business</span>'
                    : '<span class="badge bg-info text-white">Lead: ' + escapeHtml(data.leadName) + '</span>';

                if (data.rephrased) {
                    meta += ' <span class="badge bg-success">AI rephrased</span>';
                } else if (wantsRephrase) {
                    meta += ' <span class="badge bg-secondary">Original copy</span>';
                }

                $('#previewMeta').html(meta);
                $('#previewSubject').text(data.subject || '');
                // Server-rendered HTML from the operator's own template - shown as it will send.
                $('#previewBody').html(data.body || '');
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'An error occurred while rendering the preview.';

                $('#previewMeta').html(
                    '<div class="alert alert-danger mb-0"><i class="bx bx-error-circle me-2"></i>' +
                    escapeHtml(message) + '</div>'
                );
            }
        });
    }

    // ==================== DELETE ====================

    var templateToDelete = null;

    $('#templatesBody').on('click', '.template-delete-btn', function() {
        templateToDelete = {
            id: $(this).data('template-id'),
            name: $(this).data('template-name')
        };

        $('#deleteItemName').text(templateToDelete.name);
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function() {
        if (!templateToDelete) {
            return;
        }

        var $btn = $(this);
        var originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: TEMPLATE_DELETE_URL,
            type: 'POST',
            data: { _token: CSRF_TOKEN, id: templateToDelete.id },
            success: function(response) {
                if (!response.success) {
                    toastr.error(response.message || 'The template could not be deleted.', 'Error!', { timeOut: 5000 });
                    return;
                }

                removeTemplate(templateToDelete.id);
                $('#deleteModal').modal('hide');
                toastr.success(response.message, 'Success!');
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'An error occurred while deleting the template.';
                toastr.error(message, 'Error!', { timeOut: 5000 });
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
                templateToDelete = null;
            }
        });
    });

});
</script>
@endsection
