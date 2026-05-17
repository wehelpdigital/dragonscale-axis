// ---------- PROTOCOL ----------
$('#saveProtocolBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const fd = new FormData(document.getElementById('protocolForm'));
    fd.append('_token', CSRF);
    $.ajax({
        url: URLS.protocolSave(),
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            const p = res.data || {};
            if (p.protocolFile) {
                $('#protocolCurrentFileName').text(p.protocolFileOriginalName || 'Download');
                $('#protocolCurrentFile').show();
            }
            // Reset the file input so the same file isn't re-uploaded on next save.
            $('#protocolFile').val('');
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Protocol')
    });
});
