@extends('layouts.master')

@section('title') Course Contents - {{ $course->courseName }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
/* ============================================
   COLOR PALETTES FOR CHAPTERS (Hue-based)
   ============================================ */
:root {
    --ch1-chapter: #dc3545; --ch1-chapter-bg: #fdf2f2; --ch1-topic: #fd7e14; --ch1-topic-bg: #fff8f0; --ch1-content: #ffc107; --ch1-content-bg: #fffdf0;
    --ch2-chapter: #0d6efd; --ch2-chapter-bg: #f0f6ff; --ch2-topic: #0dcaf0; --ch2-topic-bg: #f0fbff; --ch2-content: #6ea8fe; --ch2-content-bg: #f5f9ff;
    --ch3-chapter: #198754; --ch3-chapter-bg: #f0fdf6; --ch3-topic: #20c997; --ch3-topic-bg: #f0fdfb; --ch3-content: #75b798; --ch3-content-bg: #f5fdf9;
    --ch4-chapter: #6f42c1; --ch4-chapter-bg: #f8f5fd; --ch4-topic: #9f7aea; --ch4-topic-bg: #faf8fe; --ch4-content: #c4b5fd; --ch4-content-bg: #fcfaff;
    --ch5-chapter: #0f766e; --ch5-chapter-bg: #f0fdfc; --ch5-topic: #14b8a6; --ch5-topic-bg: #f0fdfb; --ch5-content: #5eead4; --ch5-content-bg: #f5fefc;
    --ch6-chapter: #d63384; --ch6-chapter-bg: #fdf2f8; --ch6-topic: #f472b6; --ch6-topic-bg: #fef7fb; --ch6-content: #f9a8d4; --ch6-content-bg: #fefafc;
    --quest-bg: #fff3cd; --quest-border: #ff9800; --quest-header: #ffeeba;
}

.content-hierarchy { background: #fff; border-radius: 8px; overflow: hidden; }

/* Chapter & Topic & Content Styles */
.chapter-item, .questionnaire-item { margin-bottom: 2px; border-radius: 6px; overflow: hidden; }
.chapter-header, .questionnaire-header { display: flex; align-items: center; padding: 12px 16px; cursor: pointer; transition: all 0.2s; min-height: 52px; border-left: 5px solid; }
.chapter-header:hover, .questionnaire-header:hover { filter: brightness(0.98); }
.chapter-header.collapsed .expand-icon, .questionnaire-header.collapsed .expand-icon { transform: rotate(-90deg); }
.chapter-number, .questionnaire-number { color: white; padding: 5px 12px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-right: 12px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; min-width: 55px; text-align: center; }
.chapter-title, .questionnaire-title { flex: 1; font-weight: 600; color: #2c3e50; font-size: 15px; }
.chapter-body, .questionnaire-body { padding: 8px 8px 8px 24px; }

/* Questionnaire specific */
.questionnaire-item { background: linear-gradient(135deg, #fffbf0 0%, #fff8e1 100%); }
.questionnaire-header { background: var(--quest-header); border-color: var(--quest-border); }
.questionnaire-number { background: var(--quest-border); }
.questionnaire-body { background: #fffdf5; }
.question-item { margin: 4px 0; padding: 10px 12px; background: white; border-radius: 4px; border-left: 3px solid #ffc107; display: flex; align-items: center; }
.question-number { background: #ffc107; color: #333; padding: 2px 8px; border-radius: 3px; font-size: 10px; font-weight: 700; margin-right: 8px; }
.question-title { flex: 1; font-size: 13px; color: #495057; }
.question-type-badge { font-size: 10px; padding: 2px 6px; border-radius: 3px; margin-right: 8px; }
.question-type-single { background: #e3f2fd; color: #1976d2; }
.question-type-multiple { background: #f3e5f5; color: #7b1fa2; }

/* Topic & Content */
.topic-item { margin: 6px 0; border-radius: 0 6px 6px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
.topic-header { display: flex; align-items: center; padding: 10px 12px; cursor: pointer; transition: all 0.2s; min-height: 46px; border-left: 4px solid; }
.topic-header:hover { filter: brightness(0.98); }
.topic-header.collapsed .expand-icon { transform: rotate(-90deg); }
.topic-number { color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-right: 10px; white-space: nowrap; min-width: 40px; text-align: center; }
.topic-title { flex: 1; font-weight: 500; color: #2c3e50; font-size: 14px; }
.topic-img { width: 32px; height: 32px; border-radius: 4px; object-fit: cover; margin-right: 10px; border: 1px solid rgba(0,0,0,0.1); }
.topic-body { padding: 6px 6px 6px 20px; border-top: 1px solid rgba(0,0,0,0.05); }
.content-item { margin: 4px 0; border-radius: 0 4px 4px 0; box-shadow: 0 1px 2px rgba(0,0,0,0.04); overflow: hidden; }
.content-header { display: flex; align-items: center; padding: 8px 10px; transition: all 0.2s; min-height: 40px; border-left: 3px solid; }
.content-header:hover { filter: brightness(0.98); }
.content-number { color: white; padding: 3px 8px; border-radius: 3px; font-size: 10px; font-weight: 700; margin-right: 8px; white-space: nowrap; min-width: 48px; text-align: center; }
.content-title { flex: 1; font-weight: 500; color: #495057; font-size: 13px; }
.content-indicators { display: flex; align-items: center; gap: 4px; margin-right: 8px; }
.content-indicators i { font-size: 14px; opacity: 0.8; }

/* Palette Classes */
.palette-1 .chapter-header { background: var(--ch1-chapter-bg); border-color: var(--ch1-chapter); }
.palette-1 .chapter-number { background: var(--ch1-chapter); }
.palette-1 .chapter-body { background: #fefafa; }
.palette-1 .topic-header { background: var(--ch1-topic-bg); border-color: var(--ch1-topic); }
.palette-1 .topic-number { background: var(--ch1-topic); }
.palette-1 .topic-body { background: #fffcf8; }
.palette-1 .content-header { background: var(--ch1-content-bg); border-color: var(--ch1-content); }
.palette-1 .content-number { background: var(--ch1-content); color: #333; }
.palette-2 .chapter-header { background: var(--ch2-chapter-bg); border-color: var(--ch2-chapter); }
.palette-2 .chapter-number { background: var(--ch2-chapter); }
.palette-2 .chapter-body { background: #fafcff; }
.palette-2 .topic-header { background: var(--ch2-topic-bg); border-color: var(--ch2-topic); }
.palette-2 .topic-number { background: var(--ch2-topic); }
.palette-2 .topic-body { background: #f8fcff; }
.palette-2 .content-header { background: var(--ch2-content-bg); border-color: var(--ch2-content); }
.palette-2 .content-number { background: var(--ch2-content); }
.palette-3 .chapter-header { background: var(--ch3-chapter-bg); border-color: var(--ch3-chapter); }
.palette-3 .chapter-number { background: var(--ch3-chapter); }
.palette-3 .chapter-body { background: #fafdfb; }
.palette-3 .topic-header { background: var(--ch3-topic-bg); border-color: var(--ch3-topic); }
.palette-3 .topic-number { background: var(--ch3-topic); }
.palette-3 .topic-body { background: #f8fcfa; }
.palette-3 .content-header { background: var(--ch3-content-bg); border-color: var(--ch3-content); }
.palette-3 .content-number { background: var(--ch3-content); }
.palette-4 .chapter-header { background: var(--ch4-chapter-bg); border-color: var(--ch4-chapter); }
.palette-4 .chapter-number { background: var(--ch4-chapter); }
.palette-4 .chapter-body { background: #fbfafd; }
.palette-4 .topic-header { background: var(--ch4-topic-bg); border-color: var(--ch4-topic); }
.palette-4 .topic-number { background: var(--ch4-topic); }
.palette-4 .topic-body { background: #faf9fc; }
.palette-4 .content-header { background: var(--ch4-content-bg); border-color: var(--ch4-content); }
.palette-4 .content-number { background: var(--ch4-content); }
.palette-5 .chapter-header { background: var(--ch5-chapter-bg); border-color: var(--ch5-chapter); }
.palette-5 .chapter-number { background: var(--ch5-chapter); }
.palette-5 .chapter-body { background: #fafdfb; }
.palette-5 .topic-header { background: var(--ch5-topic-bg); border-color: var(--ch5-topic); }
.palette-5 .topic-number { background: var(--ch5-topic); }
.palette-5 .topic-body { background: #f8fcfa; }
.palette-5 .content-header { background: var(--ch5-content-bg); border-color: var(--ch5-content); }
.palette-5 .content-number { background: var(--ch5-content); }
.palette-6 .chapter-header { background: var(--ch6-chapter-bg); border-color: var(--ch6-chapter); }
.palette-6 .chapter-number { background: var(--ch6-chapter); }
.palette-6 .chapter-body { background: #fdfafc; }
.palette-6 .topic-header { background: var(--ch6-topic-bg); border-color: var(--ch6-topic); }
.palette-6 .topic-number { background: var(--ch6-topic); }
.palette-6 .topic-body { background: #fcf9fb; }
.palette-6 .content-header { background: var(--ch6-content-bg); border-color: var(--ch6-content); }
.palette-6 .content-number { background: var(--ch6-content); }

/* Common Elements */
.expand-icon { font-size: 18px; color: #6c757d; margin-right: 8px; transition: transform 0.2s; flex-shrink: 0; }
.drag-handle { color: #ced4da; cursor: move; margin-right: 6px; font-size: 16px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 20px; height: 20px; }
.drag-handle:hover { color: #6c757d; }
.item-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
.badge-style { font-size: 11px; padding: 4px 8px; border-radius: 4px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px; }
.badge-style i { font-size: 12px; }
.empty-state { text-align: center; padding: 24px 16px; color: #6c757d; }
.empty-state i { font-size: 36px; margin-bottom: 8px; opacity: 0.4; }
.empty-topics, .empty-contents, .empty-questions { padding: 12px; font-size: 13px; color: #adb5bd; text-align: center; }
.sortable-ghost { opacity: 0.4; background: #e8f4fd !important; }
.sortable-chosen { box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10; }

/* Modal & Form Styles */
.modal-header { background: #f8f9fa; border-bottom: 1px solid #e9ecef; }
.form-section { background: #f8f9fa; border-radius: 6px; padding: 16px; margin-bottom: 16px; }
.form-section-title { font-weight: 600; color: #495057; margin-bottom: 12px; font-size: 14px; display: flex; align-items: center; }
.form-section-title i { margin-right: 6px; }
.answer-options { margin-top: 12px; }
.answer-item { display: flex; align-items: center; gap: 8px; padding: 10px 12px; background: white; border-radius: 4px; margin-bottom: 8px; border: 1px solid #e9ecef; transition: all 0.2s; }
.answer-item:hover { border-color: #adb5bd; }
.answer-item.is-correct { background: #d4edda; border-color: #28a745; }
.answer-item input[type="text"] { flex: 1; }
.answer-item .correct-check-wrapper { display: flex; align-items: center; gap: 6px; padding: 4px 10px; background: #f8f9fa; border-radius: 4px; cursor: pointer; border: 1px solid #dee2e6; transition: all 0.2s; min-width: 90px; justify-content: center; }
.answer-item .correct-check-wrapper:hover { background: #e9ecef; }
.answer-item .correct-check-wrapper.checked { background: #28a745; border-color: #28a745; }
.answer-item .correct-check-wrapper.checked .correct-label { color: white; }
.answer-item .correct-check-wrapper.checked .correct-check { accent-color: white; }
.answer-item .correct-check { width: 16px; height: 16px; cursor: pointer; margin: 0; }
.answer-item .correct-label { font-size: 11px; font-weight: 600; color: #6c757d; user-select: none; }
.answer-item .remove-answer { color: #dc3545; cursor: pointer; font-size: 18px; padding: 4px; }
.add-answer-btn { width: 100%; border: 2px dashed #dee2e6; background: transparent; padding: 8px; border-radius: 4px; color: #6c757d; cursor: pointer; }
.add-answer-btn:hover { border-color: #adb5bd; background: #f8f9fa; }
.media-upload, .cover-photo-upload { border: 2px dashed #dee2e6; border-radius: 6px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s; min-height: 80px; display: flex; align-items: center; justify-content: center; flex-direction: column; position: relative; }
.media-upload:hover, .cover-photo-upload:hover { border-color: #556ee6; background: #f8f9ff; }
.media-upload.has-media, .cover-photo-upload.has-image { padding: 8px; min-height: auto; }
.media-preview, .cover-photo-preview { max-width: 100%; max-height: 120px; border-radius: 4px; object-fit: cover; }
.media-remove, .cover-photo-remove { position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 22px; height: 22px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 12px; }
.photos-gallery { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
.photo-item { position: relative; width: 70px; height: 70px; }
.photo-item img { width: 100%; height: 100%; object-fit: cover; border-radius: 4px; border: 1px solid #e9ecef; }
.photo-item .remove-photo { position: absolute; top: -6px; right: -6px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
.resources-list { max-height: 180px; overflow-y: auto; }
.resource-item { display: flex; align-items: center; padding: 8px 10px; background: white; border-radius: 4px; margin-bottom: 6px; border: 1px solid #e9ecef; }
.resource-item i { font-size: 18px; margin-right: 8px; }
.resource-item .resource-name { flex: 1; font-size: 13px; color: #495057; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tox-tinymce { border-radius: 4px !important; }

/* Comments Section Styles */
.comments-badge { position: relative; }
.comments-badge .badge-count { position: absolute; top: -6px; right: -6px; background: #dc3545; color: white; font-size: 9px; padding: 2px 5px; border-radius: 10px; min-width: 16px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important; }
.item-comments-section { margin-top: 8px; padding: 12px; background: #f8f9fa; border-radius: 6px; border: 1px solid #e9ecef; }
.item-comments-list { max-height: 300px; overflow-y: auto; }
.mini-comment { display: flex; padding: 10px; background: white; border-radius: 6px; margin-bottom: 8px; border-left: 3px solid #dee2e6; transition: background-color 0.5s ease; }
.mini-comment.unanswered { border-left-color: #dc3545; }
.mini-comment.answered { border-left-color: #28a745; }
.mini-comment-avatar { width: 32px; height: 32px; border-radius: 50%; margin-right: 10px; flex-shrink: 0; }
.mini-comment-content { flex: 1; min-width: 0; }
.mini-comment-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.mini-comment-author { font-weight: 600; font-size: 12px; color: #495057; }
.mini-comment-time { font-size: 10px; color: #adb5bd; }
.mini-comment-text { font-size: 13px; color: #495057; line-height: 1.4; word-wrap: break-word; }
.mini-comment-text img.gif-image { max-width: 120px; border-radius: 4px; margin: 6px 0; display: block; }
.mini-reply-form { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e9ecef; width: 100%; position: relative; overflow: visible; }
.mini-reply-input { width: 100%; padding: 12px 16px; border: 1px solid #dee2e6; border-radius: 8px; font-size: 14px; min-height: 80px; line-height: 1.5; resize: vertical; }
.mini-reply-btn { padding: 8px 16px; border-radius: 20px; }
.mini-comment-actions { display: flex; gap: 6px; margin-top: 6px; align-items: center; flex-wrap: wrap; }
.mini-comment-actions button { font-size: 11px; padding: 2px 8px; }
.mini-comment-reactions { display: flex; gap: 8px; margin-left: auto; }
.reaction-btn { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 15px; padding: 2px 10px; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: all 0.2s; }
.reaction-btn:hover { background: #e9ecef; }
.reaction-btn.liked { background: #e3f2fd; border-color: #2196f3; color: #1976d2; }
.reaction-btn.hearted { background: #fce4ec; border-color: #e91e63; color: #c2185b; }
.reaction-btn.user-reacted { cursor: pointer; }
.reaction-btn.user-reacted::after { content: '✓'; font-size: 9px; margin-left: 2px; }
.reaction-count { font-weight: 600; }
.mini-comment-date { font-size: 9px; color: #adb5bd; margin-left: 8px; }
.total-comments-badge { background: #dc3545; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important; }
.mini-replies { margin-left: 42px; margin-top: 8px; padding-left: 10px; border-left: 2px solid #e9ecef; }
.mini-reply-item { padding: 8px; background: #f0f8ff; border-radius: 4px; margin-bottom: 6px; border-left: 2px solid #a8d4ff; transition: background-color 0.5s ease; }
.mini-reply-item.admin { background: #e8f5e9; border-left: 2px solid #28a745; }
/* Nested reply (reply to a reply) - lighter color */
.mini-replies .mini-replies .mini-reply-item { background: #faf5ff; border-left: 2px solid #d4b8ff; }
.mini-replies .mini-replies .mini-reply-item.admin { background: #f0fff4; border-left: 2px solid #68d391; }
/* Highlight animation for new comments */
.mini-comment.new-highlight, .mini-reply-item.new-highlight { background-color: #c8e6c9 !important; }
.mini-comment.delete-highlight, .mini-reply-item.delete-highlight { background-color: #ffcdd2 !important; }
.no-comments-mini { text-align: center; padding: 20px; color: #adb5bd; }
.no-comments-mini i { font-size: 24px; margin-bottom: 8px; display: block; }

/* Mention tag styling */
.mention-tag { background-color: #e7f3ff; color: #0066cc; padding: 1px 4px; border-radius: 3px; font-weight: 500; }

/* @Mention Autocomplete */
.mention-autocomplete { position: absolute; z-index: 1070; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.25); min-width: 200px; max-width: 280px; display: none; max-height: 200px; overflow-y: auto; }
.mention-autocomplete.show { display: block; }
.mention-autocomplete-item { padding: 8px 12px; cursor: pointer; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #f0f0f0; }
.mention-autocomplete-item:last-child { border-bottom: none; }
.mention-autocomplete-item:hover, .mention-autocomplete-item.active { background: #f0f4ff; }
.mention-autocomplete-item img { width: 28px; height: 28px; border-radius: 50%; }
.mention-autocomplete-item .mention-name { font-weight: 500; color: #333; font-size: 13px; }
.mention-autocomplete-item .mention-type { font-size: 10px; color: #6c757d; }
.mention-autocomplete-empty { padding: 12px; text-align: center; color: #6c757d; font-size: 12px; }

/* Emoji/GIF Picker for Inline */
.inline-emoji-picker { position: fixed; z-index: 1060; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.25); width: 280px; display: none; }
.inline-emoji-picker.show { display: block; }
.inline-emoji-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 2px; padding: 8px; max-height: 150px; overflow-y: auto; }
.inline-emoji-item { font-size: 18px; padding: 4px; cursor: pointer; border-radius: 4px; text-align: center; }
.inline-emoji-item:hover { background: #f0f0f0; }
.inline-gif-picker { position: fixed; z-index: 1060; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.25); width: 300px; display: none; }
.inline-gif-picker.show { display: block; }
.inline-gif-search { padding: 8px; border-bottom: 1px solid #e9ecef; }
.inline-gif-search input { width: 100%; padding: 6px 10px; border: 1px solid #dee2e6; border-radius: 15px; font-size: 12px; }
.inline-gif-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; padding: 8px; max-height: 180px; overflow-y: auto; }
.inline-gif-item { cursor: pointer; border-radius: 4px; overflow: hidden; }
.inline-gif-item:hover { transform: scale(1.02); }
.inline-gif-item img { width: 100%; display: block; }
.reply-input-container { position: relative; width: 100%; }
.reply-input-tools { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); display: flex; gap: 4px; }
.reply-input-tools button { background: none; border: none; padding: 2px; cursor: pointer; font-size: 16px; opacity: 0.5; }
.reply-input-tools button:hover { opacity: 1; }

@media (max-width: 768px) {
    .chapter-header, .topic-header, .content-header, .questionnaire-header { flex-wrap: wrap; }
    .item-actions { margin-top: 8px; width: 100%; justify-content: flex-end; }
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('title') Course Contents @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1 text-dark">{{ $course->courseName }}</h4>
                        <p class="text-secondary mb-0 small">Manage chapters, questionnaires, topics, and contents</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="d-flex align-items-center gap-1 me-2" id="total-comments-indicator" style="display: none !important;">
                            <i class="bx bx-message-square-dots text-danger"></i>
                            <span class="total-comments-badge" id="total-unanswered-count">0</span>
                            <small class="text-secondary">unanswered</small>
                        </span>
                        <button type="button" class="btn btn-primary btn-sm" onclick="showAddChapterModal()"><i class="bx bx-plus me-1"></i> Add Chapter</button>
                        <button type="button" class="btn btn-warning btn-sm" onclick="showAddQuestionnaireModal()"><i class="bx bx-help-circle me-1"></i> Add Questionnaire</button>
                        <a href="{{ route('anisenso-courses') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i> Back</a>
                    </div>
                </div>
            </div>
            <div class="card-body p-3">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                        <i class="bx bx-check-circle me-1"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="content-hierarchy border rounded" id="course-items-container">
                    @php $chapterIndex = 0; @endphp
                    @forelse($courseItems as $item)
                        @if($item['type'] === 'chapter')
                            @php $chapterIndex++; $paletteNum = (($chapterIndex - 1) % 6) + 1; $chapter = $item['data']; @endphp
                            <div class="chapter-item course-item palette-{{ $paletteNum }}" data-id="{{ $chapter->id }}" data-type="chapter" data-order="{{ $item['order'] }}">
                                <div class="chapter-header" data-bs-toggle="collapse" data-bs-target="#chapter-{{ $chapter->id }}">
                                    <div class="drag-handle course-item-drag" onclick="event.stopPropagation();"><i class="bx bx-grid-vertical"></i></div>
                                    <i class="bx bx-chevron-down expand-icon"></i>
                                    <span class="chapter-number">Ch {{ $chapterIndex }}</span>
                                    <span class="chapter-title">{{ $chapter->chapterTitle }}</span>
                                    <div class="item-actions" onclick="event.stopPropagation();">
                                        <button class="btn btn-sm btn-outline-secondary badge-style" onclick="showAddTopicModal({{ $chapter->id }}, {{ $chapterIndex }})"><i class="bx bx-plus"></i>Topic</button>
                                        <button class="btn btn-sm btn-outline-primary badge-style" onclick="showEditChapterModal({{ $chapter->id }})"><i class="bx bx-edit"></i>Edit</button>
                                        <button class="btn btn-sm btn-outline-danger badge-style" onclick="deleteChapter({{ $chapter->id }})"><i class="bx bx-trash"></i>Delete</button>
                                    </div>
                                </div>
                                <div class="collapse show chapter-body" id="chapter-{{ $chapter->id }}">
                                    <div class="topics-container" data-chapter-id="{{ $chapter->id }}">
                                        @forelse($chapter->topics as $topicIndex => $topic)
                                            <div class="topic-item" data-id="{{ $topic->id }}">
                                                <div class="topic-header" data-bs-toggle="collapse" data-bs-target="#topic-{{ $topic->id }}">
                                                    <div class="drag-handle topic-drag" onclick="event.stopPropagation();"><i class="bx bx-grid-vertical"></i></div>
                                                    <i class="bx bx-chevron-down expand-icon"></i>
                                                    @if($topic->topicCoverPhoto)<img src="{{ asset($topic->topicCoverPhoto) }}" class="topic-img" alt="">@endif
                                                    <span class="topic-number">{{ $chapterIndex }}.{{ $topicIndex + 1 }}</span>
                                                    <span class="topic-title">{{ $topic->topicTitle }}</span>
                                                    <div class="item-actions" onclick="event.stopPropagation();">
                                                        <button class="btn btn-sm btn-outline-secondary badge-style" onclick="showAddContentModal({{ $topic->id }}, '{{ $chapterIndex }}.{{ $topicIndex + 1 }}')"><i class="bx bx-plus"></i>Content</button>
                                                        <button class="btn btn-sm btn-outline-primary badge-style" onclick="showEditTopicModal({{ $topic->id }})"><i class="bx bx-edit"></i>Edit</button>
                                                        <button class="btn btn-sm btn-outline-danger badge-style" onclick="deleteTopic({{ $topic->id }})"><i class="bx bx-trash"></i>Delete</button>
                                                    </div>
                                                </div>
                                                <div class="collapse show topic-body" id="topic-{{ $topic->id }}">
                                                    <div class="contents-container" data-topic-id="{{ $topic->id }}">
                                                        @forelse($topic->contents as $contentIndex => $content)
                                                            <div class="content-item" data-id="{{ $content->id }}">
                                                                <div class="content-header">
                                                                    <div class="drag-handle content-drag"><i class="bx bx-grid-vertical"></i></div>
                                                                    <span class="content-number">{{ $chapterIndex }}.{{ $topicIndex + 1 }}.{{ $contentIndex + 1 }}</span>
                                                                    <span class="content-title">{{ $content->contentTitle }}</span>
                                                                    <div class="content-indicators">
                                                                        @if($content->youtubeUrl)<i class="bx bxl-youtube text-danger" title="YouTube"></i>@endif
                                                                        @if($content->contentPhotos && count($content->contentPhotos) > 0)<i class="bx bx-image text-info" title="Photos"></i>@endif
                                                                        @if($content->resources->count() > 0)<i class="bx bx-download text-success" title="Downloads"></i>@endif
                                                                        @if($content->takeaways)<i class="bx bx-bulb text-warning" title="Takeaways"></i>@endif
                                                                    </div>
                                                                    <div class="item-actions">
                                                                        <button class="btn btn-sm btn-outline-info badge-style comments-badge" onclick="toggleContentComments({{ $content->id }}, 'content')"><i class="bx bx-message-square-dots"></i>Comments<span class="badge-count content-comment-count" data-id="{{ $content->id }}" style="display:none;">0</span></button>
                                                                        <button class="btn btn-sm btn-outline-primary badge-style" onclick="showEditContentModal({{ $content->id }})"><i class="bx bx-edit"></i>Edit</button>
                                                                        <button class="btn btn-sm btn-outline-danger badge-style" onclick="deleteContent({{ $content->id }})"><i class="bx bx-trash"></i>Delete</button>
                                                                    </div>
                                                                </div>
                                                                <!-- Content Comments Section (hidden by default) -->
                                                                <div class="item-comments-section" id="content-comments-{{ $content->id }}" style="display: none;">
                                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                                        <small class="fw-semibold text-dark"><i class="bx bx-message-square-dots me-1"></i>Comments</small>
                                                                        <button class="btn btn-sm btn-link p-0" onclick="toggleContentComments({{ $content->id }}, 'content')"><i class="bx bx-x"></i></button>
                                                                    </div>
                                                                    <div class="item-comments-list" id="content-comments-list-{{ $content->id }}">
                                                                        <div class="text-center py-3"><i class="bx bx-loader-alt bx-spin"></i></div>
                                                                    </div>
                                                                    <div class="mini-reply-form">
                                                                        <div class="reply-input-container">
                                                                            <input type="text" class="mini-reply-input" id="content-reply-input-{{ $content->id }}" placeholder="Add a comment..." onkeypress="if(event.key==='Enter')sendInlineReply({{ $content->id }}, 'content', null)">
                                                                            <div class="reply-input-tools">
                                                                                <button onclick="toggleInlineEmojiPicker({{ $content->id }}, 'content', event)" title="Emoji"><i class="bx bx-smile"></i></button>
                                                                                <button onclick="toggleInlineGifPicker({{ $content->id }}, 'content', event)" title="GIF"><i class="bx bx-image"></i></button>
                                                                            </div>
                                                                            <div class="inline-emoji-picker" id="emoji-picker-content-{{ $content->id }}"></div>
                                                                            <div class="inline-gif-picker" id="gif-picker-content-{{ $content->id }}"><div class="inline-gif-search"><input type="text" placeholder="Search GIFs..." onkeyup="searchInlineGifs(this.value, {{ $content->id }}, 'content')"></div><div class="inline-gif-grid" id="gif-grid-content-{{ $content->id }}"></div></div>
                                                                        </div>
                                                                        <button class="btn btn-sm btn-success mini-reply-btn" onclick="sendInlineReply({{ $content->id }}, 'content', null)"><i class="bx bx-send"></i></button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <div class="empty-contents"><i class="bx bx-file-blank d-block text-muted"></i><small class="text-muted">No contents yet</small></div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="empty-topics"><i class="bx bx-folder-open d-block text-muted"></i><small class="text-muted">No topics yet</small></div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @else
                            @php $questionnaire = $item['data']; @endphp
                            <div class="questionnaire-item course-item" data-id="{{ $questionnaire->id }}" data-type="questionnaire" data-order="{{ $item['order'] }}">
                                <div class="questionnaire-header" data-bs-toggle="collapse" data-bs-target="#questionnaire-{{ $questionnaire->id }}">
                                    <div class="drag-handle course-item-drag" onclick="event.stopPropagation();"><i class="bx bx-grid-vertical"></i></div>
                                    <i class="bx bx-chevron-down expand-icon"></i>
                                    <span class="questionnaire-number"><i class="bx bx-help-circle"></i> Quiz</span>
                                    <span class="questionnaire-title">{{ $questionnaire->title }}</span>
                                    <div class="item-actions" onclick="event.stopPropagation();">
                                        <button class="btn btn-sm btn-outline-secondary badge-style" onclick="showAddQuestionModal({{ $questionnaire->id }})"><i class="bx bx-plus"></i>Question</button>
                                        <button class="btn btn-sm btn-outline-info badge-style comments-badge" onclick="event.stopPropagation(); toggleQuestionnaireComments({{ $questionnaire->id }})"><i class="bx bx-message-square-dots"></i>Comments<span class="badge-count questionnaire-comment-count" data-id="{{ $questionnaire->id }}" style="display:none;">0</span></button>
                                        <button class="btn btn-sm btn-outline-primary badge-style" onclick="showEditQuestionnaireModal({{ $questionnaire->id }})"><i class="bx bx-edit"></i>Edit</button>
                                        <button class="btn btn-sm btn-outline-danger badge-style" onclick="deleteQuestionnaire({{ $questionnaire->id }})"><i class="bx bx-trash"></i>Delete</button>
                                    </div>
                                </div>
                                <!-- Questionnaire Comments Section -->
                                <div class="item-comments-section mx-3 mt-2" id="questionnaire-comments-{{ $questionnaire->id }}" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <small class="fw-semibold text-dark"><i class="bx bx-message-square-dots me-1"></i>Questionnaire Comments</small>
                                        <button class="btn btn-sm btn-link p-0" onclick="toggleQuestionnaireComments({{ $questionnaire->id }})"><i class="bx bx-x"></i></button>
                                    </div>
                                    <div class="item-comments-list" id="questionnaire-comments-list-{{ $questionnaire->id }}">
                                        <div class="text-center py-3"><i class="bx bx-loader-alt bx-spin"></i></div>
                                    </div>
                                    <div class="mini-reply-form">
                                        <div class="reply-input-container">
                                            <input type="text" class="mini-reply-input" id="questionnaire-reply-input-{{ $questionnaire->id }}" placeholder="Add a comment..." onkeypress="if(event.key==='Enter')sendQuestionnaireReply({{ $questionnaire->id }}, null)">
                                            <div class="reply-input-tools">
                                                <button onclick="toggleInlineEmojiPicker({{ $questionnaire->id }}, 'questionnaire', event)" title="Emoji"><i class="bx bx-smile"></i></button>
                                                <button onclick="toggleInlineGifPicker({{ $questionnaire->id }}, 'questionnaire', event)" title="GIF"><i class="bx bx-image"></i></button>
                                            </div>
                                            <div class="inline-emoji-picker" id="emoji-picker-questionnaire-{{ $questionnaire->id }}"></div>
                                            <div class="inline-gif-picker" id="gif-picker-questionnaire-{{ $questionnaire->id }}"><div class="inline-gif-search"><input type="text" placeholder="Search GIFs..." onkeyup="searchInlineGifs(this.value, {{ $questionnaire->id }}, 'questionnaire')"></div><div class="inline-gif-grid" id="gif-grid-questionnaire-{{ $questionnaire->id }}"></div></div>
                                        </div>
                                        <button class="btn btn-sm btn-success mini-reply-btn" onclick="sendQuestionnaireReply({{ $questionnaire->id }}, null)"><i class="bx bx-send"></i></button>
                                    </div>
                                </div>
                                <div class="collapse show questionnaire-body" id="questionnaire-{{ $questionnaire->id }}">
                                    <div class="questions-container" data-questionnaire-id="{{ $questionnaire->id }}">
                                        @forelse($questionnaire->questions as $qIndex => $question)
                                            <div class="question-item" data-id="{{ $question->id }}">
                                                <div class="drag-handle question-drag"><i class="bx bx-grid-vertical"></i></div>
                                                <span class="question-number">Q{{ $qIndex + 1 }}</span>
                                                <span class="question-type-badge {{ $question->questionType === 'single' ? 'question-type-single' : 'question-type-multiple' }}">{{ $question->questionType === 'single' ? 'Single' : 'Multiple' }}</span>
                                                <span class="question-title">{{ $question->questionTitle }}</span>
                                                <div class="item-actions">
                                                    @if($question->questionPhoto)<i class="bx bx-image text-info me-1"></i>@endif
                                                    @if($question->questionVideo)<i class="bx bxl-youtube text-danger me-1"></i>@endif
                                                    <button class="btn btn-sm btn-outline-primary badge-style" onclick="showEditQuestionModal({{ $question->id }})"><i class="bx bx-edit"></i>Edit</button>
                                                    <button class="btn btn-sm btn-outline-danger badge-style" onclick="deleteQuestion({{ $question->id }})"><i class="bx bx-trash"></i>Delete</button>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="empty-questions"><i class="bx bx-help-circle d-block text-muted"></i><small class="text-muted">No questions yet</small></div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="empty-state py-5">
                            <i class="bx bx-book-open d-block text-muted"></i>
                            <h5 class="mt-2 text-dark">No Content Yet</h5>
                            <p class="text-secondary small mb-0">Start by adding chapters or questionnaires using the buttons above</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chapter Modal -->
<div class="modal fade" id="chapterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="chapterModalTitle"><i class="bx bx-book-content me-1 text-primary"></i> Add Chapter</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="chapterId">
                <div class="mb-3"><label class="form-label small fw-semibold">Chapter Title <span class="text-danger">*</span></label><input type="text" class="form-control" id="chapterTitle" placeholder="Enter chapter title"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Description</label><textarea class="form-control" id="chapterDescription" rows="2" placeholder="Brief description"></textarea></div>
                <div class="mb-0"><label class="form-label small fw-semibold">Cover Photo</label><div class="cover-photo-upload" id="chapterCoverUpload" onclick="$('#chapterCoverInput').click()"><i class="bx bx-image-add text-muted" style="font-size: 24px;"></i><small class="text-muted">Click to upload</small></div><input type="file" id="chapterCoverInput" accept="image/*" style="display: none;"><input type="hidden" id="chapterCoverPhoto"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="saveChapterBtn" onclick="saveChapter()"><i class="bx bx-check me-1"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Topic Modal -->
<div class="modal fade" id="topicModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="topicModalTitle"><i class="bx bx-file me-1 text-success"></i> Add Topic</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="topicId"><input type="hidden" id="topicChapterId">
                <div class="mb-3"><label class="form-label small fw-semibold">Topic Title <span class="text-danger">*</span></label><input type="text" class="form-control" id="topicTitle" placeholder="Enter topic title"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Description</label><textarea class="form-control" id="topicDescription" rows="2" placeholder="Brief description"></textarea></div>
                <div class="mb-0"><label class="form-label small fw-semibold">Cover Photo</label><div class="cover-photo-upload" id="topicCoverUpload" onclick="$('#topicCoverInput').click()"><i class="bx bx-image-add text-muted" style="font-size: 24px;"></i><small class="text-muted">Click to upload</small></div><input type="file" id="topicCoverInput" accept="image/*" style="display: none;"><input type="hidden" id="topicCoverPhoto"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-success" id="saveTopicBtn" onclick="saveTopic()"><i class="bx bx-check me-1"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Content Modal -->
<div class="modal fade" id="contentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="contentModalTitle"><i class="bx bx-edit me-1 text-warning"></i> Add Content</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <input type="hidden" id="contentId"><input type="hidden" id="contentTopicId">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="form-section"><div class="form-section-title"><i class="bx bx-info-circle"></i> Basic Info</div><div class="mb-0"><label class="form-label small fw-semibold">Content Title <span class="text-danger">*</span></label><input type="text" class="form-control" id="contentTitle" placeholder="Enter content title"></div></div>
                        <div class="form-section"><div class="form-section-title"><i class="bx bx-text"></i> Content Body</div><textarea id="contentBody"></textarea></div>
                        <div class="form-section mb-0"><div class="form-section-title"><i class="bx bx-bulb"></i> Key Takeaways</div><textarea class="form-control" id="contentTakeaways" rows="2" placeholder="Key points that popup for the reader"></textarea></div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-section"><div class="form-section-title"><i class="bx bxl-youtube text-danger"></i> YouTube Video</div><input type="url" class="form-control form-control-sm" id="contentYoutubeUrl" placeholder="https://youtube.com/watch?v=..."><div id="youtubePreview" class="mt-2"></div></div>
                        <div class="form-section"><div class="form-section-title"><i class="bx bx-images text-info"></i> Photos</div><div class="cover-photo-upload py-3" id="photosUpload" onclick="$('#photosInput').click()"><i class="bx bx-image-add text-muted"></i><small class="text-muted">Upload photos</small></div><input type="file" id="photosInput" accept="image/*" multiple style="display: none;"><div class="photos-gallery" id="photosGallery"></div></div>
                        <div class="form-section mb-0"><div class="form-section-title"><i class="bx bx-download text-success"></i> Downloads</div><div class="cover-photo-upload py-3" id="resourcesUpload" onclick="$('#resourcesInput').click()"><i class="bx bx-cloud-upload text-muted"></i><small class="text-muted">Upload files</small></div><input type="file" id="resourcesInput" multiple style="display: none;"><div class="resources-list mt-2" id="resourcesList"></div></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-warning" id="saveContentBtn" onclick="saveContent()"><i class="bx bx-check me-1"></i> Save Content</button>
            </div>
        </div>
    </div>
</div>

<!-- Questionnaire Modal -->
<div class="modal fade" id="questionnaireModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header py-2 bg-warning">
                <h6 class="modal-title text-dark" id="questionnaireModalTitle"><i class="bx bx-help-circle me-1"></i> Add Questionnaire</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="questionnaireId">
                <div class="mb-3"><label class="form-label small fw-semibold">Questionnaire Title <span class="text-danger">*</span></label><input type="text" class="form-control" id="questionnaireTitle" placeholder="e.g., Chapter 1 Quiz"></div>
                <div class="mb-0"><label class="form-label small fw-semibold">Description</label><textarea class="form-control" id="questionnaireDescription" rows="2" placeholder="Brief description"></textarea></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-warning" id="saveQuestionnaireBtn" onclick="saveQuestionnaire()"><i class="bx bx-check me-1"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Comment Modal -->
<div class="modal fade" id="deleteCommentModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1061;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Delete Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this comment?</p>
                <p class="text-secondary small mb-0"><i class="bx bx-info-circle me-1"></i>This will also delete all replies to this comment.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bx bx-x me-1"></i>Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteComment"><i class="bx bx-trash me-1"></i>Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Question Modal -->
<div class="modal fade" id="questionModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header py-2 bg-warning">
                <h6 class="modal-title text-dark" id="questionModalTitle"><i class="bx bx-help-circle me-1"></i> Add Question</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <input type="hidden" id="questionId"><input type="hidden" id="questionQuestionnaireId">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="form-section">
                            <div class="form-section-title"><i class="bx bx-info-circle"></i> Question Details</div>
                            <div class="mb-3"><label class="form-label small fw-semibold">Question Title <span class="text-danger">*</span></label><input type="text" class="form-control" id="questionTitle" placeholder="Brief title for the question"></div>
                            <div class="mb-0"><label class="form-label small fw-semibold">Question Text <span class="text-danger">*</span></label><textarea class="form-control" id="questionText" rows="3" placeholder="The full question text"></textarea></div>
                        </div>
                        <div class="form-section">
                            <div class="form-section-title"><i class="bx bx-list-check"></i> Answer Options</div>
                            <div class="mb-3"><label class="form-label small fw-semibold">Question Type <span class="text-danger">*</span></label><select class="form-select" id="questionType"><option value="single">Single Choice (Radio - one correct answer)</option><option value="multiple">Multiple Choice (Checkbox - multiple correct)</option></select></div>
                            <div class="answer-options" id="answerOptions">
                                <div class="answer-item">
                                    <div class="correct-check-wrapper" onclick="toggleCorrect(this)">
                                        <input type="checkbox" class="correct-check" title="Mark as correct">
                                        <span class="correct-label">Correct</span>
                                    </div>
                                    <input type="text" class="form-control form-control-sm" placeholder="Answer option 1">
                                    <i class="bx bx-x remove-answer" onclick="removeAnswer(this)"></i>
                                </div>
                                <div class="answer-item">
                                    <div class="correct-check-wrapper" onclick="toggleCorrect(this)">
                                        <input type="checkbox" class="correct-check" title="Mark as correct">
                                        <span class="correct-label">Correct</span>
                                    </div>
                                    <input type="text" class="form-control form-control-sm" placeholder="Answer option 2">
                                    <i class="bx bx-x remove-answer" onclick="removeAnswer(this)"></i>
                                </div>
                            </div>
                            <button type="button" class="add-answer-btn mt-2" onclick="addAnswer()"><i class="bx bx-plus me-1"></i> Add Answer Option</button>
                            <small class="text-secondary d-block mt-2"><i class="bx bx-info-circle"></i> Click "Correct" button to mark the right answer(s)</small>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-section"><div class="form-section-title"><i class="bx bx-image text-info"></i> Question Photo (Optional)</div><div class="media-upload" id="questionPhotoUpload" onclick="$('#questionPhotoInput').click()"><i class="bx bx-image-add text-muted" style="font-size: 24px;"></i><small class="text-muted">Click to upload</small></div><input type="file" id="questionPhotoInput" accept="image/*" style="display: none;"><input type="hidden" id="questionPhotoPath"></div>
                        <div class="form-section mb-0"><div class="form-section-title"><i class="bx bxl-youtube text-danger"></i> Question Video (Optional)</div><input type="url" class="form-control form-control-sm" id="questionVideoUrl" placeholder="https://youtube.com/watch?v=..."><div id="questionVideoPreview" class="mt-2"></div></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-warning" id="saveQuestionBtn" onclick="saveQuestion()"><i class="bx bx-check me-1"></i> Save Question</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2 bg-danger text-white"><h6 class="modal-title"><i class="bx bx-trash me-1"></i> Delete</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center py-4"><p id="deleteMessage" class="mb-0 text-dark">Are you sure?</p></div>
            <div class="modal-footer py-2 justify-content-center"><button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-sm btn-danger" id="confirmDeleteBtn">Delete</button></div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.tiny.cloud/1/lbsbsr7t63wjii3wjqcftu0e9ot0c6e6f7mle8yqp6umxmpq/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
const courseId = {{ $course->id }};
let tinymceEditor = null, pendingPhotos = [], pendingResources = [], existingPhotos = [], existingResources = [];
toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: 3000 };

$(document).ready(function() { initializeSortable(); initializeCollapse(); });

function initializeSortable() {
    const container = document.getElementById('course-items-container');
    if (container && container.querySelector('.course-item')) {
        new Sortable(container, { animation: 150, handle: '.course-item-drag', ghostClass: 'sortable-ghost', chosenClass: 'sortable-chosen', draggable: '.course-item', onEnd: () => { updateCourseItemsOrder(); updateNumbering(); } });
    }
    document.querySelectorAll('.topics-container').forEach(c => { if (c.querySelector('.topic-item')) new Sortable(c, { animation: 150, handle: '.topic-drag', ghostClass: 'sortable-ghost', onEnd: () => updateOrder('topics', c.dataset.chapterId) }); });
    document.querySelectorAll('.contents-container').forEach(c => { if (c.querySelector('.content-item')) new Sortable(c, { animation: 150, handle: '.content-drag', ghostClass: 'sortable-ghost', onEnd: () => updateOrder('contents', c.dataset.topicId) }); });
    document.querySelectorAll('.questions-container').forEach(c => { if (c.querySelector('.question-item')) new Sortable(c, { animation: 150, handle: '.question-drag', ghostClass: 'sortable-ghost', onEnd: () => updateQuestionOrder(c.dataset.questionnaireId) }); });
}

function initializeCollapse() {
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(h => { const t = document.querySelector(h.dataset.bsTarget); if (t) { t.addEventListener('show.bs.collapse', () => h.classList.remove('collapsed')); t.addEventListener('hide.bs.collapse', () => h.classList.add('collapsed')); } });
}

function updateCourseItemsOrder() {
    const items = []; document.querySelectorAll('.course-item').forEach((item, i) => items.push({ id: item.dataset.id, type: item.dataset.type, order: i + 1 }));
    $.ajax({ url: '/api/anisenso/course-items/order', method: 'PUT', data: { _token: '{{ csrf_token() }}', items }, success: r => { if(r.success) toastr.success('Order updated'); }, error: () => toastr.error('Failed') });
}

function updateOrder(type, parentId) {
    let items = [], url = '';
    if (type === 'topics') { document.querySelectorAll(`.topics-container[data-chapter-id="${parentId}"] .topic-item`).forEach((item, i) => items.push({ id: item.dataset.id, order: i + 1 })); url = '/api/anisenso/topics/order'; }
    else if (type === 'contents') { document.querySelectorAll(`.contents-container[data-topic-id="${parentId}"] .content-item`).forEach((item, i) => items.push({ id: item.dataset.id, order: i + 1 })); url = '/api/anisenso/contents/order'; }
    $.ajax({ url, method: 'PUT', data: { _token: '{{ csrf_token() }}', items }, success: r => { if(r.success) { toastr.success('Order updated'); updateNumbering(); } }, error: () => toastr.error('Failed') });
}

function updateQuestionOrder(qId) {
    const items = []; document.querySelectorAll(`.questions-container[data-questionnaire-id="${qId}"] .question-item`).forEach((item, i) => items.push({ id: item.dataset.id, order: i + 1 }));
    $.ajax({ url: '/api/anisenso/questions/order', method: 'PUT', data: { _token: '{{ csrf_token() }}', items }, success: r => { if(r.success) { toastr.success('Order updated'); document.querySelectorAll(`.questions-container[data-questionnaire-id="${qId}"] .question-item`).forEach((q, i) => q.querySelector('.question-number').textContent = 'Q' + (i + 1)); } }, error: () => toastr.error('Failed') });
}

function updateNumbering() {
    let chapterNum = 0;
    document.querySelectorAll('.course-item').forEach((item) => {
        if (item.dataset.type === 'chapter') {
            chapterNum++; item.className = item.className.replace(/palette-\d/, 'palette-' + (((chapterNum - 1) % 6) + 1)); item.querySelector('.chapter-number').textContent = 'Ch ' + chapterNum;
            item.querySelectorAll('.topic-item').forEach((tp, ti) => { tp.querySelector('.topic-number').textContent = chapterNum + '.' + (ti + 1); tp.querySelectorAll('.content-item').forEach((ct, cti) => ct.querySelector('.content-number').textContent = chapterNum + '.' + (ti + 1) + '.' + (cti + 1)); });
        }
    });
}

// ==================== CHAPTER CRUD ====================
function showAddChapterModal() { $('#chapterModalTitle').html('<i class="bx bx-book-content me-1 text-primary"></i> Add Chapter'); $('#chapterId, #chapterTitle, #chapterDescription, #chapterCoverPhoto').val(''); resetCoverUpload('chapterCoverUpload'); $('#chapterModal').modal('show'); }
function showEditChapterModal(id) { $.get('/api/anisenso/chapters/' + id, r => { if (r.success) { $('#chapterModalTitle').html('<i class="bx bx-edit me-1 text-primary"></i> Edit Chapter'); $('#chapterId').val(r.data.id); $('#chapterTitle').val(r.data.chapterTitle); $('#chapterDescription').val(r.data.chapterDescription); $('#chapterCoverPhoto').val(r.data.chapterCoverPhoto || ''); r.data.chapterCoverPhoto ? showCoverPreview('chapterCoverUpload', r.data.chapterCoverPhoto) : resetCoverUpload('chapterCoverUpload'); $('#chapterModal').modal('show'); } }); }
function saveChapter() { const id = $('#chapterId').val(); const data = { _token: '{{ csrf_token() }}', courseId, chapterTitle: $('#chapterTitle').val(), chapterDescription: $('#chapterDescription').val(), chapterCoverPhoto: $('#chapterCoverPhoto').val() }; if (!data.chapterTitle) { toastr.error('Title required'); return; } $('#saveChapterBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>'); $.ajax({ url: id ? '/api/anisenso/chapters/' + id : '/api/anisenso/chapters', method: id ? 'PUT' : 'POST', data, success: r => { if(r.success) { toastr.success(r.message); $('#chapterModal').modal('hide'); location.reload(); } else toastr.error(r.message); }, error: xhr => toastr.error(xhr.responseJSON?.message || 'Error'), complete: () => $('#saveChapterBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i> Save') }); }
function deleteChapter(id) { $('#deleteMessage').text('Delete this chapter and all its contents?'); $('#confirmDeleteBtn').off('click').on('click', () => { $.ajax({ url: '/api/anisenso/chapters/' + id, method: 'DELETE', data: { _token: '{{ csrf_token() }}' }, success: r => { if(r.success) { toastr.success('Deleted'); $('#deleteModal').modal('hide'); location.reload(); } } }); }); $('#deleteModal').modal('show'); }

// ==================== TOPIC CRUD ====================
function showAddTopicModal(chapterId, chapterNum) { $('#topicModalTitle').html('<i class="bx bx-file me-1 text-success"></i> Add Topic to Chapter ' + chapterNum); $('#topicId, #topicTitle, #topicDescription, #topicCoverPhoto').val(''); $('#topicChapterId').val(chapterId); resetCoverUpload('topicCoverUpload'); $('#topicModal').modal('show'); }
function showEditTopicModal(id) { $.get('/api/anisenso/topics/' + id, r => { if (r.success) { $('#topicModalTitle').html('<i class="bx bx-edit me-1 text-success"></i> Edit Topic'); $('#topicId').val(r.data.id); $('#topicChapterId').val(r.data.chapterId); $('#topicTitle').val(r.data.topicTitle); $('#topicDescription').val(r.data.topicDescription); $('#topicCoverPhoto').val(r.data.topicCoverPhoto || ''); r.data.topicCoverPhoto ? showCoverPreview('topicCoverUpload', r.data.topicCoverPhoto) : resetCoverUpload('topicCoverUpload'); $('#topicModal').modal('show'); } }); }
function saveTopic() { const id = $('#topicId').val(); const data = { _token: '{{ csrf_token() }}', chapterId: $('#topicChapterId').val(), topicTitle: $('#topicTitle').val(), topicDescription: $('#topicDescription').val(), topicCoverPhoto: $('#topicCoverPhoto').val() }; if (!data.topicTitle) { toastr.error('Title required'); return; } $('#saveTopicBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>'); $.ajax({ url: id ? '/api/anisenso/topics/' + id : '/api/anisenso/topics', method: id ? 'PUT' : 'POST', data, success: r => { if(r.success) { toastr.success(r.message); $('#topicModal').modal('hide'); location.reload(); } }, error: xhr => toastr.error(xhr.responseJSON?.message || 'Error'), complete: () => $('#saveTopicBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i> Save') }); }
function deleteTopic(id) { $('#deleteMessage').text('Delete this topic and all its contents?'); $('#confirmDeleteBtn').off('click').on('click', () => { $.ajax({ url: '/api/anisenso/topics/' + id, method: 'DELETE', data: { _token: '{{ csrf_token() }}' }, success: r => { if(r.success) { toastr.success('Deleted'); $('#deleteModal').modal('hide'); location.reload(); } } }); }); $('#deleteModal').modal('show'); }

// ==================== CONTENT CRUD ====================
function showAddContentModal(topicId, topicNum) { $('#contentModalTitle').html('<i class="bx bx-edit me-1 text-warning"></i> Add Content to Topic ' + topicNum); $('#contentId, #contentTitle, #contentYoutubeUrl, #contentTakeaways').val(''); $('#contentTopicId').val(topicId); $('#youtubePreview, #photosGallery, #resourcesList').html(''); pendingPhotos = []; pendingResources = []; existingPhotos = []; existingResources = []; initTinyMCE(''); $('#contentModal').modal('show'); }
function showEditContentModal(id) { $.get('/api/anisenso/contents/' + id, r => { if (r.success) { const d = r.data; $('#contentModalTitle').html('<i class="bx bx-edit me-1 text-warning"></i> Edit Content'); $('#contentId').val(d.id); $('#contentTopicId').val(d.topicId); $('#contentTitle').val(d.contentTitle); $('#contentYoutubeUrl').val(d.youtubeUrl || ''); $('#contentTakeaways').val(d.takeaways || ''); existingPhotos = d.contentPhotos || []; existingResources = d.resources || []; renderPhotosGallery(); renderResourcesList(); updateYoutubePreview(); initTinyMCE(d.contentBody || ''); $('#contentModal').modal('show'); } }); }
function saveContent() { const id = $('#contentId').val(); const contentBody = tinymceEditor ? tinymceEditor.getContent() : ''; const data = { _token: '{{ csrf_token() }}', topicId: $('#contentTopicId').val(), contentTitle: $('#contentTitle').val(), contentBody, youtubeUrl: $('#contentYoutubeUrl').val(), takeaways: $('#contentTakeaways').val(), contentPhotos: JSON.stringify(existingPhotos), existingResources: JSON.stringify(existingResources.map(r => r.id || r)) }; if (!data.contentTitle) { toastr.error('Title required'); return; } const formData = new FormData(); Object.keys(data).forEach(k => formData.append(k, data[k])); pendingPhotos.forEach(f => formData.append('newPhotos[]', f)); pendingResources.forEach(f => formData.append('newResources[]', f)); $('#saveContentBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>'); $.ajax({ url: id ? '/api/anisenso/contents/' + id : '/api/anisenso/contents', method: 'POST', data: formData, processData: false, contentType: false, headers: { 'X-HTTP-Method-Override': id ? 'PUT' : 'POST' }, success: r => { if(r.success) { toastr.success(r.message); $('#contentModal').modal('hide'); location.reload(); } }, error: xhr => toastr.error(xhr.responseJSON?.message || 'Error'), complete: () => $('#saveContentBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i> Save Content') }); }
function deleteContent(id) { $('#deleteMessage').text('Delete this content?'); $('#confirmDeleteBtn').off('click').on('click', () => { $.ajax({ url: '/api/anisenso/contents/' + id, method: 'DELETE', data: { _token: '{{ csrf_token() }}' }, success: r => { if(r.success) { toastr.success('Deleted'); $('#deleteModal').modal('hide'); location.reload(); } } }); }); $('#deleteModal').modal('show'); }

// ==================== QUESTIONNAIRE CRUD ====================
function showAddQuestionnaireModal() { $('#questionnaireModalTitle').html('<i class="bx bx-help-circle me-1"></i> Add Questionnaire'); $('#questionnaireId, #questionnaireTitle, #questionnaireDescription').val(''); $('#questionnaireModal').modal('show'); }
function showEditQuestionnaireModal(id) { $.get('/api/anisenso/questionnaires/' + id, r => { if (r.success) { $('#questionnaireModalTitle').html('<i class="bx bx-edit me-1"></i> Edit Questionnaire'); $('#questionnaireId').val(r.data.id); $('#questionnaireTitle').val(r.data.title); $('#questionnaireDescription').val(r.data.description); $('#questionnaireModal').modal('show'); } }); }
function saveQuestionnaire() { const id = $('#questionnaireId').val(); const data = { _token: '{{ csrf_token() }}', courseId, title: $('#questionnaireTitle').val(), description: $('#questionnaireDescription').val() }; if (!data.title) { toastr.error('Title required'); return; } $('#saveQuestionnaireBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>'); $.ajax({ url: id ? '/api/anisenso/questionnaires/' + id : '/api/anisenso/questionnaires', method: id ? 'PUT' : 'POST', data, success: r => { if(r.success) { toastr.success(r.message); $('#questionnaireModal').modal('hide'); location.reload(); } else toastr.error(r.message); }, error: xhr => toastr.error(xhr.responseJSON?.message || 'Error'), complete: () => $('#saveQuestionnaireBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i> Save') }); }
function deleteQuestionnaire(id) { $('#deleteMessage').text('Delete this questionnaire and all its questions?'); $('#confirmDeleteBtn').off('click').on('click', () => { $.ajax({ url: '/api/anisenso/questionnaires/' + id, method: 'DELETE', data: { _token: '{{ csrf_token() }}' }, success: r => { if(r.success) { toastr.success('Deleted'); $('#deleteModal').modal('hide'); location.reload(); } } }); }); $('#deleteModal').modal('show'); }

// ==================== QUESTION CRUD ====================
function showAddQuestionModal(questionnaireId) { $('#questionModalTitle').html('<i class="bx bx-help-circle me-1"></i> Add Question'); $('#questionId, #questionTitle, #questionText, #questionVideoUrl, #questionPhotoPath').val(''); $('#questionQuestionnaireId').val(questionnaireId); $('#questionType').val('single'); resetMediaUpload('questionPhotoUpload'); $('#questionVideoPreview').html(''); $('#answerOptions').html(getAnswerItemHtml(1) + getAnswerItemHtml(2)); $('#questionModal').modal('show'); }
function showEditQuestionModal(id) { $.get('/api/anisenso/questions/' + id, r => { if (r.success) { const d = r.data; $('#questionModalTitle').html('<i class="bx bx-edit me-1"></i> Edit Question'); $('#questionId').val(d.id); $('#questionQuestionnaireId').val(d.questionnaireId); $('#questionTitle').val(d.questionTitle); $('#questionText').val(d.questionText); $('#questionType').val(d.questionType); $('#questionVideoUrl').val(d.questionVideo || ''); $('#questionPhotoPath').val(d.questionPhoto || ''); d.questionPhoto ? showMediaPreview('questionPhotoUpload', d.questionPhoto) : resetMediaUpload('questionPhotoUpload'); updateQuestionVideoPreview(); $('#answerOptions').html(''); d.answers.forEach((a, i) => $('#answerOptions').append(getAnswerItemHtml(i + 1, escapeHtml(a.answerText), a.isCorrect))); $('#questionModal').modal('show'); } }); }
function saveQuestion() { const id = $('#questionId').val(); const answers = []; let hasCorrect = false; $('#answerOptions .answer-item').each(function() { const text = $(this).find('input[type="text"]').val().trim(); const isCorrect = $(this).find('.correct-check').is(':checked'); if (text) { answers.push({ text, isCorrect }); if (isCorrect) hasCorrect = true; } }); if (!$('#questionTitle').val().trim()) { toastr.error('Question title required'); return; } if (!$('#questionText').val().trim()) { toastr.error('Question text required'); return; } if (answers.length < 2) { toastr.error('At least 2 answer options required'); return; } if (!hasCorrect) { toastr.error('Please mark at least one correct answer'); return; } const formData = new FormData(); formData.append('_token', '{{ csrf_token() }}'); formData.append('questionnaireId', $('#questionQuestionnaireId').val()); formData.append('questionTitle', $('#questionTitle').val()); formData.append('questionText', $('#questionText').val()); formData.append('questionType', $('#questionType').val()); formData.append('questionVideo', $('#questionVideoUrl').val()); formData.append('answers', JSON.stringify(answers)); const photoInput = $('#questionPhotoInput')[0]; if (photoInput.files.length > 0) formData.append('questionPhoto', photoInput.files[0]); $('#saveQuestionBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>'); $.ajax({ url: id ? '/api/anisenso/questions/' + id : '/api/anisenso/questions', method: 'POST', data: formData, processData: false, contentType: false, success: r => { if(r.success) { toastr.success(r.message); $('#questionModal').modal('hide'); location.reload(); } else toastr.error(r.message); }, error: xhr => toastr.error(xhr.responseJSON?.message || 'Error'), complete: () => $('#saveQuestionBtn').prop('disabled', false).html('<i class="bx bx-check me-1"></i> Save Question') }); }
function deleteQuestion(id) { $('#deleteMessage').text('Delete this question?'); $('#confirmDeleteBtn').off('click').on('click', () => { $.ajax({ url: '/api/anisenso/questions/' + id, method: 'DELETE', data: { _token: '{{ csrf_token() }}' }, success: r => { if(r.success) { toastr.success('Deleted'); $('#deleteModal').modal('hide'); location.reload(); } } }); }); $('#deleteModal').modal('show'); }
function addAnswer() { const count = $('#answerOptions .answer-item').length + 1; $('#answerOptions').append(getAnswerItemHtml(count)); }
function removeAnswer(el) { if ($('#answerOptions .answer-item').length > 2) { $(el).closest('.answer-item').remove(); } else { toastr.warning('At least 2 answers required'); } }
function getAnswerItemHtml(num, value = '', isCorrect = false) {
    return `<div class="answer-item${isCorrect ? ' is-correct' : ''}">
        <div class="correct-check-wrapper${isCorrect ? ' checked' : ''}" onclick="toggleCorrect(this)">
            <input type="checkbox" class="correct-check" title="Mark as correct"${isCorrect ? ' checked' : ''}>
            <span class="correct-label">Correct</span>
        </div>
        <input type="text" class="form-control form-control-sm" value="${value}" placeholder="Answer option ${num}">
        <i class="bx bx-x remove-answer" onclick="removeAnswer(this)"></i>
    </div>`;
}
function toggleCorrect(wrapper) {
    const checkbox = $(wrapper).find('.correct-check');
    const isChecked = checkbox.is(':checked');
    checkbox.prop('checked', !isChecked);
    $(wrapper).toggleClass('checked', !isChecked);
    $(wrapper).closest('.answer-item').toggleClass('is-correct', !isChecked);
}

// ==================== HELPERS ====================
function initTinyMCE(content) { if (tinymceEditor) tinymce.remove('#contentBody'); tinymce.init({ selector: '#contentBody', height: 280, plugins: ['advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'searchreplace', 'visualblocks', 'code', 'fullscreen', 'media', 'table', 'help', 'wordcount'], toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | image media | removeformat', content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; font-size: 14px; }', images_upload_handler: (blobInfo) => new Promise((resolve, reject) => { const fd = new FormData(); fd.append('file', blobInfo.blob(), blobInfo.filename()); fd.append('_token', '{{ csrf_token() }}'); $.ajax({ url: '/upload-image', method: 'POST', data: fd, processData: false, contentType: false, success: r => resolve(r.location), error: () => reject('Upload failed') }); }), setup: editor => { tinymceEditor = editor; editor.on('init', () => editor.setContent(content)); } }); }
$('#chapterCoverInput').on('change', function() { if (this.files[0]) uploadCoverPhoto(this.files[0], 'chapterCoverUpload', 'chapterCoverPhoto'); });
$('#topicCoverInput').on('change', function() { if (this.files[0]) uploadCoverPhoto(this.files[0], 'topicCoverUpload', 'topicCoverPhoto'); });
function uploadCoverPhoto(file, uploadId, inputId) { const fd = new FormData(); fd.append('file', file); fd.append('_token', '{{ csrf_token() }}'); $.ajax({ url: '/upload-image', method: 'POST', data: fd, processData: false, contentType: false, success: r => { $('#' + inputId).val(r.location); showCoverPreview(uploadId, r.location); }, error: () => toastr.error('Upload failed') }); }
function showCoverPreview(uploadId, url) { $('#' + uploadId).addClass('has-image').html(`<img src="${url}" class="cover-photo-preview"><button type="button" class="cover-photo-remove" onclick="event.stopPropagation(); removeCoverPhoto('${uploadId}')"><i class="bx bx-x"></i></button>`); }
function resetCoverUpload(uploadId) { $('#' + uploadId).removeClass('has-image').html('<i class="bx bx-image-add text-muted" style="font-size: 24px;"></i><small class="text-muted">Click to upload</small>'); }
function removeCoverPhoto(uploadId) { if (uploadId === 'chapterCoverUpload') { $('#chapterCoverPhoto, #chapterCoverInput').val(''); } else if (uploadId === 'topicCoverUpload') { $('#topicCoverPhoto, #topicCoverInput').val(''); } resetCoverUpload(uploadId); }
$('#questionPhotoInput').on('change', function() { if (this.files[0]) { const reader = new FileReader(); reader.onload = e => showMediaPreview('questionPhotoUpload', e.target.result); reader.readAsDataURL(this.files[0]); } });
function showMediaPreview(uploadId, url) { $('#' + uploadId).addClass('has-media').html(`<img src="${url}" class="media-preview"><button type="button" class="media-remove" onclick="event.stopPropagation(); resetMediaUpload('${uploadId}')"><i class="bx bx-x"></i></button>`); }
function resetMediaUpload(uploadId) { $('#' + uploadId).removeClass('has-media').html('<i class="bx bx-image-add text-muted" style="font-size: 24px;"></i><small class="text-muted">Click to upload</small>'); if (uploadId === 'questionPhotoUpload') { $('#questionPhotoInput, #questionPhotoPath').val(''); } }
$('#photosInput').on('change', function() { Array.from(this.files).forEach(f => { pendingPhotos.push(f); const r = new FileReader(); r.onload = e => addPhotoToGallery(e.target.result, pendingPhotos.length - 1, true); r.readAsDataURL(f); }); this.value = ''; });
function addPhotoToGallery(url, index, isPending) { $('#photosGallery').append(`<div class="photo-item" data-index="${index}" data-pending="${isPending}"><img src="${url}"><button type="button" class="remove-photo" onclick="removePhoto(${index}, ${isPending})"><i class="bx bx-x"></i></button></div>`); }
function renderPhotosGallery() { $('#photosGallery').html(''); existingPhotos.forEach((url, i) => addPhotoToGallery(url, i, false)); }
function removePhoto(index, isPending) { isPending ? pendingPhotos.splice(index, 1) : existingPhotos.splice(index, 1); renderPhotosGallery(); pendingPhotos.forEach((f, i) => { const r = new FileReader(); r.onload = e => addPhotoToGallery(e.target.result, i, true); r.readAsDataURL(f); }); }
$('#resourcesInput').on('change', function() { Array.from(this.files).forEach(f => { pendingResources.push(f); addResourceToList(f.name, pendingResources.length - 1, true); }); this.value = ''; });
function addResourceToList(name, index, isPending, id = null) { const ext = name.split('.').pop().toLowerCase(); const icons = { 'pdf': 'bx bxs-file-pdf text-danger', 'doc': 'bx bxs-file-doc text-primary', 'docx': 'bx bxs-file-doc text-primary', 'xls': 'bx bxs-file text-success', 'xlsx': 'bx bxs-file text-success', 'zip': 'bx bxs-file-archive text-secondary', 'rar': 'bx bxs-file-archive text-secondary' }; $('#resourcesList').append(`<div class="resource-item" data-index="${index}" data-pending="${isPending}" data-id="${id}"><i class="${icons[ext] || 'bx bxs-file text-muted'}"></i><span class="resource-name">${name}</span><button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeResource(${index}, ${isPending})"><i class="bx bx-x"></i></button></div>`); }
function renderResourcesList() { $('#resourcesList').html(''); existingResources.forEach((r, i) => addResourceToList(r.fileName, i, false, r.id)); }
function removeResource(index, isPending) { isPending ? pendingResources.splice(index, 1) : existingResources.splice(index, 1); renderResourcesList(); pendingResources.forEach((f, i) => addResourceToList(f.name, i, true)); }
$('#contentYoutubeUrl').on('input', updateYoutubePreview);
function updateYoutubePreview() { const url = $('#contentYoutubeUrl').val(); const match = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i); $('#youtubePreview').html(match ? `<div class="ratio ratio-16x9"><iframe src="https://www.youtube.com/embed/${match[1]}" allowfullscreen></iframe></div>` : ''); }
$('#questionVideoUrl').on('input', updateQuestionVideoPreview);
function updateQuestionVideoPreview() { const url = $('#questionVideoUrl').val(); const match = url.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i); $('#questionVideoPreview').html(match ? `<div class="ratio ratio-16x9"><iframe src="https://www.youtube.com/embed/${match[1]}" allowfullscreen></iframe></div>` : ''); }
function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }

// ==================== COMMENTS FUNCTIONALITY ====================
const emojiList = ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '😚', '😋', '😛', '😜', '🤪', '😝', '🤗', '🤔', '👍', '👎', '👏', '🙌', '❤️', '🧡', '💛', '💚', '💙', '💜', '⭐', '🔥', '💯', '✅', '❌', '❓', '❗', '💡', '🎉', '🎊'];
let inlineGifTimeout = null;

// Load comment counts on page load
$(document).ready(function() {
    loadAllCommentCounts();
});

function loadAllCommentCounts() {
    // Load total unanswered count for header
    loadTotalUnansweredCount();

    // For content items
    $('.content-comment-count').each(function() {
        const contentId = $(this).data('id');
        loadContentCommentCount(contentId);
    });
    // For questionnaire items
    $('.questionnaire-comment-count').each(function() {
        const qId = $(this).data('id');
        loadQuestionnaireCommentCount(qId);
    });
}

function loadContentCommentCount(contentId) {
    $.get(`/api/anisenso/contents/${contentId}/comments`, function(r) {
        if (r.success) {
            // Count comments from students that have no replies (unreplied comments)
            const unreplied = r.data.filter(c => {
                if (c.authorType === 'admin') return false; // Skip admin comments
                const replies = c.all_replies || c.replies || [];
                return replies.length === 0; // No replies means unreplied
            }).length;
            const $badge = $(`.content-comment-count[data-id="${contentId}"]`);
            if (unreplied > 0) {
                $badge.text(unreplied).show();
            } else {
                $badge.hide();
            }
        }
    });
}

function loadQuestionnaireCommentCount(qId) {
    // Use negative contentId for questionnaire comments
    $.get(`/api/anisenso/contents/-${qId}/comments`, function(r) {
        if (r.success) {
            // Count comments from students that have no replies (unreplied comments)
            const unreplied = r.data.filter(c => {
                if (c.authorType === 'admin') return false;
                const replies = c.all_replies || c.replies || [];
                return replies.length === 0;
            }).length;
            const $badge = $(`.questionnaire-comment-count[data-id="${qId}"]`);
            if (unreplied > 0) {
                $badge.text(unreplied).show();
            } else {
                $badge.hide();
            }
        }
    });
}

function toggleContentComments(contentId, type) {
    const $section = $(`#content-comments-${contentId}`);
    if ($section.is(':visible')) {
        $section.slideUp(200);
    } else {
        $section.slideDown(200);
        loadContentComments(contentId);
    }
}

function toggleQuestionnaireComments(questionnaireId) {
    const $section = $(`#questionnaire-comments-${questionnaireId}`);
    if ($section.is(':visible')) {
        $section.slideUp(200);
    } else {
        $section.slideDown(200);
        loadQuestionnaireComments(questionnaireId);
    }
}

// Update counter bubbles locally without reloading
function updateCounterLocally(itemId, itemType, action) {
    const $badge = $(`#${itemType}-comment-badge-${itemId}`);
    let currentCount = parseInt($badge.text()) || 0;

    if (action === 'reply') {
        // Replying to a comment decreases unanswered count
        currentCount = Math.max(0, currentCount - 1);
    } else if (action === 'add') {
        // Adding a new admin comment doesn't change the unanswered count
        // (admin comments are not counted as unanswered)
    } else if (action === 'delete') {
        // Deleting might need a full reload to recalculate
        loadAllCommentCounts();
        return;
    }

    // Update badge
    if (currentCount > 0) {
        $badge.text(currentCount).show();
    } else {
        $badge.hide();
    }

    // Update total unanswered count in header
    const $totalBadge = $('#unansweredCount');
    let totalCount = parseInt($totalBadge.text()) || 0;
    if (action === 'reply') {
        totalCount = Math.max(0, totalCount - 1);
        $totalBadge.text(totalCount);
        if (totalCount === 0) {
            $totalBadge.hide();
        }
    }
}

function loadContentComments(contentId) {
    const $list = $(`#content-comments-list-${contentId}`);
    $list.html('<div class="text-center py-3"><i class="bx bx-loader-alt bx-spin"></i></div>');

    console.log('Loading comments for contentId:', contentId);

    $.get(`/api/anisenso/contents/${contentId}/comments`, function(r) {
        console.log('API Response:', r);
        if (r.success) {
            console.log('Comments count:', r.data.length);
            if (r.data.length > 0) {
                console.log('First comment:', r.data[0]);
                console.log('First comment all_replies:', r.data[0].all_replies);
            }
            renderMiniComments(r.data, $list, contentId, 'content');
        }
    }).fail(function(xhr) {
        console.error('Failed to load comments:', xhr.responseText);
        $list.html('<div class="text-center text-danger py-2"><i class="bx bx-error"></i> Failed to load</div>');
    });
}

function loadQuestionnaireComments(questionnaireId) {
    const $list = $(`#questionnaire-comments-list-${questionnaireId}`);
    $list.html('<div class="text-center py-3"><i class="bx bx-loader-alt bx-spin"></i></div>');

    // Use negative contentId for questionnaire comments
    $.get(`/api/anisenso/contents/-${questionnaireId}/comments`, function(r) {
        if (r.success) {
            renderMiniComments(r.data, $list, questionnaireId, 'questionnaire');
        }
    }).fail(function() {
        $list.html('<div class="text-center text-danger py-2"><i class="bx bx-error"></i> Failed to load</div>');
    });
}

function renderMiniComments(comments, $container, itemId, itemType) {
    if (!comments || comments.length === 0) {
        $container.html('<div class="no-comments-mini"><i class="bx bx-message-square-x"></i><small>No comments yet</small></div>');
        return;
    }

    let html = '';
    comments.forEach(comment => {
        html += renderMiniCommentItem(comment, itemId, itemType);
    });
    $container.html(html);
}

function renderMiniCommentItem(comment, itemId, itemType) {
    const avatar = generateMiniAvatar(comment.authorName);
    const statusClass = comment.isAnswered ? 'answered' : 'unanswered';
    const timeAgo = formatMiniTimeAgo(comment.created_at);
    const fullDate = formatFullDateTime(comment.updated_at || comment.created_at);
    const likesCount = comment.likesCount || 0;
    const heartsCount = comment.heartsCount || 0;

    // Check localStorage for user reactions
    const userReactions = JSON.parse(localStorage.getItem(`anisenso_reactions_${comment.id}`) || '{}');
    const likedClass = (likesCount > 0 ? 'liked' : '') + (userReactions.like ? ' user-reacted' : '');
    const heartedClass = (heartsCount > 0 ? 'hearted' : '') + (userReactions.heart ? ' user-reacted' : '');

    let html = `<div class="mini-comment ${statusClass}" data-comment-id="${comment.id}">
        <img src="${avatar}" class="mini-comment-avatar" alt="">
        <div class="mini-comment-content">
            <div class="mini-comment-header">
                <span class="mini-comment-author">${escapeHtml(comment.authorName)} ${comment.authorType === 'admin' ? '<span class="badge bg-success" style="font-size:9px;">Admin</span>' : ''}</span>
                <span class="mini-comment-time" title="${fullDate}">${timeAgo}</span>
                <span class="mini-comment-date">${fullDate}</span>
            </div>
            <div class="mini-comment-text">${formatMiniCommentText(comment.commentText)}</div>
            <div class="mini-comment-actions">
                <button class="btn btn-outline-success btn-sm" onclick="showMiniReplyForm(${comment.id}, ${itemId}, '${itemType}')"><i class="bx bx-reply"></i> Reply</button>
                <button class="btn btn-outline-danger btn-sm" onclick="deleteMiniComment(${comment.id}, ${itemId}, '${itemType}')"><i class="bx bx-trash"></i></button>
                <div class="mini-comment-reactions">
                    <button class="reaction-btn ${likedClass}" onclick="addReaction(${comment.id}, 'like', ${itemId}, '${itemType}')" title="${userReactions.like ? 'You liked this' : 'Like'}">
                        <i class="bx bx-like"></i> <span class="reaction-count" id="likes-${comment.id}">${likesCount}</span>
                    </button>
                    <button class="reaction-btn ${heartedClass}" onclick="addReaction(${comment.id}, 'heart', ${itemId}, '${itemType}')" title="${userReactions.heart ? 'You hearted this' : 'Heart'}">
                        <i class="bx bx-heart"></i> <span class="reaction-count" id="hearts-${comment.id}">${heartsCount}</span>
                    </button>
                </div>
            </div>`;

    // Render replies (Laravel returns all_replies in snake_case)
    const replies = comment.all_replies || comment.replies || [];
    if (replies.length > 0) {
        html += '<div class="mini-replies">';
        replies.forEach(reply => {
            html += renderMiniReplyItem(reply, itemId, itemType, 1);
        });
        html += '</div>';
    }

    // Reply form with emoji/GIF
    html += `<div class="mini-reply-form mt-2" id="reply-form-${comment.id}" style="display: none; flex-direction: column;">
        <div class="reply-input-container" style="width: 100%;">
            <textarea class="mini-reply-input" id="reply-input-${comment.id}" placeholder="Write a reply... (Press Enter to send, Shift+Enter for new line)" rows="3" onkeypress="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();sendMiniReply(${comment.id}, ${itemId}, '${itemType}');}"></textarea>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <div class="reply-input-tools" style="position: static; transform: none; display: flex; gap: 8px;">
                <button onclick="toggleReplyEmojiPicker(${comment.id}, event)" title="Emoji" style="padding: 4px 10px;"><i class="bx bx-smile"></i></button>
                <button onclick="toggleReplyGifPicker(${comment.id}, event)" title="GIF" style="padding: 4px 10px;"><i class="bx bx-image"></i></button>
            </div>
            <button class="btn btn-sm btn-success mini-reply-btn" onclick="sendMiniReply(${comment.id}, ${itemId}, '${itemType}')"><i class="bx bx-send me-1"></i>Send</button>
        </div>
        <div class="inline-emoji-picker" id="reply-emoji-picker-${comment.id}"></div>
        <div class="inline-gif-picker" id="reply-gif-picker-${comment.id}">
            <div class="inline-gif-search"><input type="text" placeholder="Search GIFs..." onkeyup="searchReplyGifs(this.value, ${comment.id})"></div>
            <div class="inline-gif-grid" id="reply-gif-grid-${comment.id}"></div>
        </div>
    </div>`;

    html += '</div></div>';
    return html;
}

const MAX_REPLY_DEPTH = 2; // Limit to 3 levels total (root + 2 reply levels)

function renderMiniReplyItem(reply, itemId, itemType, depth = 1) {
    const avatar = generateMiniAvatar(reply.authorName);
    const isAdmin = reply.authorType === 'admin';
    const timeAgo = formatMiniTimeAgo(reply.created_at);
    const fullDate = formatFullDateTime(reply.updated_at || reply.created_at);
    const likesCount = reply.likesCount || 0;
    const heartsCount = reply.heartsCount || 0;
    const canReply = depth < MAX_REPLY_DEPTH;

    // Check localStorage for user reactions
    const userReactions = JSON.parse(localStorage.getItem(`anisenso_reactions_${reply.id}`) || '{}');
    const likedClass = (likesCount > 0 ? 'liked' : '') + (userReactions.like ? ' user-reacted' : '');
    const heartedClass = (heartsCount > 0 ? 'hearted' : '') + (userReactions.heart ? ' user-reacted' : '');

    let html = `<div class="mini-reply-item ${isAdmin ? 'admin' : ''}" data-reply-id="${reply.id}">
        <div class="d-flex align-items-start">
            <img src="${avatar}" class="mini-comment-avatar" style="width: 24px; height: 24px;" alt="">
            <div class="flex-grow-1 ms-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <span class="mini-comment-author" style="font-size: 11px;">${escapeHtml(reply.authorName)} ${isAdmin ? '<span class="badge bg-success" style="font-size:8px;">Admin</span>' : ''}</span>
                    <span class="mini-comment-time" title="${fullDate}">${timeAgo}</span>
                    <span class="mini-comment-date">${fullDate}</span>
                </div>
                <div class="mini-comment-text" style="font-size: 12px;">${formatMiniCommentText(reply.commentText)}</div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    ${canReply ? `<button class="btn btn-link btn-sm text-success p-0" style="font-size: 10px;" onclick="showMiniReplyForm(${reply.id}, ${itemId}, '${itemType}')"><i class="bx bx-reply"></i> Reply</button>` : ''}
                    <button class="btn btn-link btn-sm text-danger p-0" style="font-size: 10px;" onclick="deleteMiniComment(${reply.id}, ${itemId}, '${itemType}')"><i class="bx bx-trash"></i></button>
                    <div class="mini-comment-reactions" style="font-size: 10px; margin-left: auto;">
                        <button class="reaction-btn ${likedClass}" style="padding: 1px 6px; font-size: 10px;" onclick="addReaction(${reply.id}, 'like', ${itemId}, '${itemType}')" title="${userReactions.like ? 'You liked this' : 'Like'}">
                            <i class="bx bx-like"></i> <span id="likes-${reply.id}">${likesCount}</span>
                        </button>
                        <button class="reaction-btn ${heartedClass}" style="padding: 1px 6px; font-size: 10px;" onclick="addReaction(${reply.id}, 'heart', ${itemId}, '${itemType}')" title="${userReactions.heart ? 'You hearted this' : 'Heart'}">
                            <i class="bx bx-heart"></i> <span id="hearts-${reply.id}">${heartsCount}</span>
                        </button>
                    </div>
                </div>`;

    // Render nested replies if they exist
    const nestedReplies = reply.all_replies || reply.replies || [];
    if (nestedReplies.length > 0) {
        html += '<div class="mini-replies" style="margin-left: 20px; margin-top: 8px;">';
        nestedReplies.forEach(nestedReply => {
            html += renderMiniReplyItem(nestedReply, itemId, itemType, depth + 1);
        });
        html += '</div>';
    }

    // Reply form for this reply (if can reply)
    if (canReply) {
        html += `<div class="mini-reply-form mt-2" id="reply-form-${reply.id}" style="display: none; flex-direction: column;">
            <div class="reply-input-container" style="width: 100%;">
                <textarea class="mini-reply-input" id="reply-input-${reply.id}" placeholder="Write a reply..." rows="2" style="font-size: 12px;" onkeypress="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();sendMiniReply(${reply.id}, ${itemId}, '${itemType}');}"></textarea>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <div class="reply-input-tools" style="display: flex; gap: 6px;">
                    <button onclick="toggleReplyEmojiPicker(${reply.id}, event)" title="Emoji" style="padding: 2px 8px; font-size: 10px;"><i class="bx bx-smile"></i></button>
                    <button onclick="toggleReplyGifPicker(${reply.id}, event)" title="GIF" style="padding: 2px 8px; font-size: 10px;"><i class="bx bx-image"></i></button>
                </div>
                <button class="btn btn-sm btn-success" style="font-size: 10px;" onclick="sendMiniReply(${reply.id}, ${itemId}, '${itemType}')"><i class="bx bx-send"></i> Send</button>
            </div>
            <div class="inline-emoji-picker" id="reply-emoji-picker-${reply.id}"></div>
            <div class="inline-gif-picker" id="reply-gif-picker-${reply.id}">
                <div class="inline-gif-search"><input type="text" placeholder="Search GIFs..." onkeyup="searchReplyGifs(this.value, ${reply.id})"></div>
                <div class="inline-gif-grid" id="reply-gif-grid-${reply.id}"></div>
            </div>
        </div>`;
    }

    html += `</div>
        </div>
    </div>`;
    return html;
}

function generateMiniAvatar(name) {
    const initial = name ? name.charAt(0).toUpperCase() : '?';
    const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
    const color = colors[initial.charCodeAt(0) % colors.length];
    return `data:image/svg+xml,${encodeURIComponent(`<svg xmlns='http://www.w3.org/2000/svg' width='32' height='32'><rect fill='${color}' width='32' height='32'/><text x='50%' y='50%' dy='.35em' fill='white' text-anchor='middle' font-family='Arial' font-size='14' font-weight='bold'>${initial}</text></svg>`)}`;
}

function formatMiniTimeAgo(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return date.toLocaleDateString();
}

function formatFullDateTime(dateStr) {
    const date = new Date(dateStr);
    const options = {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    };
    return date.toLocaleDateString('en-US', options);
}

function formatMiniCommentText(text) {
    if (!text) return '';
    // Handle GIF tags
    text = text.replace(/\[gif:(https?:\/\/[^\]]+)\]/g, '<img src="$1" class="gif-image" alt="GIF">');
    // Highlight @mentions with bracket format: @[Full Name]
    text = text.replace(/@\[([^\]]+)\]/g, '<span class="mention-tag">@$1</span>');
    // Also support legacy @mentions without brackets (single word names)
    text = text.replace(/@([a-zA-Z0-9]+)(\s|$|,|\.)/g, '<span class="mention-tag">@$1</span>$2');
    // Convert newlines to breaks
    text = text.replace(/\n/g, '<br>');
    return text;
}

function showMiniReplyForm(commentId, itemId, itemType) {
    $(`#reply-form-${commentId}`).slideToggle(200);
    $(`#reply-input-${commentId}`).focus();
}

function sendMiniReply(commentId, itemId, itemType) {
    const $input = $(`#reply-input-${commentId}`);
    const text = $input.val().trim();
    if (!text) { toastr.error('Please enter a reply'); return; }

    console.log('Sending reply to commentId:', commentId, 'itemId:', itemId, 'itemType:', itemType);

    const $btn = $(`#reply-form-${commentId} .btn-success`);
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

    $.ajax({
        url: `/api/anisenso/comments/${commentId}/reply`,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}', commentText: text },
        success: function(r) {
            console.log('Reply API Response:', r);
            if (r.success) {
                console.log('Reply saved with ID:', r.data.id, 'parentCommentId:', r.data.parentCommentId);
                toastr.success('Reply sent');
                $input.val('');

                // Dynamically insert the new reply with slide-in animation
                const $parentComment = $(`[data-comment-id="${commentId}"], [data-reply-id="${commentId}"]`).first();

                // Find the content container (different structure for comments vs replies)
                let $content;
                if ($parentComment.hasClass('mini-comment')) {
                    $content = $parentComment.find('> .mini-comment-content');
                } else {
                    $content = $parentComment.find('> .d-flex > .flex-grow-1');
                }

                // Find existing replies container or create one
                let $repliesContainer = $content.find('> .mini-replies').first();
                if ($repliesContainer.length === 0) {
                    $content.append('<div class="mini-replies" style="margin-top: 8px;"></div>');
                    $repliesContainer = $content.find('> .mini-replies').first();
                }

                // Calculate depth for the new reply
                const currentDepth = $parentComment.parents('.mini-replies').length;
                const newDepth = currentDepth + 1;

                // Render the new reply HTML
                const newReplyHtml = renderMiniReplyItem(r.data, itemId, itemType, newDepth);
                const $newReply = $(newReplyHtml).hide();

                // Append and slide in with highlight effect
                $repliesContainer.append($newReply);
                $newReply.addClass('new-highlight').slideDown(300, function() {
                    // Remove highlight after animation
                    setTimeout(() => {
                        $(this).removeClass('new-highlight');
                    }, 1500);
                });

                // Hide the reply form
                $(`#reply-form-${commentId}`).slideUp(200);

                // Update counter bubbles locally (decrease unanswered since we replied)
                updateCounterLocally(itemId, itemType, 'reply');
            }
        },
        error: function() { toastr.error('Failed to send reply'); },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-send me-1"></i>Send');
        }
    });
}

function sendInlineReply(itemId, itemType, parentId) {
    const $input = $(`#${itemType}-reply-input-${itemId}`);
    const text = $input.val().trim();
    if (!text) { toastr.error('Please enter a comment'); return; }

    // Use negative contentId for questionnaire comments to differentiate from content comments
    const contentIdValue = itemType === 'content' ? itemId : -itemId;

    const $btn = $input.closest('.mini-comment-form, .d-flex').find('.btn-success, .mini-reply-btn');
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

    const data = {
        _token: '{{ csrf_token() }}',
        courseId: courseId,
        contentId: contentIdValue,
        commentText: text,
        authorName: '{{ Auth::user()->name ?? "Admin" }}',
        authorType: 'admin'
    };

    $.ajax({
        url: '/api/anisenso/comments',
        method: 'POST',
        data: data,
        success: function(r) {
            if (r.success) {
                toastr.success('Comment added');
                $input.val('');

                // Get the comments list container
                const $list = $(`#${itemType}-comments-list-${itemId}`);

                // Check if "no comments" placeholder exists
                const $noComments = $list.find('.no-comments-mini');
                if ($noComments.length > 0) {
                    $noComments.remove();
                }

                // Render the new comment HTML
                const newCommentHtml = renderMiniCommentItem(r.data, itemId, itemType);
                const $newComment = $(newCommentHtml).hide();

                // Prepend and slide in with highlight effect (new comments appear at top)
                $list.prepend($newComment);
                $newComment.addClass('new-highlight').slideDown(300, function() {
                    // Remove highlight after animation
                    setTimeout(() => {
                        $(this).removeClass('new-highlight');
                    }, 1500);
                });

                // Update counter locally
                updateCounterLocally(itemId, itemType, 'add');
            }
        },
        error: function() { toastr.error('Failed to add comment'); },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-send"></i>');
        }
    });
}

function sendQuestionnaireReply(questionnaireId, parentId) {
    sendInlineReply(questionnaireId, 'questionnaire', parentId);
}

function markCommentAnswered(commentId, itemId, itemType) {
    $.ajax({
        url: `/api/anisenso/comments/${commentId}`,
        method: 'PUT',
        data: { _token: '{{ csrf_token() }}', isAnswered: true },
        success: function(r) {
            if (r.success) {
                toastr.success('Marked as answered');
                if (itemType === 'content') loadContentComments(itemId);
                else loadQuestionnaireComments(itemId);
                loadAllCommentCounts();
                loadTotalUnansweredCount();
            } else {
                toastr.error(r.message || 'Failed to mark as answered');
            }
        },
        error: function(xhr) {
            console.error('Mark answered error:', xhr);
            toastr.error(xhr.responseJSON?.message || 'Failed to mark as answered');
        }
    });
}

let commentToDelete = null;

function deleteMiniComment(commentId, itemId, itemType) {
    commentToDelete = { id: commentId, itemId: itemId, itemType: itemType };
    $('#deleteCommentModal').modal('show');
}

function confirmDeleteMiniComment() {
    if (!commentToDelete) return;

    const $btn = $('#confirmDeleteComment');
    const deleteInfo = { ...commentToDelete }; // Copy before clearing
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

    $.ajax({
        url: `/api/anisenso/comments/${deleteInfo.id}`,
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(r) {
            if (r.success) {
                $('#deleteCommentModal').modal('hide');
                toastr.success('Comment deleted');

                // Find and remove the comment/reply with fade-out animation
                const $comment = $(`[data-comment-id="${deleteInfo.id}"], [data-reply-id="${deleteInfo.id}"]`).first();
                if ($comment.length) {
                    // Check if this is a root comment or a reply
                    const isRootComment = $comment.hasClass('mini-comment');

                    $comment.addClass('delete-highlight').slideUp(300, function() {
                        $(this).remove();

                        // Check if the container is now empty
                        const $list = $(`#${deleteInfo.itemType}-comments-list-${deleteInfo.itemId}`);
                        if ($list.find('.mini-comment').length === 0) {
                            $list.html('<div class="no-comments-mini"><i class="bx bx-message-square-x"></i><small>No comments yet</small></div>');
                        }
                    });

                    // Reload counts after delete (since counting replies is complex)
                    loadAllCommentCounts();
                    loadTotalUnansweredCount();
                }
            } else {
                toastr.error(r.message || 'Failed to delete');
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to delete');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
            commentToDelete = null;
        }
    });
}

// Bind delete confirmation button
$(document).on('click', '#confirmDeleteComment', confirmDeleteMiniComment);

// Inline Emoji Picker
function toggleInlineEmojiPicker(itemId, itemType, event) {
    const $picker = $(`#emoji-picker-${itemType}-${itemId}`);
    $('.inline-emoji-picker, .inline-gif-picker').not($picker).removeClass('show');

    if (!$picker.hasClass('show')) {
        let html = '<div class="inline-emoji-grid">';
        emojiList.forEach(e => {
            html += `<div class="inline-emoji-item" onclick="insertInlineEmoji('${e}', ${itemId}, '${itemType}')">${e}</div>`;
        });
        html += '</div>';
        $picker.html(html);

        // Position the picker near the button
        if (event) {
            const $btn = $(event.target).closest('button');
            const btnOffset = $btn.offset();
            $picker.css({
                top: (btnOffset.top - 180) + 'px',
                left: btnOffset.left + 'px'
            });
        }
    }
    $picker.toggleClass('show');
}

function insertInlineEmoji(emoji, itemId, itemType) {
    const $input = $(`#${itemType}-reply-input-${itemId}`);
    $input.val($input.val() + emoji);
    $input.focus();
    $('.inline-emoji-picker').removeClass('show');
}

// Inline GIF Picker
function toggleInlineGifPicker(itemId, itemType, event) {
    const $picker = $(`#gif-picker-${itemType}-${itemId}`);
    $('.inline-emoji-picker, .inline-gif-picker').not($picker).removeClass('show');

    if (!$picker.hasClass('show')) {
        loadInlineTrendingGifs(itemId, itemType);

        // Position the picker near the button
        if (event) {
            const $btn = $(event.target).closest('button');
            const btnOffset = $btn.offset();
            $picker.css({
                top: (btnOffset.top - 250) + 'px',
                left: btnOffset.left + 'px'
            });
        }
    }
    $picker.toggleClass('show');
}

function loadInlineTrendingGifs(itemId, itemType) {
    const $grid = $(`#gif-grid-${itemType}-${itemId}`);
    $grid.html('<div class="text-center py-2"><i class="bx bx-loader-alt bx-spin"></i></div>');

    $.get('/api/anisenso/gifs/trending', { limit: 12 }, function(r) {
        if (r.success) renderInlineGifs(r.data, itemId, itemType);
    });
}

function searchInlineGifs(query, itemId, itemType) {
    clearTimeout(inlineGifTimeout);
    if (!query.trim()) { loadInlineTrendingGifs(itemId, itemType); return; }

    inlineGifTimeout = setTimeout(() => {
        const $grid = $(`#gif-grid-${itemType}-${itemId}`);
        $grid.html('<div class="text-center py-2"><i class="bx bx-loader-alt bx-spin"></i></div>');

        $.get('/api/anisenso/gifs/search', { q: query, limit: 12 }, function(r) {
            if (r.success) renderInlineGifs(r.data, itemId, itemType);
        });
    }, 300);
}

function renderInlineGifs(gifs, itemId, itemType) {
    const $grid = $(`#gif-grid-${itemType}-${itemId}`);
    if (!gifs || gifs.length === 0) {
        $grid.html('<div class="text-center py-2 text-secondary">No GIFs found</div>');
        return;
    }
    let html = '';
    gifs.forEach(gif => {
        html += `<div class="inline-gif-item" onclick="insertInlineGif('${gif.url}', ${itemId}, '${itemType}')"><img src="${gif.preview}" alt="" loading="lazy"></div>`;
    });
    $grid.html(html);
}

function insertInlineGif(url, itemId, itemType) {
    const $input = $(`#${itemType}-reply-input-${itemId}`);
    const text = $input.val();
    $input.val(text + (text ? ' ' : '') + `[gif:${url}]`);
    $input.focus();
    $('.inline-gif-picker').removeClass('show');
}

// ============ REACTION FUNCTIONS ============
// Check if user has already reacted to a comment
function hasUserReacted(commentId, type) {
    const key = `anisenso_reactions_${commentId}`;
    const reactions = JSON.parse(localStorage.getItem(key) || '{}');
    return reactions[type] === true;
}

// Save user reaction to localStorage
function saveUserReaction(commentId, type) {
    const key = `anisenso_reactions_${commentId}`;
    const reactions = JSON.parse(localStorage.getItem(key) || '{}');
    reactions[type] = true;
    localStorage.setItem(key, JSON.stringify(reactions));
}

// Remove user reaction from localStorage
function removeUserReaction(commentId, type) {
    const key = `anisenso_reactions_${commentId}`;
    const reactions = JSON.parse(localStorage.getItem(key) || '{}');
    delete reactions[type];
    localStorage.setItem(key, JSON.stringify(reactions));
}

// Check and apply user's previous reactions on page load
function applyUserReactions(commentId) {
    const key = `anisenso_reactions_${commentId}`;
    const reactions = JSON.parse(localStorage.getItem(key) || '{}');

    if (reactions.like) {
        $(`#likes-${commentId}`).closest('.reaction-btn').addClass('liked user-reacted');
    }
    if (reactions.heart) {
        $(`#hearts-${commentId}`).closest('.reaction-btn').addClass('hearted user-reacted');
    }
}

function addReaction(commentId, type, itemId, itemType) {
    const alreadyReacted = hasUserReacted(commentId, type);
    const action = alreadyReacted ? 'remove' : 'add';

    $.ajax({
        url: `/api/anisenso/comments/${commentId}/reaction`,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}', type: type, action: action },
        success: function(r) {
            if (r.success) {
                // Update localStorage
                if (action === 'add') {
                    saveUserReaction(commentId, type);
                } else {
                    removeUserReaction(commentId, type);
                }

                $(`#likes-${commentId}`).text(r.data.likesCount);
                $(`#hearts-${commentId}`).text(r.data.heartsCount);

                // Update button styles
                const $btn = $(`#${type === 'like' ? 'likes' : 'hearts'}-${commentId}`).closest('.reaction-btn');

                if (action === 'add') {
                    $btn.addClass(type === 'like' ? 'liked' : 'hearted').addClass('user-reacted');
                    $btn.attr('title', `You ${type === 'like' ? 'liked' : 'hearted'} this`);
                    toastr.success(type === 'like' ? 'Liked!' : 'Hearted!');
                } else {
                    $btn.removeClass(type === 'like' ? 'liked' : 'hearted').removeClass('user-reacted');
                    $btn.attr('title', type === 'like' ? 'Like' : 'Heart');
                    toastr.info(type === 'like' ? 'Like removed' : 'Heart removed');
                }

                // Update liked/hearted class based on count
                if (r.data.likesCount > 0) {
                    $(`#likes-${commentId}`).closest('.reaction-btn').addClass('liked');
                } else {
                    $(`#likes-${commentId}`).closest('.reaction-btn').removeClass('liked');
                }
                if (r.data.heartsCount > 0) {
                    $(`#hearts-${commentId}`).closest('.reaction-btn').addClass('hearted');
                } else {
                    $(`#hearts-${commentId}`).closest('.reaction-btn').removeClass('hearted');
                }
            }
        },
        error: function() { toastr.error('Failed to update reaction'); }
    });
}

// ============ TOTAL UNANSWERED COUNT ============
function loadTotalUnansweredCount() {
    $.get(`/api/anisenso/courses/${courseId}/comments/unanswered-count`, function(r) {
        if (r.success && r.count > 0) {
            $('#total-unanswered-count').text(r.count);
            $('#total-comments-indicator').css('display', 'flex').show();
        } else {
            $('#total-comments-indicator').hide();
        }
    });
}

// ============ REPLY EMOJI/GIF PICKERS ============
let replyGifTimeout;

function toggleReplyEmojiPicker(commentId, event) {
    const $picker = $(`#reply-emoji-picker-${commentId}`);
    $('.inline-emoji-picker, .inline-gif-picker').not($picker).removeClass('show');

    if (!$picker.hasClass('show')) {
        let html = '<div class="inline-emoji-grid">';
        emojiList.forEach(e => {
            html += `<div class="inline-emoji-item" onclick="insertReplyEmoji('${e}', ${commentId})">${e}</div>`;
        });
        html += '</div>';
        $picker.html(html);

        // Position the picker near the button
        const $btn = $(event ? event.target : `#reply-form-${commentId} .reply-input-tools button:first`).closest('button');
        const btnOffset = $btn.offset();
        const btnHeight = $btn.outerHeight();
        $picker.css({
            top: (btnOffset.top - $picker.outerHeight() - 5) + 'px',
            left: btnOffset.left + 'px'
        });
    }
    $picker.toggleClass('show');
}

function insertReplyEmoji(emoji, commentId) {
    const $input = $(`#reply-input-${commentId}`);
    $input.val($input.val() + emoji);
    $input.focus();
    $('.inline-emoji-picker').removeClass('show');
}

function toggleReplyGifPicker(commentId, event) {
    const $picker = $(`#reply-gif-picker-${commentId}`);
    $('.inline-emoji-picker, .inline-gif-picker').not($picker).removeClass('show');

    if (!$picker.hasClass('show')) {
        loadReplyTrendingGifs(commentId);

        // Position the picker near the button
        const $btn = $(event ? event.target : `#reply-form-${commentId} .reply-input-tools button:last`).closest('button');
        const btnOffset = $btn.offset();
        $picker.css({
            top: (btnOffset.top - 250) + 'px',
            left: btnOffset.left + 'px'
        });
    }
    $picker.toggleClass('show');
}

function loadReplyTrendingGifs(commentId) {
    const $grid = $(`#reply-gif-grid-${commentId}`);
    $grid.html('<div class="text-center py-2"><i class="bx bx-loader-alt bx-spin"></i></div>');

    $.get('/api/anisenso/gifs/trending', { limit: 12 }, function(r) {
        if (r.success) renderReplyGifs(r.data, commentId);
    });
}

function searchReplyGifs(query, commentId) {
    clearTimeout(replyGifTimeout);
    if (!query.trim()) { loadReplyTrendingGifs(commentId); return; }

    replyGifTimeout = setTimeout(() => {
        const $grid = $(`#reply-gif-grid-${commentId}`);
        $grid.html('<div class="text-center py-2"><i class="bx bx-loader-alt bx-spin"></i></div>');

        $.get('/api/anisenso/gifs/search', { q: query, limit: 12 }, function(r) {
            if (r.success) renderReplyGifs(r.data, commentId);
        });
    }, 300);
}

function renderReplyGifs(gifs, commentId) {
    const $grid = $(`#reply-gif-grid-${commentId}`);
    if (!gifs || gifs.length === 0) {
        $grid.html('<div class="text-center py-2 text-secondary">No GIFs found</div>');
        return;
    }
    let html = '';
    gifs.forEach(gif => {
        html += `<div class="inline-gif-item" onclick="insertReplyGif('${gif.url}', ${commentId})"><img src="${gif.preview}" alt="" loading="lazy"></div>`;
    });
    $grid.html(html);
}

function insertReplyGif(url, commentId) {
    const $input = $(`#reply-input-${commentId}`);
    const text = $input.val();
    $input.val(text + (text ? ' ' : '') + `[gif:${url}]`);
    $input.focus();
    $('.inline-gif-picker').removeClass('show');
}

// Close pickers on outside click
$(document).on('click', function(e) {
    if (!$(e.target).closest('.inline-emoji-picker, .inline-gif-picker, .reply-input-tools').length) {
        $('.inline-emoji-picker, .inline-gif-picker').removeClass('show');
    }
    // Close mention autocomplete
    if (!$(e.target).closest('.mention-autocomplete, .mini-reply-input').length) {
        $('.mention-autocomplete').removeClass('show');
    }
});

// ============ @MENTION AUTOCOMPLETE ============
// Store thread participants per comment ID
const threadParticipants = {};
let activeMentionInput = null;
let mentionStartPos = 0;
let currentMentionIndex = -1;

// Extract participants from rendered comments
function extractThreadParticipants(itemId, itemType) {
    const participants = new Map(); // Use Map to avoid duplicates by name

    // Get the comment list container
    const $list = $(`#${itemType}-comments-list-${itemId}`);

    // Get all author names from comments and replies
    $list.find('.mini-comment-author, .mini-comment .mini-comment-content .mini-comment-header span:first-child').each(function() {
        const fullText = $(this).text().trim();
        const name = fullText.replace(/Admin$/, '').trim();
        const isAdmin = fullText.includes('Admin');
        if (name && name.length > 0) {
            participants.set(name, { name: name, type: isAdmin ? 'Admin' : 'Student' });
        }
    });

    // Also get from data-reply elements
    $list.find('[data-reply-id], [data-comment-id]').each(function() {
        const $authorSpan = $(this).find('.mini-comment-author').first();
        if ($authorSpan.length) {
            const fullText = $authorSpan.text().trim();
            const name = fullText.replace(/Admin$/, '').trim();
            const isAdmin = fullText.includes('Admin');
            if (name && name.length > 0) {
                participants.set(name, { name: name, type: isAdmin ? 'Admin' : 'Student' });
            }
        }
    });

    return Array.from(participants.values());
}

// Initialize mention autocomplete on input
function initMentionAutocomplete($input, itemId, itemType) {
    // Create autocomplete dropdown if not exists
    let $autocomplete = $input.siblings('.mention-autocomplete');
    if ($autocomplete.length === 0) {
        $input.parent().css('position', 'relative');
        $input.after('<div class="mention-autocomplete"></div>');
        $autocomplete = $input.siblings('.mention-autocomplete');
    }

    // Listen for @ input
    $input.off('input.mention keydown.mention').on('input.mention', function(e) {
        const text = $(this).val();
        const cursorPos = this.selectionStart;

        // Find @ before cursor
        const textBeforeCursor = text.substring(0, cursorPos);
        const atMatch = textBeforeCursor.match(/@(\w*)$/);

        if (atMatch) {
            mentionStartPos = cursorPos - atMatch[0].length;
            const searchTerm = atMatch[1].toLowerCase();
            activeMentionInput = $(this);

            // Get participants for this thread
            const participants = extractThreadParticipants(itemId, itemType);

            // Filter by search term
            const filtered = participants.filter(p =>
                p.name.toLowerCase().includes(searchTerm)
            ).slice(0, 5);

            if (filtered.length > 0) {
                renderMentionSuggestions($autocomplete, filtered, itemId, itemType);
                positionMentionAutocomplete($autocomplete, $(this));
                $autocomplete.addClass('show');
                currentMentionIndex = -1;
            } else {
                $autocomplete.html('<div class="mention-autocomplete-empty">No participants found</div>');
                positionMentionAutocomplete($autocomplete, $(this));
                $autocomplete.addClass('show');
            }
        } else {
            $autocomplete.removeClass('show');
        }
    }).on('keydown.mention', function(e) {
        const $autocomplete = $(this).siblings('.mention-autocomplete');
        if (!$autocomplete.hasClass('show')) return;

        const $items = $autocomplete.find('.mention-autocomplete-item');
        if ($items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentMentionIndex = Math.min(currentMentionIndex + 1, $items.length - 1);
            $items.removeClass('active').eq(currentMentionIndex).addClass('active');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentMentionIndex = Math.max(currentMentionIndex - 1, 0);
            $items.removeClass('active').eq(currentMentionIndex).addClass('active');
        } else if (e.key === 'Enter' && currentMentionIndex >= 0) {
            e.preventDefault();
            $items.eq(currentMentionIndex).click();
        } else if (e.key === 'Escape') {
            $autocomplete.removeClass('show');
        }
    }).on('blur.mention', function() {
        // Delay to allow click on autocomplete
        setTimeout(() => {
            $(this).siblings('.mention-autocomplete').removeClass('show');
        }, 200);
    });
}

function renderMentionSuggestions($autocomplete, participants, itemId, itemType) {
    let html = '';
    participants.forEach(p => {
        const avatar = generateMiniAvatar(p.name);
        html += `<div class="mention-autocomplete-item" data-name="${escapeHtml(p.name)}" data-item-id="${itemId}" data-item-type="${itemType}">
            <img src="${avatar}" alt="">
            <div>
                <div class="mention-name">${escapeHtml(p.name)}</div>
                <div class="mention-type">${p.type}</div>
            </div>
        </div>`;
    });
    $autocomplete.html(html);

    // Bind click handlers
    $autocomplete.find('.mention-autocomplete-item').on('click', function() {
        const name = $(this).data('name');
        insertMention(name);
    });
}

function positionMentionAutocomplete($autocomplete, $input) {
    const inputOffset = $input.position();
    $autocomplete.css({
        top: inputOffset.top + $input.outerHeight() + 5,
        left: inputOffset.left
    });
}

function insertMention(name) {
    if (!activeMentionInput) return;

    const $input = activeMentionInput;
    const text = $input.val();
    const cursorPos = $input[0].selectionStart;

    // Find where the @ started
    const textBeforeCursor = text.substring(0, cursorPos);
    const atMatch = textBeforeCursor.match(/@(\w*)$/);
    if (!atMatch) return;

    const startPos = cursorPos - atMatch[0].length;
    const beforeAt = text.substring(0, startPos);
    const afterCursor = text.substring(cursorPos);

    // Insert @[Full Name] format
    const mention = `@[${name}] `;
    const newText = beforeAt + mention + afterCursor;
    $input.val(newText);

    // Set cursor position after the mention
    const newCursorPos = startPos + mention.length;
    $input[0].setSelectionRange(newCursorPos, newCursorPos);
    $input.focus();

    // Close autocomplete
    $input.siblings('.mention-autocomplete').removeClass('show');
    activeMentionInput = null;
}

// Initialize mention autocomplete on all reply inputs when they appear
$(document).on('focus', '.mini-reply-input', function() {
    // Find the parent content/questionnaire ID
    const $container = $(this).closest('.item-comments-section');
    let itemId, itemType;

    if ($container.length) {
        const id = $container.attr('id');
        if (id.startsWith('content-comments-')) {
            itemId = id.replace('content-comments-', '');
            itemType = 'content';
        } else if (id.startsWith('questionnaire-comments-')) {
            itemId = id.replace('questionnaire-comments-', '');
            itemType = 'questionnaire';
        }
    }

    if (itemId && itemType) {
        initMentionAutocomplete($(this), itemId, itemType);
    }
});
</script>
@endsection
