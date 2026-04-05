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

    @php
        $settings = $form->formSettings ?? [];
        $submitColor = $settings['submitButtonColor'] ?? '#2e7d32';
        $submitText = $settings['submitButtonText'] ?? 'Submit';
        $successMessage = $settings['successMessage'] ?? 'Thank you for your submission!';
        $pageContent = $form->pageContent ?? [];
    @endphp

    <style>
        :root {
            --primary-green: #2e7d32;
            --dark-green: #1b5e20;
            --light-green: #4caf50;
            --submit-color: {{ $submitColor }};
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            background-color: #f5f7f5;
        }

        /* ============ SPLIT LAYOUT ============ */
        .split-container {
            display: flex;
            min-height: 100vh;
        }

        /* ============ LEFT PANEL ============ */
        .content-panel {
            width: 55%;
            background: linear-gradient(160deg, var(--dark-green) 0%, var(--primary-green) 50%, #388e3c 100%);
            color: #fff;
            padding: 3rem 3.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Decorative leaf shapes via CSS */
        .content-panel::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.04);
        }

        .content-panel::after {
            content: '';
            position: absolute;
            bottom: -60px;
            left: -60px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
        }

        .content-inner {
            position: relative;
            z-index: 1;
            max-width: 560px;
        }

        /* Decorative leaf accents */
        .leaf-accent {
            position: absolute;
            opacity: 0.06;
            z-index: 0;
        }

        .leaf-accent-1 {
            top: 10%;
            right: 5%;
            width: 120px;
            height: 120px;
            border: 3px solid #fff;
            border-radius: 0 50% 50% 50%;
            transform: rotate(-45deg);
        }

        .leaf-accent-2 {
            bottom: 15%;
            right: 15%;
            width: 80px;
            height: 80px;
            border: 2px solid #fff;
            border-radius: 0 50% 50% 50%;
            transform: rotate(30deg);
        }

        .leaf-accent-3 {
            top: 40%;
            left: 3%;
            width: 60px;
            height: 60px;
            border: 2px solid #fff;
            border-radius: 50% 0 50% 50%;
            transform: rotate(15deg);
        }

        /* Content block styles */
        .content-block {
            margin-bottom: 1.5rem;
        }

        .content-block:last-child {
            margin-bottom: 0;
        }

        .content-heading {
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 0.75rem;
        }

        .content-heading.h1 { font-size: 2.25rem; }
        .content-heading.h2 { font-size: 1.75rem; }
        .content-heading.h3 { font-size: 1.375rem; }

        .content-paragraph {
            font-size: 1.0625rem;
            line-height: 1.7;
            opacity: 0.92;
            margin-bottom: 0.5rem;
        }

        .content-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .content-list li {
            padding: 0.5rem 0;
            padding-left: 2rem;
            position: relative;
            font-size: 1.0625rem;
            line-height: 1.6;
            opacity: 0.92;
        }

        .content-list li::before {
            content: '\ea41'; /* bx-check-circle */
            font-family: 'boxicons';
            position: absolute;
            left: 0;
            top: 0.5rem;
            color: #a5d6a7;
            font-size: 1.2rem;
        }

        .content-divider {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin: 1.5rem 0;
        }

        .content-image {
            margin-bottom: 0.5rem;
        }

        .content-image img {
            max-width: 100%;
            border-radius: 0.75rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .content-image-alt {
            font-size: 0.875rem;
            opacity: 0.7;
            margin-top: 0.5rem;
        }

        /* ============ RIGHT PANEL ============ */
        .form-panel {
            width: 45%;
            background: #f8faf8;
            padding: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
        }

        .form-wrapper {
            width: 100%;
            max-width: 480px;
        }

        .form-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .form-card-header {
            padding: 1.75rem 2rem 1rem;
        }

        .form-card-header h2 {
            font-size: 1.375rem;
            font-weight: 700;
            color: #2d3436;
            margin-bottom: 0.375rem;
        }

        .form-card-header p {
            font-size: 0.9375rem;
            color: #636e72;
            margin-bottom: 0;
        }

        .form-card-body {
            padding: 0.75rem 2rem 2rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.15);
        }

        .form-check-input:checked {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .btn-submit {
            background-color: var(--submit-color);
            border-color: var(--submit-color);
            color: #fff;
            padding: 0.75rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            width: 100%;
        }

        .btn-submit:hover {
            background-color: var(--submit-color);
            border-color: var(--submit-color);
            color: #fff;
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            transform: none;
            box-shadow: none;
        }

        /* Form field styles */
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

        /* ============ SUCCESS STATE ============ */
        .success-state {
            text-align: center;
            padding: 3rem 2rem;
        }

        .success-state .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a5d6a7, #66bb6a);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .success-state .success-icon i {
            font-size: 2.5rem;
            color: #fff;
        }

        .success-state h2 {
            color: #2d3436;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .success-state p {
            color: #636e72;
            font-size: 1rem;
            line-height: 1.6;
        }

        /* ============ RESPONSIVE ============ */
        @media (max-width: 991.98px) {
            .split-container {
                flex-direction: column;
            }

            .content-panel {
                width: 100%;
                padding: 2.5rem 2rem;
                min-height: auto;
            }

            .content-heading.h1 { font-size: 1.75rem; }
            .content-heading.h2 { font-size: 1.5rem; }

            .form-panel {
                width: 100%;
                padding: 2rem 1.5rem;
            }

            .form-wrapper {
                max-width: 100%;
            }

            .leaf-accent {
                display: none;
            }
        }

        @media (max-width: 575.98px) {
            .content-panel {
                padding: 2rem 1.25rem;
            }

            .form-panel {
                padding: 1.5rem 1rem;
            }

            .form-card-header,
            .form-card-body {
                padding-left: 1.25rem;
                padding-right: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="split-container">
        {{-- ==================== LEFT PANEL: CONTENT ==================== --}}
        <div class="content-panel">
            {{-- Decorative leaf accents --}}
            <div class="leaf-accent leaf-accent-1"></div>
            <div class="leaf-accent leaf-accent-2"></div>
            <div class="leaf-accent leaf-accent-3"></div>

            <div class="content-inner">
                @foreach($pageContent as $block)
                    <div class="content-block">
                        @switch($block['type'] ?? '')
                            @case('heading')
                                @php
                                    $tag = $block['tag'] ?? 'h2';
                                    $tagClass = $tag;
                                @endphp
                                <{{ $tag }} class="content-heading {{ $tagClass }}">{{ $block['content'] ?? '' }}</{{ $tag }}>
                                @break

                            @case('paragraph')
                                <p class="content-paragraph">{{ $block['content'] ?? '' }}</p>
                                @break

                            @case('list')
                                <ul class="content-list">
                                    @foreach(($block['items'] ?? []) as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                                @break

                            @case('image')
                                <div class="content-image">
                                    <img src="{{ $block['src'] ?? '' }}" alt="{{ $block['alt'] ?? '' }}">
                                    @if(!empty($block['alt']))
                                        <p class="content-image-alt">{{ $block['alt'] }}</p>
                                    @endif
                                </div>
                                @break

                            @case('divider')
                                <hr class="content-divider">
                                @break
                        @endswitch
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ==================== RIGHT PANEL: FORM ==================== --}}
        <div class="form-panel">
            <div class="form-wrapper">
                <div class="form-card">
                    <div class="form-card-header">
                        <h2>{{ $form->formName }}</h2>
                        @if($form->formDescription)
                            <p>{{ $form->formDescription }}</p>
                        @endif
                    </div>

                    <div class="form-card-body">
                        <form id="publicForm" novalidate>
                            @csrf
                            <div class="row" id="formFields">
                                @foreach($form->formElements ?? [] as $element)
                                    @include('crm.forms.partials.public-field', ['element' => $element])
                                @endforeach
                            </div>
                        </form>

                        <div id="successState" class="success-state" style="display: none;">
                            <div class="success-icon">
                                <i class="bx bx-check"></i>
                            </div>
                            <h2>Salamat!</h2>
                            <p id="successMessage">{{ $successMessage }}</p>
                        </div>
                    </div>
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
            const originalBtnText = submitBtn.length ? submitBtn.html() : '';

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

                            // Scroll to first error
                            const firstError = $('.is-invalid').first();
                            if (firstError.length) {
                                firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
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
