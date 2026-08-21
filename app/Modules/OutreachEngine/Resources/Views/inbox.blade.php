@extends('layouts.master')

@section('title') Lead Finder Inbox @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .thread-list {
        max-height: 620px;
        overflow-y: auto;
    }
    .thread-item {
        display: block;
        width: 100%;
        text-align: left;
        border: 0;
        border-bottom: 1px solid #eff2f7;
        background: #fff;
        padding: 12px 14px;
        cursor: pointer;
        transition: background-color .15s ease;
    }
    .thread-item:hover {
        background: #f8f9fa;
    }
    .thread-item.is-active {
        background: #eff2ff;
        border-left: 3px solid #556ee6;
        padding-left: 11px;
    }
    .thread-item.is-unread .thread-name {
        font-weight: 700;
    }
    .thread-name {
        font-size: 13.5px;
        font-weight: 600;
        color: #2a3042;
    }
    .thread-snippet {
        font-size: 12px;
        color: #495057;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .thread-time {
        font-size: 11px;
        white-space: nowrap;
    }
    .unread-dot {
        display: inline-block;
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #556ee6;
        flex: 0 0 auto;
    }
    .conversation {
        max-height: 520px;
        overflow-y: auto;
        padding: 6px 4px;
        background: #f8f9fa;
        border-radius: 6px;
    }
    .msg-row {
        display: flex;
        margin-bottom: 14px;
    }
    .msg-row.is-outbound {
        justify-content: flex-end;
    }
    .msg-bubble {
        max-width: 78%;
        border-radius: 10px;
        padding: 10px 14px;
        border: 1px solid transparent;
        word-break: break-word;
    }
    .msg-row.is-inbound .msg-bubble {
        background: #ffffff;
        border-color: #e9ecef;
        border-top-left-radius: 2px;
    }
    .msg-row.is-outbound .msg-bubble {
        background: #eff2ff;
        border-color: #d7ddfb;
        border-top-right-radius: 2px;
    }
    .msg-row.is-bounce .msg-bubble {
        background: #fdf1f1;
        border-color: #f1b0b0;
    }
    .msg-meta {
        font-size: 11px;
        margin-bottom: 4px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .msg-subject {
        font-size: 12.5px;
        font-weight: 600;
        color: #2a3042;
        margin-bottom: 4px;
    }
    .msg-body {
        font-size: 13px;
        color: #2a3042;
    }
    .msg-body p {
        margin-bottom: .5rem;
    }
    .msg-body img {
        max-width: 100%;
        height: auto;
    }
    .msg-body table {
        max-width: 100%;
    }
    .empty-state {
        padding: 48px 20px;
        text-align: center;
    }
    .empty-state i {
        font-size: 52px;
        color: #c3cbe4;
    }
    .composer-wrap {
        border-top: 1px solid #eff2f7;
        padding-top: 14px;
        margin-top: 14px;
    }
    @media (max-width: 991.98px) {
        .thread-list {
            max-height: 340px;
        }
        .conversation {
            max-height: 420px;
        }
        .msg-bubble {
            max-width: 92%;
        }
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Lead Finder @endslot
        @slot('title') Inbox @endslot
    @endcomponent

    @if(!$imapConfigured)
        <div class="alert alert-warning d-flex align-items-start" role="alert">
            <i class="bx bx-error-circle me-2 mt-1"></i>
            <div class="text-dark">
                IMAP is not configured, so replies can never be pulled in automatically.
                <a href="{{ route('outreach.settings') }}?tab=imap" class="alert-link">Add your mailbox details in Settings</a>
                to see answers land here.
            </div>
        </div>
    @endif

    @if(!$smtpConfigured)
        <div class="alert alert-warning d-flex align-items-start" role="alert">
            <i class="bx bx-error-circle me-2 mt-1"></i>
            <div class="text-dark">
                SMTP is not configured, so quick replies cannot be sent from this screen.
                <a href="{{ route('outreach.settings') }}?tab=smtp" class="alert-link">Finish the SMTP tab in Settings</a> first.
            </div>
        </div>
    @endif

    <div class="row">
        <!-- ==================== THREAD LIST ==================== -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body pb-0">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="card-title mb-0 text-dark">
                            Conversations
                            <span class="badge bg-danger ms-1 {{ $unreadCount > 0 ? '' : 'd-none' }}" id="unreadBadge">{{ $unreadCount }}</span>
                        </h5>
                        <button type="button" class="btn btn-sm btn-primary" id="btnFetchNow"
                                {{ $imapConfigured ? '' : 'disabled' }}>
                            <i class="bx bx-refresh me-1"></i> Fetch replies now
                        </button>
                    </div>

                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" id="threadSearch" placeholder="Search business, email or town" maxlength="120">
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="unreadOnly">
                            <label class="form-check-label text-dark" for="unreadOnly" style="font-size: 12.5px;">Unread only</label>
                        </div>
                        <small class="text-body-secondary" id="threadCount"></small>
                    </div>
                </div>

                <div class="thread-list border-top" id="threadList">
                    <div class="empty-state">
                        <i class="bx bx-loader-alt bx-spin"></i>
                        <p class="text-dark mt-2 mb-0">Loading conversations...</p>
                    </div>
                </div>

                <div class="card-body pt-2">
                    <small class="text-body-secondary d-block" id="unmatchedNote"></small>
                    <small class="text-body-secondary d-block">This list refreshes on its own every 60 seconds.</small>
                </div>
            </div>
        </div>

        <!-- ==================== CONVERSATION ==================== -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <!-- Header: filled in once a thread is open -->
                    <div id="threadHeader" class="d-none">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                            <div>
                                <h5 class="mb-1 text-dark" id="threadBusinessName">&mdash;</h5>
                                <div class="text-secondary" style="font-size: 12.5px;">
                                    <span id="threadEmail"></span>
                                    <span id="threadLocation"></span>
                                    <span id="threadPhone"></span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span id="threadStatusBadge"></span>
                                <a href="#" class="btn btn-sm btn-secondary d-none" id="threadWebsite" target="_blank" rel="noopener noreferrer">
                                    <i class="bx bx-link-external me-1"></i> Website
                                </a>
                                <a href="{{ route('outreach.leads') }}" class="btn btn-sm btn-secondary">
                                    <i class="bx bx-user-pin me-1"></i> All leads
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="conversation" id="threadMessages">
                        <div class="empty-state">
                            <i class="bx bx-conversation"></i>
                            <p class="text-dark mt-2 mb-1">No conversation selected.</p>
                            <small class="text-secondary">Pick a business on the left to read the full thread.</small>
                        </div>
                    </div>

                    <!-- Quick reply -->
                    <div class="composer-wrap d-none" id="composer">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0 text-dark"><i class="bx bx-edit-alt me-1"></i>Quick reply</h6>
                            <small class="text-body-secondary" id="composerHint"></small>
                        </div>

                        <div class="mb-2">
                            <label for="replySubject" class="form-label text-dark" style="font-size: 12.5px;">Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="replySubject" maxlength="500">
                        </div>

                        <div class="mb-2">
                            <label for="replyBody" class="form-label text-dark" style="font-size: 12.5px;">Message <span class="text-danger">*</span></label>
                            <textarea id="replyBody" class="form-control"></textarea>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <small class="text-body-secondary">
                                A reply sent here does not count against the daily cap &mdash; that quota belongs to the automated campaign.
                            </small>
                            <button type="button" class="btn btn-primary" id="btnSendReply">
                                <i class="bx bx-send me-1"></i> Send reply
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/tinymce/tinymce.min.js') }}"></script>

<script>
$(document).ready(function () {

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    var CSRF_TOKEN = '{{ csrf_token() }}';
    var THREADS_URL = '{{ route('outreach.inbox.threads') }}';
    var THREAD_URL = '{{ route('outreach.inbox.thread') }}';
    var READ_URL = '{{ route('outreach.inbox.read') }}';
    var REPLY_URL = '{{ route('outreach.inbox.reply') }}';
    var FETCH_URL = '{{ route('outreach.inbox.fetch') }}';
    var POLL_MS = 60000;

    var openLeadId = {{ $openLeadId ? (int) $openLeadId : 'null' }};
    var openLeadLastMessageAt = null;
    var canReply = false;
    var searchTimer = null;

    // Everything that reaches innerHTML passes through here. Thread text is written by
    // strangers replying to cold email - the one place in this app where hostile HTML is
    // genuinely expected.
    function escapeHtml(value) {
        if (value === null || typeof value === 'undefined') return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeMultiline(value) {
        return escapeHtml(value).replace(/\r\n|\r|\n/g, '<br>');
    }

    // 'Y-m-d H:i:s' from the server is already Asia/Manila, and so is the operator, so the
    // parts are read as local time rather than parsed as an ISO string.
    function parseServerDate(value) {
        if (!value) return null;
        var m = String(value).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})$/);
        if (!m) return null;
        return new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], +m[6]);
    }

    function relativeTime(value, fallback) {
        var date = parseServerDate(value);
        if (!date) return fallback || '';

        var seconds = Math.floor((Date.now() - date.getTime()) / 1000);

        // A clock a few seconds ahead of the server must not print "in -4 seconds".
        if (seconds < 60) return 'just now';
        if (seconds < 3600) {
            var minutes = Math.floor(seconds / 60);
            return minutes + (minutes === 1 ? ' min ago' : ' mins ago');
        }
        if (seconds < 86400) {
            var hours = Math.floor(seconds / 3600);
            return hours + (hours === 1 ? ' hour ago' : ' hours ago');
        }
        if (seconds < 604800) {
            var days = Math.floor(seconds / 86400);
            return days + (days === 1 ? ' day ago' : ' days ago');
        }

        return fallback || '';
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

    // ==================== TINYMCE ====================

    tinymce.init({
        selector: '#replyBody',
        height: 240,
        menubar: false,
        plugins: ['advlist', 'autolink', 'lists', 'link', 'charmap', 'code'],
        toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat | code',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
        skin: 'oxide',
        branding: false,
        promotion: false,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true
    });

    function replyBodyHtml() {
        var editor = tinymce.get('replyBody');
        return editor ? $.trim(editor.getContent()) : $.trim($('#replyBody').val() || '');
    }

    function clearReplyBody() {
        var editor = tinymce.get('replyBody');
        if (editor) {
            editor.setContent('');
        } else {
            $('#replyBody').val('');
        }
    }

    function replyBodyIsEmpty() {
        var html = replyBodyHtml();
        // TinyMCE leaves an empty paragraph behind after a manual clear.
        return html === '' || html.replace(/<[^>]*>|&nbsp;|\s/g, '') === '';
    }

    // ==================== THREAD LIST ====================

    function renderThreads(threads) {
        if (!threads.length) {
            $('#threadList').html(
                '<div class="empty-state">'
                + '<i class="bx bx-inbox"></i>'
                + '<p class="text-dark mt-2 mb-1">No conversations yet.</p>'
                + '<small class="text-secondary">Replies appear here once a lead answers one of your emails.</small>'
                + '</div>'
            );
            return;
        }

        var html = '';

        for (var i = 0; i < threads.length; i++) {
            var thread = threads[i];
            var unread = parseInt(thread.unreadCount, 10) || 0;
            var classes = 'thread-item';

            if (unread > 0) classes += ' is-unread';
            if (openLeadId !== null && parseInt(thread.leadId, 10) === openLeadId) classes += ' is-active';

            var snippet = thread.snippet || thread.subject || '';
            var prefix = thread.lastDirection === 'outbound' ? 'You: ' : '';

            html += '<div class="' + classes + '" role="button" tabindex="0" data-lead-id="' + escapeHtml(thread.leadId) + '"'
                + ' data-last-message-at="' + escapeHtml(thread.lastMessageAt || '') + '">'
                + '<div class="d-flex align-items-start justify-content-between gap-2">'
                + '<div class="d-flex align-items-center gap-2" style="min-width: 0;">'
                + (unread > 0 ? '<span class="unread-dot" title="' + escapeHtml(unread + ' unread') + '"></span>' : '')
                + '<span class="thread-name text-truncate">' + escapeHtml(thread.businessName || 'Unnamed business') + '</span>'
                + '</div>'
                + '<span class="thread-time text-secondary" title="' + escapeHtml(thread.lastMessageLabel || '') + '">'
                + escapeHtml(relativeTime(thread.lastMessageAt, thread.lastMessageLabel))
                + '</span>'
                + '</div>'
                + '<div class="thread-snippet mt-1">' + escapeHtml(prefix + snippet) + '</div>'
                + '<div class="d-flex align-items-center gap-2 mt-1">'
                // statusBadge is markup built by the model accessor, not user text - the one
                // value on this screen that is deliberately not escaped.
                + (thread.statusBadge || '')
                + (thread.hasBounce ? '<span class="badge bg-danger">Bounce</span>' : '')
                + (unread > 0 ? '<span class="badge bg-info text-white">' + escapeHtml(String(unread)) + ' new</span>' : '')
                + '</div>'
                + '</div>';
        }

        $('#threadList').html(html);
    }

    function loadThreads(silent) {
        if (!silent) {
            $('#threadList').html(
                '<div class="empty-state"><i class="bx bx-loader-alt bx-spin"></i>'
                + '<p class="text-dark mt-2 mb-0">Loading conversations...</p></div>'
            );
        }

        $.ajax({
            url: THREADS_URL,
            type: 'GET',
            data: {
                search: $.trim($('#threadSearch').val() || ''),
                unreadOnly: $('#unreadOnly').is(':checked') ? 1 : 0
            },
            success: function (response) {
                if (!response.success) {
                    if (!silent) toastr.error(response.message || 'The conversations could not be loaded.', 'Error');
                    return;
                }

                var data = response.data || {};
                var threads = data.threads || [];

                renderThreads(threads);

                $('#threadCount').text(threads.length + (threads.length === 1 ? ' conversation' : ' conversations'));

                var unreadTotal = parseInt(data.unreadTotal, 10) || 0;
                $('#unreadBadge').text(unreadTotal).toggleClass('d-none', unreadTotal === 0);

                var unmatched = parseInt(data.unmatchedCount, 10) || 0;
                $('#unmatchedNote').text(unmatched > 0
                    ? unmatched + (unmatched === 1 ? ' message' : ' messages') + ' arrived that could not be matched to a lead.'
                    : '');

                // A poll that finds something new in the open thread refreshes it, but only
                // the message pane - re-rendering the composer would eat a half-typed reply.
                if (openLeadId !== null) {
                    for (var i = 0; i < threads.length; i++) {
                        if (parseInt(threads[i].leadId, 10) !== openLeadId) continue;
                        if (threads[i].lastMessageAt && threads[i].lastMessageAt !== openLeadLastMessageAt) {
                            loadThread(openLeadId, true);
                        }
                        break;
                    }
                }
            },
            error: function (xhr) {
                if (silent) return;
                toastr.error((xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'The conversations could not be loaded.', 'Error');
            }
        });
    }

    // ==================== CONVERSATION ====================

    function renderEntries(entries) {
        if (!entries.length) {
            $('#threadMessages').html(
                '<div class="empty-state"><i class="bx bx-message-square-dots"></i>'
                + '<p class="text-dark mt-2 mb-1">Nothing in this thread yet.</p>'
                + '<small class="text-secondary">Sent emails and every reply will show up here.</small></div>'
            );
            return;
        }

        var html = '';

        for (var i = 0; i < entries.length; i++) {
            var entry = entries[i];
            var outbound = entry.direction === 'outbound';
            var rowClass = 'msg-row ' + (outbound ? 'is-outbound' : 'is-inbound') + (entry.isBounce ? ' is-bounce' : '');

            var who = entry.fromName ? entry.fromName : entry.from;

            html += '<div class="' + rowClass + '">'
                + '<div class="msg-bubble">'
                + '<div class="msg-meta">'
                + '<span class="' + (entry.isBounce ? 'text-danger' : 'text-secondary') + '">'
                + escapeHtml(who || (outbound ? 'You' : 'Them'))
                + '</span>'
                // statusBadge comes from a model accessor: fixed markup, no user text in it.
                + (entry.statusBadge || '')
                + (entry.isBounce ? '<span class="badge bg-danger">Bounced</span>' : '')
                + (entry.aiRephrased ? '<span class="badge bg-info text-white">AI worded</span>' : '')
                + '<span class="text-secondary">' + escapeHtml(entry.atLabel || '') + '</span>'
                + '</div>';

            if (entry.subject) {
                html += '<div class="msg-subject">' + escapeHtml(entry.subject) + '</div>';
            }

            // isHtml is only true for markup this system composed and sent. Inbound bodies
            // arrive already flattened to text by the controller and are escaped here.
            html += '<div class="msg-body">'
                + (entry.isHtml ? (entry.body || '') : escapeMultiline(entry.body || ''))
                + '</div>';

            if (entry.errorMessage) {
                html += '<div class="text-danger mt-2" style="font-size: 12px;">'
                    + '<i class="bx bx-error-circle me-1"></i>' + escapeHtml(entry.errorMessage)
                    + '</div>';
            }

            html += '</div></div>';
        }

        $('#threadMessages').html(html);

        var pane = document.getElementById('threadMessages');
        pane.scrollTop = pane.scrollHeight;
    }

    function loadThread(leadId, silent) {
        if (!silent) {
            $('#threadMessages').html(
                '<div class="empty-state"><i class="bx bx-loader-alt bx-spin"></i>'
                + '<p class="text-dark mt-2 mb-0">Loading the conversation...</p></div>'
            );
        }

        $.ajax({
            url: THREAD_URL + '?leadId=' + encodeURIComponent(leadId),
            type: 'GET',
            success: function (response) {
                if (!response.success) {
                    toastr.error(response.message || 'The conversation could not be loaded.', 'Error');
                    return;
                }

                var data = response.data || {};
                var lead = data.lead || {};

                openLeadId = parseInt(lead.id, 10);

                $('#threadHeader').removeClass('d-none');
                $('#threadBusinessName').text(lead.businessName || 'Unnamed business');
                $('#threadEmail').text(lead.email || 'No email on file');
                $('#threadLocation').text(lead.location ? ' · ' + lead.location : '');
                $('#threadPhone').text(lead.phone ? ' · ' + lead.phone : '');
                $('#threadStatusBadge').html(lead.statusBadge || '');

                // The lead's own site is a stranger-supplied URL, so only http(s) is ever
                // put in an href - a javascript: value here would run on click.
                var website = String(lead.website || '');
                if (/^https?:\/\//i.test(website)) {
                    $('#threadWebsite').attr('href', website).removeClass('d-none');
                } else {
                    $('#threadWebsite').attr('href', '#').addClass('d-none');
                }

                renderEntries(data.entries || []);

                canReply = !!data.canReply;
                $('#composer').removeClass('d-none');
                $('#btnSendReply').prop('disabled', !canReply);
                $('#replySubject').prop('disabled', !canReply);
                $('#composerHint').text(canReply
                    ? 'Sends from your configured SMTP account.'
                    : 'Replying needs a configured SMTP account and a valid email on this lead.');

                // Only prefill a subject the operator has not started editing.
                if (replyBodyIsEmpty() && $.trim($('#replySubject').val() || '') === '') {
                    $('#replySubject').val(data.replySubject || '');
                }

                var entries = data.entries || [];
                openLeadLastMessageAt = entries.length ? entries[entries.length - 1].at : null;

                markRead(openLeadId);

                $('.thread-item').removeClass('is-active');
                $('.thread-item[data-lead-id="' + openLeadId + '"]').addClass('is-active').removeClass('is-unread');
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'The conversation could not be loaded.', 'Error');
            }
        });
    }

    function markRead(leadId) {
        $.ajax({
            url: READ_URL,
            type: 'POST',
            data: { _token: CSRF_TOKEN, leadId: leadId },
            success: function (response) {
                if (!response.success) return;

                var data = response.data || {};
                var unreadTotal = parseInt(data.unreadTotal, 10) || 0;

                $('#unreadBadge').text(unreadTotal).toggleClass('d-none', unreadTotal === 0);
                $('.thread-item[data-lead-id="' + leadId + '"]')
                    .removeClass('is-unread')
                    .find('.unread-dot, .badge.bg-info').remove();
            }
        });
    }

    // ==================== EVENTS ====================

    $(document).on('click', '.thread-item', function () {
        var leadId = parseInt($(this).data('lead-id'), 10);
        if (!leadId) return;

        openLeadLastMessageAt = $(this).data('last-message-at') || null;
        loadThread(leadId, false);
    });

    $(document).on('keydown', '.thread-item', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            $(this).trigger('click');
        }
    });

    $('#threadSearch').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { loadThreads(false); }, 350);
    });

    $('#unreadOnly').on('change', function () {
        loadThreads(false);
    });

    $('#btnFetchNow').on('click', function () {
        var $btn = $(this);

        busy($btn, 'Checking...');

        $.ajax({
            url: FETCH_URL,
            type: 'POST',
            data: { _token: CSRF_TOKEN, limit: 50 },
            success: function (response) {
                if (!response.success) {
                    toastr.warning(response.message || 'The mailbox could not be checked.', 'Nothing fetched');
                    return;
                }

                toastr.success(response.message, 'Mailbox checked');
                loadThreads(true);

                if (openLeadId !== null) {
                    loadThread(openLeadId, true);
                }
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'The mailbox could not be checked.', 'Error');
            },
            complete: function () {
                restore($btn);
            }
        });
    });

    $('#btnSendReply').on('click', function () {
        if (openLeadId === null) {
            toastr.warning('Open a conversation first.', 'No thread selected');
            return;
        }

        if (!canReply) {
            toastr.warning('This thread cannot be replied to yet.', 'Reply unavailable');
            return;
        }

        var subject = $.trim($('#replySubject').val() || '');
        var body = replyBodyHtml();

        if (subject === '') {
            toastr.warning('A subject line is required.', 'Nothing sent');
            $('#replySubject').trigger('focus');
            return;
        }

        if (replyBodyIsEmpty()) {
            toastr.warning('The reply cannot be empty.', 'Nothing sent');
            return;
        }

        var $btn = $(this);
        busy($btn, 'Sending...');

        $.ajax({
            url: REPLY_URL,
            type: 'POST',
            data: {
                _token: CSRF_TOKEN,
                leadId: openLeadId,
                subject: subject,
                body: body
            },
            success: function (response) {
                if (!response.success) {
                    toastr.error(response.message || 'The reply could not be sent.', 'Not sent');
                    return;
                }

                toastr.success(response.message, 'Reply sent');
                clearReplyBody();

                loadThread(openLeadId, true);
                loadThreads(true);
            },
            error: function (xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'The reply could not be sent.';

                Swal.fire({
                    icon: 'error',
                    title: 'Not sent',
                    text: message,
                    confirmButtonColor: '#556ee6'
                });
            },
            complete: function () {
                restore($btn);
            }
        });
    });

    // ==================== BOOT ====================

    loadThreads(false);

    if (openLeadId !== null) {
        loadThread(openLeadId, false);
    }

    // The list keeps itself current so a reply that lands while the screen is open is
    // visible without a manual refresh. Silent: a failed poll must not spray toasts.
    setInterval(function () {
        loadThreads(true);
    }, POLL_MS);
});
</script>
@endsection
