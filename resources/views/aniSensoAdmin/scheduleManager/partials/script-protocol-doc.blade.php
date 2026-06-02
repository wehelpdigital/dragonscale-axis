// ================================================================
// PROTOCOL TAB: Attachments + Critical Rules
// ================================================================
// The Protocol Introduction modal/handlers live in script-activities
// (they share the Quill editor init). This file is for the two schedule-
// level data sets that don't exist outside the Protocol tab.

// ---- Render helpers ------------------------------------------------
// File-size formatter ("12.3 KB", "1.2 MB").
function _fmtSize(bytes) {
    if (!bytes || bytes < 1) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(2) + ' MB';
}

// Render a freshly-uploaded attachment card. Mirrors the server-side
// attachment-card partial so the inserted DOM matches what a page
// reload would emit.
function renderAttachmentCard(a) {
    const ext = (a.filename || '').split('.').pop().toUpperCase();
    const thumb = a.isImage && a.url
        ? `<a href="${escapeHtml(a.url)}" target="_blank" rel="noopener">
               <img src="${escapeHtml(a.url)}" alt="${escapeHtml(a.filename)}" loading="lazy">
           </a>`
        : `<a href="${escapeHtml(a.url || '#')}" target="_blank" rel="noopener" class="attachment-file-icon">
               <i class="bx bxs-file"></i>
               <span class="ext-badge">${escapeHtml(ext)}</span>
           </a>`;
    const desc = a.description
        ? escapeHtml(a.description)
        : '<em class="text-secondary">No description.</em>';
    const sizeStr = _fmtSize(a.fileSize || 0);
    return `<div class="attachment-card" data-id="${a.id}">
        <div class="attachment-thumb">${thumb}</div>
        <div class="attachment-body">
            <div class="attachment-filename" title="${escapeHtml(a.filename)}">${escapeHtml(a.filename)}</div>
            ${sizeStr ? `<small class="text-secondary">${sizeStr}</small>` : ''}
            <div class="attachment-description" data-id="${a.id}">${desc}</div>
            <div class="attachment-actions">
                <button type="button" class="btn btn-sm btn-outline-primary edit-attachment-btn"
                        data-id="${a.id}"
                        data-filename="${escapeHtml(a.filename)}"
                        data-description="${escapeHtml(a.description || '')}"
                        title="Edit description"><i class="bx bx-edit-alt"></i></button>
                <button type="button" class="btn btn-sm btn-outline-danger delete-attachment-btn"
                        data-id="${a.id}"
                        data-filename="${escapeHtml(a.filename)}"
                        title="Delete attachment"><i class="bx bx-trash"></i></button>
            </div>
        </div>
    </div>`;
}

function renderCriticalRuleRow(r) {
    return `<div class="critical-rule-row" data-id="${r.id}" data-sort-order="${parseInt(r.sortOrder, 10) || 0}" draggable="true">
        <div class="critical-rule-handle" title="Drag to reorder"><i class="bx bx-grid-vertical"></i></div>
        <div class="critical-rule-text">${escapeHtml(r.ruleText)}</div>
        <div class="critical-rule-actions">
            <button type="button" class="btn btn-sm btn-outline-primary edit-critical-rule-btn"
                    data-id="${r.id}"
                    data-text="${escapeHtml(r.ruleText)}"
                    title="Edit"><i class="bx bx-edit-alt"></i></button>
            <button type="button" class="btn btn-sm btn-outline-danger delete-critical-rule-btn"
                    data-id="${r.id}"
                    title="Delete"><i class="bx bx-trash"></i></button>
        </div>
    </div>`;
}

// Recompute the Protocol tab's count badge after add/remove.
function _refreshProtocolBadge() {
    const n = $('#attachmentsGrid .attachment-card[data-id]').length
            + $('#criticalRulesList .critical-rule-row[data-id]').length;
    $('#badge-protocol-doc').text(n);
}

// ================================================================
// Attachments
// ================================================================

$(document).on('click', '#openAttachmentUploadBtn', function () {
    $('#attachmentFileInput').val('');
    $('#attachmentDescriptionInput').val('');
    $('#attachmentUploadProgress').hide().find('.progress-bar').css('width', '0%');
    $('#attachmentUploadModal').modal('show');
});

$(document).on('click', '#uploadAttachmentBtn', function () {
    const $file = $('#attachmentFileInput');
    const files = $file.prop('files');
    if (!files || files.length === 0) {
        toastr.warning('Pick a file first.');
        return;
    }
    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('file', files[0]);
    fd.append('description', $('#attachmentDescriptionInput').val() || '');

    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Uploading...');
    const $bar = $('#attachmentUploadProgress').show().find('.progress-bar');

    $.ajax({
        url: URLS.attachmentsStore(),
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        xhr: function () {
            const xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', function (evt) {
                if (evt.lengthComputable) {
                    $bar.css('width', Math.round((evt.loaded / evt.total) * 100) + '%');
                }
            }, false);
            return xhr;
        },
        success: (res) => {
            if (!res.success) {
                toastr.error(res.message || 'Upload failed');
                return;
            }
            toastr.success('Attachment uploaded.');
            $('#attachmentsEmpty').remove();
            $('#attachmentsGrid').append(renderAttachmentCard(res.data));
            _refreshProtocolBadge();
            $('#attachmentUploadModal').modal('hide');
        },
        error: (xhr) => {
            const errs = xhr.responseJSON?.errors;
            if (errs) {
                const first = Object.values(errs)[0];
                toastr.error(Array.isArray(first) ? first[0] : 'Upload failed');
            } else {
                toastr.error(xhr.responseJSON?.message || 'Upload failed');
            }
        },
        complete: () => {
            $btn.prop('disabled', false).html('<i class="bx bx-upload me-1"></i> Upload');
        }
    });
});

$(document).on('click', '.edit-attachment-btn', function () {
    $('#attachmentEditId').val($(this).data('id'));
    $('#attachmentEditFilename').text($(this).data('filename') || '');
    $('#attachmentEditDescription').val($(this).data('description') || '');
    $('#attachmentEditModal').modal('show');
});

$(document).on('click', '#saveAttachmentEditBtn', function () {
    const id = $('#attachmentEditId').val();
    const description = $('#attachmentEditDescription').val();
    if (!id) return;
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url: URLS.attachmentsUpdate(id),
        type: 'PUT',
        data: { _token: CSRF, description: description },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            const $card = $('#attachmentsGrid .attachment-card[data-id="' + id + '"]');
            const newDescHtml = description.trim()
                ? escapeHtml(description)
                : '<em class="text-secondary">No description.</em>';
            $card.find('.attachment-description').html(newDescHtml);
            $card.find('.edit-attachment-btn').attr('data-description', description);
            toastr.success('Description updated.');
            $('#attachmentEditModal').modal('hide');
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save')
    });
});

$(document).on('click', '.delete-attachment-btn', function () {
    const id = $(this).data('id');
    const filename = $(this).data('filename');
    confirmAction({
        title: 'Delete attachment',
        message: 'Delete <strong>"' + escapeHtml(filename) + '"</strong>?',
        detail: 'The attachment will be removed from the worker presentation and export schedule. The file itself stays on disk until a future cleanup.',
        confirmText: 'Delete Attachment',
        onConfirm: () => {
            $.ajax({
                url: URLS.attachmentsDelete(id),
                type: 'DELETE',
                data: { _token: CSRF },
                success: (res) => {
                    if (!res.success) { toastr.error(res.message); return; }
                    $('#attachmentsGrid .attachment-card[data-id="' + id + '"]').fadeOut(250, function () {
                        $(this).remove();
                        if ($('#attachmentsGrid .attachment-card[data-id]').length === 0) {
                            $('#attachmentsGrid').append(
                                `<div id="attachmentsEmpty" class="protocol-empty-state text-center py-4 w-100">
                                    <i class="bx bx-image-alt" style="font-size: 2rem; color: #b8c0d3;"></i>
                                    <p class="text-dark mt-2 mb-1">No attachments uploaded yet.</p>
                                    <small class="text-secondary">Click <strong>Upload Attachment</strong> to add reference images.</small>
                                </div>`
                            );
                        }
                        _refreshProtocolBadge();
                    });
                    toastr.success('Attachment removed.');
                },
                error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
            });
        }
    });
});

// ================================================================
// Critical Rules
// ================================================================

$(document).on('click', '#openCriticalRuleAddBtn', function () {
    $('#criticalRuleEditId').val('');
    $('#criticalRuleInput').val('');
    $('#criticalRuleModalTitle').text('Add Critical Rule');
    $('#criticalRuleModal').modal('show');
    setTimeout(() => $('#criticalRuleInput').trigger('focus'), 200);
});

$(document).on('click', '.edit-critical-rule-btn', function () {
    $('#criticalRuleEditId').val($(this).data('id'));
    $('#criticalRuleInput').val($(this).data('text') || '');
    $('#criticalRuleModalTitle').text('Edit Critical Rule');
    $('#criticalRuleModal').modal('show');
    setTimeout(() => $('#criticalRuleInput').trigger('focus'), 200);
});

$(document).on('click', '#saveCriticalRuleBtn', function () {
    const id   = $('#criticalRuleEditId').val();
    const text = ($('#criticalRuleInput').val() || '').trim();
    if (!text) { toastr.warning('Enter the rule text.'); return; }

    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url:  id ? URLS.criticalRulesUpdate(id) : URLS.criticalRulesStore(),
        type: id ? 'PUT' : 'POST',
        data: { _token: CSRF, ruleText: text },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            const html = renderCriticalRuleRow(res.data);
            if (id) {
                $('#criticalRulesList .critical-rule-row[data-id="' + id + '"]').replaceWith(html);
            } else {
                $('#criticalRulesEmpty').remove();
                $('#criticalRulesList').append(html);
                _refreshProtocolBadge();
            }
            $('#criticalRuleModal').modal('hide');
            toastr.success(id ? 'Rule updated.' : 'Rule added.');
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Rule')
    });
});

$(document).on('click', '.delete-critical-rule-btn', function () {
    const id = $(this).data('id');
    confirmAction({
        title: 'Delete rule',
        message: 'Delete this critical rule?',
        detail: 'Permanently removes the reminder. The worker presentation and export schedule update on next render.',
        confirmText: 'Delete Rule',
        onConfirm: () => {
            $.ajax({
                url: URLS.criticalRulesDelete(id),
                type: 'DELETE',
                data: { _token: CSRF },
                success: (res) => {
                    if (!res.success) { toastr.error(res.message); return; }
                    $('#criticalRulesList .critical-rule-row[data-id="' + id + '"]').fadeOut(250, function () {
                        $(this).remove();
                        if ($('#criticalRulesList .critical-rule-row[data-id]').length === 0) {
                            $('#criticalRulesList').append(
                                `<div id="criticalRulesEmpty" class="protocol-empty-state text-center py-4">
                                    <i class="bx bx-flag" style="font-size: 2rem; color: #b8c0d3;"></i>
                                    <p class="text-dark mt-2 mb-1">No critical rules defined yet.</p>
                                    <small class="text-secondary">Click <strong>Add Rule</strong> to write the first reminder.</small>
                                </div>`
                            );
                        }
                        _refreshProtocolBadge();
                    });
                    toastr.success('Rule deleted.');
                },
                error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
            });
        }
    });
});

// ---- Drag-drop reorder for critical rules --------------------------
let _ruleDragging = null;
$(document).on('dragstart', '#criticalRulesList .critical-rule-row', function (e) {
    _ruleDragging = this;
    $(this).addClass('dragging');
    if (e.originalEvent && e.originalEvent.dataTransfer) {
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        e.originalEvent.dataTransfer.setData('text/plain', $(this).data('id'));
    }
});
$(document).on('dragend', '#criticalRulesList .critical-rule-row', function () {
    $(this).removeClass('dragging');
    $('#criticalRulesList .critical-rule-row').removeClass('drag-over');
    _ruleDragging = null;
});
$(document).on('dragover', '#criticalRulesList .critical-rule-row', function (e) {
    if (!_ruleDragging || _ruleDragging === this) return;
    e.preventDefault();
    if (e.originalEvent && e.originalEvent.dataTransfer) {
        e.originalEvent.dataTransfer.dropEffect = 'move';
    }
    $('#criticalRulesList .critical-rule-row').not(this).removeClass('drag-over');
    $(this).addClass('drag-over');
});
$(document).on('dragleave', '#criticalRulesList .critical-rule-row', function () {
    $(this).removeClass('drag-over');
});
$(document).on('drop', '#criticalRulesList .critical-rule-row', function (e) {
    e.preventDefault();
    $(this).removeClass('drag-over');
    if (!_ruleDragging || _ruleDragging === this) return;
    $(this).before(_ruleDragging);
    _persistCriticalRuleOrder();
});

function _persistCriticalRuleOrder() {
    const items = $('#criticalRulesList .critical-rule-row[data-id]').map(function (idx) {
        const id = parseInt($(this).data('id'), 10);
        const sortOrder = idx + 1;
        $(this).attr('data-sort-order', sortOrder);
        return { id: id, sortOrder: sortOrder };
    }).get();
    if (items.length === 0) return;
    $.ajax({
        url: URLS.criticalRulesReorder(),
        type: 'POST',
        data: { _token: CSRF, items: items },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Could not save the new order')
    });
}
