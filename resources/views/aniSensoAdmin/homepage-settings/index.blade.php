@extends('layouts.master')

@section('title') Homepage Settings @endsection

@section('css')
<style>
    .nav-tabs-custom .nav-link {
        border: none;
        padding: 12px 20px;
        font-weight: 500;
        color: #495057;
        background: transparent;
        border-bottom: 2px solid transparent;
    }
    .nav-tabs-custom .nav-link:hover {
        color: #556ee6;
        border-bottom-color: #556ee6;
    }
    .nav-tabs-custom .nav-link.active {
        color: #556ee6;
        border-bottom-color: #556ee6;
        background: transparent;
    }
    .section-toggle {
        cursor: pointer;
    }
    .item-card {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background: #fff;
        transition: box-shadow 0.2s;
    }
    .item-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .item-card.sortable-ghost {
        opacity: 0.4;
        background: #f8f9fa;
    }
    .drag-handle {
        cursor: grab;
        color: #adb5bd;
    }
    .drag-handle:hover {
        color: #495057;
    }
    .image-preview {
        max-width: 150px;
        max-height: 100px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #e9ecef;
    }
    .image-upload-zone {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #f8f9fa;
    }
    .image-upload-zone:hover {
        border-color: #556ee6;
        background: #f0f4ff;
    }
    .image-upload-zone.dragover {
        border-color: #556ee6;
        background: #e8eeff;
    }
    .settings-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .tab-icon {
        margin-right: 8px;
        font-size: 16px;
    }
    .section-badge {
        font-size: 11px;
        padding: 3px 8px;
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Website @endslot
        @slot('title') Homepage Settings @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="card-title mb-1">Homepage Sections</h4>
                            <p class="text-secondary mb-0">Manage your homepage content by section</p>
                        </div>
                        <div>
                            <a href="{{ url('/') }}/anisenso-course" target="_blank" class="btn btn-soft-primary">
                                <i class="bx bx-link-external me-1"></i> Preview Website
                            </a>
                        </div>
                    </div>

                    <!-- Section Tabs -->
                    <ul class="nav nav-tabs nav-tabs-custom nav-justified mb-4" id="sectionTabs" role="tablist">
                        @foreach($sections as $section)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                        id="{{ $section->sectionKey }}-tab"
                                        data-bs-toggle="tab"
                                        data-bs-target="#{{ $section->sectionKey }}"
                                        type="button"
                                        role="tab"
                                        aria-controls="{{ $section->sectionKey }}"
                                        aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    <i class="bx {{ $section->sectionIcon ?? 'bx-cog' }} tab-icon"></i>
                                    <span class="d-none d-md-inline">{{ $section->sectionName }}</span>
                                    @if(!$section->isEnabled)
                                        <span class="badge bg-secondary section-badge ms-1">Disabled</span>
                                    @endif
                                </button>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="sectionTabsContent">
                        @foreach($sections as $section)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                 id="{{ $section->sectionKey }}"
                                 role="tabpanel"
                                 aria-labelledby="{{ $section->sectionKey }}-tab"
                                 data-section-key="{{ $section->sectionKey }}">

                                <!-- Section Header -->
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h5 class="text-dark mb-1">{{ $section->sectionName }}</h5>
                                        <small class="text-secondary">Section Key: {{ $section->sectionKey }}</small>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input section-toggle"
                                               type="checkbox"
                                               id="toggle-{{ $section->sectionKey }}"
                                               data-section="{{ $section->sectionKey }}"
                                               {{ $section->isEnabled ? 'checked' : '' }}>
                                        <label class="form-check-label text-dark" for="toggle-{{ $section->sectionKey }}">
                                            {{ $section->isEnabled ? 'Enabled' : 'Disabled' }}
                                        </label>
                                    </div>
                                </div>

                                @include('aniSensoAdmin.homepage-settings.partials.' . $section->sectionKey, ['section' => $section])

                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-plus-circle text-primary me-2"></i>Add New Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addItemForm">
                        <input type="hidden" id="addItemSectionKey" name="sectionKey">
                        <input type="hidden" id="addItemType" name="itemType">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Title</label>
                                <input type="text" class="form-control" name="title" id="addItemTitle">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Subtitle</label>
                                <input type="text" class="form-control" name="subtitle" id="addItemSubtitle">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-dark">Description</label>
                            <textarea class="form-control" name="description" id="addItemDescription" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Icon (Boxicons class)</label>
                                <input type="text" class="form-control" name="icon" id="addItemIcon" placeholder="e.g., bx-star">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Link URL</label>
                                <input type="text" class="form-control" name="linkUrl" id="addItemLinkUrl">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-dark">Link Text</label>
                            <input type="text" class="form-control" name="linkText" id="addItemLinkText">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveNewItem">
                        <i class="bx bx-save me-1"></i> Save Item
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-edit text-primary me-2"></i>Edit Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editItemForm">
                        <input type="hidden" id="editItemId" name="itemId">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Title</label>
                                <input type="text" class="form-control" name="title" id="editItemTitle">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Subtitle</label>
                                <input type="text" class="form-control" name="subtitle" id="editItemSubtitle">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-dark">Description</label>
                            <textarea class="form-control" name="description" id="editItemDescription" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Icon (Boxicons class)</label>
                                <input type="text" class="form-control" name="icon" id="editItemIcon" placeholder="e.g., bx-star">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Link URL</label>
                                <input type="text" class="form-control" name="linkUrl" id="editItemLinkUrl">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Link Text</label>
                                <input type="text" class="form-control" name="linkText" id="editItemLinkText">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Status</label>
                                <select class="form-select" name="isActive" id="editItemActive">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <!-- Image uploads in edit modal -->
                        <div class="row" id="editItemImagesRow">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark">Image</label>
                                <div class="image-upload-zone" data-field="image" data-item-id="">
                                    <input type="file" class="d-none item-image-input" accept="image/*">
                                    <div class="upload-placeholder">
                                        <i class="bx bx-cloud-upload text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-secondary mb-0 mt-2">Click or drag to upload</p>
                                    </div>
                                    <img src="" class="image-preview d-none" alt="Preview">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3" id="editImage2Container" style="display: none;">
                                <label class="form-label text-dark">Image 2 (After)</label>
                                <div class="image-upload-zone" data-field="image2" data-item-id="">
                                    <input type="file" class="d-none item-image-input" accept="image/*">
                                    <div class="upload-placeholder">
                                        <i class="bx bx-cloud-upload text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-secondary mb-0 mt-2">Click or drag to upload</p>
                                    </div>
                                    <img src="" class="image-preview d-none" alt="Preview">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEditItem">
                        <i class="bx bx-save me-1"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteItemModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Delete Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to delete <strong id="deleteItemName"></strong>?</p>
                    <p class="text-secondary mb-0">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteItem">
                        <i class="bx bx-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    const csrfToken = '{{ csrf_token() }}';
    let itemToDelete = null;
    let currentEditItem = null;

    // Initialize toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Section toggle
    $('.section-toggle').on('change', function() {
        const sectionKey = $(this).data('section');
        const $label = $(this).next('label');

        $.ajax({
            url: `/anisenso-homepage-settings/toggle/${sectionKey}`,
            type: 'POST',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    $label.text(response.isEnabled ? 'Enabled' : 'Disabled');
                    toastr.success(response.message);

                    // Update tab badge
                    const $tab = $(`#${sectionKey}-tab`);
                    if (response.isEnabled) {
                        $tab.find('.section-badge').remove();
                    } else {
                        if ($tab.find('.section-badge').length === 0) {
                            $tab.append('<span class="badge bg-secondary section-badge ms-1">Disabled</span>');
                        }
                    }
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error toggling section');
                $(this).prop('checked', !$(this).prop('checked'));
            }
        });
    });

    // Save section settings
    function saveSectionSettings(sectionKey, settings, callback) {
        $.ajax({
            url: `/anisenso-homepage-settings/section/${sectionKey}`,
            type: 'PUT',
            data: {
                _token: csrfToken,
                settings: settings
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Settings saved successfully');
                    if (callback) callback(response);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error saving settings');
            }
        });
    }

    // Upload section image
    function uploadSectionImage(sectionKey, file, settingKey, callback) {
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('image', file);
        formData.append('settingKey', settingKey);

        $.ajax({
            url: `/anisenso-homepage-settings/section/${sectionKey}/image`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('Image uploaded successfully');
                    if (callback) callback(response.imageUrl);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error uploading image');
            }
        });
    }

    // Add item modal
    function openAddItemModal(sectionKey, itemType) {
        $('#addItemSectionKey').val(sectionKey);
        $('#addItemType').val(itemType);
        $('#addItemForm')[0].reset();
        $('#addItemModal').modal('show');
    }

    // Save new item
    $('#saveNewItem').on('click', function() {
        const $btn = $(this);
        const sectionKey = $('#addItemSectionKey').val();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');

        $.ajax({
            url: `/anisenso-homepage-settings/section/${sectionKey}/items`,
            type: 'POST',
            data: {
                _token: csrfToken,
                itemType: $('#addItemType').val(),
                title: $('#addItemTitle').val(),
                subtitle: $('#addItemSubtitle').val(),
                description: $('#addItemDescription').val(),
                icon: $('#addItemIcon').val(),
                linkUrl: $('#addItemLinkUrl').val(),
                linkText: $('#addItemLinkText').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#addItemModal').modal('hide');
                    toastr.success('Item added successfully!');
                    $('#addItemForm')[0].reset();
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error adding item');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Item');
            }
        });
    });

    // Edit item modal
    function openEditItemModal(item, showImage2 = false) {
        currentEditItem = item;
        $('#editItemId').val(item.id);
        $('#editItemTitle').val(item.title || '');
        $('#editItemSubtitle').val(item.subtitle || '');
        $('#editItemDescription').val(item.description || '');
        $('#editItemIcon').val(item.icon || '');
        $('#editItemLinkUrl').val(item.linkUrl || '');
        $('#editItemLinkText').val(item.linkText || '');
        $('#editItemActive').val(item.isActive ? '1' : '0');

        // Handle images
        $('#editItemImagesRow .image-upload-zone').attr('data-item-id', item.id);

        const $img1 = $('#editItemImagesRow').find('[data-field="image"]');
        if (item.image) {
            $img1.find('.image-preview').attr('src', item.image).removeClass('d-none');
            $img1.find('.upload-placeholder').addClass('d-none');
        } else {
            $img1.find('.image-preview').addClass('d-none');
            $img1.find('.upload-placeholder').removeClass('d-none');
        }

        if (showImage2) {
            $('#editImage2Container').show();
            const $img2 = $('#editItemImagesRow').find('[data-field="image2"]');
            if (item.image2) {
                $img2.find('.image-preview').attr('src', item.image2).removeClass('d-none');
                $img2.find('.upload-placeholder').addClass('d-none');
            } else {
                $img2.find('.image-preview').addClass('d-none');
                $img2.find('.upload-placeholder').removeClass('d-none');
            }
        } else {
            $('#editImage2Container').hide();
        }

        $('#editItemModal').modal('show');
    }

    // Save edit item
    $('#saveEditItem').on('click', function() {
        const $btn = $(this);
        const itemId = $('#editItemId').val();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');

        $.ajax({
            url: `/anisenso-homepage-settings/items/${itemId}`,
            type: 'PUT',
            data: {
                _token: csrfToken,
                title: $('#editItemTitle').val(),
                subtitle: $('#editItemSubtitle').val(),
                description: $('#editItemDescription').val(),
                icon: $('#editItemIcon').val(),
                linkUrl: $('#editItemLinkUrl').val(),
                linkText: $('#editItemLinkText').val(),
                isActive: $('#editItemActive').val() == '1'
            },
            success: function(response) {
                if (response.success) {
                    $('#editItemModal').modal('hide');
                    toastr.success('Item updated successfully!');
                    // Update the item card in DOM
                    const $card = $(`[data-item-id="${itemId}"]`);
                    if ($card.length) {
                        $card.find('h6').first().text($('#editItemTitle').val() || 'Untitled');
                    }
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error updating item');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Changes');
            }
        });
    });

    // Image upload in edit modal
    $('.image-upload-zone').on('click', function() {
        $(this).find('.item-image-input').click();
    });

    $('.item-image-input').on('change', function() {
        const file = this.files[0];
        if (!file) return;

        const $zone = $(this).closest('.image-upload-zone');
        const itemId = $zone.attr('data-item-id');
        const field = $zone.attr('data-field');

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('image', file);
        formData.append('field', field);

        $.ajax({
            url: `/anisenso-homepage-settings/items/${itemId}/image`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $zone.find('.image-preview').attr('src', response.imageUrl).removeClass('d-none');
                    $zone.find('.upload-placeholder').addClass('d-none');
                    toastr.success('Image uploaded successfully');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error uploading image');
            }
        });
    });

    // Delete item
    function openDeleteItemModal(itemId, itemName) {
        itemToDelete = itemId;
        $('#deleteItemName').text(itemName || 'this item');
        $('#deleteItemModal').modal('show');
    }

    $('#confirmDeleteItem').on('click', function() {
        if (!itemToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Deleting...');

        $.ajax({
            url: `/anisenso-homepage-settings/items/${itemToDelete}`,
            type: 'DELETE',
            data: { _token: csrfToken },
            success: function(response) {
                if (response.success) {
                    $('#deleteItemModal').modal('hide');
                    toastr.success('Item deleted successfully');
                    $(`[data-item-id="${itemToDelete}"]`).fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error deleting item');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
                itemToDelete = null;
            }
        });
    });

    // Initialize sortable for items
    document.querySelectorAll('.sortable-items').forEach(function(el) {
        new Sortable(el, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                const items = [];
                $(evt.to).find('[data-item-id]').each(function(index) {
                    items.push({
                        id: $(this).data('item-id'),
                        order: index + 1
                    });
                });

                $.ajax({
                    url: '/anisenso-homepage-settings/items/reorder',
                    type: 'POST',
                    data: { _token: csrfToken, items: items },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Order updated');
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Error updating order');
                    }
                });
            }
        });
    });

    // Drag and drop for section image uploads
    $('.section-image-upload').each(function() {
        const $zone = $(this);

        $zone.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });

        $zone.on('dragleave', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
        });

        $zone.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                const sectionKey = $(this).data('section');
                const settingKey = $(this).data('setting');
                uploadSectionImage(sectionKey, files[0], settingKey, function(imageUrl) {
                    $zone.find('.image-preview').attr('src', imageUrl).removeClass('d-none');
                    $zone.find('.upload-placeholder').addClass('d-none');
                });
            }
        });

        $zone.on('click', function() {
            $(this).find('.section-image-input').click();
        });
    });

    $('.section-image-input').on('change', function() {
        const file = this.files[0];
        if (!file) return;

        const $zone = $(this).closest('.section-image-upload');
        const sectionKey = $zone.data('section');
        const settingKey = $zone.data('setting');

        uploadSectionImage(sectionKey, file, settingKey, function(imageUrl) {
            $zone.find('.image-preview').attr('src', imageUrl).removeClass('d-none');
            $zone.find('.upload-placeholder').addClass('d-none');
        });
    });

    // Save settings on blur for text inputs
    $('.section-setting-input').on('blur', function() {
        const $input = $(this);
        const sectionKey = $input.data('section');
        const settingKey = $input.data('setting');
        const value = $input.val();

        const settings = {};
        settings[settingKey] = value;
        saveSectionSettings(sectionKey, settings);
    });
</script>
@endsection
