<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomTriggerFlowEnrollment;
use App\Models\EcomTriggerFlowTask;
use App\Models\EcomTriggerFlowLog;
use App\Models\EcomTriggerSetting;
use App\Models\EcomTriggerFlow;
use App\Services\TriggerFlowProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TriggerTasksController extends Controller
{
    /**
     * Display the trigger tasks index page.
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'tasks');

        // Get statistics
        $stats = [
            'totalTasks' => EcomTriggerFlowTask::active()->count(),
            'pendingTasks' => EcomTriggerFlowTask::active()->pending()->count(),
            'completedTasks' => EcomTriggerFlowTask::active()->withStatus('completed')->count(),
            'failedTasks' => EcomTriggerFlowTask::active()->withStatus('failed')->count(),
        ];

        // Build tasks query with filters
        $tasksQuery = EcomTriggerFlowTask::active()
            ->with(['enrollment.flow', 'enrollment.client', 'enrollment.order']);

        // Apply flow filter
        if ($request->filled('flow_id')) {
            $tasksQuery->where('flowId', $request->flow_id);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $tasksQuery->withStatus($request->status);
        }

        // Apply node type filter
        if ($request->filled('node_type')) {
            $tasksQuery->where('nodeType', $request->node_type);
        }

        // Get all tasks
        $allTasks = $tasksQuery
            ->orderBy('flowId', 'asc')
            ->orderBy('enrollmentId', 'asc')
            ->orderBy('taskOrder', 'asc')
            ->get();

        // Group tasks by flow, then by enrollment (client)
        $groupedTasks = $allTasks->groupBy('flowId')->map(function ($flowTasks) {
            return $flowTasks->groupBy('enrollmentId');
        });

        // Get tasks for pagination (fallback for simple view)
        $tasks = $tasksQuery
            ->orderBy('scheduledAt', 'asc')
            ->orderBy('taskOrder', 'asc')
            ->paginate(50, ['*'], 'tasks_page');

        // Get cron settings
        $cronSettings = [
            'enabled' => EcomTriggerSetting::isCronEnabled(),
            'secretKey' => EcomTriggerSetting::getCronSecret(),
            'batchSize' => EcomTriggerSetting::getCronBatchSize(),
            'lastRun' => EcomTriggerSetting::getLastCronRun(),
            'totalRuns' => EcomTriggerSetting::getTotalCronRuns(),
        ];

        // Get recent logs
        $recentLogs = EcomTriggerFlowLog::with(['enrollment', 'task'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Get flows for filtering
        $flows = EcomTriggerFlow::active()->orderBy('flowName')->get(['id', 'flowName']);

        return view('ecommerce.trigger-tasks.index', compact(
            'tab',
            'stats',
            'tasks',
            'groupedTasks',
            'cronSettings',
            'recentLogs',
            'flows'
        ));
    }

    /**
     * Get filtered enrollments (AJAX).
     */
    public function getEnrollments(Request $request)
    {
        $query = EcomTriggerFlowEnrollment::active()
            ->with(['flow', 'client', 'order']);

        // Apply filters
        if ($request->filled('flowId')) {
            $query->forFlow($request->flowId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('client', function($q) use ($search) {
                $q->where('clientName', 'like', "%{$search}%")
                  ->orWhere('clientEmail', 'like', "%{$search}%");
            })->orWhereHas('flow', function($q) use ($search) {
                $q->where('flowName', 'like', "%{$search}%");
            });
        }

        $enrollments = $query->orderBy('created_at', 'desc')->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $enrollments,
        ]);
    }

    /**
     * Get filtered tasks (AJAX).
     */
    public function getTasks(Request $request)
    {
        $query = EcomTriggerFlowTask::active()
            ->with(['enrollment.flow', 'enrollment.client']);

        // Apply filters
        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        if ($request->filled('nodeType')) {
            $query->where('nodeType', $request->nodeType);
        }

        if ($request->filled('enrollmentId')) {
            $query->forEnrollment($request->enrollmentId);
        }

        $tasks = $query->orderBy('scheduledAt', 'asc')
            ->orderBy('taskOrder', 'asc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $tasks,
        ]);
    }

    /**
     * Cancel an enrollment.
     */
    public function cancelEnrollment(Request $request, $id)
    {
        try {
            $enrollment = EcomTriggerFlowEnrollment::active()->findOrFail($id);

            $enrollment->cancel(Auth::id(), $request->input('reason', 'Manually cancelled'));

            EcomTriggerFlowLog::info(
                EcomTriggerFlowLog::ACTION_ENROLLMENT_CANCELLED,
                'Enrollment cancelled manually',
                [
                    'enrollmentId' => $enrollment->id,
                    'flowId' => $enrollment->flowId,
                    'executionSource' => EcomTriggerFlowLog::SOURCE_MANUAL,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Enrollment cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling enrollment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling enrollment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pause an enrollment.
     */
    public function pauseEnrollment($id)
    {
        try {
            $enrollment = EcomTriggerFlowEnrollment::active()->findOrFail($id);

            $enrollment->pause();

            EcomTriggerFlowLog::info(
                EcomTriggerFlowLog::ACTION_ENROLLMENT_PAUSED,
                'Enrollment paused manually',
                [
                    'enrollmentId' => $enrollment->id,
                    'flowId' => $enrollment->flowId,
                    'executionSource' => EcomTriggerFlowLog::SOURCE_MANUAL,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Enrollment paused successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error pausing enrollment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error pausing enrollment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resume an enrollment.
     */
    public function resumeEnrollment($id)
    {
        try {
            $enrollment = EcomTriggerFlowEnrollment::active()->findOrFail($id);

            $enrollment->resume();

            EcomTriggerFlowLog::info(
                EcomTriggerFlowLog::ACTION_ENROLLMENT_RESUMED,
                'Enrollment resumed manually',
                [
                    'enrollmentId' => $enrollment->id,
                    'flowId' => $enrollment->flowId,
                    'executionSource' => EcomTriggerFlowLog::SOURCE_MANUAL,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Enrollment resumed successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error resuming enrollment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error resuming enrollment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk cancel enrollments.
     */
    public function bulkCancelEnrollments(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No enrollments selected.',
                ], 400);
            }

            $count = 0;
            foreach ($ids as $id) {
                $enrollment = EcomTriggerFlowEnrollment::active()->find($id);
                if ($enrollment && $enrollment->status !== 'cancelled') {
                    $enrollment->cancel(Auth::id(), 'Bulk cancelled');
                    $count++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$count} enrollment(s) cancelled successfully.",
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk cancelling enrollments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling enrollments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a task.
     */
    public function cancelTask($id)
    {
        try {
            $task = EcomTriggerFlowTask::active()->findOrFail($id);

            if (!in_array($task->status, ['pending', 'scheduled', 'ready'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This task cannot be cancelled.',
                ], 400);
            }

            $task->markCancelled();

            EcomTriggerFlowLog::info(
                EcomTriggerFlowLog::ACTION_TASK_CANCELLED,
                'Task cancelled manually',
                [
                    'enrollmentId' => $task->enrollmentId,
                    'taskId' => $task->id,
                    'flowId' => $task->flowId,
                    'nodeType' => $task->nodeType,
                    'executionSource' => EcomTriggerFlowLog::SOURCE_MANUAL,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Task cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling task: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling task: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk cancel tasks.
     */
    public function bulkCancelTasks(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tasks selected.',
                ], 400);
            }

            $count = 0;
            foreach ($ids as $id) {
                $task = EcomTriggerFlowTask::active()->find($id);
                if ($task && in_array($task->status, ['pending', 'scheduled', 'ready'])) {
                    $task->markCancelled();
                    $count++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$count} task(s) cancelled successfully.",
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk cancelling tasks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a task (soft delete).
     */
    public function deleteTask($id)
    {
        try {
            $task = EcomTriggerFlowTask::active()->findOrFail($id);

            // Only allow deletion of completed, cancelled, failed, or skipped tasks
            if (!in_array($task->status, ['completed', 'cancelled', 'failed', 'skipped'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only completed, cancelled, failed, or skipped tasks can be deleted.',
                ], 400);
            }

            $task->update(['deleteStatus' => 'deleted']);

            EcomTriggerFlowLog::info(
                EcomTriggerFlowLog::ACTION_TASK_DELETED ?? 'task_deleted',
                'Task deleted manually',
                [
                    'enrollmentId' => $task->enrollmentId,
                    'taskId' => $task->id,
                    'flowId' => $task->flowId,
                    'nodeType' => $task->nodeType,
                    'previousStatus' => $task->status,
                    'executionSource' => EcomTriggerFlowLog::SOURCE_MANUAL,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting task: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting task: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete tasks.
     */
    public function bulkDeleteTasks(Request $request)
    {
        try {
            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tasks selected.',
                ], 400);
            }

            $count = 0;
            foreach ($ids as $id) {
                $task = EcomTriggerFlowTask::active()->find($id);
                if ($task && in_array($task->status, ['completed', 'cancelled', 'failed', 'skipped'])) {
                    $task->update(['deleteStatus' => 'deleted']);
                    $count++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$count} task(s) deleted successfully.",
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting tasks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete all tasks for a specific enrollment (client group).
     */
    public function deleteEnrollment($enrollmentId)
    {
        try {
            $enrollment = EcomTriggerFlowEnrollment::active()->findOrFail($enrollmentId);

            // Get all active tasks for this enrollment
            $tasks = EcomTriggerFlowTask::active()
                ->where('enrollmentId', $enrollmentId)
                ->get();

            $count = 0;
            foreach ($tasks as $task) {
                // Cancel pending tasks first, then delete
                if (in_array($task->status, ['pending', 'scheduled', 'ready'])) {
                    $task->markCancelled();
                }
                $task->update(['deleteStatus' => 'deleted']);
                $count++;
            }

            // Also soft-delete the enrollment
            $enrollment->update(['deleteStatus' => 'deleted']);

            EcomTriggerFlowLog::info(
                'enrollment_deleted',
                "Enrollment and {$count} tasks deleted",
                [
                    'enrollmentId' => $enrollmentId,
                    'flowId' => $enrollment->flowId,
                    'tasksDeleted' => $count,
                    'executionSource' => EcomTriggerFlowLog::SOURCE_MANUAL,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "{$count} task(s) deleted successfully.",
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting enrollment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting enrollment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete all tasks for a specific flow.
     */
    public function deleteFlowTasks($flowId)
    {
        try {
            $flow = EcomTriggerFlow::active()->findOrFail($flowId);

            // Get all active tasks for this flow
            $tasks = EcomTriggerFlowTask::active()
                ->where('flowId', $flowId)
                ->get();

            if ($tasks->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tasks found for this flow.',
                ], 404);
            }

            $count = 0;
            foreach ($tasks as $task) {
                // Cancel pending tasks first, then delete
                if (in_array($task->status, ['pending', 'scheduled', 'ready'])) {
                    $task->markCancelled();
                }
                $task->update(['deleteStatus' => 'deleted']);
                $count++;
            }

            // Also delete enrollments for this flow
            EcomTriggerFlowEnrollment::active()
                ->where('flowId', $flowId)
                ->update(['deleteStatus' => 'deleted']);

            EcomTriggerFlowLog::info(
                'flow_tasks_deleted',
                "All tasks deleted for flow: {$flow->flowName}",
                [
                    'flowId' => $flowId,
                    'tasksDeleted' => $count,
                    'executionSource' => EcomTriggerFlowLog::SOURCE_MANUAL,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "{$count} task(s) deleted from flow '{$flow->flowName}'.",
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting flow tasks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting flow tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry a failed task.
     */
    public function retryTask($id)
    {
        try {
            $task = EcomTriggerFlowTask::active()->findOrFail($id);

            if (!$task->canRetry()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This task cannot be retried.',
                ], 400);
            }

            $task->incrementRetry();

            EcomTriggerFlowLog::info(
                EcomTriggerFlowLog::ACTION_TASK_RETRIED,
                'Task queued for retry',
                [
                    'enrollmentId' => $task->enrollmentId,
                    'taskId' => $task->id,
                    'flowId' => $task->flowId,
                    'nodeType' => $task->nodeType,
                    'logData' => ['retryCount' => $task->retryCount],
                    'executionSource' => EcomTriggerFlowLog::SOURCE_MANUAL,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Task queued for retry.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrying task: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrying task: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get enrollment details.
     */
    public function getEnrollmentDetails($id)
    {
        try {
            $enrollment = EcomTriggerFlowEnrollment::active()
                ->with(['flow', 'client', 'order', 'tasks', 'logs'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $enrollment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Enrollment not found.',
            ], 404);
        }
    }

    /**
     * Update cron settings.
     */
    public function updateCronSettings(Request $request)
    {
        try {
            if ($request->has('enabled')) {
                EcomTriggerSetting::setValue('cron_enabled', $request->enabled, 'boolean');
            }

            if ($request->has('batchSize')) {
                $batchSize = max(1, min(100, (int) $request->batchSize));
                EcomTriggerSetting::setValue('cron_batch_size', $batchSize, 'integer');
            }

            return response()->json([
                'success' => true,
                'message' => 'Cron settings updated successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating cron settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating cron settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regenerate cron secret key.
     */
    public function regenerateCronSecret()
    {
        try {
            $newSecret = EcomTriggerSetting::regenerateCronSecret();

            return response()->json([
                'success' => true,
                'message' => 'Cron secret key regenerated successfully.',
                'secretKey' => $newSecret,
            ]);
        } catch (\Exception $e) {
            Log::error('Error regenerating cron secret: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error regenerating cron secret: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually trigger cron processing.
     */
    public function manualCronRun()
    {
        try {
            $processor = new TriggerFlowProcessorService();
            $result = $processor->processPendingTasks('manual');

            return response()->json([
                'success' => true,
                'message' => "Processed {$result['processed']} task(s). Failed: {$result['failed']}.",
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in manual cron run: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error running cron: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API endpoint for external cron services.
     * This should be accessible without authentication but with a secret key.
     */
    public function cronEndpoint(Request $request)
    {
        $providedKey = $request->input('key') ?? $request->header('X-Cron-Key');
        $storedKey = EcomTriggerSetting::getCronSecret();

        if (!$storedKey || $providedKey !== $storedKey) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing cron key.',
            ], 401);
        }

        if (!EcomTriggerSetting::isCronEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Cron processing is disabled.',
            ], 503);
        }

        try {
            $processor = new TriggerFlowProcessorService();
            $result = $processor->processPendingTasks('cron');

            return response()->json([
                'success' => true,
                'message' => 'Cron executed successfully.',
                'processed' => $result['processed'],
                'failed' => $result['failed'],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            Log::error('Cron endpoint error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing tasks.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cron logs.
     */
    public function getCronLogs(Request $request)
    {
        $logs = EcomTriggerFlowLog::where('action', EcomTriggerFlowLog::ACTION_CRON_RUN)
            ->orderBy('created_at', 'desc')
            ->limit($request->get('limit', 50))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Delete all enrollment groups where all tasks are completed.
     */
    public function deleteCompletedEnrollments()
    {
        try {
            // Get all active enrollments
            $enrollments = EcomTriggerFlowEnrollment::active()->get();

            $deletedCount = 0;
            $tasksDeletedCount = 0;

            foreach ($enrollments as $enrollment) {
                // Get all active tasks for this enrollment
                $tasks = EcomTriggerFlowTask::active()
                    ->where('enrollmentId', $enrollment->id)
                    ->get();

                // Skip if no tasks
                if ($tasks->isEmpty()) {
                    continue;
                }

                // Check if all tasks are in a "done" state (completed, cancelled, failed, skipped)
                $allDone = $tasks->every(function ($task) {
                    return in_array($task->status, ['completed', 'cancelled', 'failed', 'skipped']);
                });

                if ($allDone) {
                    // Delete all tasks
                    foreach ($tasks as $task) {
                        $task->update(['deleteStatus' => 'deleted']);
                        $tasksDeletedCount++;
                    }

                    // Delete the enrollment
                    $enrollment->update(['deleteStatus' => 'deleted']);
                    $deletedCount++;
                }
            }

            if ($deletedCount === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'No completed groups found to delete.',
                    'data' => [
                        'enrollmentsDeleted' => 0,
                        'tasksDeleted' => 0,
                    ],
                ]);
            }

            EcomTriggerFlowLog::info(
                'completed_enrollments_deleted',
                "Deleted {$deletedCount} completed enrollment groups with {$tasksDeletedCount} tasks",
                [
                    'enrollmentsDeleted' => $deletedCount,
                    'tasksDeleted' => $tasksDeletedCount,
                    'executionSource' => EcomTriggerFlowLog::SOURCE_MANUAL,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deletedCount} completed group(s) with {$tasksDeletedCount} task(s).",
                'data' => [
                    'enrollmentsDeleted' => $deletedCount,
                    'tasksDeleted' => $tasksDeletedCount,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting completed enrollments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting completed groups: ' . $e->getMessage(),
            ], 500);
        }
    }
}
