<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\NotificationHistory;
use App\Models\Task;
use App\Models\ThresholdTask;
use Carbon\Carbon;

class CryptoNotificationHistoryController extends Controller
{
    /**
     * Display the crypto notification history page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get notification history for the current user with task relationship
        $query = NotificationHistory::with(['task'])
            ->where('usersId', $user->id)
            ->orderBy('created_at', 'desc');

        // Apply date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Apply task type filter
        if ($request->filled('task_type')) {
            $query->whereHas('task', function($q) use ($request) {
                $q->where('taskType', $request->task_type);
            });
        }

        // Get paginated results
        $notificationHistory = $query->paginate(100);

        // Get threshold quotients for each notification
        foreach ($notificationHistory as $notification) {
            $thresholdTask = ThresholdTask::where('taskId', $notification->taskId)
                ->where('usersId', $notification->usersId)
                ->orderBy('created_at', 'desc')
                ->first();

            $notification->threshold_quotient = $thresholdTask ? $thresholdTask->thresholdQuotient : null;
        }

        // Get unique task types for filter dropdown
        $taskTypes = Task::where('usersId', $user->id)
            ->distinct()
            ->pluck('taskType')
            ->filter()
            ->values();

        // Debug information
        Log::info("User ID: " . $user->id);
        Log::info("Notification History Count: " . $notificationHistory->count());
        Log::info("Task Types: " . $taskTypes->implode(', '));

        return view('crypto-notification-history', compact('notificationHistory', 'taskTypes'));
    }
}
