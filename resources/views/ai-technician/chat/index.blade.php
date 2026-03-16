@extends('layouts.master')

@section('title') AI Chat @endsection

@section('css')
<!-- Cache control for development - forces fresh load -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    /* Hide toastr on mobile */
    @media (max-width: 768px) {
        #toast-container {
            display: none !important;
        }
    }

    /* NUCLEAR FIX: Disable Bootstrap backdrop completely and use modal's own background */
    .modal-backdrop {
        display: none !important;
    }

    /* Modal provides its own dark overlay - covers ENTIRE viewport */
    .modal {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        background: rgba(0, 0, 0, 0.5) !important;
        z-index: 99999 !important;
        overflow-x: hidden;
        overflow-y: auto;
    }

    /* Ensure modal dialog is clickable and centered */
    .modal-dialog {
        pointer-events: auto;
        position: relative;
        z-index: 1;
        margin: 1.75rem auto;
    }

    .modal-content {
        pointer-events: auto;
    }

    /* Hide mobile sidebar overlay when modal is open */
    body.modal-open .mobile-sidebar-overlay {
        display: none !important;
    }

    /* Hide global sidebar overlay when modal is open */
    body.modal-open .sidebar-overlay {
        display: none !important;
    }

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
        overflow-x: hidden; /* Prevent horizontal scrollbar from animations */
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
        animation: fadeIn 0.4s ease-out forwards;
    }

    .chat-empty i {
        font-size: 4rem;
        opacity: 0.3;
        margin-bottom: 1rem;
    }

    /* Animation for chat-empty fade out */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: scale(1);
        }
        to {
            opacity: 0;
            transform: scale(0.95);
        }
    }

    .chat-empty.fade-out {
        animation: fadeOut 0.3s ease-out forwards;
    }

    /* Message Bubble Animations - simplified to prevent blinking */
    @keyframes slideInFromRight {
        0% {
            opacity: 0;
            transform: translateX(10px);
        }
        100% {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideInFromLeft {
        0% {
            opacity: 0;
            transform: translateX(-10px);
        }
        100% {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeInUp {
        0% {
            opacity: 0;
            transform: translateY(8px);
        }
        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Message Bubbles */
    .message-wrapper {
        margin-bottom: 1rem;
        display: flex;
        flex-direction: column;
        animation: fadeInUp 0.25s ease-out forwards;
    }

    .message-wrapper.user {
        align-items: flex-end;
        animation: slideInFromRight 0.25s ease-out forwards;
    }

    .message-wrapper.assistant {
        align-items: flex-start;
        animation: slideInFromLeft 0.25s ease-out forwards;
    }

    .message-wrapper.thinking {
        animation: slideInFromLeft 0.25s ease-out forwards;
    }

    .message-bubble {
        max-width: 70%;
        padding: 0.75rem 1rem;
        border-radius: 1rem;
        position: relative;
        /* No animation on bubble - wrapper handles it to prevent double-animation blink */
    }

    /* Widen bubble when it contains a table */
    .message-bubble:has(.table-responsive) {
        max-width: 95%;
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
        max-width: calc(100% - 60px);
    }

    /* Allow wider content wrapper when bubble has tables */
    .message-wrapper.assistant .message-content-wrapper:has(.table-responsive),
    .message-wrapper.thinking .message-content-wrapper:has(.table-responsive) {
        max-width: calc(100% - 60px);
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

    /* Chat table styling - simple, elegant with grey borders */
    .message-content .table-responsive {
        margin: 0.75rem 0;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .message-content .table {
        font-size: 0.72rem;
        margin-bottom: 0;
        color: #333;
        background-color: transparent;
        width: 100%;
        min-width: 300px;
    }

    .message-content .table th {
        background-color: transparent !important;
        color: #333 !important;
        font-weight: 600;
        padding: 0.35rem 0.5rem;
        border: 1px solid #ccc !important;
        white-space: nowrap;
        font-size: 0.72rem;
    }

    .message-content .table td {
        padding: 0.3rem 0.5rem;
        vertical-align: middle;
        border: 1px solid #ccc !important;
        color: #333 !important;
        background-color: transparent !important;
        font-weight: 400;
    }

    .message-content .table tbody tr:nth-child(even) {
        background-color: transparent;
    }

    .message-content .table tbody tr:hover {
        background-color: rgba(0,0,0,0.02);
    }

    /* All table text should be black/dark grey */
    .message-content .table .text-success,
    .message-content .table .text-danger,
    .message-content .table .text-warning,
    .message-content .table .text-dark,
    .message-content .table .fw-bold,
    .message-content .table .fw-semibold {
        color: #333 !important;
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
        gap: 10px;
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

    /* Processing Progress Indicator */
    .processing-progress-wrapper {
        display: none;
        align-items: flex-start;
        margin-bottom: 1rem;
        gap: 0.5rem;
    }

    .processing-progress-wrapper.show {
        display: flex;
    }

    .processing-progress-content {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 1rem;
        padding: 0.75rem 1rem;
        min-width: 180px;
        max-width: 200px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .progress-bar-container {
        width: 100%;
        height: 8px;
        background: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #556ee6 0%, #34c38f 100%);
        border-radius: 4px;
        width: 0%;
        transition: width 0.3s ease;
    }

    /* Animated striped progress bar */
    .progress-bar-fill.progress-bar-striped {
        background-image: linear-gradient(
            45deg,
            rgba(255, 255, 255, 0.3) 25%,
            transparent 25%,
            transparent 50%,
            rgba(255, 255, 255, 0.3) 50%,
            rgba(255, 255, 255, 0.3) 75%,
            transparent 75%,
            transparent
        );
        background-size: 20px 20px;
        background-color: #556ee6;
        animation: progress-stripes 1s linear infinite;
    }

    @keyframes progress-stripes {
        0% { background-position: 20px 0; }
        100% { background-position: 0 0; }
    }

    /* Animated dots below progress bar */
    .processing-dots {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 4px;
        margin-top: 0.5rem;
    }

    .processing-dots span {
        width: 6px;
        height: 6px;
        background: #556ee6;
        border-radius: 50%;
        animation: processing-bounce 1.4s infinite ease-in-out;
    }

    .processing-dots span:nth-child(1) { animation-delay: 0s; }
    .processing-dots span:nth-child(2) { animation-delay: 0.2s; }
    .processing-dots span:nth-child(3) { animation-delay: 0.4s; }

    @keyframes processing-bounce {
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

    /* Flow Log Step Styles */
    .cursor-pointer {
        cursor: pointer;
    }

    .cursor-pointer:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }

    .collapse-icon {
        transition: transform 0.2s ease;
    }

    #flowStepsList .list-group-item {
        border-left: none;
        border-right: none;
    }

    #flowStepsList .list-group-item:first-child {
        border-top: none;
    }

    #flowStepsList pre {
        font-family: 'Courier New', monospace;
    }

    /* Custom colors for flow log styling */
    .text-purple {
        color: #7c3aed !important;
    }

    .border-purple {
        border-color: #7c3aed !important;
    }

    .bg-purple {
        background-color: #7c3aed !important;
    }

    /* Dual-AI step styling */
    .dual-ai-step {
        background: linear-gradient(90deg, rgba(99, 102, 241, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
    }

    /* Step border styling */
    #flowStepsList .border-start.border-4 {
        border-left-width: 4px !important;
    }

    /* Badge opacity classes */
    .bg-opacity-10 {
        background-color: rgba(var(--bs-info-rgb), 0.1) !important;
    }

    .bg-warning.bg-opacity-10 {
        background-color: rgba(255, 193, 7, 0.1) !important;
    }

    .bg-primary.bg-opacity-10 {
        background-color: rgba(13, 110, 253, 0.1) !important;
    }

    .bg-success.bg-opacity-10 {
        background-color: rgba(25, 135, 84, 0.1) !important;
    }

    .bg-info.bg-opacity-10 {
        background-color: rgba(13, 202, 240, 0.1) !important;
    }

    /* Flow Log Summary Stats Cards */
    #aiFlowContent .stats-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    #aiFlowContent .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    /* Flow Log Question Type Header */
    #aiFlowContent .question-type-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }

    /* Token display inline format */
    #flowQuickTokens .token-separator {
        opacity: 0.5;
    }

    /* ============================================
       MOBILE RESPONSIVE STYLES
       ============================================ */

    /* Mobile Sidebar Toggle Button */
    .mobile-sidebar-toggle {
        display: none;
        position: fixed;
        bottom: 20px;
        right: 15px;
        z-index: 9999;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: #556ee6;
        color: #fff;
        border: none;
        box-shadow: 0 4px 15px rgba(85, 110, 230, 0.4);
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .mobile-sidebar-toggle:hover,
    .mobile-sidebar-toggle:focus {
        background: #4458cb;
        color: #fff;
    }

    /* Mobile Sidebar Overlay */
    .mobile-sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1030;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .mobile-sidebar-overlay.show {
        opacity: 1;
    }

    /* Mobile close button in sidebar */
    .mobile-sidebar-close {
        display: none;
        position: absolute;
        top: 12px;
        right: 12px;
        width: 36px;
        height: 36px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 50%;
        font-size: 1.25rem;
        color: #495057;
        z-index: 10;
        cursor: pointer;
        transition: all 0.2s ease;
        align-items: center;
        justify-content: center;
    }

    .mobile-sidebar-close:hover,
    .mobile-sidebar-close:focus {
        background: #e9ecef;
        color: #dc3545;
    }

    .mobile-sidebar-close i {
        line-height: 1;
    }

    /* Tablet Styles (768px - 1024px) */
    @media (max-width: 1024px) {
        .chat-container {
            height: calc(100vh - 180px);
        }

        .chat-sidebar {
            width: 240px;
        }

        .chat-messages {
            padding: 1rem;
        }

        .chat-input-area {
            padding: 0.75rem 1rem;
        }

        .message-bubble {
            max-width: 80%;
        }

        .ai-avatar {
            width: 40px;
            height: 40px;
            min-width: 40px;
        }
    }

    /* Mobile Styles (max-width: 768px) */
    @media (max-width: 768px) {
        .chat-container {
            height: calc(100vh - 80px);
            min-height: 400px;
            border-radius: 0 !important;
            border: none !important;
            margin: 0 !important;
            margin-top: 0 !important;
            box-shadow: none !important;
        }

        /* Force full-width on mobile */
        .chat-main {
            width: 100% !important;
            margin: 0 !important;
        }

        .chat-header {
            margin: 0 !important;
            margin-top: 3px !important;
            border-radius: 0 !important;
            border-left: none !important;
            border-right: none !important;
            width: 100% !important;
        }

        .chat-input-area {
            margin: 0 !important;
            border-radius: 0 !important;
            border-left: none !important;
            border-right: none !important;
            width: 100% !important;
        }

        /* Show mobile toggle button */
        .mobile-sidebar-toggle {
            display: flex;
        }

        /* Hide footer on mobile for full-screen chat experience */
        .footer {
            display: none !important;
        }

        .mobile-sidebar-overlay {
            display: block;
            pointer-events: none;
        }

        .mobile-sidebar-overlay.show {
            pointer-events: auto;
        }

        /* Sidebar as slide-out panel */
        .chat-sidebar {
            position: fixed;
            top: 0;
            left: -300px;
            width: 280px;
            height: 100vh;
            z-index: 1035;
            transition: left 0.3s ease;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
        }

        .chat-sidebar.mobile-open {
            left: 0;
        }

        .mobile-sidebar-close {
            display: flex;
        }

        .chat-sidebar-header {
            padding-top: 3.5rem;
        }

        /* Full width main area */
        .chat-main {
            width: 100%;
        }

        .chat-header {
            padding: 0.75rem 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 0;
            border-radius: 0;
        }

        .chat-header h5 {
            font-size: 1rem;
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .chat-header .header-actions {
            display: flex;
            gap: 0.25rem;
        }

        .chat-header .btn {
            padding: 0.35rem 0.5rem;
            font-size: 0.8rem;
        }

        .chat-messages {
            padding: 0.75rem;
        }

        /* Message bubbles */
        .message-bubble {
            max-width: 90%;
            padding: 0.6rem 0.85rem;
            font-size: 0.9rem;
        }

        .message-bubble:has(.table-responsive) {
            max-width: 98%;
        }

        .ai-avatar {
            width: 36px;
            height: 36px;
            min-width: 36px;
            margin-right: 8px;
        }

        .message-wrapper.assistant .message-content-wrapper,
        .message-wrapper.thinking .message-content-wrapper {
            max-width: calc(100% - 48px);
        }

        /* Input area */
        .chat-input-area {
            padding: 0.5rem 0.75rem;
            position: sticky;
            bottom: 0;
            background: #fff;
        }

        .chat-input-wrapper {
            gap: 0.5rem;
        }

        .chat-input-wrapper textarea {
            font-size: 16px; /* Prevent iOS zoom */
            padding: 0.5rem 0.85rem;
        }

        .chat-input-actions .btn {
            width: 36px;
            height: 36px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .btn-send {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .btn-send i,
        .btn-attach i {
            margin: 0;
            line-height: 1;
        }

        .chat-input-area small {
            display: none;
        }

        /* Image previews */
        .image-preview-container {
            padding: 0.5rem;
        }

        .image-preview-item {
            width: 60px;
            height: 60px;
        }

        /* Message images */
        .message-images img {
            max-width: 120px;
            max-height: 120px;
        }

        .searched-image-item {
            width: 50px;
            height: 50px;
        }

        /* Tables in messages */
        .message-content .table {
            font-size: 0.65rem;
            min-width: 250px;
        }

        .message-content .table th,
        .message-content .table td {
            padding: 0.25rem 0.35rem;
        }

        /* Thinking indicator */
        .thinking-indicator {
            padding: 0.5rem 0.75rem;
        }

        /* Typing indicator - closer to avatar on mobile */
        .typing-indicator-wrapper {
            gap: 8px;
        }

        .typing-indicator {
            margin-left: 0;
        }

        /* Flow log modal */
        .modal-dialog {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }

        /* Session items */
        .chat-session-item {
            padding: 0.6rem 0.75rem;
        }

        .session-name {
            font-size: 0.8rem;
        }
    }

    /* Small Mobile Styles (max-width: 480px) */
    @media (max-width: 480px) {
        .chat-container {
            height: calc(100vh - 80px);
            min-height: 350px;
            border-radius: 0 !important;
            border: none !important;
            margin: 0 !important;
            margin-top: 0 !important;
        }

        .chat-header {
            margin: 0 !important;
            margin-top: 3px !important;
            padding: 0.5rem 0.75rem;
        }

        .chat-input-area {
            margin: 0 !important;
            padding: 0.5rem 0.75rem;
        }

        .chat-header h5 {
            font-size: 0.9rem;
        }

        .message-bubble {
            max-width: 95%;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            border-radius: 0.75rem;
        }

        .ai-avatar {
            width: 32px;
            height: 32px;
            min-width: 32px;
            margin-right: 6px;
        }

        .message-wrapper.assistant .message-content-wrapper,
        .message-wrapper.thinking .message-content-wrapper {
            max-width: calc(100% - 40px);
        }

        .chat-messages {
            padding: 0.75rem;
            padding-bottom: 0;
            border-radius: 0;
        }

        .chat-input-area {
            padding: 0.75rem;
            border-radius: 0;
        }

        .chat-input-wrapper textarea {
            border-radius: 1.25rem;
            padding-top: 8px;
            padding-bottom: 8px;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            min-height: 38px;
            height: 38px;
            line-height: 22px;
        }

        .btn-send,
        .chat-input-actions .btn {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .btn-send i,
        .btn-attach i {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-send {
            text-align: center;
        }

        .btn-send i {
            transform: translateX(-1px);
        }

        /* Breadcrumb adjustments - Force full-width on mobile */
        .page-content {
            padding: 0 !important;
            margin: 0 !important;
        }

        .page-content .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
            max-width: 100% !important;
        }

        .page-content .container-fluid .row {
            margin: 0 !important;
        }

        .page-content .container-fluid .row > * {
            padding: 0 !important;
        }

        /* Hide breadcrumb on mobile for full-screen chat experience */
        .page-title-box {
            display: none !important;
        }

        /* Empty state - mobile styling */
        .chat-messages:has(.chat-empty) {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .chat-empty {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 2rem 1.5rem;
            border-radius: 1.5rem;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            text-align: center;
            width: 85%;
            max-width: 300px;
            height: auto;
        }

        .chat-empty i {
            font-size: 3.5rem;
            color: #556ee6;
            opacity: 0.7;
            margin-bottom: 1rem;
        }

        .chat-empty h5 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            text-align: center;
        }

        .chat-empty p {
            font-size: 0.85rem;
            text-align: center;
            padding: 0 1rem;
            margin-bottom: 0;
        }

        /* Reset message wrapper alignment for chat bubbles */
        .message-wrapper {
            width: 100%;
        }

        .message-wrapper.user {
            align-items: flex-end;
        }

        .message-wrapper.assistant,
        .message-wrapper.thinking {
            align-items: flex-start;
        }

        .message-bubble {
            max-width: 85%;
        }

        /* Mobile toggle position */
        .mobile-sidebar-toggle {
            bottom: 15px;
            right: 12px;
            width: 50px;
            height: 50px;
            font-size: 1.4rem;
        }

        /* Lightbox modal */
        .image-lightbox-modal .modal-dialog {
            margin: 0;
            max-width: 100%;
        }

        .image-lightbox-modal .lightbox-image {
            max-height: 60vh;
        }
    }

    /* Landscape mode adjustments for mobile */
    @media (max-height: 500px) and (orientation: landscape) {
        .chat-container {
            height: calc(100vh - 80px);
            min-height: 250px;
            border-radius: 0;
            border: none;
        }

        .chat-header {
            padding: 0.5rem 1rem;
        }

        .chat-messages {
            padding: 0.5rem;
        }

        .chat-input-area {
            padding: 0.35rem 0.5rem;
        }

        .mobile-sidebar-toggle {
            bottom: 10px;
            right: 10px;
        }
    }

    /* Tablet portrait (max-width: 991px) */
    @media (max-width: 991.98px) {
        .chat-container {
            margin-top: 0 !important;
        }
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Chat Technician @endslot
@slot('title') Chat @endslot
@endcomponent

<!-- Mobile Sidebar Overlay -->
<div class="mobile-sidebar-overlay" id="mobileSidebarOverlay"></div>

<!-- Mobile Sidebar Toggle Button -->
<button type="button" class="mobile-sidebar-toggle" id="mobileSidebarToggle" title="Chat History">
    <i class="bx bx-plus"></i>
</button>

<div class="chat-container">
    <!-- Sessions Sidebar -->
    <div class="chat-sidebar" id="chatSidebar">
        <!-- Mobile Close Button -->
        <button type="button" class="mobile-sidebar-close" id="mobileSidebarClose">
            <i class="bx bx-x"></i>
        </button>
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
                    <i class="bx bx-copy me-1"></i><span class="d-none d-md-inline">Copy Chat</span><span class="d-md-none">Copy</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger me-2" id="saveToErrorsBtn" title="Save chat to error log">
                    <i class="bx bx-bug me-1"></i><span class="d-none d-md-inline">Save to Errors</span><span class="d-md-none">Error</span>
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
                    <div class="message-wrapper {{ $message->role }}" data-message-id="{{ $message->id }}" @if($message->role === 'assistant') data-raw-content="{{ $message->content }}" @endif>
                        <div class="message-bubble">
                            <div class="message-content">{!! nl2br(e($message->content)) !!}</div>
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

            <!-- Processing Progress Indicator -->
            <div class="processing-progress-wrapper" id="processingProgress">
                <img src="{{ $avatarSettings->avatar_url }}" alt="{{ $avatarSettings->displayName }}" class="ai-avatar">
                <div class="processing-progress-content">
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill progress-bar-striped" id="progressBarFill"></div>
                    </div>
                    <div class="processing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
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
            <!-- Desktop helper text -->
            <small class="text-secondary mt-1 d-none d-md-block">
                <i class="bx bx-info-circle me-1"></i>Press Enter to send, Shift+Enter for new line. Upload up to 10 images for analysis.
            </small>
            <!-- Mobile helper text -->
            <small class="text-secondary mt-1 d-block d-md-none">
                <i class="bx bx-info-circle me-1"></i>Tap the send button to send. You can attach up to 10 images.
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

<!-- Save to Errors Modal -->
<div class="modal fade" id="saveToErrorsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark">
                    <i class="bx bx-bug text-danger me-2"></i>Save Chat to Errors
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-3">Save this chat thread and flow logs to the error log for review.</p>
                <div class="mb-3">
                    <label for="errorDescriptionInput" class="form-label text-dark">Error Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="errorDescriptionInput" rows="4" placeholder="Describe what went wrong or what issue you noticed..."></textarea>
                    <small class="text-secondary">Please explain the issue so it can be reviewed and fixed.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmSaveToErrorsBtn">
                    <i class="bx bx-save me-1"></i>Save to Errors
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
                    <!-- ============================================== -->
                    <!-- MESSAGE INDICATOR & NAVIGATION                -->
                    <!-- ============================================== -->
                    <div class="px-3 py-2 border-bottom bg-light d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="flowPrevMsg" onclick="navigateFlowLog(-1)" title="Previous Message">
                                <i class="bx bx-chevron-left"></i>
                            </button>
                            <span class="text-dark fw-medium" style="font-size: 0.85rem;">
                                Message <span id="flowMsgNumber" class="text-primary">1</span> of <span id="flowMsgTotal" class="text-primary">1</span>
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="flowNextMsg" onclick="navigateFlowLog(1)" title="Next Message">
                                <i class="bx bx-chevron-right"></i>
                            </button>
                        </div>
                        <div class="d-flex align-items-center">
                            <small class="text-secondary me-2" id="flowMsgTime">-</small>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyAiFlowLog()" title="Copy Log">
                                <i class="bx bx-copy me-1"></i>Copy
                            </button>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- SECTION 1: QUESTION & PROCESSING INFO         -->
                    <!-- ============================================== -->
                    <div class="p-3 border-bottom" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                        <!-- Question Type Header -->
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                <i class="bx bx-message-rounded-dots text-white" style="font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <small class="text-secondary d-block" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;">Question Type</small>
                                <span class="fw-semibold text-dark" id="flowQuestionType" style="font-size: 1rem;">-</span>
                            </div>
                        </div>

                        <!-- Processing Info Row -->
                        <div class="bg-white rounded border p-2">
                            <div class="row text-center g-0">
                                <div class="col-6 border-end">
                                    <small class="text-secondary d-block" style="font-size: 0.7rem;">AI Provider</small>
                                    <span class="fw-bold text-dark" id="flowAiProvider">-</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-secondary d-block" style="font-size: 0.7rem;">Processing Time</small>
                                    <span class="fw-bold text-dark" id="flowProcessingTime">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- SECTION 2: THIS MESSAGE TOKEN USAGE           -->
                    <!-- ============================================== -->
                    <div class="p-3 border-bottom bg-white">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bx bx-coin text-warning me-2"></i>
                            <small class="text-secondary fw-medium" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">This Message - Token Usage & Cost</small>
                        </div>
                        <div class="alert alert-light border mb-0 py-2">
                            <div class="row text-center g-0">
                                <div class="col-3">
                                    <small class="text-secondary d-block" style="font-size: 0.7rem;">Input Tokens</small>
                                    <span class="fw-bold text-info" id="flowQuickInput" style="font-size: 1.05rem;">0</span>
                                </div>
                                <div class="col-3">
                                    <small class="text-secondary d-block" style="font-size: 0.7rem;">Output Tokens</small>
                                    <span class="fw-bold text-purple" id="flowQuickOutput" style="font-size: 1.05rem;">0</span>
                                </div>
                                <div class="col-3">
                                    <small class="text-secondary d-block" style="font-size: 0.7rem;">Total Tokens</small>
                                    <span class="fw-bold text-primary" id="flowQuickTotal" style="font-size: 1.05rem;">0</span>
                                </div>
                                <div class="col-3">
                                    <small class="text-success d-block" style="font-size: 0.7rem;">Est. Cost</small>
                                    <span class="fw-bold text-success" id="flowQuickCost" style="font-size: 1.05rem;">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- SECTION 3: SESSION TOTALS                     -->
                    <!-- ============================================== -->
                    <div class="p-3 border-bottom" style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bx bx-calculator text-success me-2"></i>
                            <small class="text-success fw-medium" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Session Totals (All Messages)</small>
                        </div>
                        <div class="bg-white rounded border p-2">
                            <div class="row text-center g-0">
                                <div class="col-3">
                                    <small class="text-secondary d-block" style="font-size: 0.65rem;">Input</small>
                                    <span class="fw-bold text-dark" id="flowSessionInput" style="font-size: 0.95rem;">0</span>
                                </div>
                                <div class="col-3">
                                    <small class="text-secondary d-block" style="font-size: 0.65rem;">Output</small>
                                    <span class="fw-bold text-dark" id="flowSessionOutput" style="font-size: 0.95rem;">0</span>
                                </div>
                                <div class="col-3">
                                    <small class="text-secondary d-block" style="font-size: 0.65rem;">Total</small>
                                    <span class="fw-bold text-primary" id="flowSessionTotal" style="font-size: 0.95rem;">0</span>
                                </div>
                                <div class="col-3">
                                    <small class="text-success d-block" style="font-size: 0.65rem;">Total Cost</small>
                                    <span class="fw-bold text-success" id="flowSessionCost" style="font-size: 0.95rem;">₱0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Sections - All collapsed by default for cleaner view -->
                    <div class="accordion" id="aiFlowAccordion">
                        <!-- User Message -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flowUserMessage">
                                    <i class="bx bx-user me-2 text-primary"></i>User Message
                                </button>
                            </h2>
                            <div id="flowUserMessage" class="accordion-collapse collapse" data-bs-parent="#aiFlowAccordion">
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
// ==========================================
// AI Chat v2.4 - Mobile Layout Fixes
// Last updated: {{ now()->format('Y-m-d H:i:s') }}
// ==========================================
console.log('=== AI CHAT v2.7 LOADED - Fixed Mobile Screen Wake Recovery ===');

// Exchange rate from settings (PHP per USD)
const usdToPhpRate = {{ $currencySettings->usdToPhpRate ?? 56 }};

// AI Avatar settings
const aiAvatarUrl = '{{ $avatarSettings->avatar_url ?? asset("images/ai-avatar-default.png") }}';
const aiDisplayName = '{{ $avatarSettings->displayName ?? "AI Technician" }}';

$(document).ready(function() {
    // Toastr configuration - auto-dismiss after 4 seconds
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-center",
        timeOut: 4000,
        extendedTimeOut: 1000,
        preventDuplicates: true,
        newestOnTop: true,
        tapToDismiss: true,
        onclick: null,
        showDuration: 300,
        hideDuration: 300,
        showMethod: "fadeIn",
        hideMethod: "fadeOut"
    };

    // Move FAB button, overlay, and sidebar to body for proper fixed positioning on mobile
    // This escapes the stacking context created by .page-content's animation transform
    if ($(window).width() < 992) {
        $('#mobileSidebarToggle').appendTo('body');
        $('#mobileSidebarOverlay').appendTo('body');
        $('#chatSidebar').appendTo('body');
    }

    // CRITICAL FIX: Move ALL modals to body to escape .page-content stacking context
    // The .page-content has transform animation which creates stacking context, trapping modals inside
    // This must happen on ALL devices, not just mobile
    $('.modal').appendTo('body');

    // State (using window for global access by search functions)
    window.currentSessionId = {{ $currentSession ? $currentSession->id : 'null' }};
    let currentSessionId = window.currentSessionId; // Local alias
    let selectedImages = [];
    let isSending = false;
    let questionType = 'new'; // 'new', 'followup'
    let lastQuestion = ''; // Store last question for follow-up validation
    window.lastFlowLog = null; // Store the last flow log for the modal (global for copy function)
    window.searchDebounceTimer = null; // For search debouncing
    let currentProgressPercent = 0; // Track progress bar percentage
    let progressMsgId = null; // Track thinking message ID for removal

    // Session-wide flow log tracking
    window.sessionFlowLogs = []; // Array of all flow logs in this session
    window.sessionTotalTokens = { input: 0, output: 0, total: 0, cost: 0 }; // Cumulative tokens

    // Navigation while thinking - AbortController and pending action
    let currentAbortController = null;
    let pendingNavigationAction = null; // { type: 'session'|'newChat'|'link', data: any }
    let isExecutingPendingNavigation = false; // Flag to bypass check when executing pending action
    let globalProgressTimer = null; // Global reference to progress timer

    // ===== WAKE LOCK - Prevent screen from dimming while AI is processing =====
    let wakeLock = null;

    async function requestWakeLock() {
        if ('wakeLock' in navigator) {
            try {
                wakeLock = await navigator.wakeLock.request('screen');
                console.log('Wake Lock activated - screen will stay on');

                // Listen for wake lock release (e.g., if user switches tabs)
                wakeLock.addEventListener('release', () => {
                    console.log('Wake Lock released');
                });
            } catch (err) {
                console.log('Wake Lock request failed:', err.message);
            }
        } else {
            console.log('Wake Lock API not supported on this device');
        }
    }

    async function releaseWakeLock() {
        if (wakeLock !== null) {
            try {
                await wakeLock.release();
                wakeLock = null;
                console.log('Wake Lock released manually');
            } catch (err) {
                console.log('Wake Lock release failed:', err.message);
            }
        }
    }

    // Re-acquire wake lock if page becomes visible again while still processing
    document.addEventListener('visibilitychange', async () => {
        if (document.visibilityState === 'visible' && isSending && wakeLock === null) {
            console.log('Page visible again while processing - re-acquiring wake lock');
            await requestWakeLock();
        }
    });

    // ===== MOBILE SIDEBAR TOGGLE =====
    const $mobileSidebarToggle = $('#mobileSidebarToggle');
    const $mobileSidebarOverlay = $('#mobileSidebarOverlay');
    const $chatSidebar = $('#chatSidebar');
    const $mobileSidebarClose = $('#mobileSidebarClose');

    // Open mobile sidebar
    $mobileSidebarToggle.on('click', function() {
        $chatSidebar.addClass('mobile-open');
        $mobileSidebarOverlay.addClass('show');
        $('body').css('overflow', 'hidden'); // Prevent body scroll
    });

    // Close mobile sidebar
    function closeMobileSidebar() {
        $chatSidebar.removeClass('mobile-open');
        $mobileSidebarOverlay.removeClass('show');
        $('body').css('overflow', ''); // Restore body scroll
    }

    $mobileSidebarClose.on('click', closeMobileSidebar);
    $mobileSidebarOverlay.on('click', closeMobileSidebar);

    // Close sidebar when selecting a session on mobile
    $(document).on('click', '.chat-session-item', function() {
        if ($(window).width() <= 768) {
            closeMobileSidebar();
        }
    });

    // Close sidebar when creating new chat on mobile
    $('#newChatBtn').on('click', function() {
        if ($(window).width() <= 768) {
            setTimeout(closeMobileSidebar, 100);
        }
    });

    // IMPORTANT: Close mobile sidebar and hide overlay when ANY modal opens
    // This prevents the overlay from blocking modal interactions
    $(document).on('show.bs.modal', '.modal', function() {
        closeMobileSidebar();
        // Also hide the overlay element completely while modal is open
        $mobileSidebarOverlay.css('display', 'none');
    });

    // Restore overlay when modal closes (only on mobile)
    $(document).on('hidden.bs.modal', '.modal', function() {
        if ($(window).width() <= 991) {
            $mobileSidebarOverlay.css('display', '');
        }
    });

    // Update session count badge
    function updateMobileSessionCount() {
        const count = $('.chat-session-item').length;
        const $badge = $('#mobileSessionCount');
        if (count > 0) {
            $badge.text(count).show();
        } else {
            $badge.hide();
        }
    }
    updateMobileSessionCount();

    // Handle escape key to close sidebar
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $chatSidebar.hasClass('mobile-open')) {
            closeMobileSidebar();
        }
    });

    // ===== FORMAT EXISTING MESSAGES ON PAGE LOAD =====
    // Format assistant messages that were rendered via Blade (to apply markdown formatting including tables)
    setTimeout(function() {
        $('.message-wrapper.assistant').each(function() {
            const $wrapper = $(this);
            const $content = $wrapper.find('.message-content');
            const rawContent = $wrapper.data('raw-content');

            // Format if we have raw content
            if (rawContent) {
                console.log('Formatting existing assistant message');
                const formattedContent = formatAIContent(rawContent);
                $content.html(formattedContent);
            }
        });
    }, 100); // Small delay to ensure formatAIContent is defined

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

        // Hide progress indicator and reset progress bar
        $('#processingProgress').removeClass('show');
        $('#progressBarFill').css('width', '0%');
        currentProgressPercent = 0;

        // Remove any thinking/progress messages
        $('.message-wrapper.thinking').remove();
        $('[data-message-id^="progress-"]').remove();

        // Reset sending state
        isSending = false;
        releaseWakeLock(); // Allow screen to dim again

        // Remove thinking-in-progress class from chat input area
        $('.chat-input-area').removeClass('thinking-in-progress');

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

                    // Focus on input field after new chat created
                    setTimeout(function() {
                        $('#messageInput').focus();
                    }, 100);
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

    // NOTE: Title generation now happens during first message processing (inline)
    // The generateTitlesForUntitledSessions() function is kept for legacy sessions
    // but is no longer called on page load to reduce API calls
    // generateTitlesForUntitledSessions(); // DISABLED - titles now generated inline

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

                        // Focus on input field after new chat created
                        setTimeout(function() {
                            $('#messageInput').focus();
                        }, 100);
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

        // Hide any lingering progress/typing indicators
        $('#processingProgress').removeClass('show');
        $('#progressBarFill').css('width', '0%');
        $('#typingIndicator').removeClass('show');
        currentProgressPercent = 0;
        $('.chat-input-area').removeClass('thinking-in-progress');

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

        // Reset session flow log tracking when loading a session
        window.sessionFlowLogs = [];
        window.sessionTotalTokens = { input: 0, output: 0, total: 0, cost: 0 };

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
        // Also restore ALL flowLogs from assistant messages for session tracking
        let lastAssistantIndex = -1;
        let messageCounter = 0;
        for (let i = 0; i < messages.length; i++) {
            if (messages[i].role === 'assistant') {
                messageCounter++;
                lastAssistantIndex = i;
                // Restore flow log from saved message and add to session tracking
                if (messages[i].flowLog) {
                    messages[i].flowLog.messageNumber = messageCounter;
                    window.sessionFlowLogs.push(messages[i].flowLog);

                    // Accumulate session totals
                    if (messages[i].flowLog.tokenUsage && messages[i].flowLog.tokenUsage.total) {
                        window.sessionTotalTokens.input += messages[i].flowLog.tokenUsage.total.inputTokens || 0;
                        window.sessionTotalTokens.output += messages[i].flowLog.tokenUsage.total.outputTokens || 0;
                        window.sessionTotalTokens.total += messages[i].flowLog.tokenUsage.total.totalTokens || 0;
                        window.sessionTotalTokens.cost += messages[i].flowLog.tokenUsage.total.estimatedCost || 0;
                    }

                    window.lastFlowLog = messages[i].flowLog;
                }
            }
        }

        // Update modal with last flow log
        if (window.lastFlowLog) {
            updateFlowLogModal(window.lastFlowLog);
            console.log('Restored ' + window.sessionFlowLogs.length + ' flowLogs from session');
            console.log('Session totals restored:', window.sessionTotalTokens);
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
    function enableChatInput(placeholder = 'Type your message...', preserveValue = false) {
        $('.chat-input-area').removeClass('awaiting-action thinking-in-progress');
        $('#messageInput').prop('disabled', false).attr('placeholder', placeholder).css('height', 'auto');
        // Clear input value unless explicitly preserving (e.g., for error recovery)
        if (!preserveValue) {
            $('#messageInput').val('');
        }
        // Don't auto-focus on mobile to prevent keyboard popup
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

        // Remove chat-empty immediately - no delay to prevent visual gaps/blinks
        const $chatEmpty = $('#chatEmpty');
        if ($chatEmpty.length) {
            $chatEmpty.remove(); // Instant removal - message animation handles the transition
        }

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

                    // Focus on input field after new chat created
                    setTimeout(function() {
                        $('#messageInput').focus();
                    }, 100);
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

        // Clear session selection - go back to welcome/home screen
        currentSessionId = null;
        window.currentSessionId = null;

        // Remove active class from all sessions in sidebar
        $('.chat-session-item').removeClass('active');

        // Show welcome screen
        $('#chatMessages').html(`
            <div class="chat-empty" id="chatEmpty">
                <i class="bx bx-message-rounded-dots"></i>
                <h5 class="text-dark">Welcome to Anisenso Technician</h5>
                <p>Click "New Chat" to start a conversation</p>
            </div>
        `);

        // Update chat title
        $('#chatTitle').text('Select or start a chat');

        // Disable chat input
        $('#messageInput').prop('disabled', true).attr('placeholder', 'Select a chat to start messaging...').val('');
        $('#sendBtn').prop('disabled', true);
        $('#attachBtn').prop('disabled', true);

        // Clear any pending images
        selectedImages = [];
        $('#imagePreviewGrid').empty();
        $('#imagePreviewContainer').removeClass('has-images');
        updateImageCount();

        // Update URL to remove session parameter (optional - cleaner URL)
        if (window.history && window.history.pushState) {
            window.history.pushState({}, '', '/ai-technician-chat');
        }

        // Show confirmation
        toastr.success('Great! Feel free to start a new chat anytime.', 'Topic Closed');
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
    // Global response watchdog - tracks if response was received and forces fetch if not
    let responseWatchdog = null;
    let responseReceived = false;
    let receivedStreamEvent = false; // Track if we've received ANY event (thinking/progress) - means backend is active
    let mobileSafetyTimer = null; // Mobile safety timer - global for access by forceLoadLatestResponse
    let watchdogSessionId = null;
    let watchdogTempMsgId = null;
    let watchdogOriginalMsg = null;

    function startResponseWatchdog(sessionId, tempMsgId, originalMessage) {
        responseReceived = false;
        receivedStreamEvent = false; // Reset stream event flag
        watchdogSessionId = sessionId;
        watchdogTempMsgId = tempMsgId;
        watchdogOriginalMsg = originalMessage;

        // Clear any existing watchdog
        if (responseWatchdog) {
            clearInterval(responseWatchdog);
        }

        let checkCount = 0;
        const maxChecks = 30; // Check for 60 seconds (2s intervals)

        console.log('=== RESPONSE WATCHDOG STARTED ===');

        responseWatchdog = setInterval(function() {
            checkCount++;
            console.log(`Watchdog check #${checkCount}/${maxChecks}`);

            // Check if response was marked as received
            if (responseReceived) {
                console.log('Watchdog: Response was received, stopping');
                clearInterval(responseWatchdog);
                responseWatchdog = null;
                return;
            }

            // Check if progress bar is hidden (processing complete)
            const isProcessing = $('#processingProgress').hasClass('show') ||
                                 $('#typingIndicator').hasClass('show');

            // Check if there's an assistant message after the user message
            const $allMessages = $('#chatMessages').find('.message-wrapper');
            const $lastMsg = $allMessages.last();
            const hasAssistantResponse = $lastMsg.find('.ai-message').length > 0 ||
                                        $lastMsg.hasClass('assistant');

            console.log(`Watchdog: Processing=${isProcessing}, HasResponse=${hasAssistantResponse}, ReceivedEvent=${receivedStreamEvent}`);

            // IMPORTANT: If we received ANY stream event (thinking/progress), backend is actively processing
            // Agricultural queries with web search can take 30-60 seconds, so extend timeout significantly
            // Only force fetch after 10s if we haven't received ANY event (SSE failure case)
            if (!hasAssistantResponse && checkCount >= 5 && !receivedStreamEvent && !isProcessing) {
                console.log('=== WATCHDOG: No response AND no stream events after 10s - FORCE FETCHING ===');
                clearInterval(responseWatchdog);
                responseWatchdog = null;
                forceLoadLatestResponse(watchdogSessionId, watchdogTempMsgId, watchdogOriginalMsg);
                return;
            }

            // If we received stream events but no response yet, wait longer (up to 90 seconds for complex queries)
            // This handles agricultural queries with Google Search which can take 30-60 seconds
            if (!hasAssistantResponse && checkCount >= 45 && receivedStreamEvent) {
                console.log('=== WATCHDOG: Backend active but no response after 90s - FORCE FETCHING ===');
                clearInterval(responseWatchdog);
                responseWatchdog = null;
                forceLoadLatestResponse(watchdogSessionId, watchdogTempMsgId, watchdogOriginalMsg);
                return;
            }

            // Max checks reached (60 seconds)
            if (checkCount >= maxChecks) {
                console.log('Watchdog: Max checks reached');
                clearInterval(responseWatchdog);
                responseWatchdog = null;

                if (!hasAssistantResponse) {
                    console.log('=== WATCHDOG FINAL: Forcing fetch ===');
                    forceLoadLatestResponse(watchdogSessionId, watchdogTempMsgId, watchdogOriginalMsg);
                }
            }
        }, 2000); // Check every 2 seconds
    }

    function forceLoadLatestResponse(sessionId, tempMsgId, originalMessage) {
        console.log('=== FORCE LOADING LATEST RESPONSE ===');

        // Mark recovery in progress
        window.recoveryInProgress = true;

        // CRITICAL: Abort any zombie fetch stream that might be hanging
        if (currentAbortController) {
            console.log('Force load: Aborting zombie fetch stream...');
            currentAbortController.abort();
            currentAbortController = null;
        }

        // Clear any conflicting timers
        if (globalProgressTimer) {
            clearTimeout(globalProgressTimer);
            globalProgressTimer = null;
        }
        if (responseWatchdog) {
            clearInterval(responseWatchdog);
            responseWatchdog = null;
        }
        if (mobileSafetyTimer) {
            clearTimeout(mobileSafetyTimer);
            mobileSafetyTimer = null;
        }

        // Keep progress bar showing while we fetch - just update to 95%
        $('#typingIndicator').removeClass('show');
        $('#progressBarFill').css('width', '95%');
        // Make sure progress bar is visible
        if (!$('#processingProgress').hasClass('show')) {
            $('#processingProgress').addClass('show');
        }

        $.ajax({
            url: `/ai-technician-chat/session/${sessionId}/messages`,
            type: 'GET',
            timeout: 30000, // 30 second timeout for mobile networks
            success: function(response) {
                if (response.success && response.data && response.data.messages) {
                    const msgs = response.data.messages;
                    console.log('Force load: Got', msgs.length, 'messages');

                    // Update optimistic user message ID instead of remove/re-add (prevents blink)
                    const userMsgs = msgs.filter(m => m.role === 'user');
                    const $optimisticMsg = $(`.message-wrapper[data-message-id="${tempMsgId}"]`);

                    if (userMsgs.length > 0) {
                        const lastUserMsg = userMsgs[userMsgs.length - 1];
                        if ($optimisticMsg.length) {
                            // Just update the ID - no re-render needed
                            console.log('Force load: Updating optimistic message ID to', lastUserMsg.id);
                            $optimisticMsg.attr('data-message-id', lastUserMsg.id);
                            $optimisticMsg.find('.message-content').attr('id', 'content-' + lastUserMsg.id);
                        } else if ($(`.message-wrapper[data-message-id="${lastUserMsg.id}"]`).length === 0) {
                            console.log('Force load: Adding missing user message');
                            appendMessage(lastUserMsg);
                        }
                    } else if ($optimisticMsg.length) {
                        // No user messages in response but optimistic exists - remove it
                        $optimisticMsg.remove();
                    }

                    // CRITICAL FIX: For follow-ups, we need to find an assistant message
                    // that RESPONDS to the current user message, not just "the last assistant message"
                    // which could be from a previous exchange

                    // Step 1: Find the index of the last USER message (our current message)
                    let lastUserMsgIndex = -1;
                    for (let i = msgs.length - 1; i >= 0; i--) {
                        if (msgs[i].role === 'user') {
                            lastUserMsgIndex = i;
                            break;
                        }
                    }
                    console.log('Force load: Last user message at index', lastUserMsgIndex);

                    // Step 2: Find an assistant message that comes AFTER the last user message
                    // This ensures we're looking for the response to OUR question, not a previous one
                    let foundAssistantMessage = false;
                    let assistantMsgForCurrentQuestion = null;

                    if (lastUserMsgIndex >= 0) {
                        // Look for assistant messages AFTER the last user message
                        for (let i = lastUserMsgIndex + 1; i < msgs.length; i++) {
                            if (msgs[i].role === 'assistant') {
                                assistantMsgForCurrentQuestion = msgs[i];
                                break;
                            }
                        }
                    }

                    // If no assistant message after last user message, backend is still processing
                    if (!assistantMsgForCurrentQuestion) {
                        console.log('Force load: No assistant response for current question yet (backend still processing)');
                        // Fall through to trigger fallback polling
                    } else {
                        const assistantMsg = assistantMsgForCurrentQuestion;
                        console.log('Force load: Found assistant response ID', assistantMsg.id, 'for current question');

                        if ($(`.message-wrapper[data-message-id="${assistantMsg.id}"]`).length === 0) {
                            console.log('Force load: Displaying NEW assistant message');
                            foundAssistantMessage = true; // Mark as found BEFORE setTimeout

                            // Store flow log if available
                            if (assistantMsg.flowLog) {
                                assistantMsg.flowLog.messageNumber = window.sessionFlowLogs.length + 1;
                                assistantMsg.flowLog.timestamp = new Date().toLocaleTimeString();
                                window.sessionFlowLogs.push(assistantMsg.flowLog);

                                if (assistantMsg.flowLog.tokenUsage && assistantMsg.flowLog.tokenUsage.total) {
                                    window.sessionTotalTokens.input += assistantMsg.flowLog.tokenUsage.total.inputTokens || 0;
                                    window.sessionTotalTokens.output += assistantMsg.flowLog.tokenUsage.total.outputTokens || 0;
                                    window.sessionTotalTokens.total += assistantMsg.flowLog.tokenUsage.total.totalTokens || 0;
                                    window.sessionTotalTokens.cost += assistantMsg.flowLog.tokenUsage.total.estimatedCost || 0;
                                }
                                window.lastFlowLog = assistantMsg.flowLog;
                                updateFlowLogModal(window.lastFlowLog);
                            }

                            // Smooth transition: fill to 100%, then show message
                            $('#progressBarFill').css('width', '100%');

                            setTimeout(function() {
                                // Hide progress bar smoothly
                                $('#processingProgress').removeClass('show');
                                $('#progressBarFill').css('width', '0%');

                                // Display the message
                                appendMessage({
                                    id: assistantMsg.id,
                                    role: 'assistant',
                                    content: assistantMsg.content,
                                    formattedTime: assistantMsg.formattedTime,
                                    processingTime: assistantMsg.processingTime,
                                    searchedImages: assistantMsg.searchedImages || []
                                }, true);
                                scrollToBottom();

                                // Update session title from the response data
                                if (response.data.session) {
                                    const sessionInfo = response.data.session;
                                    const $session = $(`.chat-session-item[data-session-id="${currentSessionId}"]`);

                                    if (sessionInfo.displayName) {
                                        $session.find('.session-name').text(sessionInfo.displayName);
                                        $session.find('.session-time').text('Just now');
                                        $('#sessionsList').prepend($session);

                                        // If title was generated, mark it and update header
                                        if (sessionInfo.titleGenerated) {
                                            $session.data('title-generated', 'true');
                                            $session.attr('data-title-generated', 'true');
                                            $('#chatTitle').text(sessionInfo.displayName);
                                            console.log('Session title updated from force load:', sessionInfo.displayName);
                                        }
                                    }
                                }

                                responseReceived = true;
                                window.recoveryInProgress = false; // Reset recovery flag
                                window.wasProcessingWhenHidden = false;

                                // Reset UI
                                isSending = false;
                                releaseWakeLock(); // Allow screen to dim again
                                $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                                $('#attachBtn').prop('disabled', false);
                                // Keep input disabled - user must click Follow-up or New Question
                                // Note: appendMessage with showActions=true already calls disableChatInput()
                                disableChatInput();
                            }, 200);
                        } else {
                            // Message already displayed (SSE delivered it successfully)
                            console.log('Force load: Assistant message for current question already displayed (ID:', assistantMsg.id, ')');
                            foundAssistantMessage = true;
                            window.recoveryInProgress = false;
                            window.wasProcessingWhenHidden = false;
                            responseReceived = true;
                            isSending = false;
                            releaseWakeLock(); // Allow screen to dim again
                            $('#processingProgress').removeClass('show');
                            $('#progressBarFill').css('width', '0%');
                            $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                            $('#attachBtn').prop('disabled', false);
                            // Keep input disabled - user must click Follow-up or New Question
                            disableChatInput();
                        }
                    }

                    // If no assistant message found (AI still processing), try fallback polling
                    if (!foundAssistantMessage) {
                        console.log('Force load: No assistant response yet, trying fallback poll...');
                        // Use fallback polling to wait for response (more retries for long agricultural queries)
                        fetchLatestMessageFallback(sessionId, tempMsgId, originalMessage, 0, 25);
                    }
                } else {
                    // No messages at all - reset UI
                    console.log('Force load: No messages in response, resetting UI');
                    window.recoveryInProgress = false; // Reset recovery flag
                    window.wasProcessingWhenHidden = false;
                    $('#processingProgress').removeClass('show');
                    $('#progressBarFill').css('width', '0%');
                    isSending = false;
                    releaseWakeLock(); // Allow screen to dim again
                    $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                    $('#attachBtn').prop('disabled', false);
                    enableChatInput('Type your message...');
                }
            },
            error: function(xhr) {
                console.error('Force load failed:', xhr);
                window.recoveryInProgress = false; // Reset recovery flag
                window.wasProcessingWhenHidden = false;
                $('#processingProgress').removeClass('show');
                $('#progressBarFill').css('width', '0%');
                $('#typingIndicator').removeClass('show');
                $('.chat-input-area').removeClass('thinking-in-progress');
                toastr.error('Failed to load response. Please refresh the page.');
                isSending = false;
                releaseWakeLock(); // Allow screen to dim again
                $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                $('#attachBtn').prop('disabled', false);
                enableChatInput('Type your message...');
            }
        });
    }

    function sendMessage() {
        if (isSending || !currentSessionId) return;

        const message = $('#messageInput').val().trim();
        if (!message && selectedImages.length === 0) return;

        isSending = true;
        responseReceived = false; // Reset for watchdog
        $('#sendBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        // Request wake lock to prevent screen from dimming while AI processes
        requestWakeLock();

        // Disable chat input while technician is thinking
        $('.chat-input-area').addClass('thinking-in-progress');
        $('#messageInput').prop('disabled', true).attr('placeholder', 'Nag-iisip ang technician...').css('height', '38px');

        // Show typing indicator IMMEDIATELY (before any async operations)
        $('#typingIndicator').addClass('show');
        // Reset and hide progress indicator
        $('#processingProgress').removeClass('show');

        // MOBILE: Start progress bar at 0% since we show 3 dots first
        // DESKTOP: Start at 10% for immediate visual feedback
        const isMobileSend = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                             window.innerWidth <= 768;
        if (isMobileSend) {
            $('#progressBarFill').css('width', '0%');
            currentProgressPercent = 0;
        } else {
            $('#progressBarFill').css('width', '10%');
            currentProgressPercent = 10;
        }
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

        // Start the response watchdog for mobile/fallback handling
        startResponseWatchdog(currentSessionId, tempUserMsgId, message);

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

        // Progress messages for while waiting (shown only when progress bar is at 0%)
        // Messages should sound like a knowledgeable technician thinking
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
        const MAX_THINKING_MESSAGES = 3; // Maximum thinking messages to show at 0%
        let thinkingMessageCount = 0; // Track how many thinking messages shown

        // Start progress timer - shows message every 5-10 seconds while progress is at initial 10%
        function startProgressTimer() {
            progressIndex = 0;
            currentProgressPercent = 10; // Start at 10% (matches initial bar width)
            progressMsgId = null;
            thinkingMessageCount = 0; // Reset counter

            function scheduleNextProgress() {
                // Random interval between 5-10 seconds (slower to avoid shifting too fast)
                const randomInterval = Math.floor(Math.random() * 5000) + 5000;

                progressTimer = setTimeout(() => {
                    // Only show messages when progress bar is visible AND at initial 10%
                    if (!$('#processingProgress').hasClass('show') || currentProgressPercent > 5) {
                        // If progress has started (beyond initial 10%), remove any existing message and stop
                        if (currentProgressPercent > 5 && progressMsgId) {
                            $(`[data-message-id="${progressMsgId}"]`).fadeOut(200, function() {
                                $(this).remove();
                            });
                            progressMsgId = null;
                        }
                        // Schedule next check (in case progress resets)
                        if (isSending) {
                            scheduleNextProgress();
                        }
                        return;
                    }

                    // Stop after showing MAX_THINKING_MESSAGES
                    if (thinkingMessageCount >= MAX_THINKING_MESSAGES) {
                        // Keep last message visible, don't schedule more
                        return;
                    }

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
                    thinkingMessageCount++; // Increment counter

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

                    // Insert before progress indicator
                    const $progressIndicator = $('#processingProgress');
                    if ($progressIndicator.length > 0 && $progressIndicator.hasClass('show')) {
                        $progressIndicator.before(progressHtml);
                    } else {
                        $('#chatMessages').append(progressHtml);
                    }
                    scrollToBottom();

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

        // Ensure typing indicator stays visible with periodic check (only if progress not showing)
        const typingCheckInterval = setInterval(() => {
            if (isSending && !$('#processingProgress').hasClass('show')) {
                $('#typingIndicator').addClass('show');
            }
        }, 1000);

        // Create AbortController for this request (allows cancellation)
        currentAbortController = new AbortController();

        // Mobile safety timer - start fallback polling if no response after 30 seconds
        // This is crucial for mobile devices where SSE streaming may not work reliably
        const isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                               window.innerWidth <= 768;
        // mobileSafetyTimer is declared globally for access by forceLoadLatestResponse
        let mobileFallbackTriggered = false;

        if (isMobileDevice) {
            console.log('=== MOBILE DEVICE DETECTED - Setting up safety fallback ===');
            mobileSafetyTimer = setTimeout(() => {
                // Check if response was received (processingProgress is hidden when response shows)
                const isStillProcessing = $('#processingProgress').hasClass('show') ||
                                         $('#typingIndicator').hasClass('show');
                const hasAssistantResponse = $('#chatMessages').find('.message-wrapper:last .ai-message').length > 0;

                console.log('Mobile safety check - Still processing:', isStillProcessing, 'Has response:', hasAssistantResponse);

                if (isStillProcessing && !hasAssistantResponse && !mobileFallbackTriggered) {
                    console.log('=== MOBILE SAFETY: Triggering fallback poll ===');
                    mobileFallbackTriggered = true;
                    fetchLatestMessageFallback(sessionId, tempUserMsgId, message, 0, 15);
                }
            }, 30000); // 30 seconds safety timer
        }

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

            // Timeout fallback - if no events received after 120 seconds, try fallback polling
            const streamTimeout = setTimeout(() => {
                if (!receivedAnyEvent) {
                    console.error('=== STREAM TIMEOUT - No events received after 120 seconds ===');
                    // Clear mobile safety timer
                    if (mobileSafetyTimer) {
                        clearTimeout(mobileSafetyTimer);
                        mobileSafetyTimer = null;
                    }
                    // Try fallback polling instead of just showing error
                    if (!mobileFallbackTriggered) {
                        console.log('Attempting fallback poll after stream timeout...');
                        mobileFallbackTriggered = true;
                        stopProgressTimer();
                        clearInterval(typingCheckInterval);
                        fetchLatestMessageFallback(sessionId, tempUserMsgId, message, 0, 10);
                    } else {
                        toastr.error('Response timed out. Please try again.');
                        stopProgressTimer();
                        clearInterval(typingCheckInterval);
                        handleStreamError(tempUserMsgId, message, sentImages);
                    }
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

                        // Remove thinking state but keep input disabled for action selection
                        $('.chat-input-area').removeClass('thinking-in-progress');

                        // If response was received, keep input disabled for Follow-up/New Question selection
                        // If no response (error), enable input for retry
                        if (receivedResponse) {
                            disableChatInput();
                        } else {
                            $('#messageInput').prop('disabled', false).attr('placeholder', 'Type your message...').css('height', 'auto');
                        }

                        // If stream ended but no response was received, try fallback polling
                        if (!receivedResponse) {
                            // Don't release wake lock yet - fallback will handle it
                            console.warn('Stream ended without response event - trying fallback poll');
                            // Clear mobile safety timer since we're manually triggering fallback
                            if (mobileSafetyTimer) {
                                clearTimeout(mobileSafetyTimer);
                                mobileSafetyTimer = null;
                            }
                            // Prevent duplicate fallback calls
                            if (!mobileFallbackTriggered) {
                                mobileFallbackTriggered = true;
                                // Try to fetch latest message from server as fallback
                                fetchLatestMessageFallback(sessionId, tempUserMsgId, message);
                            }
                        } else {
                            // Response was received successfully, release wake lock
                            releaseWakeLock();
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
                                    // Clear mobile safety timer since we got a response
                                    if (mobileSafetyTimer) {
                                        clearTimeout(mobileSafetyTimer);
                                        mobileSafetyTimer = null;
                                        console.log('Mobile safety timer cleared - response received');
                                    }
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
                    // Clear mobile safety timer on any error
                    if (mobileSafetyTimer) {
                        clearTimeout(mobileSafetyTimer);
                        mobileSafetyTimer = null;
                    }

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
            // Clear mobile safety timer on any error
            if (mobileSafetyTimer) {
                clearTimeout(mobileSafetyTimer);
                mobileSafetyTimer = null;
            }

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
                // Update optimistic message with real ID instead of remove/re-add (prevents blink)
                console.log('=== PROCESSING USER_MESSAGE EVENT ===');
                const $optimisticMsg = $(`.message-wrapper[data-message-id="${tempUserMsgId}"]`);
                if ($optimisticMsg.length) {
                    // Just update the ID - content is the same, no need to re-render
                    console.log('Updating optimistic message ID from', tempUserMsgId, 'to', data.id);
                    $optimisticMsg.attr('data-message-id', data.id);
                    // Update the content ID as well
                    $optimisticMsg.find('.message-content').attr('id', 'content-' + data.id);
                } else {
                    // Optimistic message not found, append the real one
                    console.log('Optimistic message not found, appending real user message...');
                    appendMessage(data);
                }
                scrollToBottom();
                break;

            case 'thinking':
                // Show thinking reply IMMEDIATELY (this is the key!)
                console.log('=== PROCESSING THINKING EVENT ===');
                console.log('Thinking content:', data.content);

                // Mark that we received a stream event - backend is actively processing
                receivedStreamEvent = true;

                const isMobileThinking = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                                         window.innerWidth <= 768;

                const thinkingMsg = {
                    id: 'thinking-' + Date.now(),
                    role: 'thinking', // Use 'thinking' role for yellow styling
                    content: data.content || '[Thinking...]',
                    formattedTime: data.formattedTime
                };
                console.log('Appending thinking message...');
                // No typewriter for thinking messages - show immediately
                $('#typingIndicator').removeClass('show');
                appendMessage(thinkingMsg, false, false);

                // MOBILE: Keep showing 3 dots during thinking, switch to progress bar only when progress events come
                // DESKTOP: Show progress indicator immediately
                if (isMobileThinking) {
                    // On mobile, show typing indicator (3 dots) during thinking
                    $('#processingProgress').removeClass('show');
                    $('#typingIndicator').addClass('show');
                    console.log('Mobile: Keeping 3 dots during thinking phase');
                } else {
                    // On desktop, show progress indicator
                    $('#typingIndicator').removeClass('show');
                    $('#processingProgress').addClass('show');
                    console.log('Desktop: Progress indicator shown after thinking message');
                }
                scrollToBottom();
                break;

            case 'progress':
                // Update progress indicator
                console.log('=== PROCESSING PROGRESS EVENT ===');
                const { step, totalSteps, percentage } = data;

                // Mark that we received a stream event - backend is actively processing
                receivedStreamEvent = true;

                // Scale percentage to max 90% (100% reserved for just before reply appears)
                // This makes the UI feel more responsive - 100% means "reply is coming NOW"
                const scaledPercentage = Math.min(Math.round(percentage * 0.9), 90);

                // Update the progress bar width (scaled to max 90%)
                $('#progressBarFill').css('width', `${scaledPercentage}%`);

                // Update current progress percent (used by thinking message timer)
                currentProgressPercent = scaledPercentage;

                // If progress started (beyond initial 10%), remove any thinking message
                if (scaledPercentage > 5 && progressMsgId) {
                    $(`[data-message-id="${progressMsgId}"]`).fadeOut(200, function() {
                        $(this).remove();
                    });
                    progressMsgId = null;
                }

                // Show progress indicator, hide typing indicator
                // On mobile, this is when we switch from 3 dots to progress bar
                $('#typingIndicator').removeClass('show');
                $('#processingProgress').addClass('show');
                scrollToBottom();
                console.log(`Progress: ${step}/${totalSteps} (${percentage}% -> ${scaledPercentage}% scaled)`);
                break;

            case 'blocked':
                // Show block message (from Blocker element)
                responseReceived = true; // Mark for watchdog
                if (responseWatchdog) { clearInterval(responseWatchdog); responseWatchdog = null; }

                $('#typingIndicator').removeClass('show');
                $('#processingProgress').removeClass('show');
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

                // Mark response as received for the watchdog
                responseReceived = true;
                if (responseWatchdog) {
                    clearInterval(responseWatchdog);
                    responseWatchdog = null;
                    console.log('Response watchdog cleared - response received');
                }

                // Store flow log for the modal and session tracking (process first)
                if (data.flowLog) {
                    // Add message number to flow log
                    data.flowLog.messageNumber = window.sessionFlowLogs.length + 1;
                    data.flowLog.timestamp = new Date().toLocaleTimeString();

                    // Add to session array
                    window.sessionFlowLogs.push(data.flowLog);

                    // Accumulate session totals
                    if (data.flowLog.tokenUsage && data.flowLog.tokenUsage.total) {
                        window.sessionTotalTokens.input += data.flowLog.tokenUsage.total.inputTokens || 0;
                        window.sessionTotalTokens.output += data.flowLog.tokenUsage.total.outputTokens || 0;
                        window.sessionTotalTokens.total += data.flowLog.tokenUsage.total.totalTokens || 0;
                        window.sessionTotalTokens.cost += data.flowLog.tokenUsage.total.estimatedCost || 0;
                    }

                    window.lastFlowLog = data.flowLog;
                    console.log('Flow log received (message #' + data.flowLog.messageNumber + '):', window.lastFlowLog);
                    console.log('Session totals:', window.sessionTotalTokens);
                    updateFlowLogModal(window.lastFlowLog);
                }

                // Check for searched images
                console.log('Images in response:', data.images);

                // Fill progress bar to 100% just before showing the reply
                // This gives visual feedback that "reply is ready NOW"
                $('#progressBarFill').css('width', '100%');
                currentProgressPercent = 100;

                // Hide progress indicator after brief delay to show 100%
                setTimeout(() => {
                    $('#typingIndicator').removeClass('show');
                    $('#processingProgress').removeClass('show');
                }, 200);

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
                console.log('=== SESSION NAME UPDATE ===');
                console.log('data.sessionName:', data.sessionName);
                console.log('data.generatedTitle:', data.generatedTitle);
                console.log('data.titleGenerated:', data.titleGenerated);
                console.log('currentSessionId:', currentSessionId);

                if (data.sessionName) {
                    const $session = $(`.chat-session-item[data-session-id="${currentSessionId}"]`);
                    console.log('Found session element:', $session.length > 0);

                    // Update the session name in sidebar
                    $session.find('.session-name').text(data.sessionName);
                    $session.find('.session-time').text('Just now');

                    // Move to top of list
                    $('#sessionsList').prepend($session);

                    // If title was generated during this message, mark it and update header
                    if (data.titleGenerated && data.generatedTitle) {
                        $session.data('title-generated', 'true');
                        $session.attr('data-title-generated', 'true');
                        // Update the header with the generated title
                        $('#chatTitle').text(data.generatedTitle);
                        console.log('AI title generated inline and applied:', data.generatedTitle);
                    }
                }
                break;

            case 'not_related':
                // Follow-up question is not related to original topic
                responseReceived = true; // Mark for watchdog
                if (responseWatchdog) { clearInterval(responseWatchdog); responseWatchdog = null; }

                $('#typingIndicator').removeClass('show');
                $('#processingProgress').removeClass('show');

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
                $('#typingIndicator').removeClass('show');

                // IMPORTANT: Only enable chat input if response was already received
                // This prevents user from sending while still loading
                if (responseReceived) {
                    releaseWakeLock(); // Allow screen to dim again
                    $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                    $('#attachBtn').prop('disabled', false);
                    $('#processingProgress').removeClass('show');
                    $('#progressBarFill').css('width', '0%');
                } else {
                    // Keep buttons disabled and progress bar visible while waiting for response
                    $('#progressBarFill').css('width', '95%');
                    console.log('Keeping UI disabled - waiting for response');
                }

                // MOBILE FIX: Check if response was actually displayed after 'done' event
                // This catches cases where stream completes but response wasn't parsed
                setTimeout(function() {
                    // Skip if response already received
                    if (responseReceived) {
                        console.log('Post-done check: Response already received, skipping');
                        return;
                    }

                    const $allMessages = $('#chatMessages').find('.message-wrapper');
                    const $lastMsg = $allMessages.last();
                    const lastMsgRole = $lastMsg.find('.ai-message').length > 0 ? 'assistant' :
                                       ($lastMsg.find('.user-message').length > 0 ? 'user' : 'unknown');

                    console.log('Post-done check: Last message role:', lastMsgRole);

                    // If last message is user (not assistant), response wasn't displayed
                    if (lastMsgRole === 'user' || lastMsgRole === 'unknown') {
                        console.warn('=== DONE EVENT but no response visible - fetching response ===');
                        const currentSession = currentSessionId;
                        if (currentSession) {
                            // Force reload messages from server (keep progress bar showing)
                            $.ajax({
                                url: `/ai-technician-chat/session/${currentSession}/messages`,
                                type: 'GET',
                                success: function(response) {
                                    if (response.success && response.data && response.data.messages) {
                                        const msgs = response.data.messages;
                                        for (let i = msgs.length - 1; i >= 0; i--) {
                                            if (msgs[i].role === 'assistant') {
                                                const assistantMsg = msgs[i];
                                                if ($(`.message-wrapper[data-message-id="${assistantMsg.id}"]`).length === 0) {
                                                    console.log('=== POST-DONE: Displaying response ===');
                                                    // Fill to 100% before showing
                                                    $('#progressBarFill').css('width', '100%');
                                                    setTimeout(function() {
                                                        $('#processingProgress').removeClass('show');
                                                        $('#progressBarFill').css('width', '0%');
                                                        appendMessage({
                                                            id: assistantMsg.id,
                                                            role: 'assistant',
                                                            content: assistantMsg.content,
                                                            formattedTime: assistantMsg.formattedTime,
                                                            processingTime: assistantMsg.processingTime,
                                                            searchedImages: assistantMsg.searchedImages || []
                                                        }, true);
                                                        scrollToBottom();
                                                        responseReceived = true;
                                                        // Enable chat input after response is displayed
                                                        $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                                                        $('#attachBtn').prop('disabled', false);
                                                    }, 200);
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    }
                }, 500); // Check 500ms after done event

                // NOTE: AI title generation now happens inline during first message processing
                // The generateTitleForSession() call is no longer needed here
                // Titles are generated in the backend and sent with the response event

                console.log('UI reset complete');
                break;

            case 'error':
                toastr.error(data.message || 'An error occurred');
                break;
        }
    }

    // Handle stream errors
    function handleStreamError(tempUserMsgId, message, sentImages, isAbort = false) {
        // If page is currently hidden (screen off), don't show error - let visibility handler recover
        if (window.isPageHidden || isAbort) {
            console.log('Stream error during page hidden or abort - skipping error display');
            // Don't clear watchdog - let visibility handler deal with recovery
            return;
        }

        // If recovery is already in progress, skip
        if (window.recoveryInProgress) {
            console.log('Stream error during recovery - skipping error display');
            return;
        }

        // Check if we should attempt recovery instead of showing error:
        // 1. Page was recently hidden (within last 2 minutes) - covers long standby
        // 2. OR we were processing when page went hidden (most reliable check)
        const recentlyHidden = window.lastHiddenTimestamp &&
                               (Date.now() - window.lastHiddenTimestamp) < 120000; // 2 minutes
        const shouldRecover = (recentlyHidden || window.wasProcessingWhenHidden) &&
                              watchdogSessionId && watchdogTempMsgId;

        if (shouldRecover) {
            console.log('Stream error after page hide - attempting fallback recovery');
            console.log('recentlyHidden:', recentlyHidden, 'wasProcessingWhenHidden:', window.wasProcessingWhenHidden);
            window.recoveryInProgress = true;
            window.wasProcessingWhenHidden = false; // Reset flag
            // Try fallback fetch instead of showing error
            forceLoadLatestResponse(watchdogSessionId, watchdogTempMsgId, watchdogOriginalMsg);
            return;
        }

        // Clear all timers and abort stream on error
        if (currentAbortController) {
            currentAbortController.abort();
            currentAbortController = null;
        }
        if (responseWatchdog) {
            clearInterval(responseWatchdog);
            responseWatchdog = null;
        }
        if (globalProgressTimer) {
            clearTimeout(globalProgressTimer);
            globalProgressTimer = null;
        }
        if (mobileSafetyTimer) {
            clearTimeout(mobileSafetyTimer);
            mobileSafetyTimer = null;
        }
        responseReceived = true; // Prevent further watchdog actions

        // Hide all loading indicators
        $('#processingProgress').removeClass('show');
        $('#progressBarFill').css('width', '0%');
        $('#typingIndicator').removeClass('show');
        $('.chat-input-area').removeClass('thinking-in-progress');

        $(`.message-wrapper[data-message-id="${tempUserMsgId}"]`).remove();
        toastr.error('Failed to send message. Please try again.');
        selectedImages = sentImages;
        rebuildImagePreviews();
        updateImageCount();
        isSending = false;
        releaseWakeLock(); // Allow screen to dim again
        $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
        $('#attachBtn').prop('disabled', false);

        // Enable chat input and restore the message for retry
        enableChatInput('Type your message...', true); // preserveValue = true
        $('#messageInput').val(message); // Restore after enabling
    }

    /**
     * Fallback function to fetch the latest message from server when SSE streaming fails
     * This is especially important for mobile devices where SSE may not work reliably
     *
     * @param {string} sessionId - The current session ID
     * @param {string} tempUserMsgId - The temporary user message ID to remove
     * @param {string} originalMessage - The original message text (for retry purposes)
     * @param {number} retryCount - Current retry attempt (default 0)
     * @param {number} maxRetries - Maximum number of retries (default 10)
     */
    function fetchLatestMessageFallback(sessionId, tempUserMsgId, originalMessage, retryCount = 0, maxRetries = 20) {
        console.log('=== FALLBACK POLLING STARTED ===');
        console.log('Session ID:', sessionId);
        console.log('Retry count:', retryCount, '/', maxRetries);

        // On first retry, abort any zombie stream and clear timers
        if (retryCount === 0) {
            if (currentAbortController) {
                console.log('Fallback: Aborting zombie fetch stream...');
                currentAbortController.abort();
                currentAbortController = null;
            }
            if (globalProgressTimer) {
                clearTimeout(globalProgressTimer);
                globalProgressTimer = null;
            }
            if (mobileSafetyTimer) {
                clearTimeout(mobileSafetyTimer);
                mobileSafetyTimer = null;
            }
        }

        // Detect if running on mobile
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ||
                         window.innerWidth <= 768;
        console.log('Is mobile device:', isMobile);

        // Exponential backoff: 3s, 4.5s, 6.75s, 10s... (start at 3s to give backend time to save)
        const delay = Math.min(3000 * Math.pow(1.5, retryCount), 10000);

        setTimeout(function() {
            $.ajax({
                url: `/ai-technician-chat/session/${sessionId}/messages`,
                type: 'GET',
                timeout: 20000, // 20 second timeout
                success: function(response) {
                    console.log('=== FALLBACK POLL RESPONSE ===');
                    console.log('Success:', response.success);

                    if (response.success && response.data && response.data.messages) {
                        const messages = response.data.messages;
                        console.log('Total messages:', messages.length);

                        // CRITICAL FIX: For follow-ups, we need to find an assistant message
                        // that RESPONDS to the current user message, not just "the last assistant message"
                        // which could be from a previous exchange

                        // Step 1: Find the index of the last USER message (our current message)
                        let lastUserMsgIndex = -1;
                        let lastUserMsg = null;
                        for (let i = messages.length - 1; i >= 0; i--) {
                            if (messages[i].role === 'user') {
                                lastUserMsgIndex = i;
                                lastUserMsg = messages[i];
                                break;
                            }
                        }
                        console.log('Fallback: Last user message at index', lastUserMsgIndex);

                        // Step 2: Find an assistant message that comes AFTER the last user message
                        // This ensures we're looking for the response to OUR question, not a previous one
                        let assistantMsgForCurrentQuestion = null;
                        if (lastUserMsgIndex >= 0) {
                            for (let i = lastUserMsgIndex + 1; i < messages.length; i++) {
                                if (messages[i].role === 'assistant') {
                                    assistantMsgForCurrentQuestion = messages[i];
                                    break;
                                }
                            }
                        }

                        if (assistantMsgForCurrentQuestion) {
                            const latestAssistantMsg = assistantMsgForCurrentQuestion;
                            console.log('Found assistant response ID:', latestAssistantMsg.id, 'for current question');

                            // Check if this message is already displayed in the chat
                            const $existingMsg = $(`.message-wrapper[data-message-id="${latestAssistantMsg.id}"]`);
                            if ($existingMsg.length > 0) {
                                console.log('Assistant message for current question already displayed (ID:', latestAssistantMsg.id, ') - success!');
                                // Reset UI since message is already there
                                window.recoveryInProgress = false;
                                window.wasProcessingWhenHidden = false;
                                isSending = false;
                                releaseWakeLock();
                                $('#typingIndicator').removeClass('show');
                                $('#processingProgress').removeClass('show');
                                $('#progressBarFill').css('width', '0%');
                                $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                                $('#attachBtn').prop('disabled', false);
                                // Keep input disabled - user must click Follow-up or New Question
                                disableChatInput();
                                return; // Success, stop polling
                            } else {
                                console.log('=== DISPLAYING FALLBACK MESSAGE ===');

                                // Hide all loading indicators
                                $('#typingIndicator').removeClass('show');
                                $('#processingProgress').removeClass('show');
                                $('#progressBarFill').css('width', '0%');

                                // Update optimistic user message ID instead of remove/re-add (prevents blink)
                                const $optimisticUserMsg = $(`.message-wrapper[data-message-id="${tempUserMsgId}"]`);

                                if ($optimisticUserMsg.length && lastUserMsg) {
                                    // Just update the ID - no re-render needed
                                    console.log('Updating optimistic message ID to', lastUserMsg.id);
                                    $optimisticUserMsg.attr('data-message-id', lastUserMsg.id);
                                    $optimisticUserMsg.find('.message-content').attr('id', 'content-' + lastUserMsg.id);
                                } else if (lastUserMsg) {
                                    const $existingUserMsg = $(`.message-wrapper[data-message-id="${lastUserMsg.id}"]`);
                                    if ($existingUserMsg.length === 0) {
                                        // User message not displayed, add it
                                        console.log('Adding missing user message');
                                        appendMessage(lastUserMsg);
                                    }
                                }

                                // Store flow log if available
                                if (latestAssistantMsg.flowLog) {
                                    latestAssistantMsg.flowLog.messageNumber = window.sessionFlowLogs.length + 1;
                                    latestAssistantMsg.flowLog.timestamp = new Date().toLocaleTimeString();
                                    window.sessionFlowLogs.push(latestAssistantMsg.flowLog);

                                    if (latestAssistantMsg.flowLog.tokenUsage && latestAssistantMsg.flowLog.tokenUsage.total) {
                                        window.sessionTotalTokens.input += latestAssistantMsg.flowLog.tokenUsage.total.inputTokens || 0;
                                        window.sessionTotalTokens.output += latestAssistantMsg.flowLog.tokenUsage.total.outputTokens || 0;
                                        window.sessionTotalTokens.total += latestAssistantMsg.flowLog.tokenUsage.total.totalTokens || 0;
                                        window.sessionTotalTokens.cost += latestAssistantMsg.flowLog.tokenUsage.total.estimatedCost || 0;
                                    }
                                    window.lastFlowLog = latestAssistantMsg.flowLog;
                                    updateFlowLogModal(window.lastFlowLog);
                                }

                                // Display the assistant message
                                appendMessage({
                                    id: latestAssistantMsg.id,
                                    role: 'assistant',
                                    content: latestAssistantMsg.content,
                                    formattedTime: latestAssistantMsg.formattedTime,
                                    processingTime: latestAssistantMsg.processingTime,
                                    searchedImages: latestAssistantMsg.searchedImages || []
                                }, true); // true = show action buttons

                                scrollToBottom();

                                // Update session title from the response data
                                if (response.data.session) {
                                    const sessionInfo = response.data.session;
                                    const $session = $(`.chat-session-item[data-session-id="${currentSessionId}"]`);

                                    if (sessionInfo.displayName) {
                                        $session.find('.session-name').text(sessionInfo.displayName);
                                        $session.find('.session-time').text('Just now');
                                        $('#sessionsList').prepend($session);

                                        // If title was generated, mark it and update header
                                        if (sessionInfo.titleGenerated) {
                                            $session.data('title-generated', 'true');
                                            $session.attr('data-title-generated', 'true');
                                            $('#chatTitle').text(sessionInfo.displayName);
                                            console.log('Session title updated from fallback:', sessionInfo.displayName);
                                        }
                                    }
                                }

                                // Reset recovery flags
                                window.recoveryInProgress = false;
                                window.wasProcessingWhenHidden = false;

                                // Reset UI state
                                isSending = false;
                                releaseWakeLock(); // Allow screen to dim again
                                $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                                $('#attachBtn').prop('disabled', false);
                                // Keep input disabled - user must click Follow-up or New Question
                                // Note: appendMessage with showActions=true already calls disableChatInput()
                                // but we call it again to ensure state is correct
                                disableChatInput();

                                console.log('Fallback message displayed successfully');
                                toastr.success('Response received!', 'Success');
                                return; // Success, stop polling
                            }
                        } else {
                            console.log('Fallback: No assistant response for current question yet (backend still processing)');
                            // Fall through to retry logic below
                        }

                        // No new assistant message found yet, retry if not at max
                        if (retryCount < maxRetries) {
                            console.log('No new assistant message found, retrying...');
                            fetchLatestMessageFallback(sessionId, tempUserMsgId, originalMessage, retryCount + 1, maxRetries);
                        } else {
                            console.warn('Max retries reached, giving up');
                            handleFallbackFailure(tempUserMsgId, originalMessage);
                        }
                    } else {
                        // API call succeeded but no data
                        if (retryCount < maxRetries) {
                            fetchLatestMessageFallback(sessionId, tempUserMsgId, originalMessage, retryCount + 1, maxRetries);
                        } else {
                            handleFallbackFailure(tempUserMsgId, originalMessage);
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Fallback poll error:', xhr);
                    if (retryCount < maxRetries) {
                        fetchLatestMessageFallback(sessionId, tempUserMsgId, originalMessage, retryCount + 1, maxRetries);
                    } else {
                        handleFallbackFailure(tempUserMsgId, originalMessage);
                    }
                }
            });
        }, delay);
    }

    /**
     * Handle fallback failure after all retries exhausted
     */
    function handleFallbackFailure(tempUserMsgId, originalMessage) {
        console.error('=== FALLBACK POLLING FAILED ===');

        // Reset recovery flags
        window.recoveryInProgress = false;
        window.wasProcessingWhenHidden = false;

        // Hide all indicators
        $('#typingIndicator').removeClass('show');
        $('#processingProgress').removeClass('show');
        $('#progressBarFill').css('width', '0%');
        $('.chat-input-area').removeClass('thinking-in-progress');

        // Remove optimistic message
        $(`.message-wrapper[data-message-id="${tempUserMsgId}"]`).remove();

        // Show error and restore message for retry
        toastr.error('Failed to receive response. Please try again.', 'Error');

        // Reset UI
        isSending = false;
        releaseWakeLock(); // Allow screen to dim again
        $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
        $('#attachBtn').prop('disabled', false);
        enableChatInput('Type your message...', true); // preserveValue = true
        $('#messageInput').val(originalMessage); // Restore after enabling
    }

    /**
     * Mobile-specific: Check and display response after progress bar completes
     * This function is called when the progress bar reaches 100% on mobile
     * to ensure the response is displayed even if SSE streaming failed
     */
    function mobileResponseCheck(sessionId, tempUserMsgId, originalMessage) {
        console.log('=== MOBILE RESPONSE CHECK ===');

        // Wait a bit for the response to be processed
        setTimeout(function() {
            // Check if response was already displayed
            const $chatMessages = $('#chatMessages');
            const $messages = $chatMessages.find('.message-wrapper');
            const $lastMessage = $messages.last();

            // Check if the last message is an assistant message
            const isAssistantMessage = $lastMessage.find('.ai-message').length > 0 ||
                                       $lastMessage.data('role') === 'assistant';

            // Check if processing indicators are still showing
            const isStillProcessing = $('#processingProgress').hasClass('show') ||
                                      $('#typingIndicator').hasClass('show');

            console.log('Last message is assistant:', isAssistantMessage);
            console.log('Still processing:', isStillProcessing);

            // If no assistant response visible and not processing, trigger fallback
            if (!isAssistantMessage && !isStillProcessing) {
                console.log('No response visible, triggering fallback poll');
                fetchLatestMessageFallback(sessionId, tempUserMsgId, originalMessage, 0, 15); // More retries on mobile
            } else if (isStillProcessing) {
                // Still processing, check again in 2 seconds
                setTimeout(function() {
                    mobileResponseCheck(sessionId, tempUserMsgId, originalMessage);
                }, 2000);
            }
        }, 1500);
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

    // Convert markdown table to responsive HTML table (simple, elegant)
    function convertMarkdownTableToHtml(tableText) {
        const lines = tableText.trim().split('\n');
        if (lines.length < 2) return tableText; // Not a valid table

        let html = '<div class="table-responsive mb-3"><table class="table table-bordered table-sm mb-0">';

        let isHeader = true;
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();

            // Skip separator line (|---|---|---|)
            if (line.match(/^\|[\s\-:]+\|$/)) {
                isHeader = false;
                continue;
            }

            // Clean up - remove empty first/last if line starts/ends with |
            const cleanCells = [];
            const rawCells = line.split('|');
            for (let j = 0; j < rawCells.length; j++) {
                // Skip first empty element if line starts with |
                if (j === 0 && rawCells[j].trim() === '') continue;
                // Skip last empty element if line ends with |
                if (j === rawCells.length - 1 && rawCells[j].trim() === '') continue;
                cleanCells.push(rawCells[j].trim());
            }

            if (cleanCells.length === 0) continue;

            if (isHeader) {
                html += '<thead><tr>';
                cleanCells.forEach(cell => {
                    html += `<th>${escapeHtml(cell)}</th>`;
                });
                html += '</tr></thead><tbody>';
            } else {
                html += '<tr>';
                cleanCells.forEach((cell, idx) => {
                    const cellContent = escapeHtml(cell);
                    // First column slightly bolder
                    if (idx === 0) {
                        html += `<td style="font-weight:500;">${cellContent}</td>`;
                    } else {
                        html += `<td>${cellContent}</td>`;
                    }
                });
                html += '</tr>';
            }
        }

        html += '</tbody></table></div>';
        return html;
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

        // ===== MARKDOWN TABLE DETECTION AND CONVERSION =====
        // Detect markdown tables and convert them to HTML BEFORE escaping
        // Table pattern: consecutive lines starting with | (with or without trailing newline)
        const tableRegex = /(\|[^\n]+\|(?:\n|$))+/g;
        const tableMatches = text.match(tableRegex);
        const tablePlaceholders = {}; // Local storage for table HTML

        if (tableMatches) {
            console.log('formatAIContent: Found', tableMatches.length, 'potential tables');

            tableMatches.forEach((tableBlock, idx) => {
                // Validate it's actually a table (has header separator row |---|)
                if (tableBlock.match(/\|[\s\-:]+\|/)) {
                    console.log('formatAIContent: Converting table', idx + 1, '- rows:', tableBlock.split('\n').length);
                    const htmlTable = convertMarkdownTableToHtml(tableBlock);
                    // Use a placeholder to prevent HTML from being escaped
                    const placeholder = `___TABLE_PLACEHOLDER_${idx}_${Date.now()}___`;
                    text = text.replace(tableBlock, placeholder);
                    tablePlaceholders[placeholder] = htmlTable;
                }
            });
        }

        // First escape HTML for safety
        let content = escapeHtml(text);

        // Replace table placeholders with actual HTML tables (after escaping)
        Object.keys(tablePlaceholders).forEach(placeholder => {
            content = content.replace(placeholder, tablePlaceholders[placeholder]);
        });

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
async function copyToClipboard(text) {
    console.log('copyToClipboard called, text length:', text.length);

    // For large text (>10KB), directly show manual copy modal to avoid browser issues
    if (text.length > 10000) {
        console.log('Large text detected, showing manual copy modal');
        showManualCopyModal(text);
        return true; // Return success since modal is shown
    }

    // Method 1: Try Clipboard API
    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
            console.log('Clipboard API: success');
            return true;
        }
    } catch (err) {
        console.warn('Clipboard API failed:', err.message);
    }

    // Method 2: execCommand fallback
    try {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.cssText = 'position:fixed;left:0;top:0;width:2em;height:2em;padding:0;border:none;outline:none;box-shadow:none;background:transparent;';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        const success = document.execCommand('copy');
        document.body.removeChild(textarea);

        if (success) {
            console.log('execCommand: success');
            return true;
        }
    } catch (err) {
        console.warn('execCommand failed:', err.message);
    }

    // Method 3: Show manual copy modal as final fallback
    console.log('All copy methods failed, showing manual copy modal');
    showManualCopyModal(text);
    return true; // Return success since modal provides copy option
}

function showManualCopyModal(text) {
    // Remove existing modal if any
    $('#manualCopyModal').remove();

    // Store text globally for the copy button
    window._manualCopyText = text;

    const modalHtml = `
        <div class="modal fade" id="manualCopyModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bx bx-copy me-2"></i>Flow Log Ready to Copy (${text.length.toLocaleString()} chars)</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="alert alert-info mb-3">
                            <i class="bx bx-info-circle me-2"></i>
                            <strong>Click the button below to copy</strong>, or manually select all (Ctrl+A) and copy (Ctrl+C).
                        </div>
                        <textarea id="manualCopyText" class="form-control text-dark" rows="18" readonly
                            style="font-family: 'Consolas', 'Monaco', monospace; font-size: 11px; line-height: 1.4; background: #f8f9fa;"></textarea>
                    </div>
                    <div class="modal-footer">
                        <span class="text-secondary me-auto" id="copyStatus"></span>
                        <button type="button" class="btn btn-success btn-lg" onclick="selectAndCopyManual()">
                            <i class="bx bx-copy me-1"></i>Copy to Clipboard
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHtml);

    // Set textarea value directly (safer than HTML encoding in template)
    document.getElementById('manualCopyText').value = text;

    const modal = new bootstrap.Modal(document.getElementById('manualCopyModal'));
    modal.show();

    // Auto-select text when modal opens
    $('#manualCopyModal').on('shown.bs.modal', function() {
        const textarea = document.getElementById('manualCopyText');
        textarea.focus();
        textarea.select();
        $('#copyStatus').text('Text selected. Click Copy or press Ctrl+C');
    });
}

async function selectAndCopyManual() {
    const textarea = document.getElementById('manualCopyText');
    const text = textarea.value;

    textarea.focus();
    textarea.select();
    textarea.setSelectionRange(0, text.length);

    // Try Clipboard API first (works better when user clicks button directly)
    try {
        await navigator.clipboard.writeText(text);
        toastr.success('Flow log copied to clipboard!');
        $('#copyStatus').html('<span class="text-success"><i class="bx bx-check"></i> Copied successfully!</span>');
        setTimeout(() => {
            bootstrap.Modal.getInstance(document.getElementById('manualCopyModal')).hide();
        }, 500);
        return;
    } catch (e) {
        console.log('Clipboard API in modal failed:', e);
    }

    // Fallback to execCommand
    try {
        const success = document.execCommand('copy');
        if (success) {
            toastr.success('Flow log copied to clipboard!');
            $('#copyStatus').html('<span class="text-success"><i class="bx bx-check"></i> Copied successfully!</span>');
            setTimeout(() => {
                bootstrap.Modal.getInstance(document.getElementById('manualCopyModal')).hide();
            }, 500);
            return;
        }
    } catch (e) {
        console.log('execCommand in modal failed:', e);
    }

    // Last resort - tell user to manually copy
    toastr.info('Text is selected. Press Ctrl+C to copy.');
    $('#copyStatus').html('<span class="text-warning"><i class="bx bx-info-circle"></i> Press Ctrl+C to copy the selected text</span>');
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

// Navigate between flow logs in the session
function navigateFlowLog(direction) {
    const totalLogs = window.sessionFlowLogs.length;
    if (totalLogs === 0) return;

    // Find current position
    let currentIndex = totalLogs - 1; // Default to last
    if (window.lastFlowLog && window.lastFlowLog.messageNumber) {
        currentIndex = window.lastFlowLog.messageNumber - 1;
    }

    // Calculate new index
    let newIndex = currentIndex + direction;
    if (newIndex < 0) newIndex = 0;
    if (newIndex >= totalLogs) newIndex = totalLogs - 1;

    // Load the flow log at new index
    if (window.sessionFlowLogs[newIndex]) {
        window.lastFlowLog = window.sessionFlowLogs[newIndex];
        updateFlowLogModal(window.lastFlowLog);
    }
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

    // Update message indicator and navigation
    const totalMessages = window.sessionFlowLogs.length || 1;
    const currentMessage = flowLog.messageNumber || totalMessages;
    $('#flowMsgNumber').text(currentMessage);
    $('#flowMsgTotal').text(totalMessages);
    $('#flowMsgTime').text(flowLog.timestamp || '-');

    // Enable/disable navigation buttons
    $('#flowPrevMsg').prop('disabled', currentMessage <= 1);
    $('#flowNextMsg').prop('disabled', currentMessage >= totalMessages);

    // Update session totals
    const sessionTokens = window.sessionTotalTokens || { input: 0, output: 0, total: 0, cost: 0 };
    $('#flowSessionInput').text(formatNumber(sessionTokens.input));
    $('#flowSessionOutput').text(formatNumber(sessionTokens.output));
    $('#flowSessionTotal').text(formatNumber(sessionTokens.total));
    $('#flowSessionCost').text('₱' + formatCost(sessionTokens.cost * usdToPhpRate));

    // Update summary section
    $('#flowQuestionType').text(flowLog.questionType || 'General');

    // Format AI Provider with special styling for Dual AI
    const aiProvider = flowLog.aiProvider || 'Not specified';
    if (aiProvider.toLowerCase().includes('dual ai') || aiProvider.toLowerCase().includes('dual-ai')) {
        $('#flowAiProvider').html(`
            <span class="badge bg-info text-white me-1"><i class="bx bx-git-compare"></i></span>
            <span class="text-info fw-bold">${escapeHtml(aiProvider)}</span>
            <small class="d-block text-secondary mt-1">OpenAI + Gemini → Combined</small>
        `);
    } else {
        $('#flowAiProvider').text(aiProvider);
    }

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
            // Check if this step has an AI response
            const hasAiResponse = step.aiResponse && step.aiResponse.trim().length > 0;
            const collapseId = `stepCollapse${index}`;

            // Get step styling based on step name
            const stepStyle = getStepStyle(step.step);

            let stepHtml = `
                <li class="list-group-item p-0 ${stepStyle.listClass}">
                    <div class="d-flex justify-content-between align-items-start p-3 ${hasAiResponse ? 'cursor-pointer' : ''}"
                         ${hasAiResponse ? `data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false"` : ''}>
                        <div class="ms-2 me-auto">
                            <div class="fw-bold ${stepStyle.titleClass}">
                                ${hasAiResponse ? '<i class="bx bx-chevron-right me-1 collapse-icon"></i>' : stepStyle.icon}
                                ${stepStyle.badge}${escapeHtml(step.step)}
                            </div>
                            <small class="text-secondary">${escapeHtml(step.details || '')}</small>
                        </div>
                        <span class="badge ${stepStyle.badgeClass} rounded-pill">${step.time || ''}</span>
                    </div>
            `;

            // Add collapsible AI response section if available
            if (hasAiResponse) {
                stepHtml += `
                    <div class="collapse" id="${collapseId}">
                        <div class="border-top ${stepStyle.collapseClass} p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong class="${stepStyle.responseLabel}"><i class="bx bx-bot me-1"></i>${stepStyle.responseLabelText}</strong>
                                <button class="btn btn-sm btn-outline-secondary" onclick="copyStepResponse(${index})" title="Copy Response">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                            <pre class="mb-0 p-2 bg-white border rounded text-dark" style="white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto; font-size: 0.85rem;" id="stepResponse${index}">${escapeHtml(step.aiResponse)}</pre>
                        </div>
                    </div>
                `;
            }

            stepHtml += `</li>`;
            $stepsList.append(stepHtml);
        });

        // Add click handler for collapse icon rotation
        $stepsList.find('[data-bs-toggle="collapse"]').on('click', function() {
            const $icon = $(this).find('.collapse-icon');
            const isExpanded = $(this).attr('aria-expanded') === 'true';
            $icon.toggleClass('bx-chevron-right bx-chevron-down');
        });

        // Handle collapse events for icon rotation
        $stepsList.find('.collapse').on('show.bs.collapse', function() {
            $(this).prev().find('.collapse-icon').removeClass('bx-chevron-right').addClass('bx-chevron-down');
        }).on('hide.bs.collapse', function() {
            $(this).prev().find('.collapse-icon').removeClass('bx-chevron-down').addClass('bx-chevron-right');
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
        $('#flowQuickCost').text('-');
        $('#flowQuickInput').text('0');
        $('#flowQuickOutput').text('0');
        $('#flowQuickTotal').text('0');
        $('#flowTokensByProvider').html('<tr><td colspan="6" class="text-center text-secondary">No token usage data</td></tr>');
        $('#flowTokensByNode').html('<tr><td colspan="6" class="text-center text-secondary">No token usage data</td></tr>');
        return;
    }

    // Update totals
    $('#flowTotalInputTokens').text(formatNumber(tokenUsage.total.inputTokens || 0));
    $('#flowTotalOutputTokens').text(formatNumber(tokenUsage.total.outputTokens || 0));
    $('#flowTotalTokens').text(formatNumber(tokenUsage.total.totalTokens || 0));
    // Convert USD to PHP using dynamic rate from settings
    const totalCostPhp = (tokenUsage.total.estimatedCost || 0) * usdToPhpRate;
    $('#flowTotalCost').text('₱' + formatCost(totalCostPhp));

    // Update quick summary at top of modal (always visible in stats grid)
    $('#flowQuickCost').text('₱' + formatCost(totalCostPhp));
    $('#flowQuickInput').text(formatNumber(tokenUsage.total.inputTokens || 0));
    $('#flowQuickOutput').text(formatNumber(tokenUsage.total.outputTokens || 0));
    $('#flowQuickTotal').text(formatNumber(tokenUsage.total.totalTokens || 0));

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
            const nodeName = formatNodeName(nodeId);
            const nodeStyle = getNodeStyle(nodeId);
            const rowHtml = `
                <tr class="${nodeStyle.rowClass}">
                    <td class="text-dark">
                        ${nodeStyle.icon}
                        <span class="${nodeStyle.textClass}">${escapeHtml(nodeName)}</span>
                    </td>
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

// Format node ID into readable name
function formatNodeName(nodeId) {
    const nodeNameMap = {
        // Dual-AI nodes (new)
        'dual_ai_detailed': 'Dual AI - Detailed Answer',
        'dual_ai_combine': 'Dual AI - Compare & Combine',
        // Existing nodes
        'ai_knowledge': 'AI Knowledge',
        'rag_query': 'RAG Query',
        'web_search': 'Web Search',
        'image_analysis': 'Image Analysis',
        'ai_image_generation': 'AI Image Generation',
        'combine_sources': 'Combine Sources',
        'refine_answer': 'Refine Answer',
        'gpt_alternatives': 'GPT Alternatives',
        'product_recommendation': 'Product Recommendation',
        'schedule_analysis': 'Schedule Analysis',
        'format_response': 'Format Response',
        'blocker_check': 'Blocker Check',
        'greeting': 'Greeting',
        'clarification': 'Clarification',
    };

    // Check if we have a mapped name
    if (nodeNameMap[nodeId]) {
        return nodeNameMap[nodeId];
    }

    // Convert snake_case to Title Case
    return nodeId.split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
}

// Get styling for processing steps in flow log
function getStepStyle(stepName) {
    const stepLower = (stepName || '').toLowerCase();

    // Dual-AI steps - highlight with special styling
    if (stepLower.includes('dual ai') || stepLower.includes('step 3c-')) {
        // OpenAI answer
        if (stepLower.includes('openai') || stepLower.includes('3c-1')) {
            return {
                listClass: 'border-start border-4 border-warning',
                titleClass: 'text-dark',
                icon: '<i class="bx bx-brain text-warning me-1"></i>',
                badge: '<span class="badge bg-warning text-dark me-2">OpenAI</span>',
                badgeClass: 'bg-warning text-dark',
                collapseClass: 'bg-warning bg-opacity-10',
                responseLabel: 'text-warning',
                responseLabelText: 'OpenAI GPT-4o Response:'
            };
        }
        // Gemini answer
        if (stepLower.includes('gemini') || stepLower.includes('3c-2')) {
            return {
                listClass: 'border-start border-4 border-primary',
                titleClass: 'text-dark',
                icon: '<i class="bx bx-atom text-primary me-1"></i>',
                badge: '<span class="badge bg-primary me-2">Gemini</span>',
                badgeClass: 'bg-primary',
                collapseClass: 'bg-primary bg-opacity-10',
                responseLabel: 'text-primary',
                responseLabelText: 'Google Gemini Response:'
            };
        }
        // Combined/Compare step
        if (stepLower.includes('combine') || stepLower.includes('3c-3') || stepLower.includes('final combined')) {
            return {
                listClass: 'border-start border-4 border-success',
                titleClass: 'text-success',
                icon: '<i class="bx bx-git-compare text-success me-1"></i>',
                badge: '<span class="badge bg-success me-2">Combined</span>',
                badgeClass: 'bg-success',
                collapseClass: 'bg-success bg-opacity-10',
                responseLabel: 'text-success',
                responseLabelText: 'Combined & Verified Response:'
            };
        }
        // General dual AI step
        return {
            listClass: 'border-start border-4 border-info',
            titleClass: 'text-info',
            icon: '<i class="bx bx-git-compare text-info me-1"></i>',
            badge: '<span class="badge bg-info text-white me-2">Dual AI</span>',
            badgeClass: 'bg-info',
            collapseClass: 'bg-info bg-opacity-10',
            responseLabel: 'text-info',
            responseLabelText: 'AI Response:'
        };
    }

    // RAG steps
    if (stepLower.includes('rag')) {
        return {
            listClass: '',
            titleClass: 'text-dark',
            icon: '<i class="bx bx-data text-purple me-1"></i>',
            badge: '',
            badgeClass: 'bg-secondary',
            collapseClass: 'bg-light',
            responseLabel: 'text-purple',
            responseLabelText: 'RAG Result:'
        };
    }

    // Web search steps
    if (stepLower.includes('web search')) {
        return {
            listClass: '',
            titleClass: 'text-dark',
            icon: '<i class="bx bx-globe text-primary me-1"></i>',
            badge: '',
            badgeClass: 'bg-secondary',
            collapseClass: 'bg-light',
            responseLabel: 'text-primary',
            responseLabelText: 'Web Search Result:'
        };
    }

    // Image analysis
    if (stepLower.includes('image')) {
        return {
            listClass: '',
            titleClass: 'text-dark',
            icon: '<i class="bx bx-image text-success me-1"></i>',
            badge: '',
            badgeClass: 'bg-secondary',
            collapseClass: 'bg-light',
            responseLabel: 'text-success',
            responseLabelText: 'Image Analysis:'
        };
    }

    // Final/Combine steps
    if (stepLower.includes('step 4') || stepLower.includes('combine sources')) {
        return {
            listClass: '',
            titleClass: 'text-dark',
            icon: '<i class="bx bx-merge text-info me-1"></i>',
            badge: '',
            badgeClass: 'bg-info',
            collapseClass: 'bg-light',
            responseLabel: 'text-info',
            responseLabelText: 'Combined Response:'
        };
    }

    // Final thinking/refine
    if (stepLower.includes('step 5') || stepLower.includes('final thinking')) {
        return {
            listClass: '',
            titleClass: 'text-dark',
            icon: '<i class="bx bx-bulb text-warning me-1"></i>',
            badge: '',
            badgeClass: 'bg-warning text-dark',
            collapseClass: 'bg-light',
            responseLabel: 'text-warning',
            responseLabelText: 'Refined Response:'
        };
    }

    // Product alternatives
    if (stepLower.includes('step 6') || stepLower.includes('product') || stepLower.includes('alternative')) {
        return {
            listClass: '',
            titleClass: 'text-dark',
            icon: '<i class="bx bx-package text-success me-1"></i>',
            badge: '',
            badgeClass: 'bg-success',
            collapseClass: 'bg-light',
            responseLabel: 'text-success',
            responseLabelText: 'Product Enhancement:'
        };
    }

    // Default styling
    return {
        listClass: '',
        titleClass: 'text-dark',
        icon: '<i class="bx bx-check-circle text-success me-1"></i>',
        badge: '',
        badgeClass: 'bg-secondary',
        collapseClass: 'bg-light',
        responseLabel: 'text-primary',
        responseLabelText: 'AI Response:'
    };
}

// Get styling for node based on its type
function getNodeStyle(nodeId) {
    // Dual-AI related nodes get special styling
    if (nodeId.startsWith('dual_ai')) {
        return {
            rowClass: 'table-info',
            textClass: 'fw-bold text-info',
            icon: '<i class="bx bx-git-compare me-1 text-info"></i>'
        };
    }

    // RAG nodes
    if (nodeId.includes('rag')) {
        return {
            rowClass: '',
            textClass: 'fw-medium',
            icon: '<i class="bx bx-data me-1 text-purple"></i>'
        };
    }

    // Web search nodes
    if (nodeId.includes('web') || nodeId.includes('search')) {
        return {
            rowClass: '',
            textClass: 'fw-medium',
            icon: '<i class="bx bx-globe me-1 text-primary"></i>'
        };
    }

    // Image analysis
    if (nodeId.includes('image')) {
        return {
            rowClass: '',
            textClass: 'fw-medium',
            icon: '<i class="bx bx-image me-1 text-success"></i>'
        };
    }

    // AI Knowledge / GPT
    if (nodeId.includes('ai_knowledge') || nodeId.includes('gpt')) {
        return {
            rowClass: '',
            textClass: 'fw-medium',
            icon: '<i class="bx bx-brain me-1 text-warning"></i>'
        };
    }

    // Combine/Refine steps
    if (nodeId.includes('combine') || nodeId.includes('refine')) {
        return {
            rowClass: '',
            textClass: 'fw-medium',
            icon: '<i class="bx bx-merge me-1 text-secondary"></i>'
        };
    }

    // Default styling
    return {
        rowClass: '',
        textClass: '',
        icon: '<i class="bx bx-cog me-1 text-secondary"></i>'
    };
}

// Copy AI Flow log to clipboard
function copyAiFlowLog() {
    try {
        const flowLog = window.lastFlowLog;
        console.log('copyAiFlowLog called, flowLog:', flowLog ? 'exists' : 'null');

        if (!flowLog) {
            toastr.warning('No flow log available to copy');
            return;
        }

        let logText = '============================================================\n';
        logText += '                    AI PROCESSING FLOW LOG                    \n';
        logText += '============================================================\n\n';

        // Add message number if available
        if (flowLog.messageNumber && window.sessionFlowLogs && window.sessionFlowLogs.length > 0) {
            logText += `[MESSAGE ${flowLog.messageNumber} OF ${window.sessionFlowLogs.length}]\n`;
            if (flowLog.timestamp) {
                logText += `Timestamp: ${flowLog.timestamp}\n`;
            }
            logText += '------------------------------------------------------------\n\n';
        }

        logText += '[SUMMARY]\n';
        logText += '------------------------------------------------------------\n';
        logText += 'Question Type: ' + (flowLog.questionType || 'General') + '\n';
        logText += 'AI Provider: ' + (flowLog.aiProvider || 'Not specified') + '\n';
    logText += 'Processing Time: ' + (flowLog.processingTime ? flowLog.processingTime + 's' : '-') + '\n\n';

    logText += '[USER MESSAGE]\n';
    logText += '------------------------------------------------------------\n';
    logText += (flowLog.userMessage || '-') + '\n\n';

    logText += '[FINAL AI RESPONSE]\n';
    logText += '------------------------------------------------------------\n';
    logText += (flowLog.aiResponse || 'No response recorded') + '\n\n';

    logText += '[PROCESSING STEPS WITH AI RESPONSES]\n';
    logText += '============================================================\n';
    if (flowLog.steps && flowLog.steps.length > 0) {
        flowLog.steps.forEach((step, index) => {
            logText += `\n--- STEP ${index + 1}: ${step.step} ---\n`;
            logText += `    Time: ${step.time || '-'}\n`;
            if (step.details) {
                logText += `    Details: ${step.details}\n`;
            }
            // Include AI response if available
            if (step.aiResponse && step.aiResponse.trim()) {
                logText += `\n    >> AI RESPONSE:\n`;
                logText += `    ------------------------------------------------\n`;
                // Indent each line of AI response
                const indentedResponse = step.aiResponse.split('\n').map(line => '    ' + line).join('\n');
                logText += indentedResponse + '\n';
            }
            logText += `------------------------------------------------------------\n`;
        });
    } else {
        logText += 'No processing steps recorded\n';
    }

    // Add token usage section
    logText += '\n[TOKEN USAGE & COST]\n';
    logText += '------------------------------------------------------------\n';
    if (flowLog.tokenUsage && flowLog.tokenUsage.total) {
        const total = flowLog.tokenUsage.total;
        logText += `Total Input Tokens: ${formatNumber(total.inputTokens || 0)}\n`;
        logText += `Total Output Tokens: ${formatNumber(total.outputTokens || 0)}\n`;
        logText += `Total Tokens: ${formatNumber(total.totalTokens || 0)}\n`;
        logText += `Estimated Cost: PHP ${formatCost((total.estimatedCost || 0) * usdToPhpRate)}\n`;

        // By provider
        if (flowLog.tokenUsage.byProvider) {
            logText += '\nBy Provider:\n';
            for (const [key, data] of Object.entries(flowLog.tokenUsage.byProvider)) {
                logText += `  - ${data.name || key}: ${formatNumber(data.totalTokens || 0)} tokens (${data.calls || 0} calls) - PHP ${formatCost((data.estimatedCost || 0) * usdToPhpRate)}\n`;
            }
        }

        // By node
        if (flowLog.tokenUsage.byNode) {
            logText += '\nBy Node:\n';
            for (const [nodeId, data] of Object.entries(flowLog.tokenUsage.byNode)) {
                logText += `  - ${nodeId}: ${formatNumber(data.totalTokens || 0)} tokens - PHP ${formatCost((data.estimatedCost || 0) * usdToPhpRate)}\n`;
            }
        }

        // Serper web search usage
        if (flowLog.tokenUsage.serper && flowLog.tokenUsage.serper.searches > 0) {
            const serper = flowLog.tokenUsage.serper;
            const serperCostPhp = (serper.credits || serper.searches || 0) * 0.001 * usdToPhpRate;
            logText += '\nSerper Web Search:\n';
            logText += `  Searches: ${serper.searches}\n`;
            logText += `  Credits Used: ${serper.credits || serper.searches}\n`;
            logText += `  Est. Cost: PHP ${formatCost(serperCostPhp)}\n`;
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

    // Add session totals if available
    if (window.sessionTotalTokens && window.sessionFlowLogs && window.sessionFlowLogs.length > 1) {
        logText += '\n[SESSION TOTALS - ALL MESSAGES]\n';
        logText += '------------------------------------------------------------\n';
        logText += `Total Messages: ${window.sessionFlowLogs.length}\n`;
        logText += `Cumulative Input Tokens: ${formatNumber(window.sessionTotalTokens.input || 0)}\n`;
        logText += `Cumulative Output Tokens: ${formatNumber(window.sessionTotalTokens.output || 0)}\n`;
        logText += `Cumulative Total Tokens: ${formatNumber(window.sessionTotalTokens.total || 0)}\n`;
        logText += `Cumulative Cost: PHP ${formatCost((window.sessionTotalTokens.cost || 0) * usdToPhpRate)}\n`;
    }

    // Add footer with timestamp
    logText += '\n============================================================\n';
    logText += `Log copied on: ${new Date().toLocaleString('en-PH', { timeZone: 'Asia/Manila' })}\n`;
    logText += '============================================================\n';

    // Debug: log the text length
    console.log('Copying flow log, length:', logText.length);

    // Always show the manual copy modal for reliability
    // This ensures the user can always copy the log even if clipboard API fails
    showManualCopyModal(logText);

    } catch (err) {
        console.error('Error in copyAiFlowLog:', err);
        toastr.error('Error preparing flow log: ' + err.message);
    }
}

// Copy individual step response to clipboard
function copyStepResponse(stepIndex) {
    const flowLog = window.lastFlowLog;
    if (!flowLog || !flowLog.steps || !flowLog.steps[stepIndex]) {
        toastr.warning('No response available to copy');
        return;
    }

    const step = flowLog.steps[stepIndex];
    const response = step.aiResponse || '';

    if (!response.trim()) {
        toastr.warning('No AI response for this step');
        return;
    }

    let copyText = `=== ${step.step} ===\n`;
    copyText += `Time: ${step.time || '-'}\n`;
    copyText += `Details: ${step.details || '-'}\n\n`;
    copyText += `--- AI RESPONSE ---\n${response}`;

    copyToClipboard(copyText).then(() => {
        toastr.success('Step response copied to clipboard!');
    }).catch(err => {
        console.error('Copy failed:', err);
        toastr.error('Failed to copy response');
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

// ==================== SAVE TO ERRORS FUNCTIONALITY ====================

// Save to Errors button click
$('#saveToErrorsBtn').on('click', function() {
    if (!currentSessionId) {
        toastr.warning('Please start a chat first');
        return;
    }

    const $chatMessages = $('#chatMessages .message-wrapper');
    if ($chatMessages.length === 0) {
        toastr.warning('No messages to save');
        return;
    }

    // Clear previous input
    $('#errorDescriptionInput').val('');
    $('#saveToErrorsModal').modal('show');
});

// Confirm save to errors
$('#confirmSaveToErrorsBtn').on('click', function() {
    const errorDescription = $('#errorDescriptionInput').val().trim();

    if (!errorDescription) {
        toastr.warning('Please enter an error description');
        $('#errorDescriptionInput').focus();
        return;
    }

    const $btn = $(this);
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

    // Collect chat thread data
    const chatThread = [];
    $('#chatMessages .message-wrapper').each(function() {
        const $wrapper = $(this);
        const role = $wrapper.hasClass('user') ? 'user' : 'assistant';
        const content = $wrapper.find('.message-content').text().trim();
        const time = $wrapper.find('.message-meta span').first().text().trim();
        const messageId = $wrapper.data('message-id');

        if (content) {
            chatThread.push({
                id: messageId,
                role: role,
                content: content,
                time: time
            });
        }
    });

    // Get flow logs if available
    const flowLogs = window.sessionFlowLogs || [];

    // Send to server
    $.ajax({
        url: '/ai-technician-chat-errors',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            sessionId: currentSessionId,
            chatThread: JSON.stringify(chatThread),
            flowLogs: JSON.stringify(flowLogs),
            errorDescription: errorDescription
        },
        success: function(response) {
            if (response.success) {
                $('#saveToErrorsModal').modal('hide');
                const message = response.data.isUpdate
                    ? 'Error log updated successfully!'
                    : 'Chat saved to error log!';
                toastr.success(message);
            } else {
                toastr.error(response.message || 'Failed to save error');
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to save error');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save to Errors');
        }
    });
});

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

                // NOTE: Title generation now happens during first message processing
                // No need to generate titles for loaded sessions - they either have titles
                // or will get them when the user sends a message in that session
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

// ==========================================
// PAGE VISIBILITY CHANGE HANDLER
// Handles mobile screen off / app switching
// ==========================================
let pageWasHidden = false;
let hiddenTimestamp = null;
window.isPageHidden = false; // Global flag for error handlers
window.lastHiddenTimestamp = null; // Persists even after page becomes visible (for race condition handling)
window.wasProcessingWhenHidden = false; // Track if we were processing when page went hidden
window.recoveryInProgress = false; // Prevent duplicate recovery attempts

document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is being hidden (screen off, app switch, tab switch)
        pageWasHidden = true;
        window.isPageHidden = true;
        hiddenTimestamp = Date.now();
        window.lastHiddenTimestamp = Date.now(); // Persists for race condition handling

        // Track if we were processing when hidden (for recovery)
        window.wasProcessingWhenHidden = isSending ||
            $('#processingProgress').hasClass('show') ||
            $('#typingIndicator').hasClass('show') ||
            $('#messageInput').prop('disabled');

        console.log('=== PAGE HIDDEN ===', 'wasProcessing:', window.wasProcessingWhenHidden);
    } else {
        // Page is visible again
        console.log('=== PAGE VISIBLE AGAIN ===');
        window.isPageHidden = false;

        if (pageWasHidden && hiddenTimestamp) {
            const hiddenDuration = Date.now() - hiddenTimestamp;
            console.log(`Page was hidden for ${hiddenDuration}ms`);

            // Check if we were in the middle of loading a response
            const isStillProcessing = $('#processingProgress').hasClass('show') ||
                                      $('#typingIndicator').hasClass('show');
            const isChatDisabled = $('#messageInput').prop('disabled');

            console.log('Still processing:', isStillProcessing, 'Chat disabled:', isChatDisabled);

            // If hidden for more than 2 seconds and still showing loading (or was processing), trigger recovery
            // Reduced from 3s to 2s for faster mobile recovery
            const shouldTriggerRecovery = (isStillProcessing || isChatDisabled || window.wasProcessingWhenHidden) &&
                                          !window.recoveryInProgress;

            if (hiddenDuration > 2000 && shouldTriggerRecovery) {
                console.log('=== VISIBILITY RECOVERY: Triggering fallback fetch ===');
                window.recoveryInProgress = true;

                // CRITICAL: Abort any existing fetch stream to prevent zombie connections
                if (currentAbortController) {
                    console.log('Aborting zombie fetch stream...');
                    currentAbortController.abort();
                    currentAbortController = null;
                }

                // Clear all related timers to prevent conflicts
                if (globalProgressTimer) {
                    clearTimeout(globalProgressTimer);
                    globalProgressTimer = null;
                }
                if (responseWatchdog) {
                    clearInterval(responseWatchdog);
                    responseWatchdog = null;
                }
                if (mobileSafetyTimer) {
                    clearTimeout(mobileSafetyTimer);
                    mobileSafetyTimer = null;
                }

                // Check if we have watchdog data to use for recovery
                if (watchdogSessionId && watchdogTempMsgId) {
                    // Force fetch the latest response
                    forceLoadLatestResponse(watchdogSessionId, watchdogTempMsgId, watchdogOriginalMsg);
                } else if (currentSessionId) {
                    // No watchdog data, just reload the session messages
                    console.log('No watchdog data, reloading session messages...');

                    // Hide loading indicators
                    $('#processingProgress').removeClass('show');
                    $('#progressBarFill').css('width', '0%');
                    $('#typingIndicator').removeClass('show');
                    $('.chat-input-area').removeClass('thinking-in-progress');

                    // Reload the session to get latest messages
                    $.ajax({
                        url: '/ai-technician-chat/session/' + currentSessionId + '/messages',
                        type: 'GET',
                        timeout: 15000, // 15 second timeout
                        success: function(response) {
                            if (response.success && response.data && response.data.messages) {
                                // FIX: Pass response.data.messages, not response.data
                                renderMessages(response.data.messages);
                                scrollToBottom();
                                console.log('VISIBILITY RECOVERY: Rendered', response.data.messages.length, 'messages');
                            }
                            window.recoveryInProgress = false;
                            window.wasProcessingWhenHidden = false;
                            isSending = false;
                            releaseWakeLock();
                            $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                            $('#attachBtn').prop('disabled', false);
                            // If action buttons are present (from renderMessages), keep input disabled
                            // Otherwise enable input for new messages
                            if ($('.response-actions:visible').length > 0) {
                                disableChatInput();
                            } else {
                                enableChatInput('Type your message...');
                            }
                        },
                        error: function() {
                            window.recoveryInProgress = false;
                            window.wasProcessingWhenHidden = false;
                            isSending = false;
                            releaseWakeLock();
                            $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                            $('#attachBtn').prop('disabled', false);
                            enableChatInput('Type your message...');
                            toastr.info('Please check if your message was sent');
                        }
                    });
                } else {
                    // No session, just reset the UI
                    window.recoveryInProgress = false;
                    window.wasProcessingWhenHidden = false;
                    isSending = false;
                    releaseWakeLock();
                    $('#processingProgress').removeClass('show');
                    $('#progressBarFill').css('width', '0%');
                    $('#typingIndicator').removeClass('show');
                    $('.chat-input-area').removeClass('thinking-in-progress');
                    $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                    $('#attachBtn').prop('disabled', false);
                    enableChatInput('Type your message...');
                }
            }
        }

        pageWasHidden = false;
        hiddenTimestamp = null;
        // Reset processing flag after visibility handling is complete
        // (Give time for recovery to be initiated)
        setTimeout(function() {
            if (!window.recoveryInProgress) {
                window.wasProcessingWhenHidden = false;
            }
        }, 1000);
    }
});

// Also handle page focus/blur for additional reliability
window.addEventListener('focus', function() {
    console.log('=== WINDOW FOCUSED ===');

    // Quick check - if loading for too long, recover
    const isStillProcessing = $('#processingProgress').hasClass('show') ||
                              $('#typingIndicator').hasClass('show');
    const isChatDisabled = $('#messageInput').prop('disabled');

    // If stuck in processing state, trigger recovery after short delay
    if ((isStillProcessing || isChatDisabled) && !window.recoveryInProgress) {
        // Give it a brief moment to let visibilitychange handler fire first
        setTimeout(function() {
            const stillProcessing = $('#processingProgress').hasClass('show') ||
                                   $('#typingIndicator').hasClass('show');
            const stillDisabled = $('#messageInput').prop('disabled');

            // Double-check we're still stuck and recovery hasn't started
            if ((stillProcessing || stillDisabled) && !responseReceived && !window.recoveryInProgress) {
                console.log('=== FOCUS RECOVERY: Detected stuck state ===');

                if (watchdogSessionId && watchdogTempMsgId) {
                    window.recoveryInProgress = true;
                    forceLoadLatestResponse(watchdogSessionId, watchdogTempMsgId, watchdogOriginalMsg);
                } else if (currentSessionId) {
                    // Fallback: reload session messages
                    console.log('Focus recovery: No watchdog data, reloading session...');
                    window.recoveryInProgress = true;

                    // Abort zombie stream
                    if (currentAbortController) {
                        currentAbortController.abort();
                        currentAbortController = null;
                    }

                    $.ajax({
                        url: '/ai-technician-chat/session/' + currentSessionId + '/messages',
                        type: 'GET',
                        timeout: 15000,
                        success: function(response) {
                            if (response.success && response.data && response.data.messages) {
                                // FIX: Pass response.data.messages, not response.data
                                renderMessages(response.data.messages);
                                scrollToBottom();
                                console.log('FOCUS RECOVERY: Rendered', response.data.messages.length, 'messages');
                            }
                            window.recoveryInProgress = false;
                            window.wasProcessingWhenHidden = false;
                            isSending = false;
                            releaseWakeLock();
                            $('#processingProgress').removeClass('show');
                            $('#progressBarFill').css('width', '0%');
                            $('#typingIndicator').removeClass('show');
                            $('.chat-input-area').removeClass('thinking-in-progress');
                            $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                            $('#attachBtn').prop('disabled', false);
                            // If action buttons are present (from renderMessages), keep input disabled
                            // Otherwise enable input for new messages
                            if ($('.response-actions:visible').length > 0) {
                                disableChatInput();
                            } else {
                                enableChatInput('Type your message...');
                            }
                        },
                        error: function() {
                            window.recoveryInProgress = false;
                            window.wasProcessingWhenHidden = false;
                            isSending = false;
                            releaseWakeLock();
                            $('#processingProgress').removeClass('show');
                            $('#progressBarFill').css('width', '0%');
                            $('#typingIndicator').removeClass('show');
                            $('.chat-input-area').removeClass('thinking-in-progress');
                            $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                            $('#attachBtn').prop('disabled', false);
                            enableChatInput('Type your message...');
                            toastr.info('Please check if your message was sent');
                        }
                    });
                } else {
                    // No session data, just reset UI
                    isSending = false;
                    releaseWakeLock();
                    $('#processingProgress').removeClass('show');
                    $('#progressBarFill').css('width', '0%');
                    $('#typingIndicator').removeClass('show');
                    $('.chat-input-area').removeClass('thinking-in-progress');
                    $('#sendBtn').prop('disabled', false).html('<i class="bx bx-send"></i>');
                    $('#attachBtn').prop('disabled', false);
                    enableChatInput('Type your message...');
                }
            }
        }, 1000); // Reduced delay for faster recovery
    }
});

// MOBILE SAFETY: Touch event handler as last resort recovery
// Some mobile browsers don't properly fire focus/visibility events
let lastTouchRecoveryCheck = 0;
document.addEventListener('touchstart', function() {
    const now = Date.now();

    // Only check once every 3 seconds to avoid spam
    if (now - lastTouchRecoveryCheck < 3000) return;
    lastTouchRecoveryCheck = now;

    const isStillProcessing = $('#processingProgress').hasClass('show') ||
                              $('#typingIndicator').hasClass('show');
    const isChatDisabled = $('#messageInput').prop('disabled');

    // If stuck and not already recovering
    if ((isStillProcessing || isChatDisabled) && !window.recoveryInProgress && !responseReceived) {
        console.log('=== TOUCH RECOVERY: Detected stuck state on touch ===');

        if (watchdogSessionId && watchdogTempMsgId) {
            window.recoveryInProgress = true;

            // Abort zombie stream
            if (currentAbortController) {
                currentAbortController.abort();
                currentAbortController = null;
            }

            forceLoadLatestResponse(watchdogSessionId, watchdogTempMsgId, watchdogOriginalMsg);
        }
    }
}, { passive: true });

</script>
@endsection
