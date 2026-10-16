{{-- Included at the end of each RSS Feeds page's script: switches, read-now, copy and delete. --}}
    // ---------------- Shared ----------------
    $('.rf-toggle').on('change', function () {
        const box = this;
        box.disabled = true;
        $.post($(box).data('url'), { isActive: box.checked ? 1 : 0 })
            .done((res) => { $(box).closest('tr').toggleClass('is-off', !box.checked); toastr.success(res.message); })
            .fail((xhr) => { box.checked = !box.checked; fail(xhr, 'That could not be saved.'); })
            .always(() => { box.disabled = false; });
    });

    $('.rf-refresh').on('click', function () {
        const $btn = $(this);
        busy($btn, true, '');
        $.post($btn.data('url'))
            .done((res) => { toastr.success(res.message, 'Read'); setTimeout(() => location.reload(), 1200); })
            .fail((xhr) => { fail(xhr, 'It could not be read.'); setTimeout(() => location.reload(), 2500); })
            .always(() => busy($btn, false));
    });

    $('.rf-copy').on('click', async function () {
        const $btn = $(this);
        try { await navigator.clipboard.writeText($btn.data('url')); $btn.html('<i class="bx bx-check"></i>'); setTimeout(() => $btn.html('<i class="bx bx-copy"></i>'), 1500); }
        catch (_) { toastr.info($btn.data('url'), 'Copy this address'); }
    });

    let toDelete = null;
    $('.rf-delete').on('click', function () {
        toDelete = { url: $(this).data('url'), row: $(this).closest('tr') };
        $('#deleteItemWhat').text($(this).data('what'));
        $('#deleteItemName').text($(this).data('name'));
        $('#deleteItemNote').text(DELETE_NOTES[$(this).data('what')] || '');
        $('#deleteModal').modal('show');
    });
    $('#confirmDelete').on('click', function () {
        if (!toDelete) return;
        const $btn = $(this);
        busy($btn, true, 'Deleting...');
        $.ajax({ url: toDelete.url, type: 'DELETE' })
            .done((res) => { $('#deleteModal').modal('hide'); toastr.success(res.message, 'Deleted'); setTimeout(() => location.reload(), 700); })
            .fail((xhr) => fail(xhr, 'It could not be deleted.'))
            .always(() => { busy($btn, false); toDelete = null; });
    });
