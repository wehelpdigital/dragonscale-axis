{{-- Included inside each Games > Salpakan Tactics! page's script: toastr, escaping, badges, busy buttons. --}}
    toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 4000, escapeHtml: true };

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const num = (n) => Number(n || 0).toLocaleString();
    const dash = '<span class="text-secondary">—</span>';
    const fail = (xhr, fallback) => toastr.error(xhr.responseJSON?.message || fallback, 'Error!');
    const busy = ($btn, on, html) => { if (on) { $btn.data('html', $btn.html()).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> ' + html); } else { $btn.prop('disabled', false).html($btn.data('html')); } };

    const MARKETING_BADGES = {
        pending: 'bg-light text-dark', subscribed: 'bg-success', skipped: 'bg-secondary',
        failed: 'bg-danger', unsubscribed: 'bg-warning text-dark'
    };
    const marketingBadge = (status, label) => '<span class="badge ' + (MARKETING_BADGES[status] || 'bg-secondary') + '">' + esc(label || status) + '</span>';

    const ORDER_BADGES = { pending: 'bg-warning text-dark', completed: 'bg-success', cancelled: 'bg-secondary', refunded: 'bg-danger' };
    const orderBadge = (status, label) => '<span class="badge ' + (ORDER_BADGES[status] || 'bg-secondary') + '">' + esc(label || status) + '</span>';

    /* The filters on the page, as query parameters (empty ones left out). */
    const filterValues = ($box) => {
        const out = {};
        $box.find('[data-filter]').each(function () {
            const $f = $(this);
            const value = $f.is(':checkbox') ? ($f.is(':checked') ? '1' : '0') : $f.val();
            if (value !== '' && value !== null) { out[$f.data('filter')] = value; }
        });
        return out;
    };
