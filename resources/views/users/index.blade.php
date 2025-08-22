@extends('layouts.master')

@section('title') Users @endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') Dashboard @endslot
@slot('title') Users @endslot
@endcomponent

<!-- Success/Error Messages -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bx bx-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Users Management</h4>
                <p class="card-title-desc">Manage system users and their accounts.</p>

                <!-- Add User Button -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="text-end">
                            <a href="{{ route('users.create') }}" class="btn btn-success">
                                <i class="bx bx-plus"></i> Add User
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                @if($users->count() > 0)
                    <div class="table-responsive">
                        <table id="usersTable" class="table table-bordered" style="border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                            <thead class="table-light" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    @php
                                        $date = \Carbon\Carbon::parse($user->created_at);
                                    @endphp
                                    <tr style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); border-left: 4px solid #007bff;">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-xs me-3">
                                                    <span class="avatar-title rounded-circle bg-primary text-white font-size-16">
                                                        {{ substr($user->name, 0, 1) }}
                                                    </span>
                                                </div>
                                                <strong>{{ $user->name }}</strong>
                                            </div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $date->format('F j, Y g:iA') }}</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('users.edit', ['id' => $user->id]) }}" class="btn btn-outline-success btn-sm">
                                                    <i class="bx bx-edit-alt"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm delete-user"
                                                        data-id="{{ $user->id }}"
                                                        data-name="{{ $user->name }}">
                                                    <i class="bx bx-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $users->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-user-x" style="font-size: 4rem; color: #ccc;"></i>
                        <h5 class="mt-3 text-muted">No users found</h5>
                        <p class="text-muted">No users have been created yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addUserForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add-name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="add-name" name="name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="add-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="add-email" name="email" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="add-password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="add-password" name="password" required>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Delete Validation Modals -->

<!-- Cannot Delete Self Modal -->
<div class="modal fade" id="cannotDeleteSelfModal" tabindex="-1" aria-labelledby="cannotDeleteSelfModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white" id="cannotDeleteSelfModalLabel">
                    <i class="bx bx-error-circle me-2"></i>Cannot Delete Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="bx bx-user-x display-1 text-warning mb-3"></i>
                    <h5>You cannot delete your own account</h5>
                    <p class="text-muted">For security reasons, you cannot delete the account you are currently logged in with.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Cannot Delete Last User Modal -->
<div class="modal fade" id="cannotDeleteLastUserModal" tabindex="-1" aria-labelledby="cannotDeleteLastUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="cannotDeleteLastUserModalLabel">
                    <i class="bx bx-error-circle me-2"></i>Cannot Delete User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="bx bx-shield-x display-1 text-danger mb-3"></i>
                    <h5>Cannot delete the only remaining user</h5>
                    <p class="text-muted">The system must have at least one active user. This is the last remaining user and cannot be deleted.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="confirmDeleteModalLabel">
                    <i class="bx bx-trash me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="bx bx-error-circle display-1 text-warning mb-3"></i>
                    <h5>Are you sure you want to delete this user?</h5>
                    <div class="alert alert-warning mt-3">
                        <strong>User:</strong> <span id="deleteUserName"></span><br>
                        <strong>Email:</strong> <span id="deleteUserEmail"></span>
                    </div>
                    <p class="text-muted">This action will deactivate the user account. The user will no longer be able to access the system.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">
                    <i class="bx bx-trash me-1"></i>Delete User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white" id="successModalLabel">
                    <i class="bx bx-check-circle me-2"></i>Success
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="bx bx-check-circle display-1 text-success mb-3"></i>
                    <h5 id="successMessage">User deleted successfully!</h5>
                    <p class="text-muted">The user has been deactivated and removed from the system.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white" id="errorModalLabel">
                    <i class="bx bx-error-circle me-2"></i>Error
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="bx bx-error-circle display-1 text-danger mb-3"></i>
                    <h5 id="errorMessage">An error occurred</h5>
                    <p class="text-muted">Please try again or contact support if the problem persists.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('style')
<style>
.table {
    border-collapse: separate;
    border-spacing: 0;
}

.table th {
    border: none;
    padding: 15px 12px;
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.table td {
    border: none;
    border-bottom: 1px solid #e9ecef;
    padding: 15px 12px;
    vertical-align: middle;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

.table tbody tr {
    transition: all 0.3s ease;
}

.table tbody tr:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.badge {
    font-weight: 500;
    font-size: 0.75rem;
}

.table-responsive {
    border-radius: 10px;
    overflow: hidden;
}

/* Custom scrollbar for table */
.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.375rem;
}

.avatar-title {
    width: 2.5rem;
    height: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endsection

@section('script')
<!-- Sweet Alerts js -->
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
    $(document).ready(function() {
        // Check for success message from edit page
        const successMessage = sessionStorage.getItem('userUpdateSuccess');
        if (successMessage) {
            // Show success alert
            const alertHtml = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-2"></i>
                    ${successMessage}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            $('.container-fluid').prepend(alertHtml);

            // Remove the message from sessionStorage
            sessionStorage.removeItem('userUpdateSuccess');
        }
        // Add User Form Submission
        $('#addUserForm').on('submit', function(e) {
            e.preventDefault();

            // Clear previous error states
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            $.ajax({
                url: '{{ route("users.store") }}',
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#addUserModal').modal('hide');
                        $('#addUserForm')[0].reset();

                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        Object.keys(errors).forEach(field => {
                            $(`#add-${field}`).addClass('is-invalid');
                            $(`#add-${field}`).next('.invalid-feedback').text(errors[field][0]);
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: xhr.responseJSON.message || 'Something went wrong!',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            });
        });



        // Delete User Button Click
        $(document).on('click', '.delete-user', function() {
            const userId = $(this).data('id');
            const userName = $(this).data('name');

            // First, check if the user can be deleted
            $.ajax({
                url: `/users/check-delete/${userId}`,
                method: 'GET',
                success: function(response) {
                    if (response.canDelete) {
                        // Show confirmation modal
                        $('#deleteUserName').text(response.user.name);
                        $('#deleteUserEmail').text(response.user.email);
                        $('#confirmDeleteModal').modal('show');

                        // Store user ID for actual deletion
                        $('#confirmDeleteButton').data('user-id', userId);
                    } else {
                        // Show appropriate error modal based on reason
                        if (response.reason === 'self_delete') {
                            $('#cannotDeleteSelfModal').modal('show');
                        } else if (response.reason === 'last_user') {
                            $('#cannotDeleteLastUserModal').modal('show');
                        } else {
                            // Show error modal for other errors
                            $('#errorMessage').text(response.message || 'Cannot delete user.');
                            $('#errorModal').modal('show');
                        }
                    }
                },
                error: function(xhr) {
                    $('#errorMessage').text(xhr.responseJSON?.message || 'Failed to check user deletion status.');
                    $('#errorModal').modal('show');
                }
            });
        });

        // Handle actual deletion when confirmed
        $('#confirmDeleteButton').on('click', function() {
            const userId = $(this).data('user-id');
            const button = $(this);
            const originalText = button.html();

            // Show loading state
            button.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

            $.ajax({
                url: `/users/${userId}`,
                method: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                                success: function(response) {
                    if (response.success) {
                        $('#confirmDeleteModal').modal('hide');

                        // Show success modal
                        $('#successMessage').text(response.message);
                        $('#successModal').modal('show');

                        // Handle success modal close event
                        $('#successModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                            // Remove the user row from the table instead of reloading
                            $(`button.delete-user[data-id="${userId}"]`).closest('tr').fadeOut(400, function() {
                                $(this).remove();

                                // Update the table if no users left
                                if ($('#usersTable tbody tr:visible').length === 0) {
                                    $('#usersTable tbody').html(`
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="bx bx-user-x display-4 d-block mb-2"></i>
                                                No users found
                                            </td>
                                        </tr>
                                    `);
                                }
                            });
                        });
                    }
                },
                error: function(xhr) {
                    $('#errorMessage').text(xhr.responseJSON?.message || 'Failed to delete user!');
                    $('#errorModal').modal('show');
                },
                complete: function() {
                    // Restore button state
                    button.prop('disabled', false).html(originalText);
                }
            });
        });
    });
</script>
@endsection
