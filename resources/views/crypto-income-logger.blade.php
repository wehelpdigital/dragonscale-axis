@extends('layouts.master')

@section('title') Crypto Income Logger @endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') Crypto @endslot
@slot('title') Crypto Income Logger @endslot
@endcomponent

<!-- Page Description -->
<div class="row">
    <div class="col-12">
        <p class="text-muted mb-4">
            This module allows you to comprehensively log and track your cryptocurrency trading activities including purchases and sales.
            Calculate, save, and monitor your income, profits, and savings over time to gain valuable insights into your trading performance
            and make data-driven investment decisions.
        </p>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Crypto Income Logger</h4>
                <p class="card-title-desc">Track your crypto trading income and analyze your performance.</p>

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

                <!-- Filters Section -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Filters</h5>
                                <form method="GET" action="{{ route('crypto-income-logger') }}" id="filterForm">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="start_date" class="form-label">Start Date</label>
                                                <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="end_date" class="form-label">End Date</label>
                                                <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="task_type" class="form-label">Task Type</label>
                                                <select class="form-select" id="task_type" name="task_type">
                                                    <option value="">All Task Types</option>
                                                    @foreach($taskTypes as $type)
                                                        <option value="{{ $type }}" {{ $taskType == $type ? 'selected' : '' }}>
                                                            {{ ucfirst($type) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="coin_type" class="form-label">Coin Type</label>
                                                <select class="form-select" id="coin_type" name="coin_type">
                                                    <option value="">All Coins</option>
                                                    @foreach($coinTypes as $coin)
                                                        <option value="{{ $coin }}" {{ $coinType == $coin ? 'selected' : '' }}>
                                                            {{ strtoupper($coin) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="bx bx-filter-alt me-1"></i> Apply Filters
                                            </button>
                                            <a href="{{ route('crypto-income-logger') }}" class="btn btn-secondary">
                                                <i class="bx bx-refresh me-1"></i> Clear Filters
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Income Log Button -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('crypto-income-logger-add') }}" class="btn btn-primary btn-lg">
                                <i class="bx bx-plus-circle me-2"></i>Add Income Log
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Income Logger Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Task Coin</th>
                                <th>Task Type</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Original Value (₱)</th>
                                <th>New Php Value (₱)</th>
                                <th>Difference (₱)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomeLogs as $log)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary rounded-pill">
                                            {{ strtoupper($log->taskCoin) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $log->taskType === 'to buy' ? 'danger' : 'success' }} rounded-pill">
                                            {{ ucfirst($log->taskType) }}
                                        </span>
                                    </td>
                                    <td>{{ $log->transactionDateTime ? $log->transactionDateTime->format('F j, Y') : $log->created_at->format('F j, Y') }}</td>
                                    <td>{{ $log->transactionDateTime ? $log->transactionDateTime->format('g:i A') : $log->created_at->format('g:i A') }}</td>
                                    <td class="text-end">₱{{ number_format($log->originalPhpValue, 2) }}</td>
                                    <td class="text-end">₱{{ number_format($log->newPhpValue, 2) }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ ($log->newPhpValue - $log->originalPhpValue) >= 0 ? 'success' : 'danger' }} rounded-pill">
                                            ₱{{ number_format($log->newPhpValue - $log->originalPhpValue, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button"
                                                class="btn btn-danger btn-sm delete-btn"
                                                data-id="{{ $log->id }}"
                                                data-coin="{{ $log->taskCoin }}"
                                                data-type="{{ $log->taskType }}"
                                                data-datetime="{{ $log->transactionDateTime ? $log->transactionDateTime->format('M j, Y g:i A') : $log->created_at->format('M j, Y g:i A') }}">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bx bx-info-circle fs-1 d-block mb-2"></i>
                                        No income logs found for the selected filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($incomeLogs->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $incomeLogs->appends(request()->query())->links() }}
                    </div>
                @endif

                <!-- Summary Section -->
                <div class="row mt-5">
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-body">
                                <h5 class="card-title text-danger">
                                    <i class="bx bx-trending-down me-2"></i>To Buy Summary
                                </h5>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h6 class="text-muted mb-1">Total To Buy Difference</h6>
                                            <h4 class="text-danger mb-0">₱{{ number_format($totalToBuyDifference, 2) }}</h4>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h6 class="text-muted mb-1">Filtered To Buy Difference</h6>
                                            <h4 class="text-danger mb-0">₱{{ number_format($filteredToBuyDifference, 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-body">
                                <h5 class="card-title text-success">
                                    <i class="bx bx-trending-up me-2"></i>To Sell Summary
                                </h5>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h6 class="text-muted mb-1">Total To Sell Difference</h6>
                                            <h4 class="text-success mb-0">₱{{ number_format($totalToSellDifference, 2) }}</h4>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center p-3 bg-light rounded">
                                            <h6 class="text-muted mb-1">Filtered To Sell Difference</h6>
                                            <h4 class="text-success mb-0">₱{{ number_format($filteredToSellDifference, 2) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="deleteConfirmModalLabel">
                    <i class="bx bx-trash me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this income log entry?</p>
                <div class="alert alert-warning">
                    <strong>Entry Details:</strong><br>
                    <span id="deleteEntryDetails"></span>
                </div>
                <p class="text-muted small">This action will mark the entry as deleted but will not permanently remove it from the database.</p>
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

@section('css')
<style>
    /* Eye-friendly curved design */
    .card {
        border-radius: 15px !important;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
        border: none !important;
    }

    .card-body {
        border-radius: 15px !important;
    }

    .table {
        border-radius: 10px;
        overflow: hidden;
    }

    .table th {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: none;
        padding: 15px 12px;
        font-weight: 600;
        color: #495057;
    }

    .table td {
        padding: 12px;
        border-color: #f1f3f4;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
        transform: translateY(-1px);
        transition: all 0.2s ease;
    }

    .badge {
        border-radius: 20px !important;
        padding: 8px 16px !important;
        font-weight: 500 !important;
    }

    .form-control, .form-select {
        border-radius: 10px !important;
        border: 2px solid #e9ecef !important;
        transition: all 0.3s ease !important;
    }

    .form-control:focus, .form-select:focus {
        border-color: #556ee6 !important;
        box-shadow: 0 0 0 0.2rem rgba(85, 110, 230, 0.25) !important;
    }

    .btn {
        border-radius: 10px !important;
        padding: 10px 20px !important;
        font-weight: 500 !important;
        transition: all 0.3s ease !important;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .border-danger {
        border-color: #dc3545 !important;
        border-width: 2px !important;
    }

    .border-success {
        border-color: #198754 !important;
        border-width: 2px !important;
    }

    .summary-card {
        background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .summary-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 10px 0;
    }

    .pagination {
        border-radius: 10px;
        overflow: hidden;
    }

    .page-link {
        border-radius: 8px;
        margin: 0 2px;
        border: none;
        color: #556ee6;
    }

    .page-item.active .page-link {
        background-color: #556ee6;
        border-color: #556ee6;
    }

    /* Smooth transitions */
    * {
        transition: all 0.2s ease;
    }

    /* Better spacing */
    .mb-4 {
        margin-bottom: 2rem !important;
    }

    .mt-5 {
        margin-top: 3rem !important;
    }

    /* Enhanced table responsiveness */
    .table-responsive {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Delete button styling */
    .delete-btn {
        border-radius: 8px !important;
        padding: 6px 12px !important;
        transition: all 0.3s ease !important;
    }

    .delete-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }

    /* Modal styling */
    .modal-content {
        border-radius: 15px !important;
        border: none !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2) !important;
    }

    .modal-header {
        border-bottom: 2px solid #f8f9fa !important;
        border-radius: 15px 15px 0 0 !important;
    }

    .modal-footer {
        border-top: 2px solid #f8f9fa !important;
        border-radius: 0 0 15px 15px !important;
    }

    .alert-warning {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        border: 2px solid #ffc107;
        color: #856404;
    }
</style>
@endsection

@section('script')
<script>
    // Auto-submit form when filters change
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.getElementById('filterForm');
        const filterInputs = filterForm.querySelectorAll('select, input[type="date"]');

        filterInputs.forEach(input => {
            input.addEventListener('change', function() {
                filterForm.submit();
            });
        });

        // Delete functionality
        let deleteId = null;

        // Handle delete button clicks
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                deleteId = this.getAttribute('data-id');
                const coin = this.getAttribute('data-coin');
                const type = this.getAttribute('data-type');
                const datetime = this.getAttribute('data-datetime');

                // Update modal content
                document.getElementById('deleteEntryDetails').innerHTML =
                    `<strong>${coin.toUpperCase()}</strong> - ${type} - ${datetime}`;

                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
                modal.show();
            });
        });

        // Handle confirm delete
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteId) {
                // Show loading state
                this.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...';
                this.disabled = true;

                // Send delete request
                fetch(`/crypto-income-logger-delete/${deleteId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        showAlert('success', 'Income log deleted successfully!');

                        // Reload the page to update the table
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showAlert('error', data.message || 'An error occurred while deleting the income log.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'An error occurred while deleting the income log.');
                })
                .finally(() => {
                    // Reset button state
                    this.innerHTML = '<i class="bx bx-trash me-1"></i>Delete';
                    this.disabled = false;

                    // Hide modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
                    modal.hide();
                });
            }
        });
    });

    // Function to show alerts
    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const iconClass = type === 'success' ? 'bx-check-circle' : 'bx-error-circle';

        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="bx ${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;

        // Insert alert at the top of the card body
        const cardBody = document.querySelector('.card-body');
        cardBody.insertAdjacentHTML('afterbegin', alertHtml);

        // Auto-remove alert after 5 seconds
        setTimeout(() => {
            const alert = cardBody.querySelector('.alert');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
</script>
@endsection
