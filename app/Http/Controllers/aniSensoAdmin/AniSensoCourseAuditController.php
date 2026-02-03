<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsCourse;
use App\Models\AsCourseAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AniSensoCourseAuditController extends Controller
{
    /**
     * Get audit logs for a course (AJAX)
     */
    public function getAuditLogs(Request $request, $courseId)
    {
        try {
            $course = AsCourse::where('deleteStatus', true)->findOrFail($courseId);

            $query = AsCourseAuditLog::active()
                ->forCourse($courseId)
                ->orderBy('created_at', 'desc');

            // Date range filter
            if ($request->filled('dateFrom')) {
                $query->whereDate('created_at', '>=', $request->dateFrom);
            }
            if ($request->filled('dateTo')) {
                $query->whereDate('created_at', '<=', $request->dateTo);
            }

            // Entity type filter
            if ($request->filled('entityType')) {
                $query->byEntityType($request->entityType);
            }

            // Action type filter
            if ($request->filled('actionType')) {
                $query->byActionType($request->actionType);
            }

            // User filter
            if ($request->filled('userId')) {
                $query->byUser($request->userId);
            }

            // Pagination
            $perPage = $request->perPage ?? 25;
            $logs = $query->paginate($perPage);

            // Format response
            $formattedLogs = $logs->map(function($log) {
                return [
                    'id' => $log->id,
                    'actionType' => $log->actionType,
                    'actionTypeLabel' => $log->action_type_label,
                    'entityType' => $log->entityType,
                    'entityTypeBadge' => $log->entity_type_badge,
                    'entityId' => $log->entityId,
                    'entityName' => $log->entityName,
                    'fieldChanged' => $log->fieldChanged,
                    'previousValue' => $log->previousValue,
                    'newValue' => $log->newValue,
                    'description' => $log->description,
                    'userName' => $log->userName,
                    'ipAddress' => $log->ipAddress,
                    'createdAt' => $log->created_at->format('M j, Y g:i A')
                ];
            });

            return response()->json([
                'success' => true,
                'logs' => $formattedLogs,
                'pagination' => [
                    'currentPage' => $logs->currentPage(),
                    'lastPage' => $logs->lastPage(),
                    'perPage' => $logs->perPage(),
                    'total' => $logs->total(),
                    'from' => $logs->firstItem(),
                    'to' => $logs->lastItem()
                ],
                'courseName' => $course->courseName
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching audit logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load audit logs'
            ], 500);
        }
    }

    /**
     * Get unique action types for filter dropdown
     */
    public function getActionTypes($courseId)
    {
        try {
            $actionTypes = AsCourseAuditLog::active()
                ->forCourse($courseId)
                ->distinct()
                ->pluck('actionType')
                ->map(function($type) {
                    $log = new AsCourseAuditLog(['actionType' => $type]);
                    return [
                        'value' => $type,
                        'label' => $log->action_type_label
                    ];
                });

            return response()->json([
                'success' => true,
                'actionTypes' => $actionTypes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load action types'
            ], 500);
        }
    }

    /**
     * Get unique users for filter dropdown
     */
    public function getUsers($courseId)
    {
        try {
            $users = AsCourseAuditLog::active()
                ->forCourse($courseId)
                ->whereNotNull('userId')
                ->distinct()
                ->get(['userId', 'userName'])
                ->unique('userId')
                ->values()
                ->map(function($user) {
                    return [
                        'id' => $user->userId,
                        'name' => $user->userName
                    ];
                });

            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load users'
            ], 500);
        }
    }
}
