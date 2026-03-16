@extends('layouts.master')

@section('title') Trigger Tasks @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr CSS -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .stat-card {
        border-radius: 10px;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .stat-card .stat-label {
        font-size: 0.8rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .nav-tabs-custom .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        color: #6c757d;
    }
    .nav-tabs-custom .nav-link.active {
        color: #556ee6;
        border-bottom-color: #556ee6;
        background: transparent;
    }
    .nav-tabs-custom .nav-link:hover:not(.active) {
        border-bottom-color: #e2e8f0;
    }
    .progress-sm {
        height: 6px;
        border-radius: 3px;
    }
    .task-timeline {
        position: relative;
        padding-left: 30px;
    }
    .task-timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e2e8f0;
    }
    .task-timeline-item {
        position: relative;
        padding-bottom: 1rem;
    }
    .task-timeline-item::before {
        content: '';
        position: absolute;
        left: -24px;
        top: 4px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #6c757d;
    }
    .task-timeline-item.completed::before {
        background: #10B981;
        border-color: #10B981;
    }
    .task-timeline-item.running::before {
        background: #F59E0B;
        border-color: #F59E0B;
        animation: pulse 1.5s infinite;
    }
    .task-timeline-item.failed::before {
        background: #EF4444;
        border-color: #EF4444;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
    .cron-url-box {
        background: #1e293b;
        border-radius: 8px;
        padding: 1rem;
        font-family: 'SFMono-Regular', Consolas, monospace;
        font-size: 0.85rem;
        color: #e2e8f0;
        word-break: break-all;
    }
    .cron-url-box .url-part {
        color: #22d3ee;
    }
    .cron-url-box .key-part {
        color: #fbbf24;
    }
    .instruction-step {
        display: flex;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    .instruction-step .step-number {
        width: 28px;
        height: 28px;
        background: #556ee6;
        color: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.85rem;
        flex-shrink: 0;
        margin-right: 1rem;
    }
    .node-type-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        color: #fff;
    }
    .flow-group {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .flow-group-header {
        background: linear-gradient(135deg, #556ee6 0%, #3b5bdb 100%);
        color: #fff;
        padding: 0.75rem 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .flow-group-header:hover {
        background: linear-gradient(135deg, #4c63d2 0%, #3451c9 100%);
    }
    .flow-group-header .flow-name {
        font-weight: 600;
        font-size: 1rem;
    }
    .flow-group-header .flow-stats {
        display: flex;
        gap: 1rem;
        font-size: 0.8rem;
    }
    .enrollment-group {
        border-bottom: 1px solid #e2e8f0;
    }
    .enrollment-group:last-child {
        border-bottom: none;
    }
    .enrollment-header {
        background: #f8f9fa;
        padding: 0.6rem 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #e2e8f0;
    }
    .enrollment-header:hover {
        background: #f1f3f5;
    }
    .enrollment-header .client-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .enrollment-header .client-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #556ee6;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.85rem;
    }
    .enrollment-tasks {
        padding: 0;
    }
    .task-row {
        display: flex;
        align-items: center;
        padding: 0.6rem 1rem 0.6rem 2.5rem;
        border-bottom: 1px solid #f1f3f5;
        background: #fff;
    }
    .task-row:last-child {
        border-bottom: none;
    }
    .task-row:hover {
        background: #fafbfc;
    }
    .task-row .task-order {
        width: 30px;
        color: #6c757d;
        font-size: 0.8rem;
    }
    .task-row .task-type {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .task-row .task-schedule {
        width: 140px;
        text-align: center;
    }
    .task-row .task-status {
        width: 100px;
        text-align: center;
    }
    .task-row .task-actions {
        width: 80px;
        text-align: right;
    }
    .collapse-icon {
        transition: transform 0.2s;
    }
    .collapsed .collapse-icon {
        transform: rotate(-90deg);
    }
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('title') Trigger Tasks @endslot
@endcomponent

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bx bx-list-ul"></i>
                    </div>
                    <div>
                        <div class="stat-value text-dark">{{ number_format($stats['totalTasks']) }}</div>
                        <div class="stat-label">Total Tasks</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bx bx-time-five"></i>
                    </div>
                    <div>
                        <div class="stat-value text-dark">{{ number_format($stats['pendingTasks']) }}</div>
                        <div class="stat-label">Pending Tasks</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bx bx-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-value text-dark">{{ number_format($stats['completedTasks']) }}</div>
                        <div class="stat-label">Completed Tasks</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card stat-card mb-0">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger me-3">
                        <i class="bx bx-x-circle"></i>
                    </div>
                    <div>
                        <div class="stat-value text-dark">{{ number_format($stats['failedTasks']) }}</div>
                        <div class="stat-label">Failed Tasks</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Card with Tabs -->
<div class="card">
    <div class="card-header bg-white border-bottom-0 pb-0">
        <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'tasks' || $tab === 'enrollments' ? 'active' : '' }}" href="#tasks" data-bs-toggle="tab" role="tab">
                    <i class="bx bx-task me-1"></i>Tasks Queue
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'cron' ? 'active' : '' }}" href="#cron" data-bs-toggle="tab" role="tab">
                    <i class="bx bx-cog me-1"></i>Cron Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'logs' ? 'active' : '' }}" href="#logs" data-bs-toggle="tab" role="tab">
                    <i class="bx bx-list-ul me-1"></i>Logs
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <!-- Tasks Tab -->
            <div class="tab-pane fade {{ $tab === 'tasks' || $tab === 'enrollments' ? 'show active' : '' }}" id="tasks" role="tabpanel">
                <!-- Filters -->
                <div class="row mb-2 align-items-center">
                    <div class="col-md-3">
                        <select class="form-select" id="taskFlowFilter">
                            <option value="">All Flows</option>
                            @foreach($flows as $flow)
                                <option value="{{ $flow->id }}" {{ request('flow_id') == $flow->id ? 'selected' : '' }}>{{ $flow->flowName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="taskStatusFilter">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="ready" {{ request('status') == 'ready' ? 'selected' : '' }}>Ready</option>
                            <option value="running" {{ request('status') == 'running' ? 'selected' : '' }}>Running</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            <option value="skipped" {{ request('status') == 'skipped' ? 'selected' : '' }}>Skipped</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="taskNodeTypeFilter">
                            <option value="">All Node Types</option>
                            <option value="delay" {{ request('node_type') == 'delay' ? 'selected' : '' }}>Delay / Wait</option>
                            <option value="email" {{ request('node_type') == 'email' ? 'selected' : '' }}>Send Email</option>
                            <option value="send_sms" {{ request('node_type') == 'send_sms' ? 'selected' : '' }}>Send SMS</option>
                            <option value="send_whatsapp" {{ request('node_type') == 'send_whatsapp' ? 'selected' : '' }}>Send WhatsApp</option>
                            <option value="if_else" {{ request('node_type') == 'if_else' ? 'selected' : '' }}>If / Else</option>
                            <option value="course_access" {{ request('node_type') == 'course_access' ? 'selected' : '' }}>Grant Course Access</option>
                            <option value="add_as_affiliate" {{ request('node_type') == 'add_as_affiliate' ? 'selected' : '' }}>Add as Affiliate</option>
                        </select>
                    </div>
                </div>
                <!-- Action Buttons -->
                <div class="row mb-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-outline-secondary me-2" id="expandAllFlows">
                            <i class="bx bx-expand-alt me-1"></i>Expand All
                        </button>
                        <button type="button" class="btn btn-primary me-2" id="manualCronRun">
                            <i class="bx bx-play me-1"></i>Run Cron
                        </button>
                        <button type="button" class="btn btn-outline-secondary me-2" id="clearCompletedTasks" title="Remove all completed, cancelled, failed & skipped tasks">
                            <i class="bx bx-trash me-1"></i>Clear Done
                        </button>
                        <button type="button" class="btn btn-outline-secondary me-2" id="clearCompletedGroups" title="Remove all groups where all tasks are completed">
                            <i class="bx bx-folder-minus me-1"></i>Clear Done Groups
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="bulkCancelTasks" disabled>
                            <i class="bx bx-x me-1"></i>Cancel
                        </button>
                    </div>
                </div>

                <!-- Grouped Tasks by Flow & Client -->
                @if($groupedTasks->count() > 0)
                    @foreach($groupedTasks as $flowId => $enrollmentGroups)
                        @php
                            $flowInfo = $enrollmentGroups->first()->first()->enrollment->flow ?? null;
                            $flowTasksCount = $enrollmentGroups->flatten()->count();
                            $flowPendingCount = $enrollmentGroups->flatten()->filter(fn($t) => in_array($t->status, ['pending', 'scheduled', 'ready']))->count();
                            $flowCompletedCount = $enrollmentGroups->flatten()->filter(fn($t) => $t->status === 'completed')->count();
                        @endphp
                        <div class="flow-group">
                            <!-- Flow Header -->
                            <div class="flow-group-header" data-bs-toggle="collapse" data-bs-target="#flow-{{ $flowId }}" aria-expanded="true">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-chevron-down collapse-icon me-2"></i>
                                    <span class="flow-name">{{ $flowInfo->flowName ?? 'Unknown Flow' }}</span>
                                    <span class="badge bg-light text-dark ms-2">{{ $enrollmentGroups->count() }} {{ Str::plural('client', $enrollmentGroups->count()) }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="flow-stats">
                                        <span><i class="bx bx-list-ul me-1"></i>{{ $flowTasksCount }} tasks</span>
                                        <span><i class="bx bx-time me-1"></i>{{ $flowPendingCount }} pending</span>
                                        <span><i class="bx bx-check me-1"></i>{{ $flowCompletedCount }} done</span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light delete-flow-tasks-btn" data-flow-id="{{ $flowId }}" data-flow-name="{{ $flowInfo->flowName ?? 'Unknown Flow' }}" title="Delete all tasks in this flow">
                                        <i class="bx bx-trash text-danger"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Enrollments within Flow -->
                            <div class="collapse show" id="flow-{{ $flowId }}">
                                @foreach($enrollmentGroups as $enrollmentId => $enrollmentTasks)
                                    @php
                                        $enrollment = $enrollmentTasks->first()->enrollment ?? null;
                                        $client = $enrollment->client ?? null;
                                        $order = $enrollment->order ?? null;
                                        $contextData = $enrollment->contextData ?? [];

                                        // Try to get client name from client record first, then fall back to contextData
                                        if ($client) {
                                            $clientName = $client->fullName ?? $client->clientFirstName ?? 'Unknown Client';
                                            $clientEmail = $client->clientEmailAddress ?? null;
                                        } else {
                                            // Fall back to contextData (for Shopping Abandonment where clientId is null)
                                            $clientName = $contextData['client_name'] ?? $contextData['clientName'] ??
                                                         (($contextData['client_first_name'] ?? '') . ' ' . ($contextData['client_last_name'] ?? '')) ?? 'Unknown Client';
                                            $clientName = trim($clientName) ?: 'Unknown Client';
                                            $clientEmail = $contextData['client_email'] ?? $contextData['clientEmail'] ?? null;
                                        }

                                        $clientInitial = strtoupper(substr($clientName, 0, 1));
                                        $enrollmentPendingCount = $enrollmentTasks->filter(fn($t) => in_array($t->status, ['pending', 'scheduled', 'ready']))->count();
                                        $enrollmentCompletedCount = $enrollmentTasks->filter(fn($t) => $t->status === 'completed')->count();
                                    @endphp
                                    <div class="enrollment-group">
                                        <!-- Client Header -->
                                        <div class="enrollment-header">
                                            <div class="client-info" data-bs-toggle="collapse" data-bs-target="#enrollment-{{ $enrollmentId }}" aria-expanded="true" style="flex: 1; cursor: pointer;">
                                                <i class="bx bx-chevron-down collapse-icon text-secondary"></i>
                                                <span class="client-avatar">{{ $clientInitial }}</span>
                                                <div>
                                                    <strong class="text-dark">{{ $clientName }}</strong>
                                                    @if($clientEmail)
                                                        <br><small class="text-secondary">{{ $clientEmail }}</small>
                                                    @endif
                                                </div>
                                                @if($order)
                                                    <span class="badge bg-info text-white ms-2">Order #{{ $order->orderNumber ?? $order->id }}</span>
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <small class="text-secondary">
                                                    {{ $enrollmentTasks->count() }} tasks |
                                                    <span class="text-warning">{{ $enrollmentPendingCount }} pending</span> |
                                                    <span class="text-success">{{ $enrollmentCompletedCount }} done</span>
                                                </small>
                                                @if($enrollmentPendingCount > 0)
                                                    <button type="button" class="btn btn-sm btn-outline-danger cancel-enrollment-btn" data-id="{{ $enrollmentId }}" title="Cancel All Pending">
                                                        <i class="bx bx-x"></i>
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-outline-secondary delete-enrollment-btn" data-id="{{ $enrollmentId }}" data-client-name="{{ $clientName }}" title="Delete All Tasks">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Tasks -->
                                        <div class="collapse show enrollment-tasks" id="enrollment-{{ $enrollmentId }}">
                                            @foreach($enrollmentTasks->sortBy('taskOrder') as $task)
                                                <div class="task-row" data-task-id="{{ $task->id }}">
                                                    <div class="task-order">#{{ $task->taskOrder }}</div>
                                                    <div class="task-type">
                                                        <span class="node-type-icon bg-{{ $task->status === 'completed' ? 'success' : ($task->status === 'failed' ? 'danger' : 'primary') }}" style="width: 24px; height: 24px; font-size: 0.75rem;">
                                                            <i class="bx {{ $task->nodeTypeIcon }}"></i>
                                                        </span>
                                                        <span class="text-dark">{{ $task->nodeTypeLabel }}</span>
                                                        @if($task->nodeType === 'delay' && isset($task->nodeData['delayValue']))
                                                            <small class="text-secondary ms-1">({{ $task->nodeData['delayValue'] }} {{ $task->nodeData['delayType'] ?? 'minutes' }})</small>
                                                        @endif
                                                    </div>
                                                    <div class="task-schedule">
                                                        @if($task->scheduledAt)
                                                            <small class="text-dark">{{ $task->scheduledAt->format('M j, g:i A') }}</small>
                                                        @else
                                                            <small class="text-secondary">-</small>
                                                        @endif
                                                    </div>
                                                    <div class="task-status">
                                                        <span class="badge {{ $task->statusBadgeClass }}" style="font-size: 0.7rem;">{{ $task->statusLabel }}</span>
                                                    </div>
                                                    <div class="task-actions">
                                                        @if(in_array($task->status, ['pending', 'scheduled', 'ready']))
                                                            <button type="button" class="btn btn-sm btn-outline-danger cancel-task-btn" data-id="{{ $task->id }}" title="Cancel">
                                                                <i class="bx bx-x" style="font-size: 0.8rem;"></i>
                                                            </button>
                                                        @endif
                                                        @if($task->canRetry())
                                                            <button type="button" class="btn btn-sm btn-outline-warning retry-task-btn" data-id="{{ $task->id }}" title="Retry">
                                                                <i class="bx bx-refresh" style="font-size: 0.8rem;"></i>
                                                            </button>
                                                        @endif
                                                        @if(in_array($task->status, ['completed', 'cancelled', 'failed', 'skipped']))
                                                            <button type="button" class="btn btn-sm btn-outline-secondary delete-task-btn" data-id="{{ $task->id }}" title="Remove">
                                                                <i class="bx bx-trash" style="font-size: 0.8rem;"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-check-circle display-4 text-success"></i>
                        <p class="text-dark mt-3 mb-1">No tasks in queue</p>
                        <small class="text-secondary">Tasks will appear here when flows are triggered</small>
                    </div>
                @endif
            </div>

            <!-- Cron Settings Tab -->
            <div class="tab-pane fade {{ $tab === 'cron' ? 'show active' : '' }}" id="cron" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Cron URL Section -->
                        <div class="card border mb-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0 text-dark">
                                    <i class="bx bx-link me-2"></i>Cron URL
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-secondary mb-3">Use this URL with external cron services (like cron-job.org, EasyCron, or your hosting's cron):</p>

                                <div class="cron-url-box mb-3" id="cronUrlBox">
                                    <span class="url-part">{{ url('/api/trigger-cron') }}</span>?key=<span class="key-part" id="cronSecretDisplay">{{ $cronSettings['secretKey'] }}</span>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="copyCronUrl">
                                        <i class="bx bx-copy me-1"></i>Copy URL
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-sm" id="regenerateSecret">
                                        <i class="bx bx-refresh me-1"></i>Regenerate Secret Key
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Setup Instructions -->
                        <div class="card border mb-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0 text-dark">
                                    <i class="bx bx-book-open me-2"></i>Setup Instructions
                                </h5>
                            </div>
                            <div class="card-body">
                                <h6 class="text-dark mb-3">Option 1: Using cron-job.org (Recommended for Shared Hosting)</h6>
                                <div class="instruction-step">
                                    <div class="step-number">1</div>
                                    <div>
                                        <strong class="text-dark">Create a free account</strong>
                                        <p class="text-secondary mb-0">Go to <a href="https://cron-job.org" target="_blank">cron-job.org</a> and sign up for a free account.</p>
                                    </div>
                                </div>
                                <div class="instruction-step">
                                    <div class="step-number">2</div>
                                    <div>
                                        <strong class="text-dark">Create a new cron job</strong>
                                        <p class="text-secondary mb-0">Click "Create cronjob" and paste the cron URL shown above.</p>
                                    </div>
                                </div>
                                <div class="instruction-step">
                                    <div class="step-number">3</div>
                                    <div>
                                        <strong class="text-dark">Set the schedule</strong>
                                        <p class="text-secondary mb-0">Set it to run every 1 minute (or 30 seconds if available on paid plans).</p>
                                    </div>
                                </div>
                                <div class="instruction-step">
                                    <div class="step-number">4</div>
                                    <div>
                                        <strong class="text-dark">Save and enable</strong>
                                        <p class="text-secondary mb-0">Save the cron job and make sure it's enabled.</p>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <h6 class="text-dark mb-3">Option 2: Using cPanel Cron Jobs</h6>
                                <div class="instruction-step">
                                    <div class="step-number">1</div>
                                    <div>
                                        <strong class="text-dark">Open cPanel</strong>
                                        <p class="text-secondary mb-0">Login to your hosting cPanel and go to "Cron Jobs".</p>
                                    </div>
                                </div>
                                <div class="instruction-step">
                                    <div class="step-number">2</div>
                                    <div>
                                        <strong class="text-dark">Add new cron job</strong>
                                        <p class="text-secondary mb-0">Set schedule to "Once Per Minute" and use this command:</p>
                                        <div class="cron-url-box mt-2" style="font-size: 0.8rem;">
                                            curl -s "{{ url('/api/trigger-cron') }}?key={{ $cronSettings['secretKey'] }}" > /dev/null 2>&1
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Settings Panel -->
                        <div class="card border mb-4">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0 text-dark">
                                    <i class="bx bx-cog me-2"></i>Settings
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="cronEnabled" {{ $cronSettings['enabled'] ? 'checked' : '' }}>
                                        <label class="form-check-label text-dark" for="cronEnabled">Enable Cron Processing</label>
                                    </div>
                                    <small class="text-secondary">When disabled, cron will not process any tasks.</small>
                                </div>

                                <div class="mb-3">
                                    <label for="cronBatchSize" class="form-label text-dark">Batch Size</label>
                                    <input type="number" class="form-control" id="cronBatchSize" value="{{ $cronSettings['batchSize'] }}" min="1" max="100">
                                    <small class="text-secondary">Number of tasks to process per cron run (1-100).</small>
                                </div>

                                <button type="button" class="btn btn-primary w-100" id="saveCronSettings">
                                    <i class="bx bx-save me-1"></i>Save Settings
                                </button>
                            </div>
                        </div>

                        <!-- Stats Panel -->
                        <div class="card border">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0 text-dark">
                                    <i class="bx bx-bar-chart-alt-2 me-2"></i>Cron Statistics
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-secondary">Last Run:</span>
                                    <strong class="text-dark" id="lastRunDisplay">{{ $cronSettings['lastRun'] ?? 'Never' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-secondary">Total Runs:</span>
                                    <strong class="text-dark">{{ number_format($cronSettings['totalRuns']) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-secondary">Status:</span>
                                    @if($cronSettings['enabled'])
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Disabled</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Logs Tab -->
            <div class="tab-pane fade {{ $tab === 'logs' ? 'show active' : '' }}" id="logs" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-dark">Time</th>
                                <th class="text-dark">Action</th>
                                <th class="text-dark">Message</th>
                                <th class="text-dark">Level</th>
                                <th class="text-dark">Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $log)
                                <tr>
                                    <td>
                                        <small class="text-dark">{{ $log->created_at->format('M j, g:i A') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $log->actionBadgeClass }}">{{ str_replace('_', ' ', $log->action) }}</span>
                                    </td>
                                    <td class="text-dark">{{ Str::limit($log->message, 60) }}</td>
                                    <td>
                                        <span class="badge {{ $log->logLevelBadgeClass }}">{{ $log->logLevel }}</span>
                                    </td>
                                    <td class="text-secondary">{{ $log->executionSource }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-secondary">
                                        No logs available
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bx bx-x-circle me-2"></i>Confirm Cancellation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to cancel <strong id="cancelItemName"></strong>?</p>
                <p class="text-secondary mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>No, Keep It
                </button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                    <i class="bx bx-check me-1"></i>Yes, Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="bx bx-trash me-2"></i>Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-2">Are you sure you want to delete:</p>
                <p class="text-dark"><strong id="deleteItemName"></strong></p>
                <p class="text-secondary mb-0"><small>This will remove the task(s) from the list permanently.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
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

@section('script')
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Toastr config
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    let cancelTarget = null;
    let deleteTarget = null;

    // Flow filter change - reload page with filter
    $('#taskFlowFilter, #taskStatusFilter, #taskNodeTypeFilter').on('change', function() {
        const flowId = $('#taskFlowFilter').val();
        const status = $('#taskStatusFilter').val();
        const nodeType = $('#taskNodeTypeFilter').val();

        let params = new URLSearchParams();
        params.set('tab', 'tasks');
        if (flowId) params.set('flow_id', flowId);
        if (status) params.set('status', status);
        if (nodeType) params.set('node_type', nodeType);

        window.location.href = '{{ route("ecom-trigger-tasks") }}?' + params.toString();
    });

    // Select all tasks
    $('#selectAllTasks').on('change', function() {
        $('.task-checkbox').prop('checked', $(this).is(':checked'));
        updateBulkButtons();
    });

    // Individual checkbox change
    $(document).on('change', '.task-checkbox', function() {
        updateBulkButtons();
    });

    function updateBulkButtons() {
        const taskCount = $('.task-checkbox:checked').length;
        $('#bulkCancelTasks').prop('disabled', taskCount === 0);
    }

    // Cancel task button
    $(document).on('click', '.cancel-task-btn', function() {
        const $btn = $(this);
        const taskId = $btn.data('id');

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: `/ecom-trigger-tasks/tasks/${taskId}/cancel`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-x"></i>');
            }
        });
    });

    // Confirm cancel
    $('#confirmCancelBtn').on('click', function() {
        if (!cancelTarget) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Cancelling...');

        $.ajax({
            url: `/ecom-trigger-tasks/tasks/${cancelTarget.id}/cancel`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#cancelModal').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Yes, Cancel');
                cancelTarget = null;
            }
        });
    });

    // Retry task
    $(document).on('click', '.retry-task-btn', function() {
        const id = $(this).data('id');
        const $btn = $(this);

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: `/ecom-trigger-tasks/tasks/${id}/retry`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error occurred');
            }
        });
    });

    // Delete task - show modal
    $(document).on('click', '.delete-task-btn', function() {
        const id = $(this).data('id');
        const $btn = $(this);
        const $row = $btn.closest('.task-row');
        const taskType = $row.find('.task-type span.text-dark').text().trim();
        const taskOrder = $row.find('.task-order').text().trim();

        deleteTarget = {
            type: 'task',
            id: id,
            row: $row,
            btn: $btn
        };

        $('#deleteItemName').text(`Task ${taskOrder} - ${taskType}`);
        $('#deleteModal').modal('show');
    });

    // Clear all completed/cancelled/failed/skipped tasks - show modal
    $('#clearCompletedTasks').on('click', function() {
        const ids = [];
        $('.delete-task-btn').each(function() {
            ids.push($(this).data('id'));
        });

        if (ids.length === 0) {
            toastr.info('No completed tasks to clear');
            return;
        }

        deleteTarget = {
            type: 'bulk',
            ids: ids,
            btn: $(this)
        };

        $('#deleteItemName').text(`${ids.length} completed/cancelled/failed task(s)`);
        $('#deleteModal').modal('show');
    });

    // Delete all tasks in a flow group - show modal
    $(document).on('click', '.delete-flow-tasks-btn', function(e) {
        e.stopImmediatePropagation();
        e.preventDefault();
        const flowId = $(this).data('flow-id');
        const flowName = $(this).data('flow-name');
        const $btn = $(this);
        const $flowGroup = $btn.closest('.flow-group');

        deleteTarget = {
            type: 'flow',
            flowId: flowId,
            flowName: flowName,
            flowGroup: $flowGroup,
            btn: $btn
        };

        $('#deleteItemName').text(`ALL tasks for flow "${flowName}"`);
        $('#deleteModal').modal('show');
    });

    // Delete all tasks for a client/enrollment group - show modal
    $(document).on('click', '.delete-enrollment-btn', function(e) {
        e.stopPropagation();
        e.stopImmediatePropagation();
        e.preventDefault();

        const enrollmentId = $(this).data('id');
        const clientName = $(this).data('client-name');
        const $btn = $(this);
        const $enrollmentGroup = $btn.closest('.enrollment-group');

        deleteTarget = {
            type: 'enrollment',
            enrollmentId: enrollmentId,
            clientName: clientName,
            enrollmentGroup: $enrollmentGroup,
            btn: $btn
        };

        $('#deleteItemName').text(`all tasks for "${clientName}"`);
        $('#deleteModal').modal('show');

        return false;
    });

    // Confirm delete - execute based on deleteTarget type
    $('#confirmDeleteBtn').on('click', function() {
        if (!deleteTarget) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        // Handle different delete types
        if (deleteTarget.type === 'task') {
            // Single task delete
            const $taskRow = deleteTarget.row;
            const $enrollmentGroup = $taskRow.closest('.enrollment-group');
            const $flowGroup = $taskRow.closest('.flow-group');

            $.ajax({
                url: `/ecom-trigger-tasks/tasks/${deleteTarget.id}`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        $('#deleteModal').modal('hide');
                        toastr.success(response.message);
                        $taskRow.fadeOut(300, function() {
                            $(this).remove();
                            // Check if this was the last task in the enrollment group
                            if ($enrollmentGroup.find('.task-row').length === 0) {
                                $enrollmentGroup.fadeOut(300, function() {
                                    $(this).remove();
                                    // Check if this was the last enrollment in the flow group
                                    if ($flowGroup.find('.enrollment-group').length === 0) {
                                        $flowGroup.fadeOut(300, function() {
                                            $(this).remove();
                                        });
                                    }
                                });
                            }
                        });
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
                    deleteTarget = null;
                }
            });
        } else if (deleteTarget.type === 'bulk') {
            // Bulk delete completed tasks
            $.ajax({
                url: '/ecom-trigger-tasks/tasks/bulk-delete',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}', ids: deleteTarget.ids },
                success: function(response) {
                    if (response.success) {
                        $('#deleteModal').modal('hide');
                        toastr.success(response.message);
                        location.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
                    deleteTarget = null;
                }
            });
        } else if (deleteTarget.type === 'flow') {
            // Delete all tasks in flow
            $.ajax({
                url: `/ecom-trigger-tasks/flow/${deleteTarget.flowId}/delete-tasks`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        $('#deleteModal').modal('hide');
                        toastr.success(response.message);
                        deleteTarget.flowGroup.fadeOut(400, function() {
                            $(this).remove();
                        });
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
                    deleteTarget = null;
                }
            });
        } else if (deleteTarget.type === 'enrollment') {
            // Delete all tasks for enrollment/client
            const $enrollmentGroup = deleteTarget.enrollmentGroup;
            const $flowGroup = $enrollmentGroup.closest('.flow-group');

            $.ajax({
                url: `/ecom-trigger-tasks/enrollment/${deleteTarget.enrollmentId}/delete`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        $('#deleteModal').modal('hide');
                        toastr.success(response.message);
                        $enrollmentGroup.fadeOut(400, function() {
                            $(this).remove();
                            // Check if this was the last enrollment in the flow group
                            if ($flowGroup.find('.enrollment-group').length === 0) {
                                $flowGroup.fadeOut(400, function() {
                                    $(this).remove();
                                });
                            }
                        });
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
                    deleteTarget = null;
                }
            });
        } else if (deleteTarget.type === 'completed_groups') {
            // Delete all groups where all tasks are completed
            $.ajax({
                url: '/ecom-trigger-tasks/enrollments/delete-completed',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        $('#deleteModal').modal('hide');
                        toastr.success(response.message);
                        if (response.data && response.data.enrollmentsDeleted > 0) {
                            location.reload();
                        }
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Error occurred');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
                    deleteTarget = null;
                }
            });
        }
    });

    // Clear deleteTarget when modal is hidden
    $('#deleteModal').on('hidden.bs.modal', function() {
        deleteTarget = null;
    });

    // Clear completed groups - show modal
    $('#clearCompletedGroups').on('click', function() {
        deleteTarget = {
            type: 'completed_groups',
            btn: $(this)
        };

        $('#deleteItemName').text('all groups where all tasks are completed');
        $('#deleteModal').modal('show');
    });

    // Bulk cancel tasks
    $('#bulkCancelTasks').on('click', function() {
        const ids = $('.task-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (ids.length === 0) return;

        if (!confirm(`Are you sure you want to cancel ${ids.length} task(s)?`)) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Cancelling...');

        $.ajax({
            url: '/ecom-trigger-tasks/tasks/bulk-cancel',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', ids: ids },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-x me-1"></i>Cancel');
            }
        });
    });

    // Copy cron URL
    $('#copyCronUrl').on('click', function() {
        const url = '{{ url("/api/trigger-cron") }}?key=' + $('#cronSecretDisplay').text();
        navigator.clipboard.writeText(url).then(function() {
            toastr.success('URL copied to clipboard!');
        });
    });

    // Regenerate secret
    $('#regenerateSecret').on('click', function() {
        if (!confirm('Are you sure? You will need to update your cron job with the new URL.')) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Regenerating...');

        $.ajax({
            url: '/ecom-trigger-tasks/cron/regenerate-secret',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#cronSecretDisplay').text(response.secretKey);
                    toastr.success('Secret key regenerated! Update your cron job.');
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-refresh me-1"></i>Regenerate Secret Key');
            }
        });
    });

    // Save cron settings
    $('#saveCronSettings').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        $.ajax({
            url: '/ecom-trigger-tasks/cron/settings',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                enabled: $('#cronEnabled').is(':checked'),
                batchSize: $('#cronBatchSize').val()
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Settings');
            }
        });
    });

    // Expand/Collapse all flows
    let allExpanded = true;
    $('#expandAllFlows').on('click', function() {
        const $btn = $(this);
        if (allExpanded) {
            $('.flow-group .collapse').collapse('hide');
            $btn.html('<i class="bx bx-expand-alt me-1"></i>Expand All');
            allExpanded = false;
        } else {
            $('.flow-group .collapse').collapse('show');
            $btn.html('<i class="bx bx-collapse-alt me-1"></i>Collapse All');
            allExpanded = true;
        }
    });

    // Toggle collapse icon on show/hide
    $('.collapse').on('show.bs.collapse', function() {
        $(this).prev().find('.collapse-icon').removeClass('bx-chevron-right').addClass('bx-chevron-down');
    }).on('hide.bs.collapse', function() {
        $(this).prev().find('.collapse-icon').removeClass('bx-chevron-down').addClass('bx-chevron-right');
    });

    // Cancel all pending tasks for an enrollment
    $(document).on('click', '.cancel-enrollment-btn', function(e) {
        e.stopPropagation();
        const enrollmentId = $(this).data('id');
        const $btn = $(this);

        if (!confirm('Cancel all pending tasks for this client?')) return;

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        // Get all pending task IDs for this enrollment
        const taskIds = [];
        $(`#enrollment-${enrollmentId} .task-row`).each(function() {
            const $cancelBtn = $(this).find('.cancel-task-btn');
            if ($cancelBtn.length) {
                taskIds.push($cancelBtn.data('id'));
            }
        });

        if (taskIds.length === 0) {
            toastr.info('No pending tasks to cancel');
            $btn.prop('disabled', false).html('<i class="bx bx-x"></i>');
            return;
        }

        $.ajax({
            url: '/ecom-trigger-tasks/tasks/bulk-cancel',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}', ids: taskIds },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-x"></i>');
            }
        });
    });

    // Manual cron run
    $('#manualCronRun').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Running...');

        $.ajax({
            url: '/ecom-trigger-tasks/cron/run',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    if (response.data && response.data.processed > 0) {
                        location.reload();
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-play me-1"></i>Run Cron');
            }
        });
    });
});
</script>
@endsection
