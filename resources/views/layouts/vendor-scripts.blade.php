<!-- JAVASCRIPT -->
<script src="{{ URL::asset('build/libs/jquery/jquery.min.js')}}"></script>
<script>
    // Always attach the CURRENT csrf token (read live from the meta tag) to
    // every jQuery AJAX request. Reading it per-request — rather than once at
    // page load — means a token refreshed by the 419 handler below is picked
    // up immediately, so a stale token can never cause a mismatch.
    $.ajaxSetup({
        // credentials for same-origin requests, so the session cookie is
        // always sent (fixes intermittent "has_cookie:false" 419s).
        xhrFields: { withCredentials: true },
        beforeSend: function (xhr) {
            var token = $('meta[name="csrf-token"]').attr('content');
            if (token) xhr.setRequestHeader('X-CSRF-TOKEN', token);
        }
    });

    // Global 419 handler: refresh the CSRF token and retry the failed request
    // once. Handles both string and object request bodies.
    $(document).ajaxError(function (event, jqXHR, ajaxSettings, thrownError) {
        if (jqXHR.status !== 419 || ajaxSettings._csrfRetried) return;

        // A background poll must never be allowed to navigate the page. The
        // heartbeat and the screen pollers run on timers, so letting one of them
        // reload on a transient failure means a user who is simply reading the
        // screen gets thrown out mid-sentence with no idea why. Background
        // requests refresh their own token and otherwise give up quietly.
        var isBackground = !!ajaxSettings._background;

        $.get('/csrf-token').done(function (data) {
            if (!data || !data.token) {
                // The session is genuinely gone (e.g. logged out elsewhere) —
                // /csrf-token returned a login redirect, not a token.
                if (!isBackground) window.location.reload();
                return;
            }
            // Publish the fresh token everywhere the app reads it from.
            $('meta[name="csrf-token"]').attr('content', data.token);
            if (typeof window.CSRF !== 'undefined') window.CSRF = data.token;

            ajaxSettings._csrfRetried = true;
            // Patch the token inside the request body — string OR object form.
            if (typeof ajaxSettings.data === 'string') {
                ajaxSettings.data = ajaxSettings.data.replace(/(_token=)[^&]*/, '$1' + encodeURIComponent(data.token));
            } else if (ajaxSettings.data && typeof ajaxSettings.data === 'object' && '_token' in ajaxSettings.data) {
                ajaxSettings.data._token = data.token;
            }
            ajaxSettings.headers = ajaxSettings.headers || {};
            ajaxSettings.headers['X-CSRF-TOKEN'] = data.token;
            $.ajax(ajaxSettings);
        }).fail(function () {
            // Couldn't even fetch a token. For a real user action that means the
            // session has ended and a reload sends them to the login page; for a
            // timer in the background it usually just means the network blipped,
            // and reloading would throw away whatever they were doing.
            if (!isBackground) window.location.reload();
        });
    });
</script>
<script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ URL::asset('build/libs/metismenu/metisMenu.min.js')}}"></script>
<script src="{{ URL::asset('build/libs/simplebar/simplebar.min.js')}}"></script>
<script src="{{ URL::asset('build/libs/node-waves/waves.min.js')}}"></script>
<script>
    $('#change-password').on('submit',function(event){
        event.preventDefault();
        var Id = $('#data_id').val();
        var current_password = $('#current-password').val();
        var password = $('#change-password').val();
        var password_confirm = $('#password-confirm').val();
        $('#current_passwordError').text('');
        $('#passwordError').text('');
        $('#password_confirmError').text('');
        $.ajax({
            url: "{{ url('update-password') }}" + "/" + Id,
            type:"POST",
            data:{
                "current_password": current_password,
                "password": password,
                "password_confirmation": password_confirm,
                "_token": "{{ csrf_token() }}",
            },
            success:function(response){
                $('#current_passwordError').text('');
                $('#passwordError').text('');
                $('#password_confirmError').text('');
                if(response.isSuccess == false){
                    $('#current_passwordError').text(response.Message);
                }else if(response.isSuccess == true){
                    setTimeout(function () {
                        window.location.href = "{{ route('root') }}";
                    }, 1000);
                }
            },
            error: function(response) {
                $('#current_passwordError').text(response.responseJSON.errors.current_password);
                $('#passwordError').text(response.responseJSON.errors.password);
                $('#password_confirmError').text(response.responseJSON.errors.password_confirmation);
            }
        });
    });
</script>

@yield('script')

<!-- App js -->
<script src="{{ URL::asset('build/js/app.js')}}"></script>

<!-- Sidebar state persistence -->
<script>
(function() {
    // Apply body classes for sidebar state (after app.js initializes)
    var sidebarState = localStorage.getItem('sidebar-collapsed');
    if (sidebarState === 'true') {
        document.body.classList.add('vertical-collpsed');
    }

    // Save sidebar state when toggled
    var menuBtn = document.getElementById('vertical-menu-btn');
    if (menuBtn) {
        menuBtn.addEventListener('click', function() {
            setTimeout(function() {
                var isCollapsed = document.body.classList.contains('vertical-collpsed');
                localStorage.setItem('sidebar-collapsed', isCollapsed);
                // Remove the initial collapse class after first interaction
                document.documentElement.classList.remove('sidebar-will-collapse');
            }, 100);
        });
    }
})();
</script>

<!-- Ensure submenu visibility -->
<script>
$(document).ready(function() {
    // Ensure initially active menus are shown properly
    $('#sidebar-menu li.mm-active').each(function() {
        $(this).children('ul.sub-menu').addClass('mm-show');
    });
});
</script>

<!-- Mobile sidebar overlay click handler -->
<script>
$(document).ready(function() {
    // GLOBAL FIX: Move ALL modals to body to escape .page-content stacking context
    // The .page-content has transform animation which creates a new stacking context,
    // trapping modals inside and preventing proper z-index layering
    $('.modal').appendTo('body');

    // Close sidebar when clicking the overlay
    $('#sidebar-overlay').on('click', function() {
        $('body').removeClass('sidebar-enable');
    });

    // Close sidebar when pressing Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('body').hasClass('sidebar-enable')) {
            $('body').removeClass('sidebar-enable');
        }
    });

    // Close sidebar when clicking a menu item on mobile (optional - improves UX)
    if ($(window).width() < 992) {
        $('#sidebar-menu a:not(.has-arrow)').on('click', function() {
            setTimeout(function() {
                $('body').removeClass('sidebar-enable');
            }, 150);
        });
    }

    // IMPORTANT: Close navigation sidebar and hide overlay when ANY modal opens
    // This prevents the overlay from blocking modal interactions on all devices
    $(document).on('show.bs.modal', '.modal', function() {
        $('body').removeClass('sidebar-enable');
        $('#sidebar-overlay').css('visibility', 'hidden');
    });

    // Restore overlay visibility when modal closes
    $(document).on('hidden.bs.modal', '.modal', function() {
        $('#sidebar-overlay').css('visibility', '');
    });
});
</script>

<!-- Admin heartbeat for chat auto-reply detection -->
@auth
<script>
(function() {
    var timer = null;
    var consecutiveFailures = 0;

    function sendHeartbeat() {
        $.ajax({
            url: '/admin-heartbeat',
            type: 'POST',
            // Flagged so the global 419 handler above never reloads the page on
            // this request's behalf.
            _background: true,
            success: function () {
                consecutiveFailures = 0;
            },
            error: function () {
                // Give up after a minute of failures. A heartbeat that cannot
                // get through will not start working by being retried every
                // twenty seconds, and the log filled with thousands of them.
                if (++consecutiveFailures >= 3 && timer) {
                    clearInterval(timer);
                    timer = null;
                }
            }
        });
    }

    sendHeartbeat();
    timer = setInterval(sendHeartbeat, 20000);
})();
</script>
@endauth

@yield('script-bottom')
