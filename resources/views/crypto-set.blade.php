@extends('layouts.master')

@section('title') Crypto Set @endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') Crypto @endslot
@slot('title') Crypto Set @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Crypto Set</h4>
                <p class="card-title-desc">Set your current crypto trading notifications and settings.</p>

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

                @if($tasks->count() > 0)
                    <div class="row">
                        @foreach($tasks as $task)
                            <div class="col-xl-6 col-lg-6 col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <div class="avatar-sm rounded">
                                                    <span class="avatar-title bg-primary-subtle text-primary rounded font-size-20">
                                                        @if(strtolower($task->taskCoin) === 'btc')
                                                            🪙
                                                        @else
                                                            {{ strtoupper($task->taskCoin) }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="font-size-16 mb-1">
                                                    Coin To Trade:
                                                    @if(strtolower($task->taskCoin) === 'btc')
                                                        Bitcoin
                                                    @else
                                                        {{ strtoupper($task->taskCoin) }}
                                                    @endif
                                                </h5>
                                                <span class="badge bg-{{ $task->taskType === 'to sell' ? 'danger' : 'success' }} font-size-12">
                                                    {{ $task->taskType === 'to sell' ? 'To Sell Crypto' : 'To Buy Crypto' }}
                                                </span>
                                            </div>
                                        </div>

                                        @if($task->taskType === 'to sell')
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Current Coin Value:</label>
                                                        <p class="text-muted mb-0">{{ $task->currentCoinValue }} {{ strtoupper($task->taskCoin) }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">PHP Value Before Buying BTC:</label>
                                                        <p class="text-muted mb-0">₱{{ number_format($task->startingPhpValue, 2) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Minimum Threshold Before Notification:</label>
                                                        <p class="text-muted mb-0">₱{{ number_format($task->minThreshold, 2) }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Threshold Interval Before Sending Notice Again:</label>
                                                        <p class="text-muted mb-0">₱{{ number_format($task->intervalThreshold, 2) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Current PHP Value:</label>
                                                        <p class="text-muted mb-0">₱{{ number_format($task->toBuyCurrentCashValue, 2) }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">BTC Value Before Selling BTC:</label>
                                                        <p class="text-muted mb-0">{{ $task->toBuyStartingCoinValue }} {{ strtoupper($task->taskCoin) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Minimum Threshold Before Notification:</label>
                                                        <p class="text-muted mb-0">₱{{ number_format($task->toBuyMinThreshold, 2) }}</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Threshold Interval Before Sending Notice Again:</label>
                                                        <p class="text-muted mb-0">₱{{ number_format($task->toBuyIntervalThreshold, 2) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="mt-3 d-flex gap-2">
                                            <a href="{{ route('crypto-set-change.index', ['id' => $task->id]) }}" class="btn btn-primary waves-effect waves-light">
                                                <i class="bx bx-edit me-1"></i> Create New Set
                                            </a>
                                            <a href="{{ route('crypto-set-update.index', ['id' => $task->id]) }}" class="btn btn-secondary waves-effect waves-light">
                                                <i class="bx bx-refresh me-1"></i> Update Current Set
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center">
                        <div class="avatar-md mx-auto">
                            <div class="avatar-title bg-light text-primary rounded-circle font-size-24">
                                <i class="bx bx-info-circle"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h4>No Tasks Found</h4>
                            <p class="text-muted">You haven't created any crypto trading tasks yet.</p>
                            <a href="{{ route('crypto-set-change.index', ['id' => Auth::user()->id]) }}" class="btn btn-primary waves-effect waves-light">
                                <i class="bx bx-plus me-1"></i> Create New Task
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
