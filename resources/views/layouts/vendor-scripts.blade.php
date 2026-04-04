<!-- JAVASCRIPT -->
<script src="{{ URL::asset('build/libs/jquery/jquery.min.js')}}"></script>
<script>
    // Global CSRF token setup for all jQuery AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Global 419 handler: refresh CSRF token and retry the failed request
    $(document).ajaxError(function(event, jqXHR, ajaxSettings, thrownError) {
        if (jqXHR.status === 419 && !ajaxSettings._csrfRetried) {
            // Fetch a fresh token from the server
            $.get('/csrf-token', function(data) {
                // Update the meta tag and ajaxSetup with the new token
                $('meta[name="csrf-token"]').attr('content', data.token);
                $.ajaxSetup({
                    headers: { 'X-CSRF-TOKEN': data.token }
                });

                // Retry the original request
                ajaxSettings._csrfRetried = true;
                if (ajaxSettings.data && typeof ajaxSettings.data === 'string') {
                    ajaxSettings.data = ajaxSettings.data.replace(/(_token=)[^&]*/, '$1' + encodeURIComponent(data.token));
                }
                ajaxSettings.headers = ajaxSettings.headers || {};
                ajaxSettings.headers['X-CSRF-TOKEN'] = data.token;
                $.ajax(ajaxSettings);
            });
        }
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
    function sendHeartbeat() {
        $.post('/admin-heartbeat');
    }
    sendHeartbeat();
    setInterval(sendHeartbeat, 20000);
})();
</script>
@endauth

@yield('script-bottom')
