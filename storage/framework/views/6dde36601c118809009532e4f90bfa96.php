<?php $__env->startSection('title'); ?> Crypto Notification History <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Crypto <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Notification History <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Crypto Notification History</h4>
                <p class="card-title-desc">View your crypto notification history and track your trading alerts.</p>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo e(request('start_date')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo e(request('end_date')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="task_type" class="form-label">Task Type</label>
                        <select class="form-select" id="task_type" name="task_type">
                            <option value="">All Types</option>
                            <?php $__currentLoopData = $taskTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($type); ?>" <?php echo e(request('task_type') == $type ? 'selected' : ''); ?>>
                                    <?php echo e(ucfirst($type)); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="button" class="btn btn-primary" onclick="applyFilters()">
                                <i class="bx bx-filter-alt"></i> Apply Filters
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                                <i class="bx bx-x"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Notification History Table -->
                <?php if($notificationHistory->count() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" style="border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                            <thead class="table-light" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                <tr>
                                    <th>Task Coin</th>
                                    <th>Task Type</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Current Value</th>
                                    <th>Starting Value</th>
                                    <th>Min Threshold</th>
                                    <th>Interval Threshold</th>
                                    <th>Threshold Quotient</th>
                                    <th>Final Amount</th>
                                    <th>Difference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $notificationHistory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $task = $notification->task;
                                        $date = \Carbon\Carbon::parse($notification->created_at);
                                    ?>

                                    <?php if($task->taskType === 'to sell'): ?>
                                        <tr style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border-left: 4px solid #28a745;">
                                            <td><strong><?php echo e(strtoupper($task->taskCoin)); ?></strong></td>
                                            <td><span class="badge bg-success" style="border-radius: 20px; padding: 8px 16px;">To Sell</span></td>
                                            <td><?php echo e($date->format('F j, Y')); ?></td>
                                            <td><?php echo e($date->format('g:iA')); ?></td>
                                            <td><?php echo e(number_format($task->currentCoinValue, 8)); ?> <?php echo e(strtoupper($task->taskCoin)); ?></td>
                                            <td>₱<?php echo e(number_format($task->startingPhpValue, 2)); ?></td>
                                            <td>₱<?php echo e(number_format($task->minThreshold, 2)); ?></td>
                                            <td>₱<?php echo e(number_format($task->intervalThreshold, 2)); ?></td>
                                            <td><span class="badge bg-info" style="border-radius: 15px;"><?php echo e($notification->threshold_quotient ?? 'N/A'); ?></span></td>
                                            <td><strong>₱<?php echo e(number_format($notification->finalAmount, 2)); ?></strong></td>
                                            <td><strong class="text-success">₱<?php echo e(number_format($notification->difference, 2)); ?></strong></td>
                                        </tr>
                                    <?php elseif($task->taskType === 'to buy'): ?>
                                        <tr style="background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); border-left: 4px solid #dc3545;">
                                            <td><strong><?php echo e(strtoupper($task->taskCoin)); ?></strong></td>
                                            <td><span class="badge bg-danger" style="border-radius: 20px; padding: 8px 16px;">To Buy</span></td>
                                            <td><?php echo e($date->format('F j, Y')); ?></td>
                                            <td><?php echo e($date->format('g:iA')); ?></td>
                                            <td>₱<?php echo e(number_format($task->toBuyCurrentCashValue, 2)); ?></td>
                                            <td><?php echo e(number_format($task->toBuyStartingCoinValue, 8)); ?> <?php echo e(strtoupper($task->taskCoin)); ?></td>
                                            <td>₱<?php echo e(number_format($task->toBuyMinThreshold, 2)); ?></td>
                                            <td>₱<?php echo e(number_format($task->toBuyIntervalThreshold, 2)); ?></td>
                                            <td><span class="badge bg-info" style="border-radius: 15px;"><?php echo e($notification->threshold_quotient ?? 'N/A'); ?></span></td>
                                            <td><strong><?php echo e(number_format($notification->finalAmount, 8)); ?> <?php echo e(strtoupper($task->taskCoin)); ?></strong></td>
                                            <td><strong class="text-danger">₱<?php echo e(number_format($notification->difference, 2)); ?></strong></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        <?php echo e($notificationHistory->appends(request()->query())->links()); ?>

                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bx bx-bell-off" style="font-size: 4rem; color: #ccc;"></i>
                        <h5 class="mt-3 text-muted">No notification history found</h5>
                        <p class="text-muted">You haven't received any crypto notifications yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('style'); ?>
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
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
function applyFilters() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const taskType = document.getElementById('task_type').value;

    let url = new URL(window.location);

    if (startDate) url.searchParams.set('start_date', startDate);
    else url.searchParams.delete('start_date');

    if (endDate) url.searchParams.set('end_date', endDate);
    else url.searchParams.delete('end_date');

    if (taskType) url.searchParams.set('task_type', taskType);
    else url.searchParams.delete('task_type');

    window.location.href = url.toString();
}

function clearFilters() {
    window.location.href = window.location.pathname;
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/crypto-notification-history.blade.php ENDPATH**/ ?>