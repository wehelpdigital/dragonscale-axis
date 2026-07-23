@extends('layouts.master')

@section('title') AniSystem AI @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    #systemPrompt { font-family: monospace; font-size: 12px; }
    .ai-avatar-preview {
        width: 72px; height: 72px; border-radius: 50%; object-fit: cover;
        border: 2px solid #eff2f7; background: #f8f9fa;
    }
    .key-state { font-size: 12px; font-weight: 600; }
    .stat-tile { background: #f8f9fa; border: 1px solid #eff2f7; border-radius: 6px; padding: 12px 14px; }
    .stat-tile .label { font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: #74788d; }
    .stat-tile .value { font-size: 20px; font-weight: 700; color: #2a3042; }
    .pack-row input { min-width: 0; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('title') AniSystem AI @endslot
    @endcomponent

    @if (! $secretConfigured)
        <div class="alert alert-warning">
            <strong>ANISYSTEM_AI_KEY_SECRET is not set.</strong>
            The provider API key is encrypted with a secret shared between this app and AniSystem.
            Add the same <code>ANISYSTEM_AI_KEY_SECRET=...</code> line to both <code>.env</code> files,
            then <code>php artisan config:clear</code> in each. Until then a key cannot be stored.
        </div>
    @endif

    <div class="row">
        {{-- ------------------------------------------------ Settings --}}
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h4 class="card-title mb-1">Agricultural AI Technician</h4>
                            <p class="card-title-desc mb-0">
                                Answers crop questions inside AniSystem. Usage is metered in AI Credits.
                            </p>
                        </div>
                        <div class="form-check form-switch form-switch-lg">
                            <input class="form-check-input" type="checkbox" id="isEnabled" {{ $settings->isEnabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="isEnabled">Live</label>
                        </div>
                    </div>

                    <form id="aiSettingsForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="provider">Provider</label>
                                <select class="form-select" id="provider">
                                    @foreach (App\Models\AsAiSetting::PROVIDERS as $key => $label)
                                        <option value="{{ $key }}" @selected($settings->provider === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="model">Model</label>
                                <input type="text" class="form-control" id="model" value="{{ $settings->model }}" placeholder="claude-sonnet-5">
                                <div class="form-text">Leave blank to use the provider's default.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="apiKey">API key</label>
                            <input type="password" class="form-control" id="apiKey" autocomplete="new-password"
                                   placeholder="{{ $settings->hasKey() ? 'A key is stored — type a new one to replace it' : 'Paste the provider API key' }}">
                            <div class="form-text">
                                @if ($settings->hasKey())
                                    <span class="key-state text-success"><i class="mdi mdi-lock-check"></i> A key is stored and encrypted.</span>
                                @else
                                    <span class="key-state text-danger"><i class="mdi mdi-lock-open-alert"></i> No key stored — the AI cannot answer yet.</span>
                                @endif
                                It is never displayed again after saving.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="assistantName">Assistant name</label>
                            <input type="text" class="form-control" id="assistantName" value="{{ $settings->assistantName }}" maxlength="100">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="systemPrompt">Prompt</label>
                            <textarea class="form-control" id="systemPrompt" rows="16">{{ $settings->systemPrompt }}</textarea>
                            <div class="form-text">
                                This is what keeps the assistant to crop questions. Editing it changes what it will and will not answer.
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="maxOutputTokens">Max answer length (tokens)</label>
                                <input type="number" class="form-control" id="maxOutputTokens" value="{{ $settings->maxOutputTokens }}" min="100" max="8192">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="temperature">Temperature</label>
                                <input type="number" class="form-control" id="temperature" value="{{ $settings->temperature }}" step="0.1" min="0" max="2">
                                <div class="form-text">Lower is more factual. 0.3 suits technical answers.</div>
                            </div>
                        </div>

                        <hr>
                        <h5 class="font-size-15 mb-3">What a question costs the client</h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label" for="creditsPerInputK">Credits per 1k input</label>
                                <input type="number" class="form-control" id="creditsPerInputK" value="{{ $settings->creditsPerInputK }}" step="0.01" min="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label" for="creditsPerOutputK">Credits per 1k output</label>
                                <input type="number" class="form-control" id="creditsPerOutputK" value="{{ $settings->creditsPerOutputK }}" step="0.01" min="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label" for="creditsPerImage">Credits per photo</label>
                                <input type="number" class="form-control" id="creditsPerImage" value="{{ $settings->creditsPerImage }}" step="0.01" min="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label" for="freeCreditsOnSignup">Free credits on signup</label>
                                <input type="number" class="form-control" id="freeCreditsOnSignup" value="{{ $settings->freeCreditsOnSignup }}" min="0">
                            </div>
                        </div>
                        <div class="alert alert-light border" id="costPreview"></div>

                        <button type="submit" class="btn btn-primary" id="saveBtn">
                            <i class="mdi mdi-content-save"></i> Save AI settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------ Side --}}
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Avatar</h5>
                    <div class="d-flex align-items-center gap-3">
                        <img id="avatarPreview" class="ai-avatar-preview"
                             src="{{ $settings->avatarPath
                                ? rtrim(config('anisystem.url'), '/').'/storage/'.$settings->avatarPath
                                : URL::asset('build/images/users/avatar-1.jpg') }}" alt="AI avatar">
                        <div class="flex-grow-1">
                            <input type="file" class="form-control form-control-sm" id="avatarFile" accept="image/*">
                            <div class="form-text">JPG, PNG or WebP · up to 2MB</div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="avatarUploadBtn">Upload</button>
                        </div>
                    </div>
                    @unless ($settings->avatarPath)
                        <p class="text-muted font-size-12 mb-0 mt-3">
                            No avatar set — AniSystem shows a placeholder icon until one is uploaded.
                        </p>
                    @endunless
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Usage</h5>
                    <div class="row g-2">
                        <div class="col-6"><div class="stat-tile"><div class="label">Answers</div><div class="value">{{ number_format($usage->answers ?? 0) }}</div></div></div>
                        <div class="col-6"><div class="stat-tile"><div class="label">Credits spent</div><div class="value">{{ number_format((float) ($usage->credits ?? 0), 0) }}</div></div></div>
                        <div class="col-6"><div class="stat-tile"><div class="label">Input tokens</div><div class="value">{{ number_format($usage->tokensIn ?? 0) }}</div></div></div>
                        <div class="col-6"><div class="stat-tile"><div class="label">Output tokens</div><div class="value">{{ number_format($usage->tokensOut ?? 0) }}</div></div></div>
                        <div class="col-6"><div class="stat-tile"><div class="label">Credits sold</div><div class="value">{{ number_format((float) ($creditsSold->credits ?? 0), 0) }}</div></div></div>
                        <div class="col-6"><div class="stat-tile"><div class="label">Revenue</div><div class="value">₱{{ number_format((float) ($creditsSold->revenue ?? 0), 0) }}</div></div></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Credit packs</h5>
                    <form id="packsForm">
                        @foreach ($packs as $pack)
                            <div class="pack-row border rounded p-2 mb-2" data-id="{{ $pack->id }}">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <input type="text" class="form-control form-control-sm js-name" value="{{ $pack->packName }}" maxlength="100">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input js-active" type="checkbox" {{ $pack->isActive ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">Credits</span>
                                            <input type="number" class="form-control js-credits" value="{{ $pack->credits }}" min="1">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control js-price" value="{{ (float) $pack->price }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-2 js-desc" value="{{ $pack->description }}" maxlength="500" placeholder="Short description">
                                <div class="form-text js-per">₱{{ $pack->credits > 0 ? number_format($pack->price / $pack->credits, 2) : '0.00' }} per credit</div>
                            </div>
                        @endforeach
                        <button type="submit" class="btn btn-sm btn-primary w-100">Save packs</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(function () {
    const DEFAULT_MODELS = @json(App\Models\AsAiSetting::DEFAULT_MODELS);

    function num(id) { return parseFloat($('#' + id).val()) || 0; }

    // What a typical question will cost, so pricing is not set blind.
    function refreshCostPreview() {
        const text = num('creditsPerInputK') * 0.9 + num('creditsPerOutputK') * 0.5;
        const photo = text + num('creditsPerImage');
        $('#costPreview').html(
            'A typical text question (~900 input, ~500 output tokens) costs about <strong>'
            + text.toFixed(1) + ' credits</strong>; with a photo, about <strong>'
            + photo.toFixed(1) + ' credits</strong>. New members start with <strong>'
            + num('freeCreditsOnSignup') + '</strong> free credits (~'
            + (text > 0 ? Math.floor(num('freeCreditsOnSignup') / text) : 0) + ' questions).'
        );
    }
    $('#creditsPerInputK, #creditsPerOutputK, #creditsPerImage, #freeCreditsOnSignup').on('input', refreshCostPreview);
    refreshCostPreview();

    // Switching provider offers that provider's default model.
    $('#provider').on('change', function () {
        const current = $('#model').val().trim();
        const defaults = Object.values(DEFAULT_MODELS);
        if (current === '' || defaults.includes(current)) {
            $('#model').val(DEFAULT_MODELS[$(this).val()] || '');
        }
    });

    $('#aiSettingsForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#saveBtn').prop('disabled', true);

        $.ajax({
            url: "{{ route('anisenso-ai-settings.save') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                provider: $('#provider').val(),
                model: $('#model').val(),
                apiKey: $('#apiKey').val(),
                assistantName: $('#assistantName').val(),
                systemPrompt: $('#systemPrompt').val(),
                creditsPerInputK: $('#creditsPerInputK').val(),
                creditsPerOutputK: $('#creditsPerOutputK').val(),
                creditsPerImage: $('#creditsPerImage').val(),
                freeCreditsOnSignup: $('#freeCreditsOnSignup').val(),
                maxOutputTokens: $('#maxOutputTokens').val(),
                temperature: $('#temperature').val(),
                isEnabled: $('#isEnabled').is(':checked') ? 1 : 0,
            },
            success: function (res) {
                toastr.success(res.message);
                $('#apiKey').val('');
                if (res.data && res.data.hasKey) {
                    $('.key-state').removeClass('text-danger').addClass('text-success')
                        .html('<i class="mdi mdi-lock-check"></i> A key is stored and encrypted.');
                }
            },
            error: function (xhr) {
                toastr.error(xhr.responseJSON?.message || 'Could not save.');
                // A rejected "Live" toggle must not look like it stuck.
                if (xhr.status === 422) $('#isEnabled').prop('checked', {{ $settings->isEnabled ? 'true' : 'false' }});
            },
            complete: function () { $btn.prop('disabled', false); },
        });
    });

    $('#avatarUploadBtn').on('click', function () {
        const file = $('#avatarFile')[0].files[0];
        if (!file) { toastr.warning('Choose an image first.'); return; }
        const form = new FormData();
        form.append('avatar', file);
        form.append('_token', "{{ csrf_token() }}");

        const $btn = $(this).prop('disabled', true);
        $.ajax({
            url: "{{ route('anisenso-ai-settings.avatar') }}",
            method: 'POST', data: form, processData: false, contentType: false,
            success: function (res) {
                toastr.success(res.message);
                $('#avatarPreview').attr('src', "{{ rtrim(config('anisystem.url'), '/') }}/storage/" + res.data.path + '?t=' + Date.now());
            },
            error: function (xhr) { toastr.error(xhr.responseJSON?.message || 'Upload failed.'); },
            complete: function () { $btn.prop('disabled', false); },
        });
    });

    $('.pack-row').on('input', '.js-credits, .js-price', function () {
        const $row = $(this).closest('.pack-row');
        const credits = parseFloat($row.find('.js-credits').val()) || 0;
        const price = parseFloat($row.find('.js-price').val()) || 0;
        $row.find('.js-per').text('₱' + (credits > 0 ? (price / credits).toFixed(2) : '0.00') + ' per credit');
    });

    $('#packsForm').on('submit', function (e) {
        e.preventDefault();
        const packs = $('.pack-row').map(function () {
            const $r = $(this);
            return {
                id: $r.data('id'),
                packName: $r.find('.js-name').val(),
                credits: $r.find('.js-credits').val(),
                price: $r.find('.js-price').val(),
                description: $r.find('.js-desc').val(),
                isActive: $r.find('.js-active').is(':checked') ? 1 : 0,
            };
        }).get();

        $.ajax({
            url: "{{ route('anisenso-ai-settings.packs') }}",
            method: 'POST',
            data: { _token: "{{ csrf_token() }}", packs: packs },
            success: function (res) { toastr.success(res.message); },
            error: function (xhr) { toastr.error(xhr.responseJSON?.message || 'Could not save.'); },
        });
    });
});
</script>
@endsection
