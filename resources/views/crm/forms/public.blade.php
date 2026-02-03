<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->formName }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Box Icons -->
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: {{ $form->formSettings['submitButtonColor'] ?? '#556ee6' }};
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-wrapper {
            width: 100%;
            max-width: {{ ($form->formSettings['formWidth'] ?? 'medium') === 'small' ? '480px' : (($form->formSettings['formWidth'] ?? 'medium') === 'large' ? '800px' : '600px') }};
        }

        .form-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .form-header {
            background: var(--primary-color);
            color: #fff;
            padding: 2rem;
            text-align: center;
        }

        .form-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            opacity: 0.9;
            margin-bottom: 0;
            font-size: 0.9375rem;
        }

        .form-body {
            padding: 2rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-submit {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: #fff;
            padding: 0.75rem 2rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .btn-submit:disabled {
            opacity: 0.7;
        }

        .success-state {
            text-align: center;
            padding: 3rem 2rem;
        }

        .success-state i {
            font-size: 4rem;
            color: #34c38f;
            margin-bottom: 1rem;
        }

        .success-state h2 {
            color: #495057;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .success-state p {
            color: #74788d;
        }

        .field-error {
            font-size: 0.8125rem;
            color: #dc3545;
            margin-top: 0.25rem;
        }

        .required-indicator {
            color: #dc3545;
        }

        .form-divider {
            border-top: 1px solid #e9ecef;
            margin: 1.5rem 0;
        }

        .form-heading {
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .form-paragraph {
            color: #74788d;
            margin-bottom: 1rem;
        }

        .form-image {
            margin-bottom: 1rem;
        }

        .form-image-caption {
            color: #6c757d;
        }

        .form-video {
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        @media (max-width: 576px) {
            body {
                padding: 1rem;
            }
            .form-body {
                padding: 1.5rem;
            }
            .form-header {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="form-wrapper">
        <div class="form-card">
            <div class="form-header">
                <h1>{{ $form->formName }}</h1>
                @if($form->formDescription)
                <p>{{ $form->formDescription }}</p>
                @endif
            </div>

            <div class="form-body">
                <form id="publicForm" novalidate>
                    @csrf
                    <div class="row" id="formFields">
                        @foreach($form->formElements ?? [] as $element)
                            @include('crm.forms.partials.public-field', ['element' => $element])
                        @endforeach
                    </div>
                </form>

                <div id="successState" class="success-state" style="display: none;">
                    <i class="bx bx-check-circle"></i>
                    <h2>Thank You!</h2>
                    <p id="successMessage">{{ $form->formSettings['successMessage'] ?? 'Your submission has been received.' }}</p>
                </div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const form = $('#publicForm');
            const submitBtn = $('#submitBtn');
            const formSlug = '{{ $form->formSlug }}';
            const originalBtnText = submitBtn.html();

            form.on('submit', function(e) {
                e.preventDefault();

                // Clear previous errors
                $('.field-error').remove();
                $('.is-invalid').removeClass('is-invalid');

                // Disable submit button
                submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Submitting...');

                // Collect form data
                const formData = new FormData(this);

                $.ajax({
                    url: '/f/' + formSlug + '/submit',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success state
                            form.hide();
                            $('#successMessage').text(response.message);
                            $('#successState').show();

                            // Redirect if URL provided
                            if (response.redirect) {
                                setTimeout(function() {
                                    window.location.href = response.redirect;
                                }, 2000);
                            }
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON?.errors || {};
                            for (const [field, messages] of Object.entries(errors)) {
                                const input = $('[name="' + field + '"]');
                                input.addClass('is-invalid');
                                input.after('<div class="field-error">' + messages[0] + '</div>');
                            }
                        } else {
                            alert('An error occurred. Please try again.');
                        }
                        submitBtn.prop('disabled', false).html(originalBtnText);
                    }
                });
            });
        });
    </script>
</body>
</html>
