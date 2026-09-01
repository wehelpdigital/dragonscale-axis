@extends('layouts.master')

@section('title') Testimonials @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
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
    .item-card.disabled {
        opacity: 0.55;
    }

/* IN THE DARK. The card is painted white here, so `.text-dark` inside it —
   which becomes near-white when the switch is thrown — went to 1.08:1. The
   surface moves rather than the ink: a panel that asked to be white asked for
   "the card colour", and in the dark that is not white. */
[data-bs-theme="dark"] .item-card { background: #2a3042; border-color: #39405a; }
[data-bs-theme="dark"] .item-card .text-dark { color: #e5e9f3 !important; }
[data-bs-theme="dark"] .item-card .text-secondary { color: #9aa2ba !important; }

</style>
@endsection

@section('content')
@component('components.breadcrumb')
    @slot('li_1') Ani-Senso @endslot
    @slot('li_2') Website @endslot
    @slot('title') Testimonials @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Testimonials</h4>
                        <p class="text-secondary mb-0" style="font-size: 0.85rem;">Manage testimonials from farmers and students. These can be displayed on the Ani-Senso homepage.</p>
                    </div>
                    <button class="btn btn-primary" id="btnAddTestimonial">
                        <i class="bx bx-plus me-1"></i>Add Testimonial
                    </button>
                </div>

                <div class="row" id="testimonialsList">
                    @forelse($testimonials as $testimonial)
                    <div class="col-md-4" id="testimonial-{{ $testimonial->id }}">
                        <div class="item-card {{ !$testimonial->isActive ? 'disabled' : '' }}">
                            <div class="d-flex justify-content-between mb-2">
                                <div>
                                    @if(!$testimonial->isActive)<span class="badge bg-secondary me-1">Disabled</span>@endif
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-soft-primary me-1 edit-testimonial"
                                        data-id="{{ $testimonial->id }}"
                                        data-name="{{ $testimonial->name }}"
                                        data-location="{{ $testimonial->location }}"
                                        data-role="{{ $testimonial->role }}"
                                        data-testimonial="{{ $testimonial->testimonial }}"
                                        data-rating="{{ $testimonial->rating }}"
                                        data-is-active="{{ $testimonial->isActive ? '1' : '0' }}"
                                    ><i class="bx bx-edit"></i></button>
                                    <button type="button" class="btn btn-sm btn-soft-danger delete-testimonial" data-id="{{ $testimonial->id }}" data-name="{{ $testimonial->name }}"><i class="bx bx-trash"></i></button>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                @if($testimonial->image)
                                    <img src="{{ asset($testimonial->image) }}" alt="{{ $testimonial->name }}" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; font-size: 14px; font-weight: bold;">{{ strtoupper(substr($testimonial->name, 0, 1)) }}</div>
                                @endif
                                <div>
                                    <h6 class="text-dark mb-0 small">{{ $testimonial->name }}</h6>
                                    @if($testimonial->location)<small class="text-secondary" style="font-size: 11px;">{{ $testimonial->location }}</small>@endif
                                    @if($testimonial->role)<br><small class="text-primary" style="font-size: 11px;">{{ $testimonial->role }}</small>@endif
                                </div>
                            </div>
                            <div class="mb-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bx {{ $i <= $testimonial->rating ? 'bxs-star text-warning' : 'bx-star text-muted' }}" style="font-size: 12px;"></i>
                                @endfor
                            </div>
                            @if($testimonial->testimonial)<small class="text-secondary fst-italic">"{{ Str::limit($testimonial->testimonial, 80) }}"</small>@endif
                        </div>
                    </div>
                    @empty
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bx bx-message-square-dots text-secondary" style="font-size: 3rem;"></i>
                            <p class="text-dark mt-3 mb-1">No testimonials yet</p>
                            <p class="text-secondary mb-0">Add testimonials from your farmers and students.</p>
                        </div>
                    </div>
                    @endforelse

                    {{-- Shown only when there is more than one page, so a farm
                         with six quotes is not given navigation for nothing. --}}
                    @if($testimonials->hasPages())
                        <div class="mt-3">{{ $testimonials->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="testimonialModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testimonialModalTitle">
                    <i class="bx bx-message-square-dots text-primary me-2"></i>Add Testimonial
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="testimonialForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="editId" value="">

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label text-dark">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formName" name="name" maxlength="150" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-dark">Rating <span class="text-danger">*</span></label>
                            <select class="form-select" id="formRating" name="rating">
                                <option value="5">5 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="2">2 Stars</option>
                                <option value="1">1 Star</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Location</label>
                            <input type="text" class="form-control" id="formLocation" name="location" maxlength="255" placeholder="e.g. Nueva Ecija, Philippines">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-dark">Role</label>
                            <input type="text" class="form-control" id="formRole" name="role" maxlength="100" placeholder="e.g. Rice Farmer">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark">Testimonial <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="formTestimonial" name="testimonial" rows="4" maxlength="2000" required placeholder="What did they say about Ani-Senso?"></textarea>
                        <div class="d-flex justify-content-end mt-1">
                            <small class="text-secondary" id="charCount">0/2000</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark">Photo</label>
                        <input type="file" class="form-control" id="formImage" name="image" accept="image/*">
                        <small class="text-secondary">Optional. Max 2MB. JPEG, PNG, WebP.</small>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="formIsActive" name="isActive" checked>
                        <label class="form-check-label text-dark" for="formIsActive">Active (available for homepage selection)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveTestimonial">
                        <i class="bx bx-save me-1"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Delete Testimonial</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete the testimonial from "<strong id="deleteItemName"></strong>"?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bx bx-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(document).ready(function() {
    toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: 3000 };

    var deleteId = null;

    // Character count
    $('#formTestimonial').on('input', function() {
        $('#charCount').text($(this).val().length + '/2000');
    });

    // Open Add modal
    $('#btnAddTestimonial').on('click', function() {
        $('#testimonialModalTitle').html('<i class="bx bx-message-square-dots text-primary me-2"></i>Add Testimonial');
        $('#editId').val('');
        $('#testimonialForm')[0].reset();
        $('#formIsActive').prop('checked', true);
        $('#charCount').text('0/2000');
        $('#testimonialModal').modal('show');
    });

    // Open Edit modal
    $(document).on('click', '.edit-testimonial', function(e) {
        e.preventDefault();
        $('#testimonialModalTitle').html('<i class="bx bx-edit text-primary me-2"></i>Edit Testimonial');
        $('#editId').val($(this).data('id'));
        $('#formName').val($(this).data('name'));
        $('#formLocation').val($(this).data('location'));
        $('#formRole').val($(this).data('role'));
        $('#formTestimonial').val($(this).data('testimonial'));
        $('#formRating').val($(this).data('rating'));
        $('#formIsActive').prop('checked', $(this).data('is-active') == 1);
        $('#charCount').text($('#formTestimonial').val().length + '/2000');
        $('#formImage').val('');
        $('#testimonialModal').modal('show');
    });

    // Save (Add/Edit)
    $('#testimonialForm').on('submit', function(e) {
        e.preventDefault();
        var editId = $('#editId').val();
        var formData = new FormData(this);
        formData.append('isActive', $('#formIsActive').is(':checked') ? '1' : '0');

        var url = editId
            ? '/anisenso-website-testimonials?id=' + editId
            : '/anisenso-website-testimonials';

        if (editId) formData.append('_method', 'PUT');

        var $btn = $('#btnSaveTestimonial');
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    $('#testimonialModal').modal('hide');
                    toastr.success(response.message);
                    location.reload();
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    var errors = xhr.responseJSON.errors;
                    var msg = Object.values(errors).flat().join('<br>');
                    toastr.error(msg, 'Validation Error');
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Failed to save');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save');
            }
        });
    });

    // Delete
    $(document).on('click', '.delete-testimonial', function(e) {
        e.preventDefault();
        deleteId = $(this).data('id');
        $('#deleteItemName').text($(this).data('name'));
        $('#deleteModal').modal('show');
    });

    $('#confirmDeleteBtn').on('click', function() {
        if (!deleteId) return;
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: '/anisenso-website-testimonials?id=' + deleteId,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    toastr.success(response.message);
                    $('#testimonial-' + deleteId).fadeOut(400, function() { $(this).remove(); });
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
                deleteId = null;
            }
        });
    });
});
</script>
@endsection
