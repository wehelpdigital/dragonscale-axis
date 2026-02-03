@extends('layouts.master')

@section('title') AI Chat @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .chat-container {
        display: flex;
        height: calc(100vh - 200px);
        min-height: 500px;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        overflow: hidden;
        background: #fff;
    }

    /* Sessions Sidebar */
    .chat-sidebar {
        width: 280px;
        border-right: 1px solid #e9ecef;
        display: flex;
        flex-direction: column;
        background: #f8f9fa;
        flex-shrink: 0;
    }

    .chat-sidebar-header {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
        background: #fff;
    }

    .chat-sessions-list {
        flex: 1;
        overflow-y: auto;
        padding: 0.5rem;
    }

    .chat-session-item {
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        cursor: pointer;
        margin-bottom: 0.25rem;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .chat-session-item:hover {
        background: #e9ecef;
    }

    .chat-session-item.active {
        background: #556ee6;
        color: #fff;
    }

    .chat-session-item.active .session-time {
        color: rgba(255,255,255,0.8);
    }

    .session-info {
        flex: 1;
        min-width: 0;
    }

    .session-name {
        font-weight: 500;
        font-size: 0.75rem;
        line-height: 1.3;
        word-wrap: break-word;
        overflow-wrap: break-word;
        white-space: normal;
        max-height: 2.6em; /* Approximately 2 lines */
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .session-time {
        font-size: 0.65rem;
        color: #6c757d;
        margin-top: 0.15rem;
    }

    /* Search Section */
    .chat-search-section {
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid #e9ecef;
        background: #f8f9fa;
    }

    .chat-search-input {
        position: relative;
    }

    .chat-search-input input {
        width: 100%;
        padding: 0.4rem 0.75rem 0.4rem 2rem;
        font-size: 0.8rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
    }

    .chat-search-input input:focus {
        border-color: #556ee6;
        box-shadow: 0 0 0 0.1rem rgba(85, 110, 230, 0.25);
        outline: none;
    }

    .chat-search-input i {
        position: absolute;
        left: 0.6rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 0.85rem;
    }

    .chat-search-filters {
        display: none;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid #e9ecef;
    }

    .chat-search-filters.show {
        display: block;
    }

    .chat-search-filters label {
        font-size: 0.7rem;
        color: #6c757d;
        margin-bottom: 0.2rem;
    }

    .chat-search-filters input[type="date"] {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }

    .chat-search-toggle {
        font-size: 0.7rem;
        color: #556ee6;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        margin-top: 0.35rem;
    }

    .chat-search-toggle:hover {
        text-decoration: underline;
    }

    .search-results-info {
        font-size: 0.7rem;
        color: #6c757d;
        padding: 0.35rem 0;
        display: none;
    }

    .search-results-info.show {
        display: block;
    }

    .session-actions {
        opacity: 0;
        transition: opacity 0.2s;
    }

    .chat-session-item:hover .session-actions,
    .chat-session-item.active .session-actions {
        opacity: 1;
    }

    /* Load More Sessions */
    .load-more-sessions {
        padding: 0.5rem;
        border-top: 1px solid #e9ecef;
        background: #f8f9fa;
    }

    .load-more-sessions .btn {
        font-size: 0.75rem;
        padding: 0.4rem 0.75rem;
        width: 100%;
    }

    .load-more-sessions .session-count {
        font-size: 0.7rem;
        color: #6c757d;
        text-align: center;
        margin-top: 0.35rem;
    }

    .load-more-sessions .btn.loading {
        pointer-events: none;
        opacity: 0.7;
    }

    /* Main Chat Area */
    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .chat-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e9ecef;
        background: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        background: #f8f9fa;
    }

    .chat-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #6c757d;
    }

    .chat-empty i {
        font-size: 4rem;
        opacity: 0.3;
        margin-bottom: 1rem;
    }

    /* Message Bubbles */
    .message-wrapper {
        margin-bottom: 1rem;
        display: flex;
        flex-direction: column;
    }

    .message-wrapper.user {
        align-items: flex-end;
    }

    .message-wrapper.assistant {
        align-items: flex-start;
    }

    .message-bubble {
        max-width: 70%;
        padding: 0.75rem 1rem;
        border-radius: 1rem;
        position: relative;
    }

    .message-wrapper.user .message-bubble {
        background: #556ee6;
        color: #fff;
        border-bottom-right-radius: 0.25rem;
    }

    .message-wrapper.assistant .message-bubble {
        background: #fff;
        color: #495057;
        border: 1px solid #e9ecef;
        border-bottom-left-radius: 0.25rem;
    }

    /* AI Avatar styling */
    .ai-avatar {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 12px;
        border: 2px solid #e9ecef;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .message-wrapper.assistant,
    .message-wrapper.thinking {
        flex-direction: row;
    }

    .message-wrapper.assistant .message-content-wrapper,
    .message-wrapper.thinking .message-content-wrapper {
        display: flex;
        flex-direction: column;
        max-width: calc(100% - 46px);
    }

    .message-wrapper.thinking .message-bubble {
        background: #fff;
        color: #495057;
        border: 1px solid #e9ecef;
        border-bottom-left-radius: 0.25rem;
    }

    /* Disabled state for chat input while technician is thinking */
    .chat-input-area.thinking-in-progress {
        opacity: 0.7;
        pointer-events: none;
    }

    .chat-input-area.thinking-in-progress .chat-input-wrapper {
        background: #f5f5f5;
        align-items: center;
    }

    .chat-input-area.thinking-in-progress #messageInput {
        background: #f5f5f5;
        cursor: not-allowed;
        height: 38px !important;
        min-height: 38px !important;
        max-height: 38px !important;
        overflow: hidden;
    }

    .message-content {
        white-space: normal;
        word-break: break-word;
        line-height: 1.6;
    }

    .message-content p {
        margin-bottom: 0.5rem;
    }

    .message-content p:last-child {
        margin-bottom: 0;
    }

    /* Chat list styling for bullet points */
    .chat-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 1rem;
    }

    .chat-list li {
        position: relative;
        padding-left: 1.5rem;
        margin-bottom: 0.5rem;
        line-height: 1.5;
    }

    .chat-list li::before {
        content: "•";
        position: absolute;
        left: 0.5rem;
        color: #556ee6;
        font-weight: bold;
        font-size: 1.1em;
    }

    /* Nested content indentation */
    .message-content strong {
        font-weight: 600;
        color: #333;
    }

    .message-images {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .message-images img {
        max-width: 150px;
        max-height: 150px;
        border-radius: 0.5rem;
        cursor: pointer;
        object-fit: cover;
    }

    /* Generated Images Gallery - Small Square Thumbnails */
    .searched-images-gallery {
        border-top: 1px solid rgba(0,0,0,0.08);
        padding-top: 0.5rem;
        margin-top: 0.5rem;
    }

    .searched-images-label {
        font-size: 0.7rem;
        color: #888;
        font-weight: 400;
        margin-bottom: 0.35rem;
    }

    .searched-images-grid {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
    }

    .searched-image-item {
        position: relative;
        border-radius: 0.25rem;
        overflow: hidden;
        background: #eee;
        cursor: pointer;
        transition: transform 0.15s, box-shadow 0.15s;
        width: 60px;
        height: 60px;
        flex-shrink: 0;
    }

    .searched-image-item:hover {
        transform: scale(1.1);
        box-shadow: 0 3px 10px rgba(0,0,0,0.25);
        z-index: 1;
    }

    .searched-image-item:hover .image-overlay {
        opacity: 1;
    }

    .searched-image-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .image-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.15s;
    }

    .image-overlay i {
        color: white;
        font-size: 1rem;
    }

    .image-type-badge {
        position: absolute;
        bottom: 2px;
        right: 2px;
        font-size: 8px;
        font-weight: 600;
        padding: 1px 3px;
        border-radius: 2px;
        text-transform: uppercase;
        line-height: 1;
    }

    .ai-badge {
        background: rgba(102, 126, 234, 0.9);
        color: white;
    }

    .infographic-badge {
        background: rgba(220, 53, 69, 0.9);
        color: white;
    }

    .web-badge {
        background: rgba(40, 167, 69, 0.9);
        color: white;
    }

    .product-badge {
        background: rgba(255, 193, 7, 0.95);
        color: #212529;
        font-weight: 600;
    }

    /* Image Lightbox Modal */
    .image-lightbox-modal .modal-dialog {
        max-width: 90vw;
        margin: 1.75rem auto;
    }

    .image-lightbox-modal .modal-content {
        background: rgba(0,0,0,0.95);
        border: none;
        border-radius: 0.75rem;
    }

    .image-lightbox-modal .modal-header {
        border-bottom: none;
        padding: 1rem 1.5rem;
    }

    .image-lightbox-modal .modal-header .btn-close {
        filter: invert(1);
        opacity: 0.8;
    }

    .image-lightbox-modal .modal-header .btn-close:hover {
        opacity: 1;
    }

    .image-lightbox-modal .modal-body {
        padding: 0 1.5rem 1.5rem;
        text-align: center;
    }

    .image-lightbox-modal .lightbox-image {
        max-width: 100%;
        max-height: 70vh;
        border-radius: 0.5rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    }

    .image-lightbox-modal .lightbox-caption {
        color: #fff;
        padding: 1rem 0 0;
        text-align: center;
    }

    .image-lightbox-modal .lightbox-caption h5 {
        color: #fff;
        margin-bottom: 0.25rem;
        font-size: 1.1rem;
    }

    .image-lightbox-modal .lightbox-caption small {
        color: rgba(255,255,255,0.7);
        font-size: 0.85rem;
    }

    /* Legacy Image Viewer Modal (keep for backwards compatibility) */
    .image-viewer-modal .modal-content {
        background: transparent;
        border: none;
    }

    .image-viewer-modal .modal-body {
        padding: 0;
        text-align: center;
    }

    .image-viewer-modal img {
        max-width: 100%;
        max-height: 80vh;
        border-radius: 0.5rem;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    }

    .image-viewer-info {
        background: rgba(0,0,0,0.75);
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 0 0 0.5rem 0.5rem;
        margin-top: -4px;
    }

    .image-viewer-info a {
        color: #7dd3fc;
    }

    .message-meta {
        font-size: 0.7rem;
        margin-top: 0.25rem;
        opacity: 0.7;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .message-wrapper.user .message-meta {
        color: rgba(255,255,255,0.8);
    }

    /* Copy button */
    .copy-msg-btn {
        background: none;
        border: none;
        padding: 2px 6px;
        cursor: pointer;
        opacity: 0.5;
        font-size: 0.75rem;
        border-radius: 3px;
        transition: all 0.2s;
    }

    .copy-msg-btn:hover {
        opacity: 1;
        background: rgba(0,0,0,0.1);
    }

    .message-wrapper.user .copy-msg-btn {
        color: rgba(255,255,255,0.9);
    }

    .message-wrapper.assistant .copy-msg-btn {
        color: #495057;
    }

    .copy-msg-btn.copied {
        color: #28a745;
        opacity: 1;
    }

    /* Copy All Chat Button */
    .copy-all-chat-btn {
        font-size: 0.8rem;
    }

    /* Input Area */
    .chat-input-area {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e9ecef;
        background: #fff;
    }

    .image-preview-container {
        display: none;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
        border: 1px dashed #ced4da;
    }

    .image-preview-container.has-images {
        display: flex;
    }

    .image-preview-header {
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
    }

    .image-preview-header .title {
        font-size: 0.8rem;
        font-weight: 500;
        color: #495057;
    }

    .image-preview-header .title i {
        color: #556ee6;
    }

    .image-preview-header .count {
        font-size: 0.75rem;
        color: #6c757d;
    }

    .image-preview-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        width: 100%;
    }

    .image-preview-item {
        position: relative;
        width: 80px;
        height: 80px;
    }

    .image-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 0.5rem;
        border: 2px solid #e9ecef;
    }

    .image-preview-item .remove-image {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #f46a6a;
        color: #fff;
        border: none;
        font-size: 0.7rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* User uploaded images in message */
    .uploaded-images-in-msg {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid rgba(255,255,255,0.2);
    }

    .uploaded-images-in-msg img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 0.35rem;
        cursor: pointer;
        transition: transform 0.15s;
    }

    .uploaded-images-in-msg img:hover {
        transform: scale(1.1);
    }

    .uploaded-images-label {
        width: 100%;
        font-size: 0.7rem;
        opacity: 0.8;
        margin-bottom: 0.25rem;
    }

    .chat-input-wrapper {
        display: flex;
        gap: 0.75rem;
        align-items: flex-end;
    }

    .chat-input-actions {
        display: flex;
        gap: 0.5rem;
    }

    .chat-input-wrapper textarea {
        flex: 1;
        resize: none;
        border-radius: 1.5rem;
        padding: 0.6rem 1rem;
        max-height: 150px;
        min-height: 38px;
        height: 38px;
        line-height: 1.4;
    }

    .btn-send {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-attach {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Typing indicator - 3 bouncing dots */
    .typing-indicator-wrapper {
        display: none;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .typing-indicator-wrapper.show {
        display: flex;
    }

    .typing-indicator {
        display: inline-flex;
        padding: 0.75rem 1rem;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 1rem;
        max-width: 100px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        align-items: center;
        gap: 4px;
    }

    .typing-indicator span {
        width: 10px;
        height: 10px;
        background: #556ee6;
        border-radius: 50%;
        animation: typing-bounce 1.4s infinite ease-in-out;
    }

    .typing-indicator span:nth-child(1) { animation-delay: 0s; }
    .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

    @keyframes typing-bounce {
        0%, 80%, 100% {
            transform: scale(0.6);
            opacity: 0.5;
        }
        40% {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Typewriter cursor effect */
    .typewriter-cursor {
        display: inline-block;
        width: 2px;
        height: 1.1em;
        background-color: #556ee6;
        margin-left: 1px;
        vertical-align: text-bottom;
        animation: blink-cursor 0.7s step-end infinite;
    }

    @keyframes blink-cursor {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }

    /* Typing message style */
    .message-content.typing-active {
        min-height: 1.5em;
    }

    /* Image count badge */
    .image-count-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #556ee6;
        color: #fff;
        font-size: 0.65rem;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Flow status indicator */
    .flow-status {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
    }

    .flow-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    .flow-status-dot.active {
        background: #34c38f;
    }

    .flow-status-dot.inactive {
        background: #f46a6a;
    }

    /* Action buttons after AI response */
    .response-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
        flex-wrap: wrap;
    }

    .response-actions .btn {
        font-size: 0.8rem;
        padding: 0.4rem 0.75rem;
        border-radius: 1rem;
    }

    .response-actions .btn i {
        font-size: 0.9rem;
    }

    /* Follow-up notice */
    .followup-notice {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        display: none;
    }

    .followup-notice.show {
        display: block;
    }

    .followup-notice p {
        margin: 0;
        color: #856404;
        font-size: 0.9rem;
    }

    /* Question type indicator */
    .question-type-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
        border-radius: 0.25rem;
        margin-left: 0.5rem;
    }

    /* Disabled input state when action buttons are shown */
    .chat-input-area.awaiting-action {
        opacity: 0.6;
        pointer-events: none;
    }

    .chat-input-area.awaiting-action .chat-input-wrapper {
        background: #f8f9fa;
    }

    .chat-input-area.awaiting-action #messageInput {
        background: #f8f9fa;
        cursor: not-allowed;
    }

    .awaiting-action-notice {
        text-align: center;
        padding: 0.5rem;
        background: #e9ecef;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        color: #495057;
        display: none;
    }

    .awaiting-action-notice.show {
        display: block;
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Chat Technician @endslot
@slot('title') Chat @endslot
@endcomponent

<div class="chat-container">
    <!-- Sessions Sidebar -->
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <button type="button" class="btn btn-primary w-100" id="newChatBtn">
                <i class="bx bx-plus me-1"></i>New Chat
            </button>
        </div>

        <!-- Search Section -->
        <div class="chat-search-section">
            <div class="chat-search-input">
                <i class="bx bx-search"></i>
                <input type="text" id="chatSearchInput" placeholder="Search chats...">
            </div>
            <span class="chat-search-toggle" id="toggleSearchFilters">
                <i class="bx bx-filter-alt me-1"></i>Filters
            </span>
            <div class="chat-search-filters" id="searchFilters">
                <div class="row g-2">
                    <div class="col-6">
                        <label>From:</label>
                        <input type="date" class="form-control form-control-sm" id="searchStartDate">
                    </div>
                    <div class="col-6">
                        <label>To:</label>
                        <input type="date" class="form-control form-control-sm" id="searchEndDate">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-2" id="clearSearchBtn">
                    <i class="bx bx-x me-1"></i>Clear Filters
                </button>
            </div>
            <div class="search-results-info" id="searchResultsInfo">
                <span id="searchResultsCount">0</span> results found
            </div>
        </div>

        <div class="chat-sessions-list" id="sessionsList">
            @forelse($sessions as $session)
                <div class="chat-session-item {{ $currentSession && $currentSession->id == $session->id ? 'active' : '' }}"
                     data-session-id="{{ $session->id }}"
                     data-title-generated="{{ $session->isTitleGenerated() ? 'true' : 'false' }}">
                    <div class="session-info">
                        <div class="session-name text-dark">{{ $session->display_name }}</div>
                        <div class="session-time">{{ $session->last_message_ago }}</div>
                    </div>
                    <div class="session-actions">
                        <button type="button" class="btn btn-sm btn-link p-0 text-danger delete-session-btn"
                                data-session-id="{{ $session->id }}" title="Delete">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-secondary" id="noSessionsMessage">
                    <i class="bx bx-chat" style="font-size: 2rem; opacity: 0.3;"></i>
                    <p class="mb-0 mt-2 small">No chat sessions yet</p>
                </div>
            @endforelse
        </div>

        <!-- Load More Sessions -->
        @if($hasMoreSessions)
        <div class="load-more-sessions" id="loadMoreSection">
            <button type="button" class="btn btn-outline-primary btn-sm" id="loadMoreBtn">
                <i class="bx bx-chevron-down me-1"></i>Load More
            </button>
            <div class="session-count">
                Showing <span id="shownCount">{{ $sessions->count() }}</span> of <span id="totalCount">{{ $totalSessions }}</span> chats
            </div>
        </div>
        @endif
    </div>

    <!-- Main Chat Area -->
    <div class="chat-main">
        <div class="chat-header">
            <div>
                <h5 class="mb-0 text-dark" id="chatTitle">
                    {{ $currentSession ? $currentSession->display_name : 'Select or start a chat' }}
                </h5>
                <div class="flow-status mt-1">
                    <span class="flow-status-dot {{ $replyFlow->isActive ? 'active' : 'inactive' }}"></span>
                    <span class="text-secondary">{{ $replyFlow->isActive ? 'Online' : 'Offline' }}</span>
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-outline-info me-2" id="checkAiFlowBtn" data-bs-toggle="modal" data-bs-target="#aiFlowModal" title="View AI processing flow and search logs">
                    <i class="bx bx-code-alt me-1"></i>Check AI Flow
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary copy-all-chat-btn me-2" id="copyAllChatBtn" onclick="copyAllChat()" title="Copy all chat to clipboard">
                    <i class="bx bx-copy me-1"></i>Copy Chat
                </button>
                @if($currentSession)
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearChatBtn" title="Clear chat history">
                        <i class="bx bx-trash me-1"></i>Clear
                    </button>
                @endif
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            @if($currentSession)
                @forelse($messages as $message)
                    <div class="message-wrapper {{ $message->role }}" data-message-id="{{ $message->id }}">
                        <div class="message-bubble">
                            <div class="message-content">{{ $message->content }}</div>
                            @if($message->has_images)
                                <div class="message-images">
                                    @foreach($message->image_urls as $imageUrl)
                                        <img src="{{ $imageUrl }}" alt="Uploaded image" onclick="viewImage(this.src)">
                                    @endforeach
                                </div>
                            @endif
                            <div class="message-meta">
                                <span>
                                    {{ $message->formatted_time }}
                                    @if($message->processing_time_formatted)
                                        <span class="ms-1">({{ $message->processing_time_formatted }})</span>
                                    @endif
                                </span>
                                <button type="button" class="copy-msg-btn" onclick="copyMessage(this)" title="Copy message">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="chat-empty" id="chatEmpty">
                        <i class="bx bx-message-rounded-dots"></i>
                        <h5 class="text-dark">Start a conversation</h5>
                        <p>Send a message to begin chatting with our Technician</p>
                    </div>
                @endforelse
            @else
                <div class="chat-empty" id="chatEmpty">
                    <i class="bx bx-message-rounded-dots"></i>
                    <h5 class="text-dark">Welcome to Anisenso Technician</h5>
                    <p>Click "New Chat" to start a conversation</p>
                </div>
            @endif

            <!-- Typing Indicator with Avatar -->
            <div class="typing-indicator-wrapper" id="typingIndicator">
                <img src="{{ $avatarSettings->avatar_url }}" alt="{{ $avatarSettings->displayName }}" class="ai-avatar">
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>

        <div class="chat-input-area">
            <!-- Image Previews -->
            <div class="image-preview-container" id="imagePreviewContainer">
                <div class="image-preview-header">
                    <span class="title"><i class="bx bx-images me-1"></i>Images for Technician Analysis</span>
                    <span class="count" id="imagePreviewCount">0/10 images</span>
                </div>
                <div class="image-preview-grid" id="imagePreviewGrid"></div>
            </div>

            <div class="chat-input-wrapper">
                <div class="chat-input-actions">
                    <input type="file" id="imageInput" multiple accept="image/*" style="display: none;">
                    <button type="button" class="btn btn-outline-secondary btn-attach" id="attachBtn" title="Attach images (max 10)" {{ !$currentSession ? 'disabled' : '' }}>
                        <i class="bx bx-image-add"></i>
                        <span class="image-count-badge" id="imageCountBadge" style="display: none;">0</span>
                    </button>
                </div>
                <textarea class="form-control" id="messageInput" rows="1"
                          placeholder="Type your message..." {{ !$currentSession ? 'disabled' : '' }}></textarea>
                <button type="button" class="btn btn-primary btn-send" id="sendBtn" {{ !$currentSession ? 'disabled' : '' }}>
                    <i class="bx bx-send"></i>
                </button>
            </div>
            <small class="text-secondary mt-1 d-block">
                <i class="bx bx-info-circle me-1"></i>Press Enter to send, Shift+Enter for new line. Upload up to 10 images for deep analysis.
            </small>
        </div>
    </div>
</div>

<!-- Image Lightbox Modal -->
<div class="modal fade image-lightbox-modal" id="imageLightboxModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <img id="lightboxImage" src="" alt="Image" class="lightbox-image">
                <div class="lightbox-caption">
                    <h5 id="lightboxTitle"></h5>
                    <div class="lightbox-source" style="display: none;">
                        <small>Source: <a id="lightboxSourceLink" href="#" target="_blank" class="text-info"></a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Legacy Image Viewer Modal (for uploaded images) -->
<div class="modal fade" id="imageViewerModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Image Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img src="" id="imageViewerImg" class="img-fluid" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<!-- Delete Chat Confirmation Modal -->
<div class="modal fade" id="deleteChatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title text-dark">
                    <i class="bx bx-trash text-danger me-2"></i>Delete Chat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-2">Are you sure you want to delete this chat?</p>
                <p class="text-secondary mb-0"><small>This action cannot be undone. All messages in this chat will be permanently deleted.</small></p>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteChatBtn">
                    <i class="bx bx-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Clear Chat Confirmation Modal -->
<div class="modal fade" id="clearChatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title text-dark">
                    <i class="bx bx-eraser text-warning me-2"></i>Clear Chat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-2">Are you sure you want to clear all messages in this chat?</p>
                <p class="text-secondary mb-0"><small>The chat session will remain, but all messages will be permanently deleted.</small></p>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-warning" id="confirmClearChatBtn">
                    <i class="bx bx-eraser me-1"></i>Clear Messages
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Navigation While Thinking Modal -->
<div class="modal fade" id="thinkingNavigationModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom bg-warning bg-opacity-10">
                <h5 class="modal-title text-dark">
                    <i class="bx bx-loader-alt bx-spin text-warning me-2"></i>Nag-iisip pa ang Technician...
                </h5>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-3">Iniisip pa po ng technician ang sagot sa tanong ninyo. Pag umalis kayo ngayon, maka-cancel ito at hindi mo na makukuha ang sagot.</p>
                <p class="text-secondary mb-0"><strong>Ano po ang gusto ninyong gawin?</strong></p>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-primary" id="waitForResponseBtn">
                    <i class="bx bx-time me-1"></i>Hintayin ang Sagot
                </button>
                <button type="button" class="btn btn-danger" id="cancelAndNavigateBtn">
                    <i class="bx bx-x me-1"></i>I-cancel at Magpatuloy
                </button>
            </div>
        </div>
    </div>
</div>

<!-- AI Flow Modal -->
<div class="modal fade" id="aiFlowModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bx bx-code-alt me-2"></i>AI Processing Flow Log</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- No Flow Logs Yet -->
                <div id="aiFlowEmpty" class="text-center py-5">
                    <i class="bx bx-info-circle text-secondary" style="font-size: 3rem;"></i>
                    <p class="text-dark mt-3 mb-1">No AI flow logs yet</p>
                    <small class="text-secondary">Send a message to see the AI processing flow</small>
                </div>

                <!-- Flow Log Content -->
                <div id="aiFlowContent" style="display: none;">
                    <!-- Summary Section -->
                    <div class="p-3 bg-light border-bottom">
                        <div class="row">
                            <div class="col-md-4">
                                <small class="text-secondary">Question Type</small>
                                <div class="text-dark fw-medium" id="flowQuestionType">-</div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-secondary">AI Provider</small>
                                <div class="text-dark fw-medium" id="flowAiProvider">-</div>
                            </div>
                            <div class="col-md-4">
                                <small class="text-secondary">Processing Time</small>
                                <div class="text-dark fw-medium" id="flowProcessingTime">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Sections -->
                    <div class="accordion" id="aiFlowAccordion">
                        <!-- User Message -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#flowUserMessage">
                                    <i class="bx bx-user me-2 text-primary"></i>User Message
                                </button>
                            </h2>
                            <div id="flowUserMessage" class="accordion-collapse collapse show" data-bs-parent="#aiFlowAccordion">
                                <div class="accordion-body">
                                    <pre class="mb-0 p-3 bg-light rounded" id="flowUserMessageContent" style="white-space: pre-wrap; font-size: 0.85rem;">-</pre>
                                </div>
                            </div>
                        </div>

                        <!-- AI Response -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flowAiResponse">
                                    <i class="bx bx-bot me-2 text-info"></i>AI Response (Raw)
                                </button>
                            </h2>
                            <div id="flowAiResponse" class="accordion-collapse collapse" data-bs-parent="#aiFlowAccordion">
                                <div class="accordion-body">
                                    <pre class="mb-0 p-3 bg-light rounded" id="flowAiResponseContent" style="white-space: pre-wrap; font-size: 0.85rem; max-height: 400px; overflow-y: auto;">-</pre>
                                </div>
                            </div>
                        </div>

                        <!-- Flow Steps -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flowSteps">
                                    <i class="bx bx-git-branch me-2 text-purple"></i>Processing Steps
                                </button>
                            </h2>
                            <div id="flowSteps" class="accordion-collapse collapse" data-bs-parent="#aiFlowAccordion">
                                <div class="accordion-body p-0">
                                    <ul class="list-group list-group-flush" id="flowStepsList">
                                        <li class="list-group-item text-secondary">No steps recorded</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Token Usage & Cost -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flowTokenUsage">
                                    <i class="bx bx-coin me-2 text-warning"></i>Token Usage & Estimated Cost
                                </button>
                            </h2>
                            <div id="flowTokenUsage" class="accordion-collapse collapse" data-bs-parent="#aiFlowAccordion">
                                <div class="accordion-body">
                                    <!-- Totals Summary -->
                                    <div class="alert alert-light border mb-3">
                                        <div class="row text-center">
                                            <div class="col-3">
                                                <small class="text-secondary d-block">Input Tokens</small>
                                                <span class="fw-bold text-dark" id="flowTotalInputTokens">0</span>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-secondary d-block">Output Tokens</small>
                                                <span class="fw-bold text-dark" id="flowTotalOutputTokens">0</span>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-secondary d-block">Total Tokens</small>
                                                <span class="fw-bold text-primary" id="flowTotalTokens">0</span>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-secondary d-block">Est. Cost</small>
                                                <span class="fw-bold text-success" id="flowTotalCost">₱0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- By Provider -->
                                    <h6 class="text-dark mb-2"><i class="bx bx-server me-1"></i>By Provider</h6>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-dark">Provider</th>
                                                    <th class="text-center text-dark">Calls</th>
                                                    <th class="text-end text-dark">Input</th>
                                                    <th class="text-end text-dark">Output</th>
                                                    <th class="text-end text-dark">Total</th>
                                                    <th class="text-end text-dark">Cost</th>
                                                </tr>
                                            </thead>
                                            <tbody id="flowTokensByProvider">
                                                <tr><td colspan="6" class="text-center text-secondary">No token usage data</td></tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- By Node -->
                                    <h6 class="text-dark mb-2"><i class="bx bx-git-merge me-1"></i>By Flow Node</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-dark">Node</th>
                                                    <th class="text-dark">Provider/Model</th>
                                                    <th class="text-end text-dark">Input</th>
                                                    <th class="text-end text-dark">Output</th>
                                                    <th class="text-end text-dark">Total</th>
                                                    <th class="text-end text-dark">Cost</th>
                                                </tr>
                                            </thead>
                                            <tbody id="flowTokensByNode">
                                                <tr><td colspan="6" class="text-center text-secondary">No token usage data</td></tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Serper Web Search Usage -->
                                    <div id="serperUsageSection" class="mt-3" style="display: none;">
                                        <h6 class="text-dark mb-2"><i class="bx bx-search-alt me-1"></i>Web Search Usage (Serper)</h6>
                                        <div class="alert alert-light border">
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <small class="text-secondary d-block">Searches</small>
                                                    <span class="fw-bold text-dark" id="flowSerperSearches">0</span>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-secondary d-block">Credits Used</small>
                                                    <span class="fw-bold text-primary" id="flowSerperCredits">0</span>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-secondary d-block">Est. Cost</small>
                                                    <span class="fw-bold text-success" id="flowSerperCost">₱0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-dark">Search Query</th>
                                                        <th class="text-center text-dark">Results</th>
                                                        <th class="text-end text-dark">Credits</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="flowSerperQueries">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <small class="text-secondary d-block mt-2">
                                        <i class="bx bx-info-circle me-1"></i>Costs are estimates in Philippine Peso (₱{{ number_format($currencySettings->usdToPhpRate ?? 56, 2) }} = $1 USD). Serper: ₱{{ number_format(0.001 * ($currencySettings->usdToPhpRate ?? 56), 4) }} per search.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="copyAiFlowLog()">
                    <i class="bx bx-copy me-1"></i>Copy Log
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
// Exchange rate from settings (PHP per USD)
const usdToPhpRate = {{ $currencySettings->usdToPhpRate ?? 56 }};

// AI Avatar settings
const aiAvatarUrl = '{{ $avatarSettings->avatar_url ?? asset("images/ai-avatar-default.png") }}';
const aiDisplayName = '{{ $avatarSettings->displayName ?? "AI Technician" }}';

$(document).ready(function() {
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // State (using window for global access by search functions)
    window.currentSessionId = {{ $currentSession ? $currentSession->id : 'null' }};
    let currentSessionId = window.currentSessionId; // Local alias
    let selectedImages = [];
    let isSending = false;
    let questionType = 'new'; // 'new', 'followup'
    let lastQuestion = ''; // Store last question for follow-up validation
    let lastFlowLog = null; // Store the last flow log for the modal
    window.searchDebounceTimer = null; // For search debouncing

    // Navigation while thinking - AbortController and pending action
    let currentAbortController = null;
    let pendingNavigationAction = null; // { type: 'session'|'newChat'|'link', data: any }
    let isExecutingPendingNavigation = false; // Flag to bypass check when executing pending action
    let globalProgressTimer = null; // Global reference to progress timer

    // Function to check if navigating while thinking and show modal
    function checkNavigationWhileThinking(actionType, actionData = null) {
        console.log('checkNavigationWhileThinking called:', actionType, 'isSending:', isSending, 'isExecuting:', isExecutingPendingNavigation);

        // Skip check if we're executing a pending navigation
        if (isExecutingPendingNavigation) {
            console.log('Bypassing check - executing pending navigation');
            return false;
        }

        if (!isSending) {
            console.log('Not sending, allowing navigation');
            return false; // Not thinking, allow navigation
        }

        // Store the pending action
        pendingNavigationAction = { type: actionType, data: actionData };
        console.log('Showing modal for pending action:', pendingNavigationAction);

        // Show the confirmation modal
        $('#thinkingNavigationModal').modal('show');

        return true; // Block navigation, modal will handle it
    }

    // Function to cancel current thinking and clean up
    function cancelThinkingAndCleanup() {
        console.log('cancelThinkingAndCleanup called');

        // Abort the fetch request if possible
        if (currentAbortController) {
            currentAbortController.abort();
            currentAbortController = null;
        }

        // Stop progress timer (global reference)
        if (globalProgressTimer) {
            clearTimeout(globalProgressTimer);
            globalProgressTimer = null;
        }

        // Hide typing indicator
        $('#typingIndicator').removeClass('show');

        // Remove any thinking/progress messages
        $('.message-wrapper.thinking').remove();
        $('[data-message-id^="progress-"]').remove();

        // Reset sending state
        isSending = false;

        // Re-enable input
        enableChatInput('Type your message...');

        console.log('Cleanup complete, isSending:', isSending);
    }

    // Execute the pending navigation action
    function executePendingNavigation() {
        if (!pendingNavigationAction) return;

        const action = pendingNavigationAction;
        pendingNavigationAction = null;

        console.log('Executing pending navigation:', action);

        // Set flag to bypass the check
        isExecutingPendingNavigation = true;

        switch (action.type) {
            case 'session':
                selectSession(action.data);
                break;
            case 'newChat':
                // Directly create new chat instead of triggering click
                createNewChatDirect();
                break;
            case 'link':
                window.location.href = action.data;
                break;
        }

        // Reset flag after a short delay
        setTimeout(() => {
            isExecutingPendingNavigation = false;
        }, 100);
    }

    // Direct new chat creation (bypasses the click handler check)
    function createNewChatDirect() {
        const $btn = $('#newChatBtn');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Creating...');

        $.ajax({
            url: '/ai-technician-chat/session/create',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    currentSessionId = response.data.id;
                    addSessionToList(response.data);
                    selectSession(currentSessionId);

                    // Clear any pending images
                    selectedImages = [];
                    $('#imagePreviewGrid').empty();
                    $('#imagePreviewContainer').removeClass('has-images');
                    updateImageCount();

                    toastr.success('New chat created');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to create chat');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-plus me-1"></i>New Chat');
            }
        });
    }

    // Modal button handlers
    $('#waitForResponseBtn').on('click', function() {
        console.log('Wait button clicked');
        pendingNavigationAction = null;
        $('#thinkingNavigationModal').modal('hide');
    });

    $('#cancelAndNavigateBtn').on('click', function() {
        console.log('Cancel and navigate button clicked');
        $('#thinkingNavigationModal').modal('hide');
        cancelThinkingAndCleanup();
        executePendingNavigation();
    });

    // Intercept sidebar navigation links while thinking
    $(document).on('click', 'a[href]:not([href="#"]):not([href^="javascript"]):not(.no-thinking-check)', function(e) {
        const href = $(this).attr('href');
        // Skip if it's a hash link, javascript link, or same page
        if (!href || href === '#' || href.startsWith('javascript:') || href === window.location.href) {
            return;
        }

        // Check if AI is thinking
        if (checkNavigationWhileThinking('link', href)) {
            e.preventDefault();
            return false;
        }
    });

    // Generate AI title for a chat session based on its conversation
    function generateTitleForSession(sessionId) {
        if (!sessionId) return;

        // Show generating indicator in sidebar
        const $session = $(`.chat-session-item[data-session-id="${sessionId}"]`);
        const originalName = $session.find('.session-name').text();
        $session.find('.session-name').html('<i class="bx bx-loader-alt bx-spin me-1"></i>Generating title...');

        $.ajax({
            url: `/ai-technician-chat/session/${sessionId}/generate-title`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success && response.title) {
                    // Update the session name in the sidebar
                    $session.find('.session-name').text(response.title);
                    // Mark as title generated to prevent future regeneration
                    $session.data('title-generated', 'true');
                    $session.attr('data-title-generated', 'true');

                    // Update the header if it's the current session
                    if (currentSessionId == sessionId) {
                        $('#chatTitle').text(response.title);
                    }

                    console.log('Session title updated:', response.title);
                } else if (response.skipped) {
                    // Title was already generated, just mark it
                    $session.data('title-generated', 'true');
                    $session.attr('data-title-generated', 'true');
                    console.log('Title already generated, skipped');
                } else {
                    // Restore original name if failed
                    $session.find('.session-name').text(originalName);
                }
            },
            error: function(xhr) {
                console.error('Failed to generate title:', xhr.responseJSON?.message);
                // Restore original name on error
                $session.find('.session-name').text(originalName);
            }
        });
    }

    // Auto-scroll to bottom
    function scrollToBottom() {
        const $messages = $('#chatMessages');
        $messages.scrollTop($messages[0].scrollHeight);
    }

    // Initialize
    scrollToBottom();

    // Generate titles for sessions that haven't been renamed yet (on page load)
    function generateTitlesForUntitledSessions() {
        const sessionsToRename = [];

        // Find sessions that need title generation
        $('.chat-session-item').each(function() {
            const $session = $(this);
            const sessionId = $session.data('session-id');
            const sessionName = $session.find('.session-name').text().trim();
            const titleGenerated = $session.data('title-generated');

            // Skip if title was already generated (check data attribute)
            if (titleGenerated === true || titleGenerated === 'true') {
                return; // Skip this session
            }

            // If name is longer than 35 chars and title not yet generated, add to rename list
            if (sessionName.length > 35) {
                sessionsToRename.push({
                    id: sessionId,
                    $element: $session,
                    originalName: sessionName
                });
            }
        });

        console.log('Sessions needing titles:', sessionsToRename.length);

        // Process sessions one at a time with delay to avoid overwhelming server
        let index = 0;
        function processNext() {
            if (index >= sessionsToRename.length) {
                console.log('All untitled sessions processed');
                return;
            }

            const session = sessionsToRename[index];
            console.log('Generating title for session:', session.id);

            // Show loading indicator
            session.$element.find('.session-name').html('<i class="bx bx-loader-alt bx-spin me-1"></i><span class="text-muted small">Generating...</span>');

            $.ajax({
                url: `/ai-technician-chat/session/${session.id}/generate-title`,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success && response.title) {
                        session.$element.find('.session-name').text(response.title);
                        // Mark as title generated to prevent future regeneration
                        session.$element.data('title-generated', 'true');
                        session.$element.attr('data-title-generated', 'true');
                        // Update header if this is the current session
                        if (currentSessionId == session.id) {
                            $('#chatTitle').text(response.title);
                        }
                        console.log('Title generated for session', session.id, ':', response.title);
                    } else if (response.skipped) {
                        // Title was already generated on server, just mark it
                        session.$element.data('title-generated', 'true');
                        session.$element.attr('data-title-generated', 'true');
                        console.log('Title already generated for session', session.id);
                    } else {
                        // Restore original name on failure
                        session.$element.find('.session-name').text(session.originalName);
                    }
                },
                error: function() {
                    // Restore original name on error
                    session.$element.find('.session-name').text(session.originalName);
                },
                complete: function() {
                    index++;
                    // Process next session after a short delay
                    setTimeout(processNext, 1000);
                }
            });
        }

        // Start processing if there are sessions to rename
        if (sessionsToRename.length > 0) {
            // Start after a short delay to let page fully load
            setTimeout(processNext, 1500);
        }
    }

    // Run on page load
    generateTitlesForUntitledSessions();

    // New Chat
    $('#newChatBtn').on('click', function(e) {
        // Check if AI is thinking - show confirmation modal
        if (checkNavigationWhileThinking('newChat')) {
            return; // Modal will handle it
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Creating...');

        // Check if current chat is empty (no messages)
        const currentChatIsEmpty = currentSessionId && $('#chatMessages .message-wrapper').length === 0;
        const oldEmptySessionId = currentChatIsEmpty ? currentSessionId : null;

        // Function to create new chat
        function createNewChat() {
            $.ajax({
                url: '/ai-technician-chat/session/create',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        currentSessionId = response.data.id;
                        addSessionToList(response.data);
                        selectSession(currentSessionId);

                        // Clear any pending images
                        selectedImages = [];
                        $('#imagePreviewGrid').empty();
                        $('#imagePreviewContainer').removeClass('has-images');
                        updateImageCount();

                        toastr.success('New chat created');
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Failed to create chat');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="bx bx-plus me-1"></i>New Chat');
                }
            });
        }

        // If current chat is empty, delete it first then create new
        if (oldEmptySessionId) {
            $.ajax({
                url: '/ai-technician-chat/session/' + oldEmptySessionId,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function() {
                    // Remove from sidebar
                    $(`.chat-session-item[data-session-id="${oldEmptySessionId}"]`).remove();
                },
                complete: function() {
                    // Create new chat regardless of delete result
                    createNewChat();
                }
            });
        } else {
            createNewChat();
        }
    });

    // Add session to list
    function addSessionToList(session) {
        // Hide "no sessions" message if visible
        $('#noSessionsMessage').hide();

        const html = `
            <div class="chat-session-item" data-session-id="${session.id}" data-title-generated="false">
                <div class="session-info">
                    <div class="session-name text-dark">${escapeHtml(session.displayName)}</div>
                    <div class="session-time">Just now</div>
                </div>
                <div class="session-actions">
                    <button type="button" class="btn btn-sm btn-link p-0 text-danger delete-session-btn"
                            data-session-id="${session.id}" title="Delete">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#sessionsList').prepend(html);

        // Update session counts
        const shownCount = parseInt($('#shownCount').text() || 0) + 1;
        const totalCount = parseInt($('#totalCount').text() || 0) + 1;
        $('#shownCount').text(shownCount);
        $('#totalCount').text(totalCount);
        window.sessionsOffset = (window.sessionsOffset || 0) + 1;
    }

    // Select session
    function selectSession(sessionId) {
        $('.chat-session-item').removeClass('active');
        $(`.chat-session-item[data-session-id="${sessionId}"]`).addClass('active');

        currentSessionId = sessionId;
        window.currentSessionId = sessionId; // Update global reference

        // Reset question type to new (user can click Follow-up to change)
        questionType = 'new';
        // Note: lastQuestion will be restored from the conversation in renderMessages()

        // Remove any existing action buttons
        $('.response-actions').remove();

        // Clear any pending images when switching sessions
        selectedImages = [];
        $('#imagePreviewGrid').empty();
        $('#imagePreviewContainer').removeClass('has-images');
        updateImageCount();

        // Properly enable chat input (removes awaiting-action class too)
        enableChatInput('Type your message...');

        // Load messages (this will restore lastQuestion from the conversation)
        loadMessages(sessionId);
    }

    // Session click
    $(document).on('click', '.chat-session-item', function(e) {
        if ($(e.target).closest('.session-actions').length) return;
        const sessionId = $(this).data('session-id');

        // Don't switch if clicking on already active session
        if (sessionId === currentSessionId) return;

        // Check if AI is thinking - show confirmation modal
        if (checkNavigationWhileThinking('session', sessionId)) {
            return; // Modal will handle it
        }

        selectSession(sessionId);
    });

    // Load messages
    function loadMessages(sessionId) {
        $.ajax({
            url: `/ai-technician-chat/session/${sessionId}/messages`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#chatTitle').text(response.data.session.displayName);
                    renderMessages(response.data.messages);
                }
            },
            error: function(xhr) {
                toastr.error('Failed to load messages');
            }
        });
    }

    // Render messages
    function renderMessages(messages) {
        const $container = $('#chatMessages');
        $container.find('.message-wrapper, .chat-empty').remove();

        if (messages.length === 0) {
            $container.prepend(`
                <div class="chat-empty" id="chatEmpty">
                    <i class="bx bx-message-rounded-dots"></i>
                    <h5 class="text-dark">Start a conversation</h5>
                    <p>Send a message to begin chatting with our Technician</p>
                </div>
            `);
            // Ensure input is enabled for new/empty sessions
            enableChatInput('Type your message...');
            // Reset lastQuestion for empty sessions
            lastQuestion = '';
            return;
        }

        // IMPORTANT: Restore lastQuestion from the last user message
        // This ensures follow-up context works even after page refresh
        for (let i = messages.length - 1; i >= 0; i--) {
            if (messages[i].role === 'user' && messages[i].content) {
                lastQuestion = messages[i].content;
                console.log('Restored lastQuestion from conversation:', lastQuestion.substring(0, 50) + '...');
                break;
            }
        }

        // Find the last assistant message to show action buttons on it
        // Also restore the flowLog from the last assistant message
        let lastAssistantIndex = -1;
        for (let i = messages.length - 1; i >= 0; i--) {
            if (messages[i].role === 'assistant') {
                lastAssistantIndex = i;
                // Restore flow log from saved message
                if (messages[i].flowLog) {
                    lastFlowLog = messages[i].flowLog;
                    updateFlowLogModal(lastFlowLog);
                    console.log('Restored flowLog from saved message');
                }
                break;
            }
        }

        messages.forEach((msg, index) => {
            // Show action buttons only on the last assistant message
            const showActions = (index === lastAssistantIndex);
            // Don't use typewriter for existing messages (loaded from history)
            appendMessage(msg, showActions, false);
        });

        scrollToBottom();
    }

    // Disable chat input when awaiting action button selection
    function disableChatInput() {
        $('.chat-input-area').addClass('awaiting-action');
        $('#messageInput').prop('disabled', true).attr('placeholder', 'Please select an action above...');
        $('#sendBtn').prop('disabled', true);
        $('#attachBtn').prop('disabled', true);
    }

    // Enable chat input after action button is clicked
    function enableChatInput(placeholder = 'Type your message...') {
        $('.chat-input-area').removeClass('awaiting-action thinking-in-progress');
        $('#messageInput').prop('disabled', false).attr('placeholder', placeholder).css('height', 'auto').focus();
        $('#sendBtn').prop('disabled', false);
        $('#attachBtn').prop('disabled', false);
    }

    // Append a single message
    function appendMessage(msg, showActions = false, useTypewriter = true) {
        console.log('=== appendMessage CALLED ===');

        if (!msg) {
            console.error('appendMessage called with null/undefined msg');
            return;
        }

        $('#chatEmpty').remove();

        // User-uploaded images for analysis
        let imagesHtml = '';
        if (msg.hasImages && msg.images && msg.images.length > 0) {
            const imageCount = msg.images.length;
            imagesHtml = '<div class="uploaded-images-in-msg">';
            imagesHtml += `<div class="uploaded-images-label"><i class="bx bx-images me-1"></i>${imageCount} image${imageCount > 1 ? 's' : ''} for analysis</div>`;
            msg.images.forEach(url => {
                imagesHtml += `<img src="${url}" alt="Uploaded image" onclick="viewImage(this.src)">`;
            });
            imagesHtml += '</div>';
        }

        // Generated images (AI infographics + photos + web)
        let searchedImagesHtml = '';
        if (msg.searchedImages && msg.searchedImages.length > 0) {
            searchedImagesHtml = '<div class="searched-images-gallery">';
            searchedImagesHtml += '<div class="searched-images-label"><i class="bx bx-image me-1"></i>Visual Reference:</div>';
            searchedImagesHtml += '<div class="searched-images-grid">';
            msg.searchedImages.forEach((img) => {
                const title = escapeHtml(img.title || 'Image');
                const safeUrl = escapeHtml(img.url || img.thumbnail);
                const sourceUrl = escapeHtml(img.sourceUrl || '');
                const isGenerated = img.isGenerated === true;
                const imageType = img.imageType || 'photo';

                // Determine badge based on source and type
                let badgeClass, badgeText;

                // Check for explicit badge settings (e.g., from product images)
                if (img.badgeClass && img.badgeText) {
                    badgeClass = img.badgeClass;
                    badgeText = img.badgeText;
                } else if (imageType === 'product' || img.isProduct) {
                    badgeClass = 'product-badge';
                    badgeText = 'Product';
                } else if (!isGenerated) {
                    badgeClass = 'web-badge';
                    badgeText = 'Web';
                } else if (imageType === 'infographic') {
                    badgeClass = 'infographic-badge';
                    badgeText = 'Info';
                } else {
                    badgeClass = 'ai-badge';
                    badgeText = 'AI';
                }

                searchedImagesHtml += `
                    <div class="searched-image-item" onclick="openImageLightbox('${safeUrl}', '${title}', '${sourceUrl}')">
                        <img src="${img.thumbnail || img.url}"
                             alt="${title}"
                             loading="lazy"
                             onerror="this.parentElement.style.display='none'">
                        <div class="image-overlay">
                            <i class="bx bx-zoom-in"></i>
                        </div>
                        <span class="image-type-badge ${badgeClass}">${badgeText}</span>
                    </div>
                `;
            });
            searchedImagesHtml += '</div></div>';
        }

        const content = msg.content || '';
        const timeHtml = msg.processingTime
            ? `${msg.formattedTime || ''} <span class="ms-1">(${msg.processingTime})</span>`
            : (msg.formattedTime || '');

        // Action buttons for assistant messages (only show after final response)
        let actionsHtml = '';
        if (showActions && msg.role === 'assistant') {
            actionsHtml = `
                <div class="response-actions" style="display: none;">
                    <button type="button" class="btn btn-outline-primary btn-new-question" title="Start a new topic">
                        <i class="bx bx-plus me-1"></i>New Question
                    </button>
                    <button type="button" class="btn btn-outline-info btn-followup" title="Ask a follow-up about this topic">
                        <i class="bx bx-message-dots me-1"></i>Follow-up
                    </button>
                    <button type="button" class="btn btn-outline-success btn-done" title="Question answered, close this topic">
                        <i class="bx bx-check me-1"></i>Done
                    </button>
                </div>
            `;
        }

        // Typewriter effect disabled - show full response immediately (faster UX)
        const isAssistant = msg.role === 'assistant';
        const isThinking = msg.role === 'thinking';
        const shouldUseTypewriter = false; // Disabled - use 3 dots typing indicator instead

        // Format content - use formatAIContent for assistant messages to clean up markdown
        const formattedContent = shouldUseTypewriter
            ? '' // Will be filled by typewriter
            : (isAssistant ? formatAIContent(content) : escapeHtml(content).replace(/\n/g, '<br>'));

        const messageId = msg.id || 'msg-' + Date.now();

        // Build avatar HTML for assistant/thinking messages
        const avatarHtml = (isAssistant || isThinking)
            ? `<img src="${aiAvatarUrl}" alt="${aiDisplayName}" class="ai-avatar" title="${aiDisplayName}">`
            : '';

        // Build message HTML - wrap content for assistant messages
        let html;
        if (isAssistant || isThinking) {
            html = `
                <div class="message-wrapper ${msg.role || 'assistant'}" data-message-id="${messageId}">
                    ${avatarHtml}
                    <div class="message-content-wrapper">
                        <div class="message-bubble">
                            <div class="message-content" id="content-${messageId}">${formattedContent}</div>
                            ${imagesHtml}
                            ${searchedImagesHtml}
                            <div class="message-meta">
                                <span>${timeHtml}</span>
                                <button type="button" class="copy-msg-btn" onclick="copyMessage(this)" title="Copy message">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                        </div>
                        ${actionsHtml}
                    </div>
                </div>
            `;
        } else {
            html = `
                <div class="message-wrapper ${msg.role || 'assistant'}" data-message-id="${messageId}">
                    <div class="message-bubble">
                        <div class="message-content" id="content-${messageId}">${formattedContent}</div>
                        ${imagesHtml}
                        ${searchedImagesHtml}
                        <div class="message-meta">
                            <span>${timeHtml}</span>
                            <button type="button" class="copy-msg-btn" onclick="copyMessage(this)" title="Copy message">
                                <i class="bx bx-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        const $typingIndicator = $('#typingIndicator');
        if ($typingIndicator.length === 0) {
            $('#chatMessages').append(html);
        } else {
            $typingIndicator.before(html);
        }

        // Apply typewriter effect for assistant messages with showActions
        if (shouldUseTypewriter) {
            const $contentElement = $(`#content-${messageId}`);
            const $wrapper = $contentElement.closest('.message-wrapper');

            typeWriterEffect($contentElement, content, function() {
                // Show action buttons after typing is complete
                $wrapper.find('.response-actions').fadeIn(300);
                // Disable chat input
                disableChatInput();
                scrollToBottom();
            });
        } else if (showActions && msg.role === 'assistant') {
            // If not using typewriter, show actions immediately
            const $wrapper = $(`[data-message-id="${messageId}"]`);
            $wrapper.find('.response-actions').show();
            disableChatInput();
        }
    }

    // Handle action button clicks
    $(document).on('click', '.btn-new-question', function() {
        // Remove all action buttons
        $('.response-actions').remove();

        // Create a NEW chat session to avoid history confusion
        // This ensures each topic has its own clean conversation
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Creating...');

        $.ajax({
            url: '/ai-technician-chat/session/create',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    currentSessionId = response.data.id;
                    addSessionToList(response.data);
                    selectSession(currentSessionId);
                    toastr.success('New chat created - ask your new question!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to create new chat');
                // Fallback: just enable input in current session
                questionType = 'new';
                lastQuestion = '';
                enableChatInput('Ask a new question...');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-plus me-1"></i>New Question');
            }
        });
    });

    $(document).on('click', '.btn-followup', function() {
        // Remove all action buttons
        $('.response-actions').remove();

        // Set to follow-up mode
        questionType = 'followup';

        // Enable input with follow-up placeholder
        enableChatInput('Ask a follow-up question...');
        toastr.info('Ask your follow-up question');
    });

    $(document).on('click', '.btn-done', function() {
        // Remove all action buttons
        $('.response-actions').remove();

        // Reset state
        questionType = 'new';
        lastQuestion = '';

        // Show confirmation
        toastr.success('Great! Feel free to ask another question anytime.');

        // Enable input with default placeholder
        enableChatInput('Type your message...');
    });

    // Image attachment
    $('#attachBtn').on('click', function() {
        if (selectedImages.length >= 10) {
            toastr.warning('Maximum 10 images allowed');
            return;
        }
        $('#imageInput').click();
    });

    $('#imageInput').on('change', function() {
        const files = Array.from(this.files);
        const remaining = 10 - selectedImages.length;

        if (files.length > remaining) {
            toastr.warning(`Only ${remaining} more image(s) can be added`);
        }

        files.slice(0, remaining).forEach(file => {
            if (file.size > 5 * 1024 * 1024) {
                toastr.error(`${file.name} is too large (max 5MB)`);
                return;
            }
            selectedImages.push(file);
            addImagePreview(file);
        });

        updateImageCount();
        this.value = '';
    });

    function addImagePreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const index = selectedImages.indexOf(file);
            const html = `
                <div class="image-preview-item" data-index="${index}">
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="remove-image" data-index="${index}">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
            `;
            $('#imagePreviewGrid').append(html);
            updateImagePreviewContainer();
        };
        reader.readAsDataURL(file);
    }

    $(document).on('click', '.remove-image', function() {
        const index = $(this).data('index');
        selectedImages.splice(index, 1);
        rebuildImagePreviews();
        updateImageCount();
    });

    function rebuildImagePreviews() {
        $('#imagePreviewGrid').empty();
        selectedImages.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const html = `
                    <div class="image-preview-item" data-index="${index}">
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="remove-image" data-index="${index}">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                `;
                $('#imagePreviewGrid').append(html);
            };
            reader.readAsDataURL(file);
        });
        updateImagePreviewContainer();
    }

    function updateImagePreviewContainer() {
        const count = selectedImages.length;
        const $container = $('#imagePreviewContainer');

        if (count > 0) {
            $container.addClass('has-images');
            $('#imagePreviewCount').text(`${count}/10 image${count > 1 ? 's' : ''}`);
        } else {
            $container.removeClass('has-images');
        }
    }

    function updateImageCount() {
        const count = selectedImages.length;
        if (count > 0) {
            $('#imageCountBadge').text(count).show();
        } else {
            $('#imageCountBadge').hide();
        }
        updateImagePreviewContainer();
    }

    // Send message with streaming (thinking reply appears immediately)
    function sendMessage() {
        if (isSending || !currentSessionId) return;

        const message = $('#messageInput').val().trim();
        if (!message && selectedImages.length === 0) return;

        isSending = true;
        $('#sendBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        // Disable chat input while technician is thinking
        $('.chat-input-area').addClass('thinking-in-progress');
        $('#messageInput').prop('disabled', true).attr('placeholder', 'Nag-iisip ang technician...').css('height', '38px');

        // Show typing indicator IMMEDIATELY (before any async operations)
        $('#typingIndicator').addClass('show');
        scrollToBottom(); // Scroll to show typing indicator immediately

        // OPTIMISTIC UI: Show user message immediately
        const tempUserMsgId = 'temp-user-' + Date.now();
        const optimisticUserMsg = {
            id: tempUserMsgId,
            role: 'user',
            content: message || '[Image' + (selectedImages.length > 1 ? 's' : '') + ' uploaded]',
            hasImages: selectedImages.length > 0,
            images: [],
            formattedTime: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
        };

        // Build image previews for optimistic display (async, but indicator already shown)
        if (selectedImages.length > 0) {
            const imagePromises = selectedImages.map(file => {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = (e) => resolve(e.target.result);
                    reader.readAsDataURL(file);
                });
            });

            Promise.all(imagePromises).then(dataUrls => {
                optimisticUserMsg.images = dataUrls;
                appendMessage(optimisticUserMsg);
                scrollToBottom();
            });
        } else {
            appendMessage(optimisticUserMsg);
            scrollToBottom();
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('sessionId', currentSessionId);
        formData.append('message', message);
        formData.append('questionType', questionType);
        formData.append('lastQuestion', lastQuestion);

        selectedImages.forEach((file) => {
            formData.append('images[]', file);
        });

        // Store current question as last question for next follow-up
        lastQuestion = message;

        // Clear input immediately
        $('#messageInput').val('');
        const sentImages = [...selectedImages];
        selectedImages = [];
        $('#imagePreviewGrid').empty();
        $('#imagePreviewContainer').removeClass('has-images');
        updateImageCount();

        // Use streaming endpoint with fetch + EventSource pattern
        console.log('=== STARTING STREAM FETCH ===');
        console.log('FormData contents:', {
            sessionId: currentSessionId,
            message: message,
            imageCount: sentImages.length
        });

        // Progress messages for long processing (random 10-20 seconds)
        // Messages should sound like a knowledgeable technician thinking, not reading/searching
        const progressMessages = [
            'Sandali lang po, iniisip ko pa ang pinakamagandang sagot...',
            'Teka lang po, may dinadagdag lang po ako...',
            'Konting tiis lang po, pinag-iisipan ko mabuti ito...',
            'Hmmm, pag-isipan ko po muna mabuti ang sitwasyon ninyo...',
            'Halos tapos na po, sinisigurado ko lang na kumpleto ang payo ko...',
            'Sandali lang po, inaayos ko pa ang mga rekomendasyon ko...',
        ];
        let progressIndex = 0;
        let progressTimer = null;
        let progressMsgId = null;

        // Start progress timer - shows message every 10-20 seconds (random for natural feel)
        function startProgressTimer() {
            progressIndex = 0;

            function scheduleNextProgress() {
                // Random interval between 10-20 seconds (10000-20000ms)
                // Also assign to global variable for external cancellation
                const randomInterval = Math.floor(Math.random() * 10000) + 10000;

                progressTimer = setTimeout(() => {
                    // Remove previous progress message if exists
                    if (progressMsgId) {
                        $(`[data-message-id="${progressMsgId}"]`).fadeOut(200, function() {
                            $(this).remove();
                        });
                    }

                    // Show new progress message
                    progressMsgId = 'progress-' + Date.now();
                    const progressContent = progressMessages[progressIndex % progressMessages.length];
                    progressIndex++;

                    const progressHtml = `
                        <div class="message-wrapper thinking" data-message-id="${progressMsgId}">
                            <img src="${aiAvatarUrl}" alt="${aiDisplayName}" class="ai-avatar" title="${aiDisplayName}">
                            <div class="message-content-wrapper">
                                <div class="message-bubble">
                                    <div class="message-content"><em>${progressContent}</em></div>
                                </div>
                            </div>
                        </div>
                    `;

                    const $typingIndicator = $('#typingIndicator');
                    if ($typingIndicator.length > 0) {
                        $typingIndicator.before(progressHtml);
                    } else {
                        $('#chatMessages').append(progressHtml);
                    }
                    scrollToBottom();

                    // Ensure typing indicator stays visible
                    $('#typingIndicator').addClass('show');

                    // Schedule next progress message
                    scheduleNextProgress();
                }, randomInterval);

                // Also assign to global for external cancellation
                globalProgressTimer = progressTimer;
            }

            // Start the first timer
            scheduleNextProgress();
        }

        // Stop progress timer and clean up
        function stopProgressTimer() {
            if (progressTimer) {
                clearTimeout(progressTimer);
                progressTimer = null;
            }
            // Also clear global reference
            if (globalProgressTimer) {
                clearTimeout(globalProgressTimer);
                globalProgressTimer = null;
            }
            // Remove any remaining progress message
            if (progressMsgId) {
                $(`[data-message-id="${progressMsgId}"]`).fadeOut(200, function() {
                    $(this).remove();
                });
                progressMsgId = null;
            }
        }

        // Start progress timer immediately
        startProgressTimer();

        // Ensure typing indicator stays visible with periodic check
        const typingCheckInterval = setInterval(() => {
            if (isSending) {
                $('#typingIndicator').addClass('show');
            }
        }, 1000);

        // Create AbortController for this request (allows cancellation)
        currentAbortController = new AbortController();

        fetch('/ai-technician-chat/message/stream', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'text/event-stream',
            },
            signal: currentAbortController.signal
        }).then(response => {
            console.log('=== FETCH RESPONSE ===');
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));

            if (!response.ok) {
                stopProgressTimer();
                clearInterval(typingCheckInterval);
                throw new Error('Network response was not ok: ' + response.status);
            }

            if (!response.body) {
                console.error('Response body is null! Browser may not support streaming.');
                stopProgressTimer();
                clearInterval(typingCheckInterval);
                throw new Error('Streaming not supported');
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let thinkingShown = false;
            let chunkCount = 0;
            let receivedAnyEvent = false;
            let receivedResponse = false; // Track if we got a response

            // Timeout fallback - if no events received after 120 seconds, show error
            const streamTimeout = setTimeout(() => {
                if (!receivedAnyEvent) {
                    console.error('=== STREAM TIMEOUT - No events received after 120 seconds ===');
                    toastr.error('Response timed out. Please try again.');
                    stopProgressTimer();
                    clearInterval(typingCheckInterval);
                    handleStreamError(tempUserMsgId, message, sentImages);
                }
            }, 120000);

            function processStream() {
                reader.read().then(({ done, value }) => {
                    chunkCount++;
                    console.log(`=== STREAM CHUNK #${chunkCount} ===`);
                    console.log('Done:', done);

                    if (done) {
                        console.log('=== STREAM COMPLETE ===');
                        console.log('Final buffer:', buffer);
                        console.log('Received response:', receivedResponse);

                        // Stop progress timer and typing check
                        stopProgressTimer();
                        clearInterval(typingCheckInterval);

                        // Stream complete
                        isSending = false;
                        $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                        $('#attachBtn').prop('disabled', false);
                        $('#typingIndicator').removeClass('show');

                        // Re-enable chat input
                        $('.chat-input-area').removeClass('thinking-in-progress');
                        $('#messageInput').prop('disabled', false).attr('placeholder', 'Type your message...').css('height', 'auto').focus();

                        // If stream ended but no response was received, enable input
                        if (!receivedResponse) {
                            console.warn('Stream ended without response event');
                            enableChatInput('Type your message...');
                        }
                        return;
                    }

                    const chunk = decoder.decode(value, { stream: true });
                    console.log('Raw chunk received:', chunk);
                    console.log('Chunk length:', chunk.length);

                    buffer += chunk;
                    console.log('Buffer after append:', buffer.substring(0, 500) + (buffer.length > 500 ? '...' : ''));

                    // Parse SSE events from buffer
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // Keep incomplete line in buffer

                    console.log('Lines to process:', lines.length);
                    console.log('Lines:', lines);
                    console.log('Remaining buffer:', buffer);

                    let currentEvent = null;
                    for (const line of lines) {
                        console.log('Processing line:', line);

                        if (line.startsWith(':')) {
                            console.log('Skipping SSE comment line');
                            continue;
                        }

                        if (line.startsWith('event: ')) {
                            currentEvent = line.substring(7).trim();
                            console.log('Found event:', currentEvent);
                        } else if (line.startsWith('data: ') && currentEvent) {
                            console.log('Found data for event:', currentEvent);
                            try {
                                const data = JSON.parse(line.substring(6));
                                console.log('Parsed data:', data);
                                console.log('Calling handleStreamEvent...');

                                // Mark that we received an event and clear timeout
                                if (!receivedAnyEvent) {
                                    receivedAnyEvent = true;
                                    clearTimeout(streamTimeout);
                                    console.log('First event received, timeout cleared');
                                }

                                handleStreamEvent(currentEvent, data, tempUserMsgId, thinkingShown);
                                console.log('handleStreamEvent completed');

                                if (currentEvent === 'thinking') {
                                    thinkingShown = true;
                                }

                                // Track if we received a final response
                                if (['response', 'blocked', 'not_related'].includes(currentEvent)) {
                                    receivedResponse = true;
                                }
                            } catch (e) {
                                console.error('Failed to parse SSE data:', e);
                                console.error('Raw data string:', line.substring(6));
                            }
                            currentEvent = null;
                        } else if (line.trim() === '') {
                            console.log('Empty line (event separator)');
                        } else {
                            console.log('Unrecognized line format');
                        }
                    }

                    // Continue reading
                    processStream();
                }).catch(error => {
                    // If the request was aborted by user, don't show error
                    if (error.name === 'AbortError') {
                        console.log('=== STREAM READ ABORTED BY USER ===');
                        $(`.message-wrapper[data-message-id="${tempUserMsgId}"]`).remove();
                        return;
                    }

                    console.error('=== STREAM READ ERROR ===');
                    console.error('Error:', error);
                    handleStreamError(tempUserMsgId, message, sentImages);
                });
            }

            processStream();

        }).catch(error => {
            // If the request was aborted by user (navigation), don't show error
            if (error.name === 'AbortError') {
                console.log('=== FETCH ABORTED BY USER ===');
                // Clean up the optimistic user message
                $(`.message-wrapper[data-message-id="${tempUserMsgId}"]`).remove();
                return;
            }

            console.error('=== FETCH ERROR ===');
            console.error('Error:', error);
            handleStreamError(tempUserMsgId, message, sentImages);
        });
    }

    // Handle streaming events
    function handleStreamEvent(event, data, tempUserMsgId, thinkingShown) {
        console.log('=== handleStreamEvent CALLED ===');
        console.log('Event type:', event);
        console.log('Data:', JSON.stringify(data, null, 2));
        console.log('tempUserMsgId:', tempUserMsgId);
        console.log('thinkingShown:', thinkingShown);

        switch (event) {
            case 'user_message':
                // Remove optimistic message and show real one
                console.log('=== PROCESSING USER_MESSAGE EVENT ===');
                console.log('Removing optimistic message:', tempUserMsgId);
                const $optimistic = $(`.message-wrapper[data-message-id="${tempUserMsgId}"]`);
                console.log('Found optimistic message element:', $optimistic.length);
                $optimistic.remove();
                console.log('Appending real user message...');
                appendMessage(data);
                scrollToBottom();
                break;

            case 'thinking':
                // Show thinking reply IMMEDIATELY (this is the key!)
                console.log('=== PROCESSING THINKING EVENT ===');
                console.log('Thinking content:', data.content);
                $('#typingIndicator').removeClass('show');
                const thinkingMsg = {
                    id: 'thinking-' + Date.now(),
                    role: 'thinking', // Use 'thinking' role for yellow styling
                    content: data.content || '[Thinking...]',
                    formattedTime: data.formattedTime
                };
                console.log('Appending thinking message...');
                // No typewriter for thinking messages - show immediately
                appendMessage(thinkingMsg, false, false);

                // Show typing indicator IMMEDIATELY after thinking message (no delay)
                // This shows the user that processing is continuing
                $('#typingIndicator').addClass('show');
                scrollToBottom();
                console.log('Typing indicator shown after thinking message');
                break;

            case 'blocked':
                // Show block message (from Blocker element)
                $('#typingIndicator').removeClass('show');
                const blockMsg = {
                    id: 'blocked-' + Date.now(),
                    role: 'assistant',
                    content: data.content,
                    formattedTime: data.formattedTime
                };
                appendMessage(blockMsg);
                scrollToBottom();
                break;

            case 'response':
                // Show final response
                console.log('=== PROCESSING RESPONSE EVENT ===');

                $('#typingIndicator').removeClass('show');

                // Store flow log for the modal
                if (data.flowLog) {
                    lastFlowLog = data.flowLog;
                    console.log('Flow log received:', lastFlowLog);
                    updateFlowLogModal(lastFlowLog);
                }

                // Check for searched images
                console.log('Images in response:', data.images);

                // Show action buttons with the response
                appendMessage({
                    id: data.id,
                    role: data.role || 'assistant',
                    content: data.content || '[No response received]',
                    formattedTime: data.formattedTime,
                    processingTime: data.processingTime,
                    searchedImages: data.images || [] // Images from search
                }, true); // true = show action buttons
                scrollToBottom();

                // Update session name in sidebar
                if (data.sessionName) {
                    const $session = $(`.chat-session-item[data-session-id="${currentSessionId}"]`);
                    $session.find('.session-name').text(data.sessionName);
                    $session.find('.session-time').text('Just now');
                    $('#sessionsList').prepend($session);
                }
                break;

            case 'not_related':
                // Follow-up question is not related to original topic
                $('#typingIndicator').removeClass('show');

                appendMessage({
                    id: 'notice-' + Date.now(),
                    role: 'assistant',
                    content: data.content || 'Mukhang hindi po ito related sa previous question natin. Gusto niyo po bang mag-start ng new chat para dito?',
                    formattedTime: data.formattedTime
                }, true);
                scrollToBottom();

                // Reset to new question mode since it's not related
                questionType = 'new';
                break;

            case 'done':
                // Stream complete
                console.log('=== PROCESSING DONE EVENT ===');
                isSending = false;
                $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                $('#attachBtn').prop('disabled', false);
                $('#typingIndicator').removeClass('show');

                // Generate AI title after first exchange if name is still long
                setTimeout(function() {
                    const $currentSession = $(`.chat-session-item[data-session-id="${currentSessionId}"]`);
                    const currentName = $currentSession.find('.session-name').text().trim();

                    // Generate title if name is too long (hasn't been renamed yet)
                    if (currentName.length > 35 && !currentName.includes('Generating')) {
                        console.log('Generating AI title for current chat...');
                        generateTitleForSession(currentSessionId);
                    }
                }, 1000);

                console.log('UI reset complete');
                break;

            case 'error':
                toastr.error(data.message || 'An error occurred');
                break;
        }
    }

    // Handle stream errors
    function handleStreamError(tempUserMsgId, message, sentImages) {
        $(`.message-wrapper[data-message-id="${tempUserMsgId}"]`).remove();
        toastr.error('Failed to send message. Please try again.');
        $('#messageInput').val(message);
        selectedImages = sentImages;
        rebuildImagePreviews();
        updateImageCount();
        isSending = false;
        $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
        $('#typingIndicator').removeClass('show');

        // Ensure chat input is enabled after error
        enableChatInput('Type your message...');
    }

    $('#sendBtn').on('click', sendMessage);

    $('#messageInput').on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-resize textarea
    $('#messageInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 150) + 'px';
    });

    // Delete session - show modal
    let sessionToDelete = null;

    $(document).on('click', '.delete-session-btn', function(e) {
        e.stopPropagation();
        sessionToDelete = $(this).data('session-id');
        $('#deleteChatModal').modal('show');
    });

    // Confirm delete session
    $('#confirmDeleteChatBtn').on('click', function() {
        if (!sessionToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: `/ai-technician-chat/session/${sessionToDelete}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $(`.chat-session-item[data-session-id="${sessionToDelete}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });

                    if (currentSessionId == sessionToDelete) {
                        currentSessionId = null;
                        $('#chatMessages').html(`
                            <div class="chat-empty" id="chatEmpty">
                                <i class="bx bx-message-rounded-dots"></i>
                                <h5 class="text-dark">Welcome to Anisenso Technician</h5>
                                <p>Click "New Chat" to start a conversation</p>
                            </div>
                        `);
                        $('#chatTitle').text('Select or start a chat');
                        $('#messageInput').prop('disabled', true);
                        $('#sendBtn').prop('disabled', true);
                        $('#attachBtn').prop('disabled', true);
                    }

                    $('#deleteChatModal').modal('hide');
                    toastr.success('Chat deleted');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to delete chat');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
                sessionToDelete = null;
            }
        });
    });

    // Clear chat - show modal
    $('#clearChatBtn').on('click', function() {
        if (!currentSessionId) {
            toastr.warning('Please select a chat first');
            return;
        }
        $('#clearChatModal').modal('show');
    });

    // Confirm clear chat
    $('#confirmClearChatBtn').on('click', function() {
        if (!currentSessionId) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Clearing...');

        $.ajax({
            url: `/ai-technician-chat/session/${currentSessionId}/clear`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#chatMessages').find('.message-wrapper').remove();
                    $('#chatMessages').prepend(`
                        <div class="chat-empty" id="chatEmpty">
                            <i class="bx bx-message-rounded-dots"></i>
                            <h5 class="text-dark">Chat cleared</h5>
                            <p>Send a message to continue</p>
                        </div>
                    `);
                    $('#clearChatModal').modal('hide');
                    toastr.success('Chat cleared');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to clear chat');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-eraser me-1"></i>Clear Messages');
            }
        });
    });

    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Format AI response content - convert markdown to HTML with proper styling
    function formatAIContent(text) {
        if (!text) return '';

        // Normalize line endings (Windows \r\n, old Mac \r, Unix \n → all to \n)
        text = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n');

        // Debug: Log if we have newlines
        const newlineCount = (text.match(/\n/g) || []).length;
        console.log('formatAIContent: text length', text.length, 'newline count:', newlineCount);

        // FALLBACK: If text is long but has very few newlines, insert newlines before common section markers
        // This handles cases where Gemini doesn't include proper line breaks
        if (text.length > 200 && newlineCount < 5) {
            console.log('formatAIContent: Low newline count detected, inserting newlines before section markers');
            // Insert newlines before emoji headers (🔍, 📋, 📊, 🔬, 🎯, ⚖️, ⚠️, 💡, 🌱, ✅)
            text = text.replace(/(🔍|📋|📊|🔬|🎯|⚖️|⚠️|💡|🌱|✅|🦠|🐛)/g, '\n\n$1');
            // Insert newlines before common Tagalog section headers
            text = text.replace(/(NAKIKITA KO|DETALYADONG OBSERBASYON|PAGSUSURI|DIYAGNOSIS|MGA REKOMENDASYON|PAGHAHAMBING|Mahalagang Paalala|Mahahalagang Paalala)/gi, '\n\n$1');
            // Insert newlines before "===" headers
            text = text.replace(/(===)/g, '\n\n$1');
            // Clean up multiple newlines
            text = text.replace(/\n{3,}/g, '\n\n');
            text = text.trim();
            console.log('formatAIContent: After fallback newline count:', (text.match(/\n/g) || []).length);
        }

        // First escape HTML for safety
        let content = escapeHtml(text);

        // Convert markdown bold ** and __ to <strong>
        content = content.replace(/\*\*([^*]+)\*\*/g, '<strong class="text-dark">$1</strong>');
        content = content.replace(/__([^_]+)__/g, '<strong class="text-dark">$1</strong>');

        // Convert markdown links [text](url) to clickable HTML links
        content = content.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" class="text-primary">$1</a>');

        // Convert plain URLs to clickable links (if not already linked)
        content = content.replace(/(?<!href="|">)(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" class="text-primary">$1</a>');

        // Split into lines for processing
        let lines = content.split('\n');
        let formattedLines = [];
        let inList = false;
        let listItems = [];

        for (let i = 0; i < lines.length; i++) {
            let line = lines[i].trim();

            // Check for bullet points (-, *, •, numbered lists)
            const bulletMatch = line.match(/^([-*•]|\d+[.)])\s+(.+)$/);

            if (bulletMatch) {
                if (!inList) {
                    inList = true;
                    listItems = [];
                }
                // Add bullet with proper spacing
                const bulletContent = bulletMatch[2];
                listItems.push(`<li class="mb-2">${bulletContent}</li>`);
            } else {
                // Close list if we were in one
                if (inList && listItems.length > 0) {
                    formattedLines.push(`<ul class="chat-list mb-3">${listItems.join('')}</ul>`);
                    listItems = [];
                    inList = false;
                }

                // Handle empty lines - add spacing
                if (line === '') {
                    formattedLines.push('<div class="mb-2"></div>');
                }
                // Handle headers (lines ending with :)
                else if (line.match(/^[^:]+:$/) && line.length < 80) {
                    formattedLines.push(`<div class="mt-3 mb-2"><strong class="text-dark">${line}</strong></div>`);
                }
                // Regular paragraph
                else {
                    formattedLines.push(`<p class="mb-2">${line}</p>`);
                }
            }
        }

        // Close any remaining list
        if (inList && listItems.length > 0) {
            formattedLines.push(`<ul class="chat-list mb-3">${listItems.join('')}</ul>`);
        }

        return formattedLines.join('');
    }

    /**
     * Typewriter effect with realistic typing simulation
     * Includes random pauses, variable speed, and occasional "mistakes"
     */
    let typewriterActive = false;
    let typewriterQueue = [];

    function typeWriterEffect($element, text, callback) {
        const formattedText = formatAIContent(text);
        let displayText = '';
        let i = 0;
        let isDeleting = false;
        let deleteCount = 0;
        let mistakeChance = 0.02; // 2% chance of making a "mistake"
        let inMistake = false;
        let mistakeLength = 0;

        typewriterActive = true;

        // Add cursor
        $element.addClass('typing-active');
        $element.html('<span class="typewriter-cursor"></span>');

        function getRandomDelay(base, variance) {
            return base + Math.random() * variance;
        }

        function type() {
            // If we're in a mistake, delete characters
            if (inMistake && deleteCount > 0) {
                displayText = displayText.slice(0, -1);
                deleteCount--;
                $element.html(displayText + '<span class="typewriter-cursor"></span>');

                if (deleteCount === 0) {
                    inMistake = false;
                    // Pause after deleting mistake
                    setTimeout(type, getRandomDelay(200, 150));
                } else {
                    setTimeout(type, getRandomDelay(30, 20)); // Fast delete
                }
                return;
            }

            // Finished typing
            if (i >= formattedText.length) {
                $element.removeClass('typing-active');
                $element.html(displayText); // Remove cursor
                typewriterActive = false;
                if (callback) callback();
                return;
            }

            // Check for HTML tags - type them instantly
            if (formattedText[i] === '<') {
                let tagEnd = formattedText.indexOf('>', i);
                if (tagEnd !== -1) {
                    displayText += formattedText.substring(i, tagEnd + 1);
                    i = tagEnd + 1;
                    $element.html(displayText + '<span class="typewriter-cursor"></span>');
                    setTimeout(type, 5); // Very fast for tags
                    return;
                }
            }

            // Check for HTML entities (&nbsp;, &amp;, etc.)
            if (formattedText[i] === '&') {
                let entityEnd = formattedText.indexOf(';', i);
                if (entityEnd !== -1 && entityEnd - i < 10) {
                    displayText += formattedText.substring(i, entityEnd + 1);
                    i = entityEnd + 1;
                    $element.html(displayText + '<span class="typewriter-cursor"></span>');
                    setTimeout(type, getRandomDelay(20, 30));
                    return;
                }
            }

            // Random chance to make a "mistake" (only for regular characters, not at start)
            if (!inMistake && Math.random() < mistakeChance && i > 20 && formattedText[i].match(/[a-zA-Z]/)) {
                // Type 1-3 wrong characters then delete them
                mistakeLength = Math.floor(Math.random() * 3) + 1;
                const wrongChars = 'abcdefghijklmnopqrstuvwxyz';
                for (let m = 0; m < mistakeLength; m++) {
                    displayText += wrongChars[Math.floor(Math.random() * wrongChars.length)];
                }
                $element.html(displayText + '<span class="typewriter-cursor"></span>');
                inMistake = true;
                deleteCount = mistakeLength;
                setTimeout(type, getRandomDelay(150, 100)); // Pause before realizing mistake
                return;
            }

            // Type the next character
            displayText += formattedText[i];
            i++;
            $element.html(displayText + '<span class="typewriter-cursor"></span>');

            // Variable delays based on character type
            let delay;
            const char = formattedText[i - 1];

            if (char === '.' || char === '!' || char === '?') {
                // Longer pause after sentence endings
                delay = getRandomDelay(250, 200);
            } else if (char === ',' || char === ':' || char === ';') {
                // Medium pause after punctuation
                delay = getRandomDelay(120, 80);
            } else if (char === ' ') {
                // Slight pause between words
                delay = getRandomDelay(30, 25);
            } else if (char === '\n' || (formattedText.substring(i-4, i) === '<br>')) {
                // Pause at line breaks
                delay = getRandomDelay(180, 120);
            } else {
                // Normal typing speed with variation
                delay = getRandomDelay(12, 20);
            }

            // Occasional random "thinking" pause (like human hesitation)
            if (Math.random() < 0.015) {
                delay += getRandomDelay(150, 250);
            }

            setTimeout(type, delay);
        }

        // Scroll to keep typing visible
        function scrollDuringTyping() {
            if (typewriterActive) {
                scrollToBottom();
                setTimeout(scrollDuringTyping, 500);
            }
        }
        scrollDuringTyping();

        // Start typing after a brief pause
        setTimeout(type, getRandomDelay(100, 150));
    }
});

// View user-uploaded image in modal (simple view)
function viewImage(src) {
    $('#imageViewerImg').attr('src', src);
    $('#imageViewerTitle').text('Uploaded Image');
    $('#imageViewerPhotographer').text('User').attr('href', '#');
    $('#imageViewerSource').text('Upload');
    $('#imageViewerModal').modal('show');
}

// Open image in lightbox modal
function openImageLightbox(url, title, sourceUrl) {
    $('#lightboxImage').attr('src', url);
    $('#lightboxTitle').text(title || '');

    // Show source link if available
    if (sourceUrl) {
        const domain = sourceUrl.replace(/^https?:\/\//, '').split('/')[0];
        $('#lightboxSourceLink').attr('href', sourceUrl).text(domain).parent().show();
    } else {
        $('#lightboxSourceLink').parent().hide();
    }

    $('#imageLightboxModal').modal('show');
}

// Legacy: View searched image in modal with attribution
function viewSearchedImage(url, title, source, photographer) {
    openImageLightbox(url, title, '');
}

// Fallback copy function for HTTP (non-secure context)
function copyToClipboard(text) {
    // Try modern API first
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(text);
    }

    // Fallback for HTTP/localhost
    return new Promise((resolve, reject) => {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        textarea.style.top = '-9999px';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        try {
            const success = document.execCommand('copy');
            document.body.removeChild(textarea);
            if (success) {
                resolve();
            } else {
                reject(new Error('Copy command failed'));
            }
        } catch (err) {
            document.body.removeChild(textarea);
            reject(err);
        }
    });
}

// Copy single message to clipboard (content only, no time)
function copyMessage(btn) {
    const $wrapper = $(btn).closest('.message-wrapper');
    const content = $wrapper.find('.message-content').text().trim();

    copyToClipboard(content).then(() => {
        const $btn = $(btn);
        const originalHtml = $btn.html();
        $btn.addClass('copied').html('<i class="bx bx-check"></i>');
        setTimeout(() => {
            $btn.removeClass('copied').html(originalHtml);
        }, 1500);
        toastr.success('Message copied!');
    }).catch(err => {
        console.error('Copy failed:', err);
        toastr.error('Failed to copy');
    });
}

// Update the AI Flow Log modal with the latest flow log data
function updateFlowLogModal(flowLog) {
    if (!flowLog) {
        $('#aiFlowEmpty').show();
        $('#aiFlowContent').hide();
        return;
    }

    $('#aiFlowEmpty').hide();
    $('#aiFlowContent').show();

    // Update summary section
    $('#flowQuestionType').text(flowLog.questionType || 'General');
    $('#flowAiProvider').text(flowLog.aiProvider || 'Not specified');
    $('#flowProcessingTime').text(flowLog.processingTime ? flowLog.processingTime + 's' : '-');

    // Update user message
    $('#flowUserMessageContent').text(flowLog.userMessage || '-');

    // Update AI response
    $('#flowAiResponseContent').text(flowLog.aiResponse || 'No response recorded');

    // Update processing steps
    const $stepsList = $('#flowStepsList');
    $stepsList.empty();

    if (flowLog.steps && flowLog.steps.length > 0) {
        flowLog.steps.forEach((step, index) => {
            const stepHtml = `
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="ms-2 me-auto">
                        <div class="fw-bold text-dark">${escapeHtml(step.step)}</div>
                        <small class="text-secondary">${escapeHtml(step.details || '')}</small>
                    </div>
                    <span class="badge bg-secondary rounded-pill">${step.time || ''}</span>
                </li>
            `;
            $stepsList.append(stepHtml);
        });
    } else {
        $stepsList.append('<li class="list-group-item text-secondary">No processing steps recorded</li>');
    }

    // Update token usage section
    updateTokenUsageSection(flowLog.tokenUsage);
}

// Update token usage section in the flow modal
function updateTokenUsageSection(tokenUsage) {
    if (!tokenUsage || !tokenUsage.total) {
        $('#flowTotalInputTokens').text('0');
        $('#flowTotalOutputTokens').text('0');
        $('#flowTotalTokens').text('0');
        $('#flowTotalCost').text('₱0.00');
        $('#flowTokensByProvider').html('<tr><td colspan="6" class="text-center text-secondary">No token usage data</td></tr>');
        $('#flowTokensByNode').html('<tr><td colspan="6" class="text-center text-secondary">No token usage data</td></tr>');
        return;
    }

    // Update totals
    $('#flowTotalInputTokens').text(formatNumber(tokenUsage.total.inputTokens || 0));
    $('#flowTotalOutputTokens').text(formatNumber(tokenUsage.total.outputTokens || 0));
    $('#flowTotalTokens').text(formatNumber(tokenUsage.total.totalTokens || 0));
    // Convert USD to PHP using dynamic rate from settings
    $('#flowTotalCost').text('₱' + formatCost((tokenUsage.total.estimatedCost || 0) * usdToPhpRate));

    // Update by provider table
    const $providerTable = $('#flowTokensByProvider');
    $providerTable.empty();

    if (tokenUsage.byProvider && Object.keys(tokenUsage.byProvider).length > 0) {
        for (const [key, data] of Object.entries(tokenUsage.byProvider)) {
            const rowHtml = `
                <tr>
                    <td class="text-dark">${escapeHtml(data.name || key)}</td>
                    <td class="text-center text-dark">${data.calls || 0}</td>
                    <td class="text-end text-dark">${formatNumber(data.inputTokens || 0)}</td>
                    <td class="text-end text-dark">${formatNumber(data.outputTokens || 0)}</td>
                    <td class="text-end text-primary fw-medium">${formatNumber(data.totalTokens || 0)}</td>
                    <td class="text-end text-success fw-medium">₱${formatCost((data.estimatedCost || 0) * usdToPhpRate)}</td>
                </tr>
            `;
            $providerTable.append(rowHtml);
        }
    } else {
        $providerTable.html('<tr><td colspan="6" class="text-center text-secondary">No provider data</td></tr>');
    }

    // Update by node table
    const $nodeTable = $('#flowTokensByNode');
    $nodeTable.empty();

    if (tokenUsage.byNode && Object.keys(tokenUsage.byNode).length > 0) {
        for (const [nodeId, data] of Object.entries(tokenUsage.byNode)) {
            const rowHtml = `
                <tr>
                    <td class="text-dark"><code>${escapeHtml(nodeId)}</code></td>
                    <td class="text-secondary small">${escapeHtml(data.model || data.provider || '-')}</td>
                    <td class="text-end text-dark">${formatNumber(data.inputTokens || 0)}</td>
                    <td class="text-end text-dark">${formatNumber(data.outputTokens || 0)}</td>
                    <td class="text-end text-primary fw-medium">${formatNumber(data.totalTokens || 0)}</td>
                    <td class="text-end text-success fw-medium">₱${formatCost((data.estimatedCost || 0) * usdToPhpRate)}</td>
                </tr>
            `;
            $nodeTable.append(rowHtml);
        }
    } else {
        $nodeTable.html('<tr><td colspan="6" class="text-center text-secondary">No node data</td></tr>');
    }

    // Update Serper web search usage section
    updateSerperUsageSection(tokenUsage.serper);
}

// Update Serper web search usage section
function updateSerperUsageSection(serperUsage) {
    const $section = $('#serperUsageSection');
    const $queries = $('#flowSerperQueries');

    if (!serperUsage || (serperUsage.searches === 0 && (!serperUsage.queries || serperUsage.queries.length === 0))) {
        $section.hide();
        return;
    }

    $section.show();

    // Update summary
    $('#flowSerperSearches').text(serperUsage.searches || 0);
    $('#flowSerperCredits').text(serperUsage.credits || serperUsage.searches || 0);
    // Serper pricing: ~$0.001 per search, converted to PHP using dynamic rate
    const serperCostUsd = (serperUsage.credits || serperUsage.searches || 0) * 0.001;
    const serperCostPhp = serperCostUsd * usdToPhpRate;
    $('#flowSerperCost').text('₱' + formatCost(serperCostPhp));

    // Update queries table
    $queries.empty();
    if (serperUsage.queries && serperUsage.queries.length > 0) {
        serperUsage.queries.forEach(query => {
            const rowHtml = `
                <tr>
                    <td class="text-dark small">${escapeHtml(query.query || '-')}</td>
                    <td class="text-center text-dark">${query.results || 0}</td>
                    <td class="text-end text-primary fw-medium">${query.credits || 1}</td>
                </tr>
            `;
            $queries.append(rowHtml);
        });
    } else {
        $queries.html('<tr><td colspan="3" class="text-center text-secondary">No query details</td></tr>');
    }
}

// Format number with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Format cost with appropriate decimal places
function formatCost(cost) {
    if (cost < 0.01) {
        return cost.toFixed(6);
    } else if (cost < 1) {
        return cost.toFixed(4);
    } else {
        return cost.toFixed(2);
    }
}

// Copy AI Flow log to clipboard
function copyAiFlowLog() {
    if (!lastFlowLog) {
        toastr.warning('No flow log available to copy');
        return;
    }

    let logText = '=== AI FLOW LOG ===\n\n';
    logText += 'Question Type: ' + (lastFlowLog.questionType || 'General') + '\n';
    logText += 'AI Provider: ' + (lastFlowLog.aiProvider || 'Not specified') + '\n';
    logText += 'Processing Time: ' + (lastFlowLog.processingTime ? lastFlowLog.processingTime + 's' : '-') + '\n\n';

    logText += '--- USER MESSAGE ---\n';
    logText += (lastFlowLog.userMessage || '-') + '\n\n';


    logText += '--- AI RESPONSE ---\n';
    logText += (lastFlowLog.aiResponse || 'No response recorded') + '\n\n';

    logText += '--- PROCESSING STEPS ---\n';
    if (lastFlowLog.steps && lastFlowLog.steps.length > 0) {
        lastFlowLog.steps.forEach((step, index) => {
            logText += `${index + 1}. [${step.time || ''}] ${step.step}`;
            if (step.details) logText += ` - ${step.details}`;
            logText += '\n';
        });
    } else {
        logText += 'No processing steps recorded\n';
    }

    // Add token usage section
    logText += '\n--- TOKEN USAGE & COST ---\n';
    if (lastFlowLog.tokenUsage && lastFlowLog.tokenUsage.total) {
        const total = lastFlowLog.tokenUsage.total;
        logText += `Total Input Tokens: ${formatNumber(total.inputTokens || 0)}\n`;
        logText += `Total Output Tokens: ${formatNumber(total.outputTokens || 0)}\n`;
        logText += `Total Tokens: ${formatNumber(total.totalTokens || 0)}\n`;
        logText += `Estimated Cost: ₱${formatCost((total.estimatedCost || 0) * usdToPhpRate)}\n`;

        // By provider
        if (lastFlowLog.tokenUsage.byProvider) {
            logText += '\nBy Provider:\n';
            for (const [key, data] of Object.entries(lastFlowLog.tokenUsage.byProvider)) {
                logText += `  ${data.name || key}: ${formatNumber(data.totalTokens || 0)} tokens (${data.calls || 0} calls) - ₱${formatCost((data.estimatedCost || 0) * usdToPhpRate)}\n`;
            }
        }

        // By node
        if (lastFlowLog.tokenUsage.byNode) {
            logText += '\nBy Node:\n';
            for (const [nodeId, data] of Object.entries(lastFlowLog.tokenUsage.byNode)) {
                logText += `  ${nodeId}: ${formatNumber(data.totalTokens || 0)} tokens - ₱${formatCost((data.estimatedCost || 0) * usdToPhpRate)}\n`;
            }
        }

        // Serper web search usage
        if (lastFlowLog.tokenUsage.serper && lastFlowLog.tokenUsage.serper.searches > 0) {
            const serper = lastFlowLog.tokenUsage.serper;
            const serperCostPhp = (serper.credits || serper.searches || 0) * 0.001 * usdToPhpRate;
            logText += '\nSerper Web Search:\n';
            logText += `  Searches: ${serper.searches}\n`;
            logText += `  Credits Used: ${serper.credits || serper.searches}\n`;
            logText += `  Est. Cost: ₱${formatCost(serperCostPhp)}\n`;
            if (serper.queries && serper.queries.length > 0) {
                logText += '  Queries:\n';
                serper.queries.forEach(q => {
                    logText += `    - "${q.query}" (${q.results || 0} results)\n`;
                });
            }
        }
    } else {
        logText += 'No token usage data available\n';
    }

    copyToClipboard(logText).then(() => {
        toastr.success('Flow log copied to clipboard!');
    }).catch(err => {
        console.error('Copy failed:', err);
        toastr.error('Failed to copy flow log');
    });
}

// Helper function to escape HTML (if not already defined globally)
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Copy all chat to clipboard
function copyAllChat() {
    const messages = [];
    $('#chatMessages .message-wrapper').each(function() {
        const $wrapper = $(this);
        const content = $wrapper.find('.message-content').text().trim();
        const time = $wrapper.find('.message-meta span').first().text().trim();

        if (content) {
            messages.push(`${content}\n${time}`);
        }
    });

    if (messages.length === 0) {
        toastr.warning('No messages to copy');
        return;
    }

    const chatText = messages.join('\n\n');

    copyToClipboard(chatText).then(() => {
        const $btn = $('#copyAllChatBtn');
        const originalHtml = $btn.html();
        $btn.html('<i class="bx bx-check me-1"></i>Copied!');
        setTimeout(() => {
            $btn.html(originalHtml);
        }, 2000);
        toastr.success('Chat copied to clipboard!');
    }).catch(err => {
        console.error('Copy failed:', err);
        toastr.error('Failed to copy chat');
    });
}

// ==================== SEARCH FUNCTIONALITY ====================

$(document).ready(function() {
    // Toggle search filters
    $('#toggleSearchFilters').on('click', function() {
        $('#searchFilters').toggleClass('show');
        const $icon = $(this).find('i');
        if ($('#searchFilters').hasClass('show')) {
            $icon.removeClass('bx-filter-alt').addClass('bx-chevron-up');
        } else {
            $icon.removeClass('bx-chevron-up').addClass('bx-filter-alt');
        }
    });

    // Debounced search on input
    $('#chatSearchInput').on('input', function() {
        clearTimeout(window.searchDebounceTimer);
        window.searchDebounceTimer = setTimeout(searchSessions, 500);
    });

    // Search on date change
    $('#searchStartDate, #searchEndDate').on('change', function() {
        searchSessions();
    });

    // Clear search
    $('#clearSearchBtn').on('click', function() {
        $('#chatSearchInput').val('');
        $('#searchStartDate').val('');
        $('#searchEndDate').val('');
        $('#searchResultsInfo').removeClass('show');
        location.reload();
    });

    // Search on Enter key
    $('#chatSearchInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            clearTimeout(window.searchDebounceTimer);
            searchSessions();
        }
    });
});

// Global escape HTML function for search
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Search chat sessions
function searchSessions() {
    const searchText = $('#chatSearchInput').val().trim();
    const startDate = $('#searchStartDate').val();
    const endDate = $('#searchEndDate').val();

    // If all empty, show original sessions
    if (!searchText && !startDate && !endDate) {
        $('#searchResultsInfo').removeClass('show');
        // Reload page to show original sessions
        location.reload();
        return;
    }

    // Show loading indicator
    $('#sessionsList').html(`
        <div class="text-center py-4 text-secondary">
            <i class="bx bx-loader-alt bx-spin" style="font-size: 2rem;"></i>
            <p class="mb-0 mt-2 small">Searching...</p>
        </div>
    `);

    $.ajax({
        url: '/ai-technician-chat/search',
        type: 'GET',
        data: {
            q: searchText,
            start_date: startDate,
            end_date: endDate
        },
        success: function(response) {
            if (response.success) {
                renderSearchResults(response.sessions);
                $('#searchResultsCount').text(response.count);
                $('#searchResultsInfo').addClass('show');
            }
        },
        error: function(xhr) {
            toastr.error('Search failed');
            location.reload();
        }
    });
}

// Render search results
function renderSearchResults(sessions) {
    const $list = $('#sessionsList');
    $list.empty();

    if (sessions.length === 0) {
        $list.html(`
            <div class="text-center py-4 text-secondary">
                <i class="bx bx-search" style="font-size: 2rem; opacity: 0.3;"></i>
                <p class="mb-0 mt-2 small">No matching chats found</p>
            </div>
        `);
        return;
    }

    sessions.forEach(session => {
        const isActive = window.currentSessionId == session.id;
        const titleGenerated = session.titleGenerated || (session.name && session.name.length <= 35);
        const html = `
            <div class="chat-session-item ${isActive ? 'active' : ''}" data-session-id="${session.id}" data-title-generated="${titleGenerated}">
                <div class="session-info">
                    <div class="session-name ${isActive ? '' : 'text-dark'}">${escapeHtml(session.name)}</div>
                    <div class="session-time">${session.lastMessageAgo} · ${session.createdAt}</div>
                </div>
                <div class="session-actions">
                    <button type="button" class="btn btn-sm btn-link p-0 text-danger delete-session-btn"
                            data-session-id="${session.id}" title="Delete">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $list.append(html);
    });

    // Hide load more section when showing search results
    $('#loadMoreSection').hide();
}

// ==================== LOAD MORE SESSIONS ====================

// Track current offset for pagination
window.sessionsOffset = {{ $sessions->count() }};
window.sessionsPerPage = 15;
window.hasMoreSessions = {{ $hasMoreSessions ? 'true' : 'false' }};

$(document).ready(function() {
    // Load more sessions button
    $('#loadMoreBtn').on('click', function() {
        loadMoreSessions();
    });
});

// Load more sessions function
function loadMoreSessions() {
    const $btn = $('#loadMoreBtn');

    // Prevent double-clicking
    if ($btn.hasClass('loading')) return;

    $btn.addClass('loading');
    $btn.html('<i class="bx bx-loader-alt bx-spin me-1"></i>Loading...');

    $.ajax({
        url: '/ai-technician-chat/sessions/load-more',
        type: 'GET',
        data: {
            offset: window.sessionsOffset
        },
        success: function(response) {
            if (response.success) {
                // Append new sessions to the list
                response.sessions.forEach(session => {
                    const isActive = window.currentSessionId == session.id;
                    const titleGenerated = session.titleGenerated || (session.displayName && session.displayName.length <= 35);
                    const html = `
                        <div class="chat-session-item ${isActive ? 'active' : ''}" data-session-id="${session.id}" data-title-generated="${titleGenerated}">
                            <div class="session-info">
                                <div class="session-name ${isActive ? '' : 'text-dark'}">${escapeHtml(session.displayName)}</div>
                                <div class="session-time">${session.lastMessageAgo}</div>
                            </div>
                            <div class="session-actions">
                                <button type="button" class="btn btn-sm btn-link p-0 text-danger delete-session-btn"
                                        data-session-id="${session.id}" title="Delete">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    $('#sessionsList').append(html);
                });

                // Update offset and counts
                window.sessionsOffset = response.nextOffset;
                $('#shownCount').text(response.nextOffset > response.totalSessions ? response.totalSessions : response.nextOffset);
                $('#totalCount').text(response.totalSessions);

                // Hide load more if no more sessions
                if (!response.hasMore) {
                    window.hasMoreSessions = false;
                    $('#loadMoreSection').hide();
                }

                // Generate titles for untitled sessions that were just loaded
                setTimeout(function() {
                    generateTitlesForUntitledSessions();
                }, 500);
            }
        },
        error: function(xhr) {
            toastr.error('Failed to load more sessions');
        },
        complete: function() {
            $btn.removeClass('loading');
            $btn.html('<i class="bx bx-chevron-down me-1"></i>Load More');
        }
    });
}

</script>
@endsection
