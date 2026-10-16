{{-- Included inside each RSS Feeds page's script: toastr, escaping, busy buttons, form errors. --}}
    toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 4000, escapeHtml: true };

    const DELETE_NOTES = {
        'source': 'Its stories leave every feed it was in.',
        'feed': 'Its RSS address stops working, so anything reading it gets nothing, and it stops being a section of any other feed.',
        'feed with sections': 'Its RSS address stops working. The feeds that were its sections stay as they are.',
    };

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fail = (xhr, fallback) => toastr.error(xhr.responseJSON?.message || fallback, 'Error!');
    const busy = ($btn, on, html) => { if (on) { $btn.data('html', $btn.html()).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> ' + html); } else { $btn.prop('disabled', false).html($btn.data('html')); } };

    function showErrors($form, $box, xhr) {
        $form.find('.is-invalid').removeClass('is-invalid');
        const errors = xhr.responseJSON?.errors || {};
        Object.keys(errors).forEach((field) => {
            const $input = $form.find('[name="' + field + '"], [name="' + field + '[]"]').first();
            if ($input.length && !$input.is(':checkbox')) {
                $input.addClass('is-invalid');
                $input.closest('[class*="col-"]').find('.invalid-feedback').first().text(errors[field][0]);
            }
        });
        $box.removeClass('d-none').text(xhr.responseJSON?.message || 'That could not be saved.');
    }
