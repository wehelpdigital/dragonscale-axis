@extends('layouts.master')

@section('title') Add Income Log @endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') Crypto @endslot
@slot('li_2') Income Logger @endslot
@slot('title') Add Income Log @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Add New Income Log</h4>
                <p class="card-title-desc">Add a new income log entry to track your crypto trading activities.</p>

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

                <form method="POST" action="{{ route('crypto-income-logger-store') }}" id="addIncomeLogForm">
                    @csrf

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="taskCoin" class="form-label">Task Coin <span class="text-danger">*</span></label>
                                <select class="form-select @error('taskCoin') is-invalid @enderror" id="taskCoin" name="taskCoin" required>
                                    <option value="">Select Coin</option>
                                    <option value="btc" {{ old('taskCoin') == 'btc' ? 'selected' : '' }}>Bitcoin (BTC)</option>
                                </select>
                                @error('taskCoin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="taskType" class="form-label">Task Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('taskType') is-invalid @enderror" id="taskType" name="taskType" required>
                                    <option value="">Select Task Type</option>
                                    <option value="to buy" {{ old('taskType') == 'to buy' ? 'selected' : '' }}>To Buy</option>
                                    <option value="to sell" {{ old('taskType') == 'to sell' ? 'selected' : '' }}>To Sell</option>
                                </select>
                                @error('taskType')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="transactionDate" class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                <input type="date"
                                       class="form-control @error('transactionDate') is-invalid @enderror"
                                       id="transactionDate"
                                       name="transactionDate"
                                       value="{{ old('transactionDate', date('Y-m-d')) }}"
                                       required>
                                @error('transactionDate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="transactionTime" class="form-label">Transaction Time <span class="text-danger">*</span></label>
                                <input type="time"
                                       class="form-control @error('transactionTime') is-invalid @enderror"
                                       id="transactionTime"
                                       name="transactionTime"
                                       value="{{ old('transactionTime', date('H:i')) }}"
                                       required>
                                @error('transactionTime')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="originalPhpValue" class="form-label">Original PHP Value <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number"
                                           class="form-control @error('originalPhpValue') is-invalid @enderror"
                                           id="originalPhpValue"
                                           name="originalPhpValue"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00"
                                           value="{{ old('originalPhpValue') }}"
                                           required>
                                </div>
                                @error('originalPhpValue')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="newPhpValue" class="form-label">New PHP Value <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number"
                                           class="form-control @error('newPhpValue') is-invalid @enderror"
                                           id="newPhpValue"
                                           name="newPhpValue"
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00"
                                           value="{{ old('newPhpValue') }}"
                                           required>
                                </div>
                                @error('newPhpValue')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Difference Display -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="bx bx-info-circle me-2"></i>
                                <strong>Difference:</strong>
                                <span id="differenceDisplay" class="fw-bold">₱0.00</span>
                                <span id="differencePercentage" class="ms-2"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('crypto-income-logger') }}" class="btn btn-secondary btn-lg">
                                    <i class="bx bx-x me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bx bx-save me-2"></i>Save
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
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
        padding: 12px 24px !important;
        font-weight: 500 !important;
        transition: all 0.3s ease !important;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .input-group-text {
        border-radius: 10px 0 0 10px !important;
        border: 2px solid #e9ecef !important;
        background-color: #f8f9fa;
    }

    .input-group .form-control {
        border-radius: 0 10px 10px 0 !important;
        border-left: none !important;
    }

    .alert {
        border-radius: 10px !important;
        border: none !important;
    }

    .alert-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        color: #0c5460;
    }

    /* Smooth transitions */
    * {
        transition: all 0.2s ease;
    }

    /* Better spacing */
    .mb-3 {
        margin-bottom: 1.5rem !important;
    }

    .gap-2 {
        gap: 0.75rem !important;
    }
</style>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const originalValueInput = document.getElementById('originalPhpValue');
        const newValueInput = document.getElementById('newPhpValue');
        const differenceDisplay = document.getElementById('differenceDisplay');
        const differencePercentage = document.getElementById('differencePercentage');

        function calculateDifference() {
            const original = parseFloat(originalValueInput.value) || 0;
            const newValue = parseFloat(newValueInput.value) || 0;
            const difference = newValue - original;
            const percentage = original > 0 ? ((difference / original) * 100) : 0;

            // Format difference with 2 decimal places
            differenceDisplay.textContent = `₱${difference.toFixed(2)}`;

            // Set color based on positive/negative difference
            if (difference > 0) {
                differenceDisplay.className = 'fw-bold text-success';
            } else if (difference < 0) {
                differenceDisplay.className = 'fw-bold text-danger';
            } else {
                differenceDisplay.className = 'fw-bold text-muted';
            }

            // Show percentage if original value exists
            if (original > 0) {
                const sign = percentage >= 0 ? '+' : '';
                differencePercentage.textContent = `(${sign}${percentage.toFixed(2)}%)`;
                differencePercentage.className = percentage >= 0 ? 'text-success' : 'text-danger';
            } else {
                differencePercentage.textContent = '';
            }
        }

        // Calculate difference on input change
        originalValueInput.addEventListener('input', calculateDifference);
        newValueInput.addEventListener('input', calculateDifference);

        // Calculate initial difference if values are pre-filled
        calculateDifference();

        // Form validation
        const form = document.getElementById('addIncomeLogForm');
        form.addEventListener('submit', function(e) {
            const original = parseFloat(originalValueInput.value) || 0;
            const newValue = parseFloat(newValueInput.value) || 0;

            if (original === 0 && newValue === 0) {
                e.preventDefault();
                alert('Please enter valid values for both Original and New PHP Values.');
                return false;
            }
        });
    });
</script>
@endsection
