@extends('layouts.master')

@section('title') Lead Finder Settings @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .master-switch-card {
        border-left: 4px solid #f1b44c;
    }
    .master-switch-card.is-on {
        border-left-color: #34c38f;
    }
    .master-switch-card .form-check-input {
        width: 3.25rem;
        height: 1.6rem;
        cursor: pointer;
    }
    .secret-input {
        font-family: monospace;
        letter-spacing: .5px;
    }
    .secret-hint {
        font-family: monospace;
        font-size: 12px;
        color: #495057;
        word-break: break-all;
    }
    .form-section-title {
        font-size: 13px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid #f0f0f0;
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .form-section-title i {
        margin-right: 6px;
        color: #556ee6;
    }
    .test-panel {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 14px 16px;
    }
    .copy-box input {
        font-family: monospace;
        font-size: 12.5px;
        background: #f8f9fa;
        color: #2a3042;
    }
    .cap-meter {
        height: 8px;
        border-radius: 4px;
        background: #eff2f7;
        overflow: hidden;
    }
    .cap-meter span {
        display: block;
        height: 100%;
        background: #34c38f;
        transition: width .25s ease, background-color .25s ease;
    }
    .cap-meter.is-hot span {
        background: #f1b44c;
    }
    .cap-meter.is-max span {
        background: #f46a6a;
    }
    .warmup-day {
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 8px 4px;
        text-align: center;
        background: #fff;
    }
    .warmup-day .day-label {
        font-size: 11px;
        letter-spacing: .3px;
        text-transform: uppercase;
    }
    .warmup-day .day-value {
        font-size: 18px;
        font-weight: 600;
    }
    .dns-table td, .dns-table th {
        vertical-align: middle;
    }
    .dns-record {
        font-family: monospace;
        font-size: 11.5px;
        color: #495057;
        word-break: break-all;
    }
    .nav-tabs-custom .nav-link {
        font-weight: 500;
    }
    .save-bar {
        position: sticky;
        bottom: 0;
        z-index: 5;
        background: rgba(255, 255, 255, .96);
        border-top: 1px solid #e9ecef;
        padding: 12px 16px;
        margin: 0 -20px -20px;
        border-radius: 0 0 4px 4px;
    }
    @media (max-width: 575.98px) {
        .nav-tabs-custom .nav-link {
            padding: 8px 10px;
            font-size: 12.5px;
        }
        .save-bar {
            margin: 0 -12px -12px;
        }
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Lead Finder @endslot
        @slot('title') Settings @endslot
    @endcomponent

    {{--
        One form wraps the whole screen, master switch included, so a single serialize()
        posts every tab. Switching tabs never loses what was typed on another one.
    --}}
    <form id="outreachSettingsForm" autocomplete="off" novalidate>
        @csrf

        <!-- ============ MASTER KILL SWITCH ============ -->
        <div class="card master-switch-card {{ $settings->outreachEnabled ? 'is-on' : '' }}" id="masterSwitchCard">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-start">
                        <div class="form-check form-switch me-3 pt-1">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="outreachEnabled" name="outreachEnabled" value="1"
                                   {{ $settings->outreachEnabled ? 'checked' : '' }}>
                        </div>
                        <div>
                            <h5 class="mb-1 text-dark">
                                Enable outreach sending
                                <span id="masterSwitchBadge" class="ms-1">
                                    @if($settings->outreachEnabled)
                                        <span class="badge bg-success">Outreach ON</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Outreach OFF</span>
                                    @endif
                                </span>
                            </h5>
                            <p class="text-secondary mb-0" style="max-width: 720px;">
                                This is the master switch for the whole module. It ships <strong class="text-dark">OFF</strong>
                                and stays off until you turn it on here &mdash; nothing is emailed to a single lead while it is off.
                                Scraping and enrichment keep working either way, so you can build a list first and start
                                sending when you are ready.
                            </p>
                        </div>
                    </div>
                    <div class="text-lg-end">
                        <div class="text-secondary" style="font-size: 12px;">Today's effective cap</div>
                        <div class="h4 mb-0 text-dark"><span id="effectiveCapValue">{{ $effectiveDailyCap }}</span> <span class="text-secondary" style="font-size: 13px;">emails</span></div>
                    </div>
                </div>

                <div class="alert alert-warning mt-3 mb-0 d-flex align-items-start" role="alert">
                    <i class="bx bx-shield-quarter me-2 mt-1"></i>
                    <div class="text-dark">
                        The switch refuses to come on until SMTP is complete (host, username, password and From address).
                        Send to a domain you own first, then turn this on.
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ TABS ============ -->
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs nav-tabs-custom" role="tablist" id="settingsTabs">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'google' ? 'active' : '' }}" id="google-tab" data-tab-key="google"
                                data-bs-toggle="tab" data-bs-target="#tab-google" type="button" role="tab">
                            <i class="bx bx-key me-1"></i> Google &amp; AI
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'smtp' ? 'active' : '' }}" id="smtp-tab" data-tab-key="smtp"
                                data-bs-toggle="tab" data-bs-target="#tab-smtp" type="button" role="tab">
                            <i class="bx bx-mail-send me-1"></i> SMTP
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'imap' ? 'active' : '' }}" id="imap-tab" data-tab-key="imap"
                                data-bs-toggle="tab" data-bs-target="#tab-imap" type="button" role="tab">
                            <i class="bx bx-inbox me-1"></i> IMAP
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'verifier' ? 'active' : '' }}" id="verifier-tab" data-tab-key="verifier"
                                data-bs-toggle="tab" data-bs-target="#tab-verifier" type="button" role="tab">
                            <i class="bx bx-badge-check me-1"></i> Verifier
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'limits' ? 'active' : '' }}" id="limits-tab" data-tab-key="limits"
                                data-bs-toggle="tab" data-bs-target="#tab-limits" type="button" role="tab">
                            <i class="bx bx-shield me-1"></i> Limits &amp; Safety
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $activeTab === 'cron' ? 'active' : '' }}" id="cron-tab" data-tab-key="cron"
                                data-bs-toggle="tab" data-bs-target="#tab-cron" type="button" role="tab">
                            <i class="bx bx-time-five me-1"></i> Cron
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-1 pt-4">

                    <!-- ==================== GOOGLE & AI ==================== -->
                    <div class="tab-pane {{ $activeTab === 'google' ? 'active' : '' }}" id="tab-google" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="form-section-title"><i class="bx bx-map-pin"></i>Google Places</div>

                                <div class="mb-3">
                                    <label for="googlePlacesApiKey" class="form-label text-dark">Places API key</label>
                                    <input type="password" class="form-control secret-input" id="googlePlacesApiKey"
                                           name="googlePlacesApiKey" autocomplete="new-password"
                                           placeholder="{{ $maskedKeys['googlePlacesApiKey'] }}">
                                    <small class="text-body-secondary d-block mt-1">
                                        Stored: <span class="secret-hint">{{ $maskedKeys['googlePlacesApiKey'] }}</span>.
                                        Leave blank to keep the saved value.
                                    </small>
                                </div>

                                <div class="form-section-title mt-4"><i class="bx bx-search-alt"></i>Google Custom Search (email discovery)</div>

                                <div class="mb-3">
                                    <label for="googleSearchApiKey" class="form-label text-dark">Custom Search API key</label>
                                    <input type="password" class="form-control secret-input" id="googleSearchApiKey"
                                           name="googleSearchApiKey" autocomplete="new-password"
                                           placeholder="{{ $maskedKeys['googleSearchApiKey'] }}">
                                    <small class="text-body-secondary d-block mt-1">
                                        Stored: <span class="secret-hint">{{ $maskedKeys['googleSearchApiKey'] }}</span>.
                                        Leave blank to keep the saved value.
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <label for="googleSearchEngineId" class="form-label text-dark">Search engine ID (cx)</label>
                                    <input type="text" class="form-control" id="googleSearchEngineId" name="googleSearchEngineId"
                                           value="{{ $settings->googleSearchEngineId }}" maxlength="255" placeholder="a1b2c3d4e5f6g7h8i">
                                    <small class="text-body-secondary">Both the key and this ID are needed before a lead without a website can be searched for.</small>
                                </div>

                                <div class="form-section-title mt-4"><i class="bx bx-bot"></i>AI provider</div>

                                <div class="row">
                                    <div class="col-sm-6 mb-3">
                                        <label for="llmProvider" class="form-label text-dark">Provider</label>
                                        <select class="form-select" id="llmProvider" name="llmProvider">
                                            @foreach($providerLabels as $value => $label)
                                                <option value="{{ $value }}" {{ $settings->llmProvider === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label for="llmModel" class="form-label text-dark">Model <span class="text-secondary">(optional)</span></label>
                                        <input type="text" class="form-control" id="llmModel" name="llmModel"
                                               value="{{ $settings->llmModel }}" maxlength="120" placeholder="Leave blank for the provider default">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="llmApiKey" class="form-label text-dark">AI API key</label>
                                    <input type="password" class="form-control secret-input" id="llmApiKey"
                                           name="llmApiKey" autocomplete="new-password"
                                           placeholder="{{ $maskedKeys['llmApiKey'] }}">
                                    <small class="text-body-secondary d-block mt-1">
                                        Stored: <span class="secret-hint">{{ $maskedKeys['llmApiKey'] }}</span>.
                                        Leave blank to keep the saved value.
                                    </small>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="test-panel">
                                    <h6 class="text-dark mb-1"><i class="bx bx-plug me-1"></i>Connection tests</h6>
                                    <p class="text-secondary mb-3" style="font-size: 12.5px;">
                                        Save first &mdash; the tests read the stored keys, not what is on screen.
                                        A Places test spends one real API call.
                                    </p>

                                    <div class="mb-3">
                                        <label for="placesKeyword" class="form-label text-dark" style="font-size: 12.5px;">Probe keyword</label>
                                        <input type="text" class="form-control form-control-sm" id="placesKeyword" placeholder="restaurant" maxlength="80">
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-primary btn-sm test-btn"
                                                data-test-url="{{ route('outreach.settings.testPlaces') }}"
                                                data-result="#placesResult"
                                                data-payload="places">
                                            <i class="bx bx-map-pin me-1"></i> Test Places key
                                        </button>
                                        <div id="placesResult"></div>

                                        <button type="button" class="btn btn-primary btn-sm test-btn"
                                                data-test-url="{{ route('outreach.settings.testLlm') }}"
                                                data-result="#llmResult">
                                            <i class="bx bx-bot me-1"></i> Test AI provider
                                        </button>
                                        <div id="llmResult"></div>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3 mb-0" role="alert">
                                    <div class="text-dark" style="font-size: 12.5px;">
                                        <i class="bx bx-info-circle me-1"></i>
                                        The AI provider is only used to find hard-to-reach emails and to vary the wording of
                                        your templates. Everything else works without it.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== SMTP ==================== -->
                    <div class="tab-pane {{ $activeTab === 'smtp' ? 'active' : '' }}" id="tab-smtp" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="form-section-title"><i class="bx bx-server"></i>Outgoing mail server</div>

                                <div class="row">
                                    <div class="col-sm-8 mb-3">
                                        <label for="smtpHost" class="form-label text-dark">SMTP host <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="smtpHost" name="smtpHost"
                                               value="{{ $settings->smtpHost }}" maxlength="255" placeholder="smtp.yourdomain.com">
                                    </div>
                                    <div class="col-sm-4 mb-3">
                                        <label for="smtpPort" class="form-label text-dark">Port</label>
                                        <input type="number" class="form-control" id="smtpPort" name="smtpPort"
                                               value="{{ $settings->smtpPort }}" min="1" max="65535" placeholder="587">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-8 mb-3">
                                        <label for="smtpUsername" class="form-label text-dark">Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="smtpUsername" name="smtpUsername"
                                               value="{{ $settings->smtpUsername }}" maxlength="255" autocomplete="off">
                                    </div>
                                    <div class="col-sm-4 mb-3">
                                        <label for="smtpEncryption" class="form-label text-dark">Encryption</label>
                                        <select class="form-select" id="smtpEncryption" name="smtpEncryption">
                                            <option value="tls" {{ $settings->smtpEncryption === 'tls' ? 'selected' : '' }}>TLS (587)</option>
                                            <option value="ssl" {{ $settings->smtpEncryption === 'ssl' ? 'selected' : '' }}>SSL (465)</option>
                                            <option value="none" {{ $settings->smtpEncryption === 'none' ? 'selected' : '' }}>None</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="smtpPassword" class="form-label text-dark">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control secret-input" id="smtpPassword"
                                           name="smtpPassword" autocomplete="new-password"
                                           placeholder="{{ $maskedKeys['smtpPassword'] }}">
                                    <small class="text-body-secondary d-block mt-1">
                                        Stored: <span class="secret-hint">{{ $maskedKeys['smtpPassword'] }}</span>.
                                        Leave blank to keep the saved value.
                                    </small>
                                </div>

                                <div class="form-section-title mt-4"><i class="bx bx-user-voice"></i>Sender identity</div>

                                <div class="row">
                                    <div class="col-sm-6 mb-3">
                                        <label for="smtpFromName" class="form-label text-dark">From name</label>
                                        <input type="text" class="form-control" id="smtpFromName" name="smtpFromName"
                                               value="{{ $settings->smtpFromName }}" maxlength="255" placeholder="Dragon Scale Web">
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label for="smtpFromEmail" class="form-label text-dark">From email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="smtpFromEmail" name="smtpFromEmail"
                                               value="{{ $settings->smtpFromEmail }}" maxlength="255" placeholder="hello@yourdomain.com">
                                        <small class="text-body-secondary">The DNS check below reads its domain from this address.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="test-panel">
                                    <h6 class="text-dark mb-1"><i class="bx bx-plug me-1"></i>Connection &amp; DNS</h6>
                                    <p class="text-secondary mb-3" style="font-size: 12.5px;">
                                        Runs two checks: an authenticated SMTP login, then SPF / DKIM / DMARC / MX for your
                                        sending domain. Fill in a test address to also deliver a real message.
                                    </p>

                                    <div class="mb-3">
                                        <label for="smtpTestEmail" class="form-label text-dark" style="font-size: 12.5px;">Send a test email to <span class="text-secondary">(optional)</span></label>
                                        <input type="email" class="form-control form-control-sm" id="smtpTestEmail" placeholder="you@yourdomain.com" maxlength="255">
                                    </div>

                                    <div class="mb-3">
                                        <label for="dkimSelector" class="form-label text-dark" style="font-size: 12.5px;">DKIM selector</label>
                                        <input type="text" class="form-control form-control-sm" id="dkimSelector" value="default" maxlength="60">
                                        <small class="text-body-secondary">Google Workspace uses <code>google</code>, most panels use <code>default</code>.</small>
                                    </div>

                                    <div class="d-grid">
                                        <button type="button" class="btn btn-primary btn-sm" id="btnTestSmtpDns"
                                                data-smtp-url="{{ route('outreach.settings.testSmtp') }}"
                                                data-dns-url="{{ route('outreach.settings.testDns') }}">
                                            <i class="bx bx-check-shield me-1"></i> Test Connection &amp; DNS Check
                                        </button>
                                    </div>

                                    <div id="smtpResult" class="mt-3"></div>
                                    <div id="dnsResult" class="mt-3"></div>
                                </div>

                                <div class="mt-3" style="font-size: 12.5px;">
                                    <div class="text-secondary">
                                        Sending domain currently on file:
                                        <strong class="text-dark">{{ $sendingDomain !== '' ? $sendingDomain : 'not set' }}</strong>
                                    </div>
                                    <div class="text-secondary mt-1">
                                        Last connection test:
                                        {!! $settings->test_status_badge !!}
                                        @if($settings->lastTestedAt)
                                            <span class="text-dark ms-1">{{ $settings->lastTestedAt->format('M j, Y g:i A') }}</span>
                                        @endif
                                    </div>
                                    @if($settings->lastTestStatus === 'failed' && $settings->lastTestError)
                                        <div class="text-danger mt-1">{{ \Illuminate\Support\Str::limit($settings->lastTestError, 200) }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== IMAP ==================== -->
                    <div class="tab-pane {{ $activeTab === 'imap' ? 'active' : '' }}" id="tab-imap" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="form-section-title"><i class="bx bx-download"></i>Incoming mail (reply detection)</div>

                                <div class="row">
                                    <div class="col-sm-8 mb-3">
                                        <label for="imapHost" class="form-label text-dark">IMAP host</label>
                                        <input type="text" class="form-control" id="imapHost" name="imapHost"
                                               value="{{ $settings->imapHost }}" maxlength="255" placeholder="imap.yourdomain.com">
                                    </div>
                                    <div class="col-sm-4 mb-3">
                                        <label for="imapPort" class="form-label text-dark">Port</label>
                                        <input type="number" class="form-control" id="imapPort" name="imapPort"
                                               value="{{ $settings->imapPort }}" min="1" max="65535" placeholder="993">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-8 mb-3">
                                        <label for="imapUsername" class="form-label text-dark">Username</label>
                                        <input type="text" class="form-control" id="imapUsername" name="imapUsername"
                                               value="{{ $settings->imapUsername }}" maxlength="255" autocomplete="off">
                                    </div>
                                    <div class="col-sm-4 mb-3">
                                        <label for="imapEncryption" class="form-label text-dark">Encryption</label>
                                        <select class="form-select" id="imapEncryption" name="imapEncryption">
                                            <option value="ssl" {{ $settings->imapEncryption === 'ssl' ? 'selected' : '' }}>SSL (993)</option>
                                            <option value="tls" {{ $settings->imapEncryption === 'tls' ? 'selected' : '' }}>STARTTLS (143)</option>
                                            <option value="none" {{ $settings->imapEncryption === 'none' ? 'selected' : '' }}>None</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-8 mb-3">
                                        <label for="imapPassword" class="form-label text-dark">Password</label>
                                        <input type="password" class="form-control secret-input" id="imapPassword"
                                               name="imapPassword" autocomplete="new-password"
                                               placeholder="{{ $maskedKeys['imapPassword'] }}">
                                        <small class="text-body-secondary d-block mt-1">
                                            Stored: <span class="secret-hint">{{ $maskedKeys['imapPassword'] }}</span>.
                                            Leave blank to keep the saved value.
                                        </small>
                                    </div>
                                    <div class="col-sm-4 mb-3">
                                        <label for="imapFolder" class="form-label text-dark">Folder</label>
                                        <input type="text" class="form-control" id="imapFolder" name="imapFolder"
                                               value="{{ $settings->imapFolder }}" maxlength="120" placeholder="INBOX">
                                    </div>
                                </div>

                                <div class="alert alert-info mb-0" role="alert">
                                    <div class="text-dark" style="font-size: 12.5px;">
                                        <i class="bx bx-info-circle me-1"></i>
                                        Replies are read with <code>BODY.PEEK</code>, so nothing is marked as read on the
                                        server &mdash; your normal mail client still sees every message as new. Read state
                                        lives in the Lead Finder inbox instead.
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="test-panel">
                                    <h6 class="text-dark mb-1"><i class="bx bx-plug me-1"></i>Mailbox test</h6>
                                    <p class="text-secondary mb-3" style="font-size: 12.5px;">
                                        Signs in and opens the folder. Nothing is downloaded and nothing is marked read.
                                    </p>
                                    <div class="d-grid">
                                        <button type="button" class="btn btn-primary btn-sm test-btn"
                                                data-test-url="{{ route('outreach.settings.testImap') }}"
                                                data-result="#imapResult">
                                            <i class="bx bx-envelope-open me-1"></i> Test IMAP login
                                        </button>
                                    </div>
                                    <div id="imapResult" class="mt-3"></div>
                                </div>

                                <div class="alert alert-warning mt-3 mb-0" role="alert">
                                    <div class="text-dark" style="font-size: 12.5px;">
                                        <i class="bx bx-error-circle me-1"></i>
                                        Without IMAP the module still sends, but it can never see a reply &mdash; which means
                                        it can never stop a follow-up to someone who already answered.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== LIMITS & SAFETY ==================== -->
                    <!-- ==================== EMAIL VERIFIER ==================== -->
                    <div class="tab-pane {{ $activeTab === 'verifier' ? 'active' : '' }}" id="tab-verifier" role="tabpanel">
                        <div class="alert alert-light border">
                            <i class="bx bx-info-circle me-1 text-dark"></i>
                            <span class="text-dark">Every address found by the email hunt is checked here before
                            anything is sent to it. Only addresses the verifier confirms as deliverable are marked
                            good &mdash; catch-all, role (info@, sales@) and unknown results are kept but held back,
                            because a bounce costs sender reputation that is far harder to win back than a
                            verification credit.</span>
                        </div>

                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="verificationEnabled"
                                   name="verificationEnabled" value="1"
                                   {{ $settings->verificationEnabled ? 'checked' : '' }}>
                            <label class="form-check-label text-dark" for="verificationEnabled">
                                Verify addresses before sending
                            </label>
                            <div><small class="text-secondary">Turn this off and addresses skip straight to the send
                            queue unchecked.</small></div>
                        </div>

                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="requireVerifiedEmail"
                                   name="requireVerifiedEmail" value="1"
                                   {{ $settings->requireVerifiedEmail ? 'checked' : '' }}>
                            <label class="form-check-label text-dark" for="requireVerifiedEmail">
                                Only send to verified-good addresses
                            </label>
                            <div><small class="text-secondary">Recommended. With this off, the queue will mail an
                            address the verifier rejected.</small></div>
                        </div>

                        <div class="mb-3">
                            <label for="reoonApiKey" class="form-label text-dark">Reoon API Key</label>
                            <input type="password" class="form-control" id="reoonApiKey" name="reoonApiKey"
                                   autocomplete="new-password"
                                   placeholder="{{ $maskedKeys['reoonApiKey'] ?? 'Not configured' }}">
                            <small class="text-secondary">Leave blank to keep the saved value.</small>
                        </div>

                        <div class="mb-3">
                            <label for="verifierMode" class="form-label text-dark">Check Depth</label>
                            <select class="form-select" id="verifierMode" name="verifierMode">
                                <option value="power" {{ $settings->verifierMode === 'power' ? 'selected' : '' }}>
                                    Power &mdash; opens an SMTP conversation with the mail server (recommended)
                                </option>
                                <option value="quick" {{ $settings->verifierMode === 'quick' ? 'selected' : '' }}>
                                    Quick &mdash; syntax, domain and MX only
                                </option>
                            </select>
                            <small class="text-secondary">Quick is cheaper and faster, but it cannot tell a live
                            mailbox from a well-formed guess &mdash; which is the whole reason to verify before a
                            cold send.</small>
                        </div>

                        <button type="button" class="btn btn-primary btn-sm test-btn"
                                data-test-url="{{ route('outreach.settings.testVerifier') }}"
                                data-payload="verifier"
                                data-result="#verifierResult">
                            <i class="bx bx-badge-check me-1"></i> Test verifier key
                        </button>
                        <div id="verifierResult"></div>
                    </div>

                    <div class="tab-pane {{ $activeTab === 'limits' ? 'active' : '' }}" id="tab-limits" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-7">
                                <div class="form-section-title"><i class="bx bx-tachometer"></i>Daily volume</div>

                                <div class="mb-2">
                                    <label for="dailySendCap" class="form-label text-dark">
                                        Daily send cap <span class="text-danger">*</span>
                                        <span class="text-secondary" style="font-weight: 400;">(1 &ndash; 50)</span>
                                    </label>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-8">
                                            <input type="range" class="form-range" id="dailySendCapRange" min="1" max="50" step="1"
                                                   value="{{ (int) $settings->dailySendCap }}">
                                        </div>
                                        <div class="col-4">
                                            <input type="number" class="form-control" id="dailySendCap" name="dailySendCap"
                                                   min="1" max="50" step="1" value="{{ (int) $settings->dailySendCap }}" required>
                                        </div>
                                    </div>
                                    <div class="cap-meter mt-2" id="capMeter"><span style="width: 0;"></span></div>
                                    <div class="d-flex justify-content-between mt-1" style="font-size: 12px;">
                                        <span class="text-secondary">1 &mdash; safest</span>
                                        <span class="text-secondary">50 &mdash; hard maximum</span>
                                    </div>
                                    <div id="capAdvice" class="mt-2"></div>
                                    <small class="text-body-secondary d-block mt-1">
                                        50 a day is the ceiling this module will accept, and it is deliberate. A brand new
                                        domain pushing more than that reads as a spam source to Gmail and Outlook, and the
                                        damage to a domain's reputation takes months to undo.
                                    </small>
                                </div>

                                <div class="form-section-title mt-4"><i class="bx bx-time"></i>Sending window (Asia/Manila)</div>

                                <div class="row">
                                    <div class="col-sm-6 mb-3">
                                        <label for="sendWindowStart" class="form-label text-dark">Window opens <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="sendWindowStart" name="sendWindowStart"
                                               value="{{ substr((string) $settings->sendWindowStart, 0, 5) }}" required>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label for="sendWindowEnd" class="form-label text-dark">Window closes <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="sendWindowEnd" name="sendWindowEnd"
                                               value="{{ substr((string) $settings->sendWindowEnd, 0, 5) }}" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label text-dark">Sending days <span class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-3">
                                        @foreach($dayLabels as $dayNumber => $dayLabel)
                                            <div class="form-check">
                                                <input class="form-check-input send-day" type="checkbox" name="sendDays[]"
                                                       value="{{ $dayNumber }}" id="sendDay{{ $dayNumber }}"
                                                       {{ in_array($dayNumber, $selectedDays, true) ? 'checked' : '' }}>
                                                <label class="form-check-label text-dark" for="sendDay{{ $dayNumber }}">{{ substr($dayLabel, 0, 3) }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <small class="text-body-secondary">Weekday mornings get read. Weekend sends get deleted.</small>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6 mb-3">
                                        <label for="minDelayMinutes" class="form-label text-dark">Minimum gap between sends (minutes) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="minDelayMinutes" name="minDelayMinutes"
                                               min="0" max="1440" value="{{ (int) $settings->minDelayMinutes }}" required>
                                    </div>
                                    <div class="col-sm-6 mb-3">
                                        <label for="maxDelayMinutes" class="form-label text-dark">Maximum gap (minutes) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="maxDelayMinutes" name="maxDelayMinutes"
                                               min="0" max="1440" value="{{ (int) $settings->maxDelayMinutes }}" required>
                                        <small class="text-body-secondary">The cron picks a random gap in this range so the send pattern never looks mechanical.</small>
                                    </div>
                                </div>

                                <div class="form-section-title mt-4"><i class="bx bx-trending-up"></i>Warm-up ramp</div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="warmupEnabled"
                                           name="warmupEnabled" value="1" {{ $settings->warmupEnabled ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="warmupEnabled">Ease into the daily cap instead of starting at it</label>
                                </div>

                                <div class="alert alert-light border mb-3" role="alert">
                                    <div class="text-dark" style="font-size: 13px;">
                                        <strong>In plain language:</strong> on the first day the module sends only
                                        <strong id="warmupPlainStart">{{ (int) $settings->warmupStartCap }}</strong> emails.
                                        Every day after that it allows
                                        <strong id="warmupPlainIncrement">{{ (int) $settings->warmupIncrementPerDay }}</strong> more,
                                        and it keeps climbing until it reaches your daily cap of
                                        <strong id="warmupPlainCap">{{ (int) $settings->dailySendCap }}</strong> &mdash; which takes about
                                        <strong id="warmupPlainDays">&mdash;</strong> days. Mailbox providers trust a slow, steady
                                        increase; a domain that sends nothing for a year and then 50 in one morning gets filtered.
                                    </div>
                                </div>

                                <div class="row g-2 mb-3" id="warmupPreview"></div>

                                <div class="row">
                                    <div class="col-sm-4 mb-3">
                                        <label for="warmupStartCap" class="form-label text-dark">Day-one cap <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="warmupStartCap" name="warmupStartCap"
                                               min="1" max="50" value="{{ (int) $settings->warmupStartCap }}" required>
                                    </div>
                                    <div class="col-sm-4 mb-3">
                                        <label for="warmupIncrementPerDay" class="form-label text-dark">Added per day <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="warmupIncrementPerDay" name="warmupIncrementPerDay"
                                               min="0" max="50" value="{{ (int) $settings->warmupIncrementPerDay }}" required>
                                    </div>
                                    <div class="col-sm-4 mb-3">
                                        <label for="warmupStartedOn" class="form-label text-dark">Ramp started on</label>
                                        <input type="date" class="form-control" id="warmupStartedOn" name="warmupStartedOn"
                                               value="{{ $settings->warmupStartedOn ? \Carbon\Carbon::parse($settings->warmupStartedOn)->toDateString() : '' }}">
                                        <small class="text-body-secondary">Blank means today, stamped on the first save.</small>
                                    </div>
                                </div>

                                <div class="form-section-title mt-4"><i class="bx bx-map"></i>Grid search defaults</div>

                                <div class="row">
                                    <div class="col-sm-4 mb-3">
                                        <label for="defaultGridRadiusKm" class="form-label text-dark">Default radius (km) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="defaultGridRadiusKm" name="defaultGridRadiusKm"
                                               min="0.5" max="50" step="0.1" value="{{ (float) $settings->defaultGridRadiusKm }}" required>
                                    </div>
                                    <div class="col-sm-4 mb-3">
                                        <label for="minGridRadiusKm" class="form-label text-dark">Smallest radius (km) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="minGridRadiusKm" name="minGridRadiusKm"
                                               min="0.1" max="50" step="0.1" value="{{ (float) $settings->minGridRadiusKm }}" required>
                                    </div>
                                    <div class="col-sm-4 mb-3">
                                        <label for="maxSubdivisionDepth" class="form-label text-dark">Max subdivisions <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="maxSubdivisionDepth" name="maxSubdivisionDepth"
                                               min="0" max="8" value="{{ (int) $settings->maxSubdivisionDepth }}" required>
                                        <small class="text-body-secondary">A saturated cell splits into four smaller ones, up to this depth.</small>
                                    </div>
                                </div>

                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="aiRephraseEnabled"
                                           name="aiRephraseEnabled" value="1" {{ $settings->aiRephraseEnabled ? 'checked' : '' }}>
                                    <label class="form-check-label text-dark" for="aiRephraseEnabled">
                                        Let the AI vary each subject line and opening sentence
                                    </label>
                                    <div><small class="text-body-secondary">Identical copies of one message landing in fifty inboxes is the single loudest spam signal there is.</small></div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="test-panel">
                                    <h6 class="text-dark mb-2"><i class="bx bx-calculator me-1"></i>What today allows</h6>
                                    <table class="table table-sm mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="text-secondary">Your daily cap</td>
                                                <td class="text-dark text-end"><strong id="summaryCap">{{ (int) $settings->dailySendCap }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-secondary">Warm-up allows today</td>
                                                <td class="text-dark text-end"><strong>{{ $effectiveDailyCap }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-secondary">Sending window</td>
                                                <td class="text-dark text-end"><strong id="summaryWindow">{{ $settings->send_window_label }}</strong></td>
                                            </tr>
                                            <tr>
                                                <td class="text-secondary">Sending days</td>
                                                <td class="text-dark text-end"><strong id="summaryDays">{{ $settings->send_days_label }}</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <small class="text-body-secondary d-block mt-2">
                                        The saved figures. Save the form to refresh them.
                                    </small>
                                </div>

                                <div class="alert alert-warning mt-3 mb-0" role="alert">
                                    <div class="text-dark" style="font-size: 12.5px;">
                                        <i class="bx bx-shield-quarter me-1"></i>
                                        These numbers are guard rails, not targets. The whole point of a cold outreach system
                                        that respects them is that it is still delivering mail in six months.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==================== CRON ==================== -->
                    <div class="tab-pane {{ $activeTab === 'cron' ? 'active' : '' }}" id="tab-cron" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="form-section-title"><i class="bx bx-terminal"></i>Server cron entry</div>

                                <p class="text-secondary">
                                    The queue runs on <code>sync</code>, which means nothing happens in the background on its own.
                                    Everything in this module is driven by Laravel's scheduler, and the scheduler is driven by
                                    this one crontab line. Add it once on the server and the four commands below take care of themselves.
                                </p>

                                <div class="mb-2 copy-box">
                                    <label for="cronCommand" class="form-label text-dark">Crontab line</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="cronCommand" readonly
                                               value="{{ $cronCommand }}">
                                        <button class="btn btn-primary copy-btn" type="button" data-copy-target="#cronCommand">
                                            <i class="bx bx-copy me-1"></i> Copy
                                        </button>
                                    </div>
                                    <small class="text-body-secondary">Run <code>crontab -e</code> on the server and paste this as a new line.</small>
                                </div>

                                <div class="form-section-title mt-4"><i class="bx bx-list-check"></i>What the scheduler runs</div>

                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="text-dark">Command</th>
                                                <th class="text-dark">Frequency</th>
                                                <th class="text-dark">What it does</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code class="text-dark">outreach:process-queue</code></td>
                                                <td><span class="badge bg-info text-white">Every minute</span></td>
                                                <td class="text-secondary">Sends at most one email per run, and only inside the window, under the cap, after the random gap.</td>
                                            </tr>
                                            <tr>
                                                <td><code class="text-dark">outreach:fetch-replies</code></td>
                                                <td><span class="badge bg-info text-white">Every 5 minutes</span></td>
                                                <td class="text-secondary">Polls IMAP, files replies against leads, and stops any campaign the moment someone answers.</td>
                                            </tr>
                                            <tr>
                                                <td><code class="text-dark">outreach:scrape-grids --limit=3</code></td>
                                                <td><span class="badge bg-info text-white">Every 2 minutes</span></td>
                                                <td class="text-secondary">Works through pending map cells, splitting the crowded ones into four smaller cells.</td>
                                            </tr>
                                            <tr>
                                                <td><code class="text-dark">outreach:enrich-leads --limit=5</code></td>
                                                <td><span class="badge bg-info text-white">Every 3 minutes</span></td>
                                                <td class="text-secondary">Hunts for the contact email of leads that do not have one yet.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <small class="text-body-secondary d-block mt-2">
                                    Each one runs with <code>withoutOverlapping()</code>, so a slow run can never be
                                    started twice. <code>php artisan outreach:status</code> prints a diagnostic table any time.
                                </small>

                                <div class="form-section-title mt-4"><i class="bx bx-broadcast"></i>Inbound webhook</div>

                                <p class="text-secondary">
                                    Optional. If your mail provider can push incoming mail instead of waiting to be polled,
                                    point it here and replies arrive instantly rather than within five minutes. The token in
                                    the URL is the only thing that authenticates the call &mdash; treat it like a password.
                                </p>

                                @if($webhookUrl)
                                    <div class="mb-2 copy-box">
                                        <label for="webhookUrl" class="form-label text-dark">Webhook URL</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="webhookUrl" readonly value="{{ $webhookUrl }}">
                                            <button class="btn btn-primary copy-btn" type="button" data-copy-target="#webhookUrl">
                                                <i class="bx bx-copy me-1"></i> Copy
                                            </button>
                                        </div>
                                        <small class="text-body-secondary">
                                            Accepts a JSON POST. It is the one endpoint in this module with no login &mdash; the
                                            <code>token</code> parameter is compared with <code>hash_equals</code> before a single
                                            byte of the payload is read.
                                        </small>
                                    </div>
                                @else
                                    <div class="alert alert-warning mb-0" role="alert">
                                        <div class="text-dark">
                                            <i class="bx bx-info-circle me-1"></i>
                                            Save your settings once and the webhook URL appears here &mdash; its token is derived
                                            from the saved row.
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="col-lg-4">
                                <div class="test-panel">
                                    <h6 class="text-dark mb-2"><i class="bx bx-help-circle me-1"></i>Checking it works</h6>
                                    <ol class="ps-3 mb-0 text-secondary" style="font-size: 12.5px;">
                                        <li class="mb-2">Add the crontab line above.</li>
                                        <li class="mb-2">Wait a minute, then run <code class="text-dark">php artisan outreach:status</code>.</li>
                                        <li class="mb-2">It prints pending grids, lead counts, how many went out today against the cap, and whether the window is open right now.</li>
                                        <li class="mb-0">If the window says closed and you expected it open, check the days and times on the Limits tab &mdash; everything is Asia/Manila.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ============ SAVE BAR ============ -->
                <div class="save-bar mt-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <small class="text-body-secondary mb-0">
                            Blank secret fields keep whatever is already stored &mdash; they are never sent back to this page.
                        </small>
                        <div class="d-flex gap-2">
                            <a href="{{ route('outreach.dashboard') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary" id="saveSettingsBtn">
                                <i class="bx bx-save me-1"></i> Save Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
$(document).ready(function () {

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    var CSRF_TOKEN = '{{ csrf_token() }}';
    var SAVE_URL = '{{ route('outreach.settings.save') }}';
    var HARD_CAP = {{ \App\Modules\OutreachEngine\Models\OutreachSetting::MAX_DAILY_SEND_CAP }};

    // Every value that reaches innerHTML goes through this first. Server messages carry
    // host names, mail errors and DNS records - all of them attacker-influencable text.
    function escapeHtml(value) {
        if (value === null || typeof value === 'undefined') return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // ==================== TAB MEMORY ====================

    // The screen is long and the tests live on specific tabs, so a reload after a save
    // should land where the operator was, not back on the first tab.
    $('#settingsTabs button[data-tab-key]').on('shown.bs.tab', function () {
        try {
            localStorage.setItem('outreach-settings-tab', $(this).data('tab-key'));
        } catch (e) {
            // Private browsing blocks storage; the tab simply does not persist.
        }
    });

    (function restoreTab() {
        @if(!request()->filled('tab'))
            var saved = null;
            try {
                saved = localStorage.getItem('outreach-settings-tab');
            } catch (e) {
                saved = null;
            }
            if (saved) {
                var $target = $('#settingsTabs button[data-tab-key="' + saved.replace(/[^a-z]/g, '') + '"]');
                if ($target.length) {
                    new bootstrap.Tab($target[0]).show();
                }
            }
        @endif
    })();

    // ==================== MASTER SWITCH ====================

    $('#outreachEnabled').on('change', function () {
        var on = $(this).is(':checked');

        $('#masterSwitchCard').toggleClass('is-on', on);
        $('#masterSwitchBadge').html(on
            ? '<span class="badge bg-success">Outreach ON</span>'
            : '<span class="badge bg-warning text-dark">Outreach OFF</span>');

        if (on) {
            toastr.warning('Outreach will start sending on the next cron minute once you save.', 'Master switch ON');
        }
    });

    // ==================== TEST RESULT RENDERING ====================

    function renderResult($box, ok, message) {
        $box.html(
            '<div class="alert alert-' + (ok ? 'success' : 'danger') + ' d-flex align-items-start mb-0" role="alert">'
            + '<i class="bx ' + (ok ? 'bx-check-circle' : 'bx-error-circle') + ' me-2 mt-1"></i>'
            + '<div class="text-dark" style="font-size: 12.5px;">' + escapeHtml(message) + '</div>'
            + '</div>'
        );
    }

    function renderSpinner($box, label) {
        $box.html(
            '<div class="alert alert-light border d-flex align-items-center mb-0" role="alert">'
            + '<i class="bx bx-loader-alt bx-spin me-2"></i>'
            + '<span class="text-dark" style="font-size: 12.5px;">' + escapeHtml(label) + '</span>'
            + '</div>'
        );
    }

    function busy($btn, label) {
        $btn.data('original-html', $btn.html())
            .prop('disabled', true)
            .html('<i class="bx bx-loader-alt bx-spin me-1"></i> ' + escapeHtml(label));
    }

    function restore($btn) {
        var html = $btn.data('original-html');
        $btn.prop('disabled', false);
        if (html) $btn.html(html);
    }

    // Generic single-endpoint test button (Places, LLM, IMAP).
    $('.test-btn').on('click', function () {
        var $btn = $(this);
        var url = $btn.data('test-url');
        var $box = $($btn.data('result'));
        var payload = { _token: CSRF_TOKEN };

        if ($btn.data('payload') === 'places') {
            payload.keyword = $.trim($('#placesKeyword').val() || '');
        }

        // Send whatever is typed but not yet saved, so a key can be checked
        // before it is committed. Blank falls back to the stored value.
        if ($btn.data('payload') === 'verifier') {
            payload.reoonApiKey = $('#reoonApiKey').val() || '';
            payload.verifierMode = $('#verifierMode').val() || 'power';
        }

        busy($btn, 'Testing...');
        renderSpinner($box, 'Contacting the service...');

        $.ajax({
            url: url,
            type: 'POST',
            data: payload,
            success: function (response) {
                renderResult($box, !!response.success, response.message || 'No message was returned.');
            },
            error: function (xhr) {
                renderResult($box, false, (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'The test could not be completed.');
            },
            complete: function () {
                restore($btn);
            }
        });
    });

    // ==================== SMTP + DNS ====================

    function dnsBadge(status) {
        if (status === 'pass') return '<span class="badge bg-success">Pass</span>';
        if (status === 'warn') return '<span class="badge bg-warning text-dark">Warning</span>';
        return '<span class="badge bg-danger">Fail</span>';
    }

    // Fixed order, fixed labels: the four rows always render even when a check is missing
    // from the payload, so a silent gap can never read as "nothing wrong here".
    var DNS_ROWS = [
        { key: 'spf', label: 'SPF', hint: 'Who may send as your domain' },
        { key: 'dkim', label: 'DKIM', hint: 'Cryptographic signature on each message' },
        { key: 'dmarc', label: 'DMARC', hint: 'What receivers do when SPF or DKIM fails' },
        { key: 'mx', label: 'MX', hint: 'Where replies to you are delivered' }
    ];

    function renderDns($box, response) {
        var data = response.data || {};
        var checks = data.checks || {};
        var domain = data.domain || '';

        var html = ''
            + '<div class="alert alert-' + (response.success ? 'success' : 'danger') + ' d-flex align-items-start mb-2" role="alert">'
            + '<i class="bx ' + (response.success ? 'bx-check-shield' : 'bx-error-circle') + ' me-2 mt-1"></i>'
            + '<div class="text-dark" style="font-size: 12.5px;">' + escapeHtml(response.message || '') + '</div>'
            + '</div>';

        html += '<div class="table-responsive"><table class="table table-sm dns-table mb-0"><tbody>';

        for (var i = 0; i < DNS_ROWS.length; i++) {
            var row = DNS_ROWS[i];
            var check = checks[row.key] || {};
            var status = check.status || 'fail';
            var message = check.message || 'This record could not be checked.';
            var record = check.record || '';

            html += '<tr>'
                + '<td style="width: 78px;"><strong class="text-dark">' + escapeHtml(row.label) + '</strong>'
                + '<div class="text-secondary" style="font-size: 11px;">' + escapeHtml(row.hint) + '</div></td>'
                + '<td style="width: 82px;">' + dnsBadge(status) + '</td>'
                + '<td><div class="text-dark" style="font-size: 12.5px;">' + escapeHtml(message) + '</div>'
                + (record ? '<div class="dns-record mt-1">' + escapeHtml(record) + '</div>' : '')
                + '</td>'
                + '</tr>';
        }

        html += '</tbody></table></div>';

        if (domain) {
            html += '<small class="text-body-secondary d-block mt-1">Checked against <strong class="text-dark">'
                + escapeHtml(domain) + '</strong>'
                + (data.selector ? ' with DKIM selector <strong class="text-dark">' + escapeHtml(data.selector) + '</strong>' : '')
                + '.</small>';
        }

        $box.html(html);
    }

    $('#btnTestSmtpDns').on('click', function () {
        var $btn = $(this);
        var smtpUrl = $btn.data('smtp-url');
        var dnsUrl = $btn.data('dns-url');
        var $smtpBox = $('#smtpResult');
        var $dnsBox = $('#dnsResult');
        var testEmail = $.trim($('#smtpTestEmail').val() || '');

        busy($btn, 'Checking...');
        renderSpinner($smtpBox, testEmail ? 'Sending a test message...' : 'Connecting to the SMTP server...');
        renderSpinner($dnsBox, 'Waiting for the SMTP result...');

        $.ajax({
            url: smtpUrl,
            type: 'POST',
            data: { _token: CSRF_TOKEN, testEmail: testEmail },
            success: function (response) {
                renderResult($smtpBox, !!response.success, response.message || 'No message was returned.');
            },
            error: function (xhr) {
                renderResult($smtpBox, false, (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'The SMTP test could not be completed.');
            },
            complete: function () {
                // The DNS check runs regardless of the SMTP outcome: a refused login and a
                // missing SPF record are separate problems, and the operator wants both.
                renderSpinner($dnsBox, 'Reading DNS records...');

                $.ajax({
                    url: dnsUrl,
                    type: 'POST',
                    data: {
                        _token: CSRF_TOKEN,
                        smtpFromEmail: $.trim($('#smtpFromEmail').val() || ''),
                        dkimSelector: $.trim($('#dkimSelector').val() || '')
                    },
                    success: function (response) {
                        renderDns($dnsBox, response);
                    },
                    error: function (xhr) {
                        renderResult($dnsBox, false, (xhr.responseJSON && xhr.responseJSON.message)
                            ? xhr.responseJSON.message
                            : 'The DNS check could not be completed.');
                    },
                    complete: function () {
                        restore($btn);
                    }
                });
            }
        });
    });

    // ==================== DAILY CAP ====================

    function clampCap(value) {
        var n = parseInt(value, 10);
        if (isNaN(n)) n = 1;
        return Math.max(1, Math.min(n, HARD_CAP));
    }

    function paintCap(value) {
        var cap = clampCap(value);
        var percent = Math.round((cap / HARD_CAP) * 100);

        $('#capMeter').removeClass('is-hot is-max')
            .addClass(cap > 40 ? 'is-max' : (cap > 25 ? 'is-hot' : ''))
            .find('span').css('width', percent + '%');

        var advice;
        if (cap <= 10) {
            advice = '<div class="text-dark" style="font-size: 12.5px;"><i class="bx bx-check-circle text-success me-1"></i>'
                + escapeHtml(cap + ' a day is a safe volume, and the right place for a domain that has not sent cold email before.')
                + '</div>';
        } else if (cap <= 25) {
            advice = '<div class="text-dark" style="font-size: 12.5px;"><i class="bx bx-check-circle text-success me-1"></i>'
                + escapeHtml(cap + ' a day is comfortable for a domain with a few weeks of clean sending behind it.')
                + '</div>';
        } else if (cap <= 40) {
            advice = '<div class="text-dark" style="font-size: 12.5px;"><i class="bx bx-error-circle text-warning me-1"></i>'
                + escapeHtml(cap + ' a day only belongs on a warmed domain with a reply rate you are already happy with.')
                + '</div>';
        } else {
            advice = '<div class="text-dark" style="font-size: 12.5px;"><i class="bx bx-error text-danger me-1"></i>'
                + escapeHtml(cap + ' a day is at the ceiling. ' + HARD_CAP + ' is the hard maximum and the form will not accept more.')
                + '</div>';
        }

        $('#capAdvice').html(advice);
        $('#warmupPlainCap').text(cap);
    }

    $('#dailySendCap').on('input change', function () {
        var cap = clampCap($(this).val());
        $(this).val(cap);
        $('#dailySendCapRange').val(cap);
        paintCap(cap);
        paintWarmup();
    });

    $('#dailySendCapRange').on('input change', function () {
        var cap = clampCap($(this).val());
        $('#dailySendCap').val(cap);
        paintCap(cap);
        paintWarmup();
    });

    // ==================== WARM-UP PREVIEW ====================

    function paintWarmup() {
        var cap = clampCap($('#dailySendCap').val());
        var start = Math.max(1, parseInt($('#warmupStartCap').val(), 10) || 1);
        var step = Math.max(0, parseInt($('#warmupIncrementPerDay').val(), 10) || 0);
        var enabled = $('#warmupEnabled').is(':checked');

        $('#warmupPlainStart').text(enabled ? start : cap);
        $('#warmupPlainIncrement').text(step);

        // Days until the ramp reaches the cap. With no increment it never gets there, and
        // saying so plainly is more useful than printing Infinity.
        var daysToCap;
        if (!enabled) {
            daysToCap = 0;
        } else if (start >= cap) {
            daysToCap = 1;
        } else if (step <= 0) {
            daysToCap = -1;
        } else {
            daysToCap = Math.ceil((cap - start) / step) + 1;
        }

        if (!enabled) {
            $('#warmupPlainDays').text('no');
        } else if (daysToCap < 0) {
            $('#warmupPlainDays').text('never - "added per day" is 0, so it stays at ' + start);
        } else {
            $('#warmupPlainDays').text(daysToCap);
        }

        var html = '';
        for (var day = 1; day <= 6; day++) {
            var allowed = enabled ? Math.min(cap, start + (step * (day - 1))) : cap;
            var label = day === 6 ? 'Day 6+' : 'Day ' + day;
            var atCap = allowed >= cap;

            html += '<div class="col-4 col-md-2">'
                + '<div class="warmup-day">'
                + '<div class="day-label text-secondary">' + escapeHtml(label) + '</div>'
                + '<div class="day-value ' + (atCap ? 'text-success' : 'text-dark') + '">' + escapeHtml(String(allowed)) + '</div>'
                + '</div></div>';
        }

        $('#warmupPreview').html(html);
    }

    $('#warmupStartCap, #warmupIncrementPerDay').on('input change', paintWarmup);
    $('#warmupEnabled').on('change', paintWarmup);

    paintCap($('#dailySendCap').val());
    paintWarmup();

    // ==================== COPY TO CLIPBOARD ====================

    $('.copy-btn').on('click', function () {
        var $btn = $(this);
        var $input = $($btn.data('copy-target'));
        var value = $input.val();

        function done() {
            var html = $btn.html();
            $btn.html('<i class="bx bx-check me-1"></i> Copied');
            toastr.success('Copied to the clipboard.', 'Copied');
            setTimeout(function () { $btn.html(html); }, 1600);
        }

        // navigator.clipboard needs a secure context, which plain-http XAMPP is not,
        // so the old select-and-execCommand path is a real fallback here, not a relic.
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(value).then(done).catch(function () {
                $input[0].select();
                document.execCommand('copy');
                done();
            });
            return;
        }

        $input[0].select();
        $input[0].setSelectionRange(0, 99999);
        try {
            document.execCommand('copy');
            done();
        } catch (e) {
            toastr.error('Select the text and copy it manually.', 'Could not copy');
        }
    });

    // ==================== SAVE ====================

    $('#outreachSettingsForm').on('submit', function (event) {
        event.preventDefault();

        var $btn = $('#saveSettingsBtn');
        var payload = $(this).serialize() + '&_token=' + encodeURIComponent(CSRF_TOKEN);

        busy($btn, 'Saving...');

        $.ajax({
            url: SAVE_URL,
            type: 'POST',
            data: payload,
            success: function (response) {
                if (!response.success) {
                    toastr.error(response.message || 'The settings could not be saved.', 'Not saved');
                    return;
                }

                toastr.success(response.message, 'Saved');

                var data = response.data || {};

                // Re-mask every secret from the fresh server values and clear the inputs, so
                // the form goes straight back to "blank means keep" for the next save.
                if (data.maskedKeys) {
                    $.each(data.maskedKeys, function (attribute, masked) {
                        var $field = $('#' + attribute);
                        if ($field.length) {
                            $field.val('').attr('placeholder', masked);
                            $field.closest('.mb-3').find('.secret-hint').text(masked);
                        }
                    });
                }

                if (typeof data.effectiveDailyCap !== 'undefined') {
                    $('#effectiveCapValue').text(data.effectiveDailyCap);
                }
                if (typeof data.sendDaysLabel !== 'undefined') {
                    $('#summaryDays').text(data.sendDaysLabel);
                }
                if (typeof data.sendWindowLabel !== 'undefined') {
                    $('#summaryWindow').text(data.sendWindowLabel);
                }
                $('#summaryCap').text(clampCap($('#dailySendCap').val()));

                // The webhook token only exists once the row has an id, so the box can appear
                // on the very first save. A reload is the honest way to render it.
                if (data.webhookUrl && !$('#webhookUrl').length) {
                    window.location.reload();
                }
            },
            error: function (xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'The settings could not be saved.';

                if (xhr.status === 422) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Check the form',
                        text: message,
                        confirmButtonColor: '#556ee6'
                    });
                } else {
                    toastr.error(message, 'Error');
                }
            },
            complete: function () {
                restore($btn);
            }
        });
    });
});
</script>
@endsection
