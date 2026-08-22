@extends('layouts.master')

@section('title') Chat Support @endsection

@section('css')
<!-- Toastr CSS -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
/* ── Chat Layout ── */
.chat-container {
    display: grid;
    grid-template-columns: 360px 1fr;
    height: calc(100vh - 220px);
    min-height: 500px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}

/* ── Conversation List (Left Panel) ── */
.chat-sidebar {
    border-right: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    background: #fff;
}

.chat-sidebar-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e9ecef;
    background: #fff;
}

.chat-sidebar-header h5 {
    font-size: 1rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0;
}

.chat-filter-tabs {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.chat-filter-tabs .btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
}

.chat-filter-tabs .btn.active {
    pointer-events: none;
}

.conversation-list {
    flex: 1;
    overflow-y: auto;
    padding: 0;
    margin: 0;
    list-style: none;
}

.conversation-item {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background-color 0.15s ease;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.conversation-item:hover {
    background-color: #f8f9fa;
}

.conversation-item.active {
    background-color: #eef2ff;
    border-left: 3px solid #556ee6;
}

.conversation-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #556ee6;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.conversation-avatar.closed {
    background: #adb5bd;
}

.conversation-details {
    flex: 1;
    min-width: 0;
}

.conversation-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #495057;
    margin-bottom: 0.15rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.conversation-name .time {
    font-weight: 400;
    font-size: 0.75rem;
    color: #adb5bd;
}

.conversation-preview {
    font-size: 0.82rem;
    color: #6c757d;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.unread-badge {
    background: #556ee6;
    color: #fff;
    font-size: 0.7rem;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.conversation-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1.5rem;
    color: #6c757d;
    text-align: center;
}

.conversation-empty i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

/* ── Chat Window (Right Panel) ── */
.chat-window {
    display: flex;
    flex-direction: column;
    background: #f8f9fa;
    overflow: hidden;
    min-height: 0;
}

.chat-window-header {
    padding: 1rem 1.25rem;
    background: #fff;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-window-header .visitor-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.chat-window-header .visitor-name {
    font-weight: 600;
    font-size: 1rem;
    color: #495057;
}

.chat-window-header .visitor-email {
    font-size: 0.82rem;
    color: #6c757d;
}

.chat-window-header .chat-actions {
    display: flex;
    gap: 0.5rem;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.chat-message {
    display: flex;
    max-width: 70%;
}

.chat-message.visitor {
    align-self: flex-start;
}

.chat-message.admin {
    align-self: flex-end;
}

.chat-bubble {
    padding: 0.75rem 1rem;
    border-radius: 12px;
    font-size: 0.9rem;
    line-height: 1.45;
    word-wrap: break-word;
}

.chat-message.visitor .chat-bubble {
    background: #fff;
    color: #495057;
    border: 1px solid #e9ecef;
    border-bottom-left-radius: 4px;
}

.chat-message.admin .chat-bubble {
    background: #556ee6;
    color: #fff;
    border-bottom-right-radius: 4px;
}

.chat-bubble .message-time {
    font-size: 0.7rem;
    margin-top: 0.35rem;
    opacity: 0.7;
}

.chat-message.visitor .message-time {
    color: #adb5bd;
}

.chat-message.admin .message-time {
    color: rgba(255, 255, 255, 0.7);
}

/* ── Chat Input ── */
.chat-input-area {
    padding: 1rem 1.25rem;
    background: #fff;
    border-top: 1px solid #e9ecef;
}

.chat-input-wrapper {
    display: flex;
    gap: 0.75rem;
    align-items: flex-end;
}

.chat-input-wrapper textarea {
    flex: 1;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 0.6rem 1rem;
    font-size: 0.9rem;
    resize: none;
    max-height: 120px;
    line-height: 1.4;
}

.chat-input-wrapper textarea:focus {
    outline: none;
    border-color: #556ee6;
    box-shadow: 0 0 0 0.15rem rgba(85, 110, 230, 0.15);
}

.chat-input-wrapper .btn-send {
    height: 40px;
    width: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* ── No Conversation Selected State ── */
.no-chat-selected {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    text-align: center;
    padding: 2rem;
}

.no-chat-selected i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.2;
}

.no-chat-selected h5 {
    color: #495057;
    margin-bottom: 0.5rem;
}

/* ── Closed Conversation Overlay ── */
.chat-closed-notice {
    padding: 0.75rem 1.25rem;
    background: #fff3cd;
    border-top: 1px solid #ffc107;
    text-align: center;
    font-size: 0.85rem;
    color: #856404;
}

/* ── Responsive ── */
@media (max-width: 768px) {
    .chat-container {
        grid-template-columns: 1fr;
        height: auto;
    }

    .chat-sidebar {
        max-height: 350px;
    }
}

/* ── Typing indicator ── */
.typing-indicator {
    display: none;
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
    color: #6c757d;
    font-style: italic;
}

/* ── Delete modal ── */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.3;
}

.empty-state h4 {
    color: #495057;
    margin-bottom: 0.5rem;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('li_2') Website @endslot
@slot('title') Chat Support @endslot
@endcomponent

<div class="chat-container">
    <!-- Left Panel: Conversation List -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0"><i class="bx bx-chat me-2"></i>Conversations</h5>
                <button class="btn btn-sm btn-soft-secondary" id="btnToggleSettings" title="Settings">
                    <i class="bx bx-cog"></i>
                </button>
            </div>
            <div id="chatSearchAndFilters">
                <div class="mt-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bx bx-search text-secondary"></i></span>
                        <input type="text" class="form-control border-start-0" id="conversationSearch" placeholder="Search by name..." style="font-size: 0.82rem;">
                    </div>
                </div>
                <div class="chat-filter-tabs">
                    <button class="btn btn-sm btn-primary active" data-filter="all">All</button>
                    <button class="btn btn-sm btn-outline-secondary" data-filter="active">Active</button>
                    <button class="btn btn-sm btn-outline-secondary" data-filter="closed">Closed</button>
                </div>
            </div>
        </div>

        <ul class="conversation-list" id="conversationList">
            @if($conversations->count() > 0)
                @foreach($conversations as $conversation)
                <li class="conversation-item" data-conversation-id="{{ $conversation->id }}" data-status="{{ $conversation->status }}">
                    <div class="conversation-avatar {{ $conversation->status === 'closed' ? 'closed' : '' }}">
                        {{ strtoupper(substr($conversation->visitorName ?? 'V', 0, 1)) }}
                    </div>
                    <div class="conversation-details">
                        <div class="conversation-name">
                            <span class="text-dark">{{ $conversation->visitorName ?? 'Visitor' }}</span>
                            <span class="time">{{ $conversation->updated_at->format('M d, h:i A') }}</span>
                        </div>
                        <div class="conversation-preview">
                            @if($conversation->latestMessage)
                                @if($conversation->latestMessage->senderType === 'admin')
                                    <span class="text-secondary">You: </span>
                                @endif
                                {{ Str::limit($conversation->latestMessage->message, 50) }}
                            @else
                                <span class="text-secondary">No messages yet</span>
                            @endif
                        </div>
                    </div>
                    @if($conversation->unreadCount() > 0)
                        <div class="unread-badge">{{ $conversation->unreadCount() }}</div>
                    @endif
                </li>
                @endforeach
            @else
                <div class="conversation-empty">
                    <i class="bx bx-message-square-dots"></i>
                    <p class="text-secondary mb-0">No conversations yet.<br>They will appear here when visitors start chatting.</p>
                </div>
            @endif
        </ul>

        <!-- Settings Panel (hidden by default) -->
        <div id="settingsPanel" style="display: none; flex: 1; overflow-y: auto;">
            <div class="p-3">
                <div class="d-flex align-items-center mb-3">
                    <button class="btn btn-sm btn-soft-secondary me-2" id="btnBackToChats" title="Back to conversations">
                        <i class="bx bx-arrow-back"></i>
                    </button>
                    <h6 class="mb-0 text-dark"><i class="bx bx-cog me-1"></i>Settings</h6>
                </div>

                <!-- Auto-Reply Setting -->
                <div class="card mb-3 border">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label mb-0 fw-medium text-dark" for="autoReplyEnabled">
                                <i class="bx bx-reply me-1 text-primary"></i>Auto-Reply
                            </label>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="autoReplyEnabled"
                                    {{ ($settings['auto_reply_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                            </div>
                        </div>
                        <p class="text-secondary mb-3" style="font-size: 0.78rem;">
                            When enabled, new conversations will automatically receive this message if no admin is logged in.
                        </p>

                        <div class="mb-3">
                            <label class="form-label text-dark small fw-medium" for="autoReplyMessage">Auto-Reply Message</label>
                            <textarea
                                class="form-control"
                                id="autoReplyMessage"
                                rows="4"
                                maxlength="1000"
                                placeholder="Enter auto-reply message..."
                                style="font-size: 0.85rem;"
                            >{{ $settings['auto_reply_message'] ?? '' }}</textarea>
                            <div class="d-flex justify-content-end mt-1">
                                <small class="text-secondary" id="autoReplyCharCount">0/1000</small>
                            </div>
                        </div>

                        <button class="btn btn-primary btn-sm w-100" id="btnSaveSettings">
                            <i class="bx bx-save me-1"></i>Save Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel: Chat Window -->
    <div class="chat-window" id="chatWindow">
        <div class="no-chat-selected" id="noChatSelected">
            <i class="bx bx-message-rounded-dots"></i>
            <h5 class="text-dark">Select a conversation</h5>
            <p class="text-secondary">Choose a conversation from the list to start responding.</p>
        </div>

        <!-- Active Chat (hidden by default) -->
        <div id="activeChatArea" style="display: none; flex-direction: column; height: 100%; min-height: 0;">
            <div class="chat-window-header">
                <div class="visitor-info">
                    <div class="conversation-avatar" id="chatAvatar">V</div>
                    <div>
                        <div class="visitor-name" id="chatVisitorName">Visitor</div>
                        <div class="visitor-email" id="chatVisitorEmail"></div>
                        <div class="d-flex align-items-center gap-2 mt-1" id="chatVisitorMeta" style="display: none !important;">
                            <span class="badge bg-info text-white" id="chatVisitorType" style="font-size: 0.72rem;"></span>
                            <span class="text-secondary" style="font-size: 0.78rem;" id="chatFarmLocation"></span>
                            <a href="#" class="badge bg-primary text-white" id="chatLeadLink" style="font-size: 0.72rem; display: none; text-decoration: none;" target="_blank">
                                <i class="bx bx-user-plus me-1"></i>View Lead
                            </a>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge" id="chatStatusBadge"></span>
                    <div class="chat-actions">
                        <button class="btn btn-soft-info btn-sm" id="btnToggleStatus" title="Close conversation">
                            <i class="bx bx-check-circle"></i>
                        </button>
                        <button class="btn btn-soft-danger btn-sm" id="btnDeleteConversation" title="Delete conversation">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <!-- Messages loaded via AJAX -->
            </div>

            <div class="chat-closed-notice" id="chatClosedNotice" style="display: none;">
                <i class="bx bx-info-circle me-1"></i>This conversation is closed.
                <button class="btn btn-sm btn-outline-warning ms-2" id="btnReopenFromNotice">Reopen</button>
            </div>

            <div class="chat-input-area" id="chatInputArea">
                <div class="chat-input-wrapper">
                    <textarea id="messageInput" class="form-control" rows="1" placeholder="Type your message..." maxlength="5000"></textarea>
                    <button class="btn btn-primary btn-send" id="btnSendMessage" title="Send message">
                        <i class="bx bx-send"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Close Confirmation Modal -->
<div class="modal fade" id="closeModal" tabindex="-1" aria-labelledby="closeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="closeModalLabel">
                    <i class="bx bx-check-circle text-warning me-2"></i>Close Conversation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Close the conversation with "<strong id="closeVisitorName"></strong>"?</p>
                <p class="text-secondary mb-0">The visitor will be notified that the conversation has ended.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-warning text-dark" id="confirmCloseBtn">
                    <i class="bx bx-check-circle me-1"></i>Close Conversation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bx bx-trash text-danger me-2"></i>Delete Conversation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete this conversation with "<strong id="deleteVisitorName"></strong>"?</p>
                <p class="text-secondary mb-0">All messages will be permanently removed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bx bx-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Toastr JS -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Configure Toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": 3000
    };

    var currentConversationId = null;
    var currentConversationStatus = null;
    var pollInterval = null;
    var conversationPollInterval = null;

    // ── Search ──
    var searchTerm = '';

    $('#conversationSearch').on('input', function() {
        searchTerm = $(this).val().toLowerCase().trim();
        applyFilters();
    });

    // ── Filter Tabs ──
    $('.chat-filter-tabs .btn').on('click', function() {
        $('.chat-filter-tabs .btn').removeClass('btn-primary active').addClass('btn-outline-secondary');
        $(this).removeClass('btn-outline-secondary').addClass('btn-primary active');
        applyFilters();
    });

    function applyFilters() {
        var filter = $('.chat-filter-tabs .btn.active').data('filter');
        $('.conversation-item').each(function() {
            var status = $(this).data('status');
            var name = $(this).find('.conversation-name span.text-dark').text().toLowerCase();
            var matchesFilter = (filter === 'all' || status === filter);
            var matchesSearch = (!searchTerm || name.indexOf(searchTerm) !== -1);
            $(this).toggle(matchesFilter && matchesSearch);
        });
    }

    // ── Select Conversation ──
    $(document).on('click', '.conversation-item', function() {
        var conversationId = $(this).data('conversation-id');

        // Highlight active
        $('.conversation-item').removeClass('active');
        $(this).addClass('active');

        // Remove unread badge
        $(this).find('.unread-badge').remove();

        loadConversation(conversationId);
    });

    function loadConversation(conversationId) {
        currentConversationId = conversationId;
        lastServerMessageId = null;

        // Show chat area
        $('#noChatSelected').hide();
        $('#activeChatArea').css('display', 'flex');

        // Load messages
        fetchMessages(conversationId);

        // Start polling for new messages
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(function() {
            fetchMessages(conversationId);
        }, 3000);
    }

    function fetchMessages(conversationId) {
        $.ajax({
            url: '/anisenso-website-chat-support-messages?id=' + conversationId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    renderChatHeader(response.conversation);
                    renderMessages(response.messages);
                }
            },
            error: function(xhr) {
                if (xhr.status === 404) {
                    toastr.error('Conversation not found.');
                    resetChatWindow();
                }
            }
        });
    }

    function renderChatHeader(conversation) {
        var initial = (conversation.visitorName || 'V').charAt(0).toUpperCase();
        $('#chatAvatar').text(initial);
        $('#chatVisitorName').text(conversation.visitorName || 'Visitor');
        $('#chatVisitorEmail').text(conversation.visitorEmail || '');
        $('#chatStatusBadge').attr('class', 'badge ' + conversation.statusBadge).text(conversation.statusLabel);

        // Show visitor meta (type, farm location, lead link)
        if (conversation.visitorTypeLabel || conversation.farmLocation) {
            $('#chatVisitorMeta').css('display', 'flex').removeAttr('style').addClass('d-flex');
            if (conversation.visitorTypeLabel) {
                $('#chatVisitorType').text(conversation.visitorTypeLabel).show();
            } else {
                $('#chatVisitorType').hide();
            }
            if (conversation.farmLocation) {
                $('#chatFarmLocation').html('<i class="bx bx-map me-1"></i>' + escapeHtml(conversation.farmLocation)).show();
            } else {
                $('#chatFarmLocation').hide();
            }
            if (conversation.leadId) {
                $('#chatLeadLink').attr('href', '/crm-leads-view?id=' + conversation.leadId).show();
            } else {
                $('#chatLeadLink').hide();
            }
        } else {
            $('#chatVisitorMeta').hide();
        }

        // Sync tracked status from server
        currentConversationStatus = conversation.status;
        updateChatInputVisibility();
    }

    function updateChatInputVisibility() {
        if (currentConversationStatus === 'active') {
            $('#btnToggleStatus').attr('title', 'Close conversation')
                .html('<i class="bx bx-check-circle"></i>');
            $('#chatInputArea').css('display', '');
            $('#chatClosedNotice').css('display', 'none');
        } else {
            $('#btnToggleStatus').attr('title', 'Reopen conversation')
                .html('<i class="bx bx-revision"></i>');
            $('#chatInputArea').css('display', 'none');
            $('#chatClosedNotice').css('display', '');
        }
    }

    var lastServerMessageId = null;

    function renderMessages(messages) {
        var $container = $('#chatMessages');
        var wasAtBottom = isScrolledToBottom($container);

        // Only re-render if the latest message ID changed
        var latestId = messages.length > 0 ? messages[messages.length - 1].id : null;
        if (latestId === lastServerMessageId) return;
        lastServerMessageId = latestId;

        var html = '';
        messages.forEach(function(msg) {
            if (msg.senderType === 'system') {
                html += '<div class="text-center my-2">';
                html += '  <div class="d-inline-block bg-light border rounded-3 px-3 py-2" style="max-width: 85%;">';
                html += '    <div class="text-secondary" style="font-size: 0.8rem;">' + escapeHtml(msg.message) + '</div>';
                html += '    <div class="text-secondary mt-1" style="font-size: 0.68rem; opacity: 0.6;">' + msg.createdAt + '</div>';
                html += '  </div>';
                html += '</div>';
            } else {
                var isAdmin = msg.senderType === 'admin';
                var textClass = isAdmin ? 'text-white' : 'text-dark';
                html += '<div class="chat-message ' + msg.senderType + '">';
                html += '  <div class="chat-bubble">';
                html += '    <div class="' + textClass + '">' + escapeHtml(msg.message) + '</div>';
                html += '    <div class="message-time">' + msg.createdAt + '</div>';
                html += '  </div>';
                html += '</div>';
            }
        });

        $container.html(html);

        // Auto-scroll to bottom if was already at bottom or first load
        if (wasAtBottom || existingCount === 0) {
            scrollToBottom($container);
        }
    }

    function isScrolledToBottom($el) {
        return $el[0].scrollHeight - $el[0].scrollTop - $el[0].clientHeight < 50;
    }

    function scrollToBottom($el) {
        $el.scrollTop($el[0].scrollHeight);
    }

    // ── Send Message ──
    $('#btnSendMessage').on('click', function() {
        sendMessage();
    });

    $('#messageInput').on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }

        // Auto-resize textarea
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    function sendMessage() {
        var message = $('#messageInput').val().trim();
        if (!message || !currentConversationId) return;

        var $btn = $('#btnSendMessage');
        $btn.prop('disabled', true);

        $.ajax({
            url: '/anisenso-website-chat-support-send?id=' + currentConversationId,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                message: message
            },
            success: function(response) {
                if (response.success) {
                    $('#messageInput').val('').css('height', 'auto');
                    // Immediately append the message
                    var msg = response.message;
                    var html = '<div class="chat-message admin">';
                    html += '  <div class="chat-bubble">';
                    html += '    <div class="text-white">' + escapeHtml(msg.message) + '</div>';
                    html += '    <div class="message-time">' + msg.createdAt + '</div>';
                    html += '  </div>';
                    html += '</div>';
                    $('#chatMessages').append(html);
                    scrollToBottom($('#chatMessages'));
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to send message');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $('#messageInput').focus();
            }
        });
    }

    // ── Close / Reopen Conversation ──
    $('#btnToggleStatus').on('click', function() {
        if (!currentConversationId) return;

        var currentStatus = $('#chatStatusBadge').text().trim();
        var action = currentStatus === 'Active' ? 'close' : 'reopen';

        if (action === 'close') {
            $('#closeVisitorName').text($('#chatVisitorName').text());
            $('#closeModal').modal('show');
            return;
        }

        executeStatusToggle(action);
    });

    $('#confirmCloseBtn').on('click', function() {
        $('#closeModal').modal('hide');
        executeStatusToggle('close');
    });

    function executeStatusToggle(action) {
        var url = '/anisenso-website-chat-support?id=' + action + '&id=' + currentConversationId;

        $.ajax({
            url: url,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    // Restart message polling to ensure it's running
                    if (pollInterval) clearInterval(pollInterval);
                    pollInterval = setInterval(function() {
                        fetchMessages(currentConversationId);
                    }, 3000);
                    fetchMessages(currentConversationId);
                    refreshConversationList();

                    // Update the conversation item's data-status
                    var $item = $('.conversation-item[data-conversation-id="' + currentConversationId + '"]');
                    $item.data('status', action === 'close' ? 'closed' : 'active');
                    var $avatar = $item.find('.conversation-avatar');
                    if (action === 'close') {
                        $avatar.addClass('closed');
                    } else {
                        $avatar.removeClass('closed');
                    }
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Action failed');
            }
        });
    }

    $('#btnReopenFromNotice').on('click', function() {
        $('#btnToggleStatus').click();
    });

    // ── Delete Conversation ──
    $('#btnDeleteConversation').on('click', function() {
        if (!currentConversationId) return;

        var status = $('#chatStatusBadge').text().trim();
        if (status === 'Active') {
            toastr.info('Please close the conversation before deleting it.', 'Info');
            return;
        }

        var visitorName = $('#chatVisitorName').text();
        $('#deleteVisitorName').text(visitorName);
        $('#deleteModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        if (!currentConversationId) return;

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: '/anisenso-website-chat-support?id=' + currentConversationId,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    toastr.success(response.message);

                    // Remove from list
                    $('.conversation-item[data-conversation-id="' + currentConversationId + '"]').fadeOut(400, function() {
                        $(this).remove();
                        if ($('.conversation-item').length === 0) {
                            $('#conversationList').html(
                                '<div class="conversation-empty">' +
                                '  <i class="bx bx-message-square-dots"></i>' +
                                '  <p class="text-secondary mb-0">No conversations yet.<br>They will appear here when visitors start chatting.</p>' +
                                '</div>'
                            );
                        }
                    });

                    resetChatWindow();
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete conversation');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
            }
        });
    });

    function resetChatWindow() {
        currentConversationId = null;
        currentConversationStatus = null;
        lastServerMessageId = null;
        if (pollInterval) clearInterval(pollInterval);
        $('#activeChatArea').hide();
        $('#noChatSelected').show();
    }

    // ── Poll for new conversations ──
    refreshConversationList();
    conversationPollInterval = setInterval(function() {
        refreshConversationList();
    }, 3000);

    function refreshConversationList() {
        var activeFilter = $('.chat-filter-tabs .btn.active').data('filter');

        $.ajax({
            url: '{{ route("anisenso-website-chat-support.conversations") }}',
            type: 'GET',
            data: { status: activeFilter === 'all' ? 'all' : activeFilter },
            success: function(response) {
                if (response.success) {
                    renderConversationList(response.conversations);
                    checkForNewConversations(response.conversations);
                }
            }
        });
    }

    function renderConversationList(conversations) {
        var $list = $('#conversationList');
        var activeFilter = $('.chat-filter-tabs .btn.active').data('filter');

        if (conversations.length === 0) {
            $list.html(
                '<div class="conversation-empty">' +
                '  <i class="bx bx-message-square-dots"></i>' +
                '  <p class="text-secondary mb-0">No conversations yet.<br>They will appear here when visitors start chatting.</p>' +
                '</div>'
            );
            return;
        }

        var html = '';
        conversations.forEach(function(conv) {
            var isActive = conv.id == currentConversationId;
            var isClosed = conv.status === 'closed';
            var matchesSearch = !searchTerm || (conv.visitorName || '').toLowerCase().indexOf(searchTerm) !== -1;
            var shouldShow = (activeFilter === 'all' || conv.status === activeFilter) && matchesSearch;
            var initial = (conv.visitorName || 'V').charAt(0).toUpperCase();

            html += '<li class="conversation-item' + (isActive ? ' active' : '') + '" ' +
                    'data-conversation-id="' + conv.id + '" ' +
                    'data-status="' + conv.status + '" ' +
                    'style="' + (shouldShow ? '' : 'display:none') + '">';
            html += '  <div class="conversation-avatar ' + (isClosed ? 'closed' : '') + '">' + initial + '</div>';
            html += '  <div class="conversation-details">';
            html += '    <div class="conversation-name">';
            html += '      <span class="text-dark">' + escapeHtml(conv.visitorName || 'Visitor') + '</span>';
            html += '      <span class="time">' + conv.updatedAt + '</span>';
            html += '    </div>';
            html += '    <div class="conversation-preview">';
            if (conv.latestMessage) {
                if (conv.latestMessage.senderType === 'admin') {
                    html += '<span class="text-secondary">You: </span>';
                }
                html += escapeHtml(conv.latestMessage.message.substring(0, 50));
            } else {
                html += '<span class="text-secondary">No messages yet</span>';
            }
            html += '    </div>';
            html += '  </div>';
            if (conv.unreadCount > 0 && !isActive) {
                html += '  <div class="unread-badge">' + conv.unreadCount + '</div>';
            }
            html += '</li>';
        });

        $list.html(html);
    }

    // ── New message notification ──
    var previousConversationCount = {{ $conversations->count() }};
    var originalTitle = document.title;
    var titleFlashInterval = null;

    function checkForNewConversations(conversations) {
        var totalUnread = 0;
        conversations.forEach(function(conv) {
            totalUnread += conv.unreadCount || 0;
        });

        // Flash title if there are unread messages and tab is not focused
        if (totalUnread > 0 && document.hidden) {
            if (!titleFlashInterval) {
                titleFlashInterval = setInterval(function() {
                    document.title = document.title === originalTitle
                        ? '(' + totalUnread + ') New message!'
                        : originalTitle;
                }, 1000);
            }
        }

        // Show toastr if a brand new conversation appeared
        if (conversations.length > previousConversationCount && previousConversationCount >= 0) {
            var newConv = conversations[0];
            toastr.info('New chat from ' + (newConv.visitorName || 'Visitor'), 'New Conversation');
        }
        previousConversationCount = conversations.length;
    }

    // Reset title when tab gets focus
    $(window).on('focus', function() {
        if (titleFlashInterval) {
            clearInterval(titleFlashInterval);
            titleFlashInterval = null;
            document.title = originalTitle;
        }
    });

    // ── Utility ──
    // ── Settings Panel ──
    $('#btnToggleSettings').on('click', function() {
        $('#conversationList, #chatSearchAndFilters').hide();
        $('#settingsPanel').show();
        updateCharCount();
    });

    $('#btnBackToChats').on('click', function() {
        $('#settingsPanel').hide();
        $('#conversationList, #chatSearchAndFilters').show();
    });

    // Character count
    function updateCharCount() {
        var len = $('#autoReplyMessage').val().length;
        $('#autoReplyCharCount').text(len + '/1000');
    }
    $('#autoReplyMessage').on('input', updateCharCount);

    // Save settings
    $('#btnSaveSettings').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: '{{ route("anisenso-website-chat-support.settings") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                auto_reply_enabled: $('#autoReplyEnabled').is(':checked') ? '1' : '0',
                auto_reply_message: $('#autoReplyMessage').val()
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save settings');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Settings');
            }
        });
    });

    // ── Utility ──
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
});
</script>
@endsection
