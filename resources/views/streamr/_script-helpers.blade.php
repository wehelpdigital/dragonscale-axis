{{-- Included inside each Streamr page's script (a copy of games/salpakan/_script-helpers plus Streamr's badge maps). --}}
    toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: 4500, escapeHtml: true };

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const num = (n) => Number(n || 0).toLocaleString();
    const peso = (n) => '₱' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const dash = '<span class="text-secondary">—</span>';
    const fail = (xhr, fallback) => toastr.error((xhr && xhr.responseJSON && xhr.responseJSON.message) || fallback, 'Error!');
    const busy = ($btn, on, html) => { if (on) { $btn.data('html', $btn.html()).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> ' + html); } else { $btn.prop('disabled', false).html($btn.data('html')); } };
    const badge = (cls, text) => '<span class="badge ' + esc(cls || 'bg-secondary') + '">' + esc(text) + '</span>';

    const STATE_BADGES = { active: 'bg-success', grace: 'bg-warning text-dark', expired: 'bg-secondary', suspended: 'bg-danger', none: 'bg-light text-secondary' };
    const ACCOUNT_BADGES = { active: 'bg-success', suspended: 'bg-danger', revoked: 'bg-danger', deletion_requested: 'bg-warning text-dark', deleted: 'bg-dark' };
    const ORDER_BADGES = { pending: 'bg-warning text-dark', paid: 'bg-info text-white', complete: 'bg-success', cancelled: 'bg-secondary', refunded: 'bg-danger' };
    const PAYMENT_BADGES = { not_required: 'bg-light text-secondary', pending: 'bg-warning text-dark', verified: 'bg-success', rejected: 'bg-danger' };
    const AI_CHIPS = { pass: ['AI: passed', 'bg-success'], fail: ['AI: failed', 'bg-danger'], unsure: ['AI: unsure', 'bg-warning text-dark'],
        error: ["AI: couldn't read", 'bg-secondary'], skipped: ['AI: not checked', 'bg-light text-secondary'], running: ['AI: checking', 'bg-info text-white'] };
    const words = (s) => String(s || '').replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());
    const orderBadge = (s) => badge(ORDER_BADGES[s] || 'bg-secondary', words(s || 'pending'));
    const paymentBadge = (s) => badge(PAYMENT_BADGES[s] || 'bg-secondary', s === 'not_required' ? 'N/A' : words(s));
    const aiChip = (ai) => ai ? badge(ai.badge || (AI_CHIPS[ai.verdict] || [])[1], ai.label || (AI_CHIPS[ai.verdict] || [ai.verdict])[0]) : '';

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
