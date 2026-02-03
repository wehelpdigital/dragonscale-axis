<!-- JAVASCRIPT -->
<script src="{{ URL::asset('build/libs/jquery/jquery.min.js')}}"></script>
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

@yield('script-bottom')
