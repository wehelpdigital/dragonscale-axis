@extends('layouts.master')

@section('title') Misc Settings @endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.nav-tabs-custom {
    border-bottom: 2px solid #dee2e6;
}

.nav-tabs-custom .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    padding: 12px 20px;
    font-weight: 500;
    color: #74788d;
    margin-bottom: -2px;
    transition: all 0.2s ease;
}

.nav-tabs-custom .nav-link:hover {
    border-bottom-color: #556ee6;
    color: #556ee6;
}

.nav-tabs-custom .nav-link.active {
    border-bottom-color: #556ee6;
    color: #556ee6;
    background: transparent;
}

.nav-tabs-custom .nav-link i {
    margin-right: 8px;
}

.settings-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    background: #fff;
}

.settings-card-header {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

.settings-card-header i {
    font-size: 1.5rem;
    margin-right: 10px;
    color: #556ee6;
}

.settings-card-header h5 {
    margin: 0;
    font-weight: 600;
    color: #495057;
}

.form-label-hint {
    font-size: 12px;
    color: #74788d;
    margin-top: 4px;
}

/* Toast positioning */
#toast-container {
    position: fixed !important;
    top: 20px !important;
    right: 20px !important;
    z-index: 9999 !important;
}

/* Dynamic steps styling */
.step-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    position: relative;
}

.step-item .step-number {
    position: absolute;
    left: -10px;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    background: #556ee6;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
}

.step-item .step-content {
    margin-left: 15px;
}

.step-item .step-actions {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
}

.add-step-btn {
    border: 2px dashed #dee2e6;
    background: transparent;
    color: #74788d;
    padding: 12px 20px;
    border-radius: 8px;
    width: 100%;
    transition: all 0.2s ease;
}

.add-step-btn:hover {
    border-color: #556ee6;
    color: #556ee6;
    background: #f8f9fa;
}

/* Emoji picker styling */
.emoji-picker-container {
    position: relative;
}

.emoji-picker-trigger {
    cursor: pointer;
    font-size: 2rem;
    padding: 10px 20px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    background: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 80px;
    transition: all 0.2s ease;
}

.emoji-picker-trigger:hover {
    border-color: #556ee6;
    background: #f8f9fa;
}

.emoji-picker-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    padding: 10px;
    width: 320px;
    max-height: 300px;
    overflow-y: auto;
    display: none;
}

.emoji-picker-dropdown.show {
    display: block;
}

.emoji-category {
    margin-bottom: 10px;
}

.emoji-category-title {
    font-size: 11px;
    font-weight: 600;
    color: #74788d;
    text-transform: uppercase;
    margin-bottom: 5px;
    padding-left: 5px;
}

.emoji-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 4px;
}

.emoji-item {
    font-size: 1.5rem;
    padding: 5px;
    border-radius: 4px;
    cursor: pointer;
    text-align: center;
    transition: all 0.15s ease;
}

.emoji-item:hover {
    background: #f0f0f0;
    transform: scale(1.2);
}

.emoji-search {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    margin-bottom: 10px;
    font-size: 13px;
}

.emoji-search:focus {
    outline: none;
    border-color: #556ee6;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') Variants @endslot
@slot('title') Misc Settings @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <!-- Back Button -->
        <div class="mb-3">
            @if($variantId && $product)
                <a href="{{ route('ecom-products.variants', ['id' => $product->id]) }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Variants
                </a>
            @else
                <a href="{{ route('ecom-products') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Products
                </a>
            @endif
        </div>

        <!-- Page Header -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">Miscellaneous Settings</h4>
                        <p class="text-secondary mb-0">Configure additional settings for your e-commerce store</p>
                    </div>
                    @if($variant)
                        <div class="text-end">
                            <span class="text-secondary">Accessing from variant:</span>
                            <strong class="text-dark">{{ $variant->ecomVariantName }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tabs Card -->
        <div class="card">
            <div class="card-body">
                <!-- Nav Tabs -->
                <ul class="nav nav-tabs nav-tabs-custom" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $tab === 'thank-you-page' ? 'active' : '' }}"
                                id="thank-you-page-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#thank-you-page"
                                type="button"
                                role="tab"
                                aria-controls="thank-you-page"
                                aria-selected="{{ $tab === 'thank-you-page' ? 'true' : 'false' }}">
                            <i class="bx bx-check-circle"></i>Thank You Page
                        </button>
                    </li>
                    <!-- Future tabs can be added here -->
                    <li class="nav-item" role="presentation">
                        <button class="nav-link disabled"
                                id="more-settings-tab"
                                type="button"
                                role="tab"
                                disabled>
                            <i class="bx bx-dots-horizontal-rounded"></i>More Settings (Coming Soon)
                        </button>
                    </li>
                </ul>

                <!-- Tab Contents -->
                <div class="tab-content pt-4" id="settingsTabContent">
                    <!-- Thank You Page Tab -->
                    <div class="tab-pane fade {{ $tab === 'thank-you-page' ? 'show active' : '' }}"
                         id="thank-you-page"
                         role="tabpanel"
                         aria-labelledby="thank-you-page-tab">

                        <form id="thankYouPageForm">
                            @csrf

                            <!-- Main Header Section -->
                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <i class="bx bx-heading"></i>
                                    <h5>Main Header Section</h5>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="mainHeading" class="form-label">Main Heading <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="mainHeading" name="mainHeading"
                                                   value="{{ $thankYouSettings->mainHeading }}" required>
                                            <small class="form-label-hint">The main "Thank You" title displayed at the top</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="subHeading" class="form-label">Sub Heading <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="subHeading" name="subHeading"
                                                   value="{{ $thankYouSettings->subHeading }}" required>
                                            <small class="form-label-hint">The subtitle shown below the main heading</small>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="subHeadingText" class="form-label">Additional Text Below Sub Heading</label>
                                            <textarea class="form-control" id="subHeadingText" name="subHeadingText"
                                                      rows="2" placeholder="Optional additional text to display below the sub heading">{{ $thankYouSettings->subHeadingText }}</textarea>
                                            <small class="form-label-hint">Optional text that appears below the sub heading (leave empty to hide)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- What's Next Section -->
                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <i class="bx bx-list-ol"></i>
                                    <h5>What's Next Section</h5>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="whatsNextTitle" class="form-label">Section Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="whatsNextTitle" name="whatsNextTitle"
                                                   value="{{ $thankYouSettings->whatsNextTitle }}" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Steps <span class="text-danger">*</span></label>
                                        <small class="form-label-hint d-block mb-3">Add the steps that will be shown to customers. Supports HTML tags like &lt;strong&gt; for bold text.</small>

                                        <div id="stepsContainer">
                                            @php
                                                $steps = $thankYouSettings->whatsNextSteps ?? [];
                                            @endphp
                                            @foreach($steps as $index => $step)
                                                <div class="step-item" data-index="{{ $index }}">
                                                    <span class="step-number">{{ $index + 1 }}</span>
                                                    <div class="step-content">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <input type="text" class="form-control step-input"
                                                                   name="whatsNextSteps[{{ $index }}][text]"
                                                                   value="{{ $step['text'] ?? '' }}"
                                                                   placeholder="Enter step text..." required>
                                                            <button type="button" class="btn btn-sm btn-outline-danger remove-step-btn" title="Remove Step">
                                                                <i class="bx bx-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <button type="button" class="add-step-btn mt-2" id="addStepBtn">
                                            <i class="bx bx-plus me-1"></i> Add New Step
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Inspirational Message Section -->
                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <i class="bx bx-bulb"></i>
                                    <h5>Inspirational Message Section</h5>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Emoji</label>
                                            <div class="emoji-picker-container">
                                                <div class="emoji-picker-trigger" id="emojiPickerTrigger">
                                                    <span id="selectedEmoji">{{ $thankYouSettings->inspirationalEmoji ?: '🌾' }}</span>
                                                </div>
                                                <input type="hidden" id="inspirationalEmoji" name="inspirationalEmoji"
                                                       value="{{ $thankYouSettings->inspirationalEmoji }}">
                                                <div class="emoji-picker-dropdown" id="emojiPickerDropdown">
                                                    <input type="text" class="emoji-search" id="emojiSearch" placeholder="Search emojis...">
                                                    <div id="emojiContent">
                                                        <!-- Emoji categories will be loaded here -->
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="form-label-hint">Click to select an emoji</small>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="mb-3">
                                            <label for="inspirationalTitle" class="form-label">Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="inspirationalTitle" name="inspirationalTitle"
                                                   value="{{ $thankYouSettings->inspirationalTitle }}" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="inspirationalMessage" class="form-label">Message <span class="text-danger">*</span></label>
                                            <textarea class="form-control" id="inspirationalMessage" name="inspirationalMessage"
                                                      rows="3" required>{{ $thankYouSettings->inspirationalMessage }}</textarea>
                                            <small class="form-label-hint">An encouraging message to display to customers after purchase</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bookmark Reminder Section -->
                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <i class="bx bx-bookmark"></i>
                                    <h5>Bookmark Reminder Section</h5>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="bookmarkTitle" class="form-label">Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="bookmarkTitle" name="bookmarkTitle"
                                                   value="{{ $thankYouSettings->bookmarkTitle }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="bookmarkMessage" class="form-label">Message <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="bookmarkMessage" name="bookmarkMessage"
                                                   value="{{ $thankYouSettings->bookmarkMessage }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons Section -->
                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <i class="bx bx-pointer"></i>
                                    <h5>Action Buttons</h5>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="copyLinkButtonText" class="form-label">Copy Link Button <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="copyLinkButtonText" name="copyLinkButtonText"
                                                   value="{{ $thankYouSettings->copyLinkButtonText }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="copyLinkSuccessText" class="form-label">Copy Success Text <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="copyLinkSuccessText" name="copyLinkSuccessText"
                                                   value="{{ $thankYouSettings->copyLinkSuccessText }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="savePhotoButtonText" class="form-label">Save Photo Button <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="savePhotoButtonText" name="savePhotoButtonText"
                                                   value="{{ $thankYouSettings->savePhotoButtonText }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="savingText" class="form-label">Saving Text <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="savingText" name="savingText"
                                                   value="{{ $thankYouSettings->savingText }}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="homeButtonText" class="form-label">Home Button <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="homeButtonText" name="homeButtonText"
                                                   value="{{ $thankYouSettings->homeButtonText }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Messages Section -->
                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <i class="bx bx-check-shield"></i>
                                    <h5>Status Messages</h5>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="statusVerifiedText" class="form-label">Payment Verified Text <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="statusVerifiedText" name="statusVerifiedText"
                                                   value="{{ $thankYouSettings->statusVerifiedText }}" required>
                                            <small class="form-label-hint">Text shown when payment is verified</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="statusPendingText" class="form-label">Payment Pending Text <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="statusPendingText" name="statusPendingText"
                                                   value="{{ $thankYouSettings->statusPendingText }}" required>
                                            <small class="form-label-hint">Text shown when payment is pending verification</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Section -->
                            <div class="settings-card">
                                <div class="settings-card-header">
                                    <i class="bx bx-layout"></i>
                                    <h5>Footer</h5>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="footerText" class="form-label">Footer Text <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="footerText" name="footerText"
                                                   value="{{ $thankYouSettings->footerText }}" required>
                                            <small class="form-label-hint">Security/branding text at the bottom of the page</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-warning" id="resetToDefaultsBtn">
                                    <i class="bx bx-reset me-1"></i> Reset to Defaults
                                </button>
                                <button type="submit" class="btn btn-primary" id="saveSettingsBtn">
                                    <i class="bx bx-save me-1"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Confirmation Modal -->
<div class="modal fade" id="resetConfirmModal" tabindex="-1" aria-labelledby="resetConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetConfirmModalLabel">
                    <i class="bx bx-reset text-warning me-2"></i>Reset to Defaults
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to reset all Thank You Page settings to their default values?</p>
                <p class="text-secondary small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-warning" id="confirmResetBtn">
                    <i class="bx bx-reset me-1"></i>Reset to Defaults
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
// Toastr configuration
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000,
    extendedTimeOut: 1000,
    preventDuplicates: true
};

// Emoji data categorized
const emojiCategories = {
    'Agriculture & Nature': ['🌾', '🌱', '🌿', '🍀', '🌳', '🌲', '🌴', '🌵', '🌷', '🌸', '🌹', '🌺', '🌻', '🌼', '💐', '🍃', '🍂', '🍁', '🌊', '☀️', '🌤️', '🌧️', '🌈', '⭐', '🌙'],
    'Food & Crops': ['🍚', '🌽', '🍅', '🥕', '🥔', '🧅', '🧄', '🥬', '🥦', '🍆', '🫑', '🌶️', '🥒', '🥗', '🍇', '🍈', '🍉', '🍊', '🍋', '🍌', '🍍', '🥭', '🍎', '🍏', '🍐'],
    'Animals': ['🐄', '🐖', '🐓', '🐔', '🐤', '🦆', '🐟', '🦐', '🦀', '🐝', '🦋', '🐛', '🐌', '🐞', '🦗'],
    'Success & Celebration': ['🎉', '🎊', '🏆', '🥇', '🏅', '⭐', '🌟', '✨', '💫', '🔥', '💪', '👏', '🙌', '✅', '☑️', '💯', '🎯', '🚀'],
    'Hearts & Love': ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💕', '💖', '💗', '💝', '💞', '💓'],
    'Hands & Gestures': ['👍', '👌', '🤝', '🙏', '💪', '✊', '👊', '🤟', '✌️', '🤞', '👋', '🖐️', '✋', '👆', '👇'],
    'Symbols': ['✔️', '❌', '➡️', '⬅️', '⬆️', '⬇️', '🔴', '🟢', '🔵', '🟡', '⚫', '⚪', '🟤', '💠', '🔷', '🔶', '▶️', '⏩', '🔔', '📌', '📍', '💡', '🔑', '🏠', '📱']
};

$(document).ready(function() {
    // ==================== Emoji Picker ====================
    let emojiPickerOpen = false;

    function renderEmojiPicker(filter = '') {
        let html = '';
        const filterLower = filter.toLowerCase();

        for (const [category, emojis] of Object.entries(emojiCategories)) {
            const filteredEmojis = emojis.filter(emoji => {
                if (!filter) return true;
                // Simple filter - just show all if no match needed
                return true;
            });

            if (filteredEmojis.length > 0) {
                html += `<div class="emoji-category">
                    <div class="emoji-category-title">${category}</div>
                    <div class="emoji-grid">`;

                filteredEmojis.forEach(emoji => {
                    html += `<div class="emoji-item" data-emoji="${emoji}">${emoji}</div>`;
                });

                html += `</div></div>`;
            }
        }

        $('#emojiContent').html(html);
    }

    // Initialize emoji picker
    renderEmojiPicker();

    // Toggle emoji picker
    $('#emojiPickerTrigger').on('click', function(e) {
        e.stopPropagation();
        emojiPickerOpen = !emojiPickerOpen;
        if (emojiPickerOpen) {
            $('#emojiPickerDropdown').addClass('show');
            $('#emojiSearch').focus();
        } else {
            $('#emojiPickerDropdown').removeClass('show');
        }
    });

    // Close emoji picker when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.emoji-picker-container').length) {
            $('#emojiPickerDropdown').removeClass('show');
            emojiPickerOpen = false;
        }
    });

    // Select emoji
    $(document).on('click', '.emoji-item', function() {
        const emoji = $(this).data('emoji');
        $('#selectedEmoji').text(emoji);
        $('#inspirationalEmoji').val(emoji);
        $('#emojiPickerDropdown').removeClass('show');
        emojiPickerOpen = false;
    });

    // Search emojis
    $('#emojiSearch').on('input', function() {
        renderEmojiPicker($(this).val());
    });

    // ==================== Dynamic Steps ====================
    let stepIndex = {{ count($thankYouSettings->whatsNextSteps ?? []) }};

    function updateStepNumbers() {
        $('#stepsContainer .step-item').each(function(index) {
            $(this).find('.step-number').text(index + 1);
            $(this).attr('data-index', index);
            $(this).find('.step-input').attr('name', `whatsNextSteps[${index}][text]`);
        });
    }

    // Add new step
    $('#addStepBtn').on('click', function() {
        const newStepHtml = `
            <div class="step-item" data-index="${stepIndex}">
                <span class="step-number">${stepIndex + 1}</span>
                <div class="step-content">
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" class="form-control step-input"
                               name="whatsNextSteps[${stepIndex}][text]"
                               value=""
                               placeholder="Enter step text..." required>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-step-btn" title="Remove Step">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        $('#stepsContainer').append(newStepHtml);
        stepIndex++;
        updateStepNumbers();

        // Focus on the new input
        $('#stepsContainer .step-item:last-child .step-input').focus();
    });

    // Remove step
    $(document).on('click', '.remove-step-btn', function() {
        const stepCount = $('#stepsContainer .step-item').length;

        if (stepCount <= 1) {
            toastr.warning('You must have at least one step.', 'Warning!');
            return;
        }

        $(this).closest('.step-item').fadeOut(200, function() {
            $(this).remove();
            updateStepNumbers();
        });
    });

    // ==================== Form Submission ====================
    $('#thankYouPageForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#saveSettingsBtn');
        const originalText = $btn.html();

        // Show loading state
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.ajax({
            url: '{{ route("ecom-misc-settings.thank-you-page.update") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success!');
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while saving settings.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        // Get first validation error
                        const errors = xhr.responseJSON.errors;
                        const firstErrorKey = Object.keys(errors)[0];
                        errorMessage = errors[firstErrorKey][0];
                    } else if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                }
                toastr.error(errorMessage, 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ==================== Reset to Defaults ====================
    $('#resetToDefaultsBtn').on('click', function() {
        $('#resetConfirmModal').modal('show');
    });

    $('#confirmResetBtn').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.html();

        // Show loading state
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Resetting...');

        $.ajax({
            url: '{{ route("ecom-misc-settings.thank-you-page.reset") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#resetConfirmModal').modal('hide');
                    toastr.success(response.message, 'Success!');

                    // Reload page to show default values
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while resetting settings.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endsection
