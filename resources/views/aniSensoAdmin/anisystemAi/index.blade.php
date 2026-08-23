@extends('layouts.master')

@section('title') AniSystem AI @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
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
    /* The thread reader: the client on the left, the technician on the right,
       the same way the app itself draws it. */
    .conv-turn { display: flex; gap: 10px; margin-bottom: 14px; }
    .conv-turn.is-ai { flex-direction: row-reverse; }
    .conv-bubble { max-width: 78%; padding: 10px 12px; border-radius: 12px; font-size: 13px;
        line-height: 1.55; white-space: pre-wrap; word-break: break-word; }
    .conv-turn.is-user .conv-bubble { background: #f1f3f7; color: #2a3042; }
    .conv-turn.is-ai .conv-bubble { background: #e8f3e0; color: #23301a; }
    .conv-meta { font-size: 11px; color: #74788d; margin-top: 4px; }
    .conv-empty { padding: 28px 0; text-align: center; color: #74788d; }
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

    {{-- Two halves of one module: what the technician answered, and how it is
         set up to answer. --}}
    <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#aiConversationsTab" role="tab">
                <i class="bx bx-conversation me-1"></i> Conversations
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#aiSettingsTab" role="tab">
                <i class="bx bx-cog me-1"></i> Settings
            </a>
        </li>
    </ul>

    <div class="tab-content">
    {{-- ============================================ Conversations ========= --}}
    <div class="tab-pane active" id="aiConversationsTab" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h4 class="card-title mb-1">Client conversations</h4>
                        <p class="card-title-desc mb-0">
                            Every thread with the technician — the personal chats and the Collab Room's
                            team sessions. Read-only.
                        </p>
                    </div>
                    <button type="button" class="btn btn-light btn-sm" id="convReload"><i class="bx bx-refresh"></i> Refresh</button>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label mb-1" for="convSearch">Search</label>
                        <input type="text" class="form-control" id="convSearch" placeholder="Title, client, email or season…">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1" for="convKind">Kind</label>
                        <select class="form-select" id="convKind">
                            <option value="">All</option>
                            <option value="personal">Personal</option>
                            <option value="team">Team (Collab Room)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1" for="convLinked">Season</label>
                        <select class="form-select" id="convLinked">
                            <option value="">Any</option>
                            <option value="yes">Attached to one</option>
                            <option value="no">Not attached</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1" for="convFrom">From</label>
                        <input type="date" class="form-control" id="convFrom">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1" for="convTo">To</label>
                        <input type="date" class="form-control" id="convTo">
                    </div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="convHideEmpty" checked>
                    <label class="form-check-label" for="convHideEmpty">Hide threads with no messages</label>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-hover" id="convTable" style="width:100%">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Conversation</th>
                                <th>Kind</th>
                                <th>Season</th>
                                <th class="text-end">Messages</th>
                                <th class="text-end">Credits</th>
                                <th>Last activity</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================= Settings ========= --}}
    <div class="tab-pane" id="aiSettingsTab" role="tabpanel">
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
                        {{-- Whose /storage this is depends on which disk the
                             file landed on, and only AnisystemMedia knows. --}}
                        <img id="avatarPreview" class="ai-avatar-preview"
                             src="{{ \App\Support\AnisystemMedia::url($settings->avatarPath)
                                ?: URL::asset('build/images/users/avatar-1.jpg') }}" alt="AI avatar">
                        <div class="flex-grow-1">
                            <input type="file" class="form-control form-control-sm" id="avatarFile" accept="image/*">
                            <div class="form-text">JPG, PNG or WebP · up to 2MB</div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="avatarUploadBtn">Upload</button>
                        </div>
                    </div>
                    @unless ($settings->avatarPath)
                        <p class="text-muted font-size-12 mb-0 mt-3">
                            No avatar set — AniSystem shows a placeholder icon until one is uploaded.
                            The same picture is used by the AI chat, the floating chat bubble and the
                            AI Technician's replies in the community discussions.
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
    </div>{{-- /#aiSettingsTab --}}
    </div>{{-- /.tab-content --}}

    {{-- One thread, turn by turn. --}}
    <div class="modal fade" id="convModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="convModalTitle">Conversation</h5>
                        <small class="text-secondary" id="convModalSub"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="convModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script>
$(function () {
    /* ---------------------------------------------- Conversations tab ---- */
    const escConv = (v) => $('<div>').text(v == null ? '' : v).html();

    const convTable = $('#convTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        // The filter row above IS the search; DataTables' own box sends a
        // parameter this endpoint does not read, so it would be a field that
        // looks like it works and does nothing.
        searching: false,
        order: [[6, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('anisenso-ai-conversations.data') }}",
            type: 'GET',
            data: function (d) {
                d.searchFilter = $('#convSearch').val();
                d.kind = $('#convKind').val();
                d.linked = $('#convLinked').val();
                d.from = $('#convFrom').val();
                d.to = $('#convTo').val();
                d.empty = $('#convHideEmpty').is(':checked') ? 'hide' : '';
            },
            error: function () { toastr.error('Could not load conversations.', 'Error'); }
        },
        columns: [
            { data: 'clientName', name: 'clientName', render: function (d, t, row) {
                return '<div class="fw-semibold text-dark">' + escConv(d || '—') + '</div>' +
                       '<small class="text-secondary">' + escConv(row.clientEmail || '') + '</small>';
            } },
            { data: 'title', name: 'title', render: function (d) {
                return '<span class="text-dark">' + escConv(d || 'Untitled') + '</span>';
            } },
            { data: 'kind', name: 'kind', orderable: false, render: function (d) {
                return d === 'team'
                    ? '<span class="badge bg-info">Team</span>'
                    : '<span class="badge bg-light text-secondary">Personal</span>';
            } },
            { data: 'scheduleTitle', name: 'scheduleTitle', orderable: false, render: function (d) {
                return d ? escConv(d) : '<span class="text-secondary">—</span>';
            } },
            { data: 'messageCount', name: 'messageCount', className: 'text-end',
              render: function (d) { return Number(d || 0).toLocaleString(); } },
            { data: 'credits', name: 'credits', className: 'text-end', render: function (d) {
                const n = Number(d || 0);
                return n ? n.toFixed(2) : '<span class="text-secondary">—</span>';
            } },
            { data: 'lastAt', name: 'lastAt', render: function (d) { return escConv(d); } },
            { data: null, orderable: false, searchable: false, className: 'text-end', render: function (d, t, row) {
                return '<button type="button" class="btn btn-sm btn-outline-primary conv-open" ' +
                       'data-id="' + row.id + '" data-kind="' + escConv(row.kind) + '">Read</button>';
            } }
        ]
    });

    // Typing is a sentence, not a search per letter.
    let convTyping = null;
    $('#convSearch').on('keyup', function () {
        clearTimeout(convTyping);
        convTyping = setTimeout(() => convTable.ajax.reload(), 350);
    });
    $('#convKind, #convLinked, #convFrom, #convTo, #convHideEmpty').on('change', () => convTable.ajax.reload());
    $('#convReload').on('click', () => convTable.ajax.reload(null, false));

    // ---- One thread, turn by turn -------------------------------------
    $('#convTable').on('click', '.conv-open', function () {
        const id = $(this).data('id');
        const kind = $(this).data('kind');
        $('#convModalTitle').text('Conversation');
        $('#convModalSub').text('');
        $('#convModalBody').html('<div class="conv-empty"><i class="bx bx-loader-alt bx-spin fs-3"></i></div>');
        $('#convModal').modal('show');

        $.get('{{ url('/anisenso-ai-conversations') }}/' + id, { kind: kind }, function (res) {
            if (!res.success) { $('#convModalBody').html('<div class="conv-empty">' + escConv(res.message || 'Not found.') + '</div>'); return; }
            const head = res.data.head, turns = res.data.turns || [];
            $('#convModalTitle').text(head.title || 'Untitled');
            $('#convModalSub').text([head.clientName || head.clientEmail, head.scheduleTitle, head.startedAt]
                .filter(Boolean).join(' · '));
            if (!turns.length) { $('#convModalBody').html('<div class="conv-empty">Nothing was said in this thread.</div>'); return; }
            $('#convModalBody').html(turns.map(function (t) {
                const ai = t.role === 'assistant';
                const meta = [t.who, t.at, t.hasPhoto ? '📷 photo' : null,
                              t.credits ? t.credits.toFixed(2) + ' credits' : null].filter(Boolean).join(' · ');
                return '<div class="conv-turn ' + (ai ? 'is-ai' : 'is-user') + '">' +
                       '<div><div class="conv-bubble">' + escConv(t.content) + '</div>' +
                       (meta ? '<div class="conv-meta">' + escConv(meta) + '</div>' : '') + '</div></div>';
            }).join(''));
        }).fail(function () {
            $('#convModalBody').html('<div class="conv-empty">Could not read that conversation.</div>');
        });
    });

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
                // The address comes back with the answer: composing it here
                // would mean guessing which of the two disks it landed on.
                $('#avatarPreview').attr('src', res.data.url + '?t=' + Date.now());
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
