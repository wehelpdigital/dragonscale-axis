// ---------- SETTINGS ----------
// Two saves, because they are two different jobs: what this season IS, and
// who hears about it each morning. The Default Groupings builder that used to
// live here went with the screen it mirrored — the client app has no such
// thing any more.

// Save Basic Info (title, description, dayType)
$('#saveSettingsBasicBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const done = () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Basic Info');
    const payload = {
        _token: CSRF,
        title: $('#settingsTitle').val(),
        description: $('#settingsDescription').val(),
        dayType: $('#settingsDayType').val(),
    };
    if (!payload.title) { toastr.warning('Title is required'); done(); return; }

    $.ajax({
        url: URLS.scheduleUpdate(),
        type: 'PUT',
        data: payload,
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            // Live-update header + every dayType label across the page
            $('#scheduleHeaderTitle').text(payload.title);
            $('#scheduleHeaderDescription').text(payload.description || 'No description provided.');
            $('.day-type-label').text(payload.dayType);
            document.title = 'Setup — ' + payload.title;
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: done
    });
});

// Save the morning email settings
$('#saveNotifyBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const done = () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Notifications');

    $.ajax({
        url: URLS.scheduleUpdate(),
        type: 'PUT',
        data: {
            _token: CSRF,
            // The schedule update endpoint validates the title on every call,
            // so it comes along even when only the switches moved.
            title: $('#settingsTitle').val(),
            description: $('#settingsDescription').val(),
            notifyWorkersDaily: $('#notifyWorkersDaily').is(':checked') ? 1 : 0,
            notifyOwnerDaily: $('#notifyOwnerDaily').is(':checked') ? 1 : 0,
            notifyHour: $('#notifyHour').val(),
        },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success('Notifications saved.');
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: done
    });
});
