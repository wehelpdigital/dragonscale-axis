<?php

namespace App\Http\Controllers\AiTechnician;

use App\Http\Controllers\Controller;
use App\Models\AiChatError;
use App\Models\AiChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiChatErrorsController extends Controller
{
    /**
     * Display the Chat Errors listing page.
     * Data is GLOBAL - all users can see and manage all errors.
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $statusFilter = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Build query - GLOBAL (no user filter)
        $query = AiChatError::active()
            ->with('user:id,name') // Eager load user info for display
            ->orderBy('errorDate', 'desc');

        if ($statusFilter && in_array($statusFilter, ['pending', 'fixed'])) {
            $query->where('status', $statusFilter);
        }

        if ($startDate) {
            $query->whereDate('errorDate', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('errorDate', '<=', $endDate);
        }

        $errors = $query->paginate(20);

        // Get stats - GLOBAL (no user filter)
        $totalErrors = AiChatError::active()->count();
        $pendingErrors = AiChatError::active()->pending()->count();
        $fixedErrors = AiChatError::active()->fixed()->count();

        return view('ai-technician.chat-errors.index', compact(
            'errors',
            'statusFilter',
            'startDate',
            'endDate',
            'totalErrors',
            'pendingErrors',
            'fixedErrors'
        ));
    }

    /**
     * Store or update a chat error (AJAX).
     * If error already exists for the session, update it instead of creating new.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sessionId' => 'nullable|integer',
            'chatThread' => 'required',
            'flowLogs' => 'nullable',
            'errorDescription' => 'nullable|string|max:5000',
        ], [
            'chatThread.required' => 'Chat thread data is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            // Process chat thread data
            $chatThread = $request->chatThread;
            if (is_string($chatThread)) {
                $chatThread = json_decode($chatThread, true);
            }

            // Process flow logs data
            $flowLogs = $request->flowLogs;
            if (is_string($flowLogs)) {
                $flowLogs = json_decode($flowLogs, true);
            }

            $userId = Auth::id();
            $sessionId = $request->sessionId;
            $isUpdate = false;

            // Check if error already exists for this session
            $existingError = null;
            if ($sessionId) {
                $existingError = AiChatError::where('usersId', $userId)
                    ->where('sessionId', $sessionId)
                    ->where('delete_status', 'active')
                    ->first();
            }

            if ($existingError) {
                // Update existing error
                $existingError->update([
                    'errorDate' => now(),
                    'chatThread' => $chatThread,
                    'flowLogs' => $flowLogs,
                    'errorDescription' => $request->errorDescription,
                    'status' => AiChatError::STATUS_PENDING, // Reset to pending on update
                ]);
                $error = $existingError;
                $isUpdate = true;
            } else {
                // Create new error
                $error = AiChatError::create([
                    'usersId' => $userId,
                    'sessionId' => $sessionId,
                    'errorDate' => now(),
                    'chatThread' => $chatThread,
                    'flowLogs' => $flowLogs,
                    'errorDescription' => $request->errorDescription,
                    'status' => AiChatError::STATUS_PENDING,
                    'delete_status' => 'active',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $isUpdate ? 'Error updated successfully.' : 'Error saved successfully.',
                'data' => [
                    'id' => $error->id,
                    'formattedDate' => $error->formatted_date,
                    'isUpdate' => $isUpdate,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Save chat error failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save error. Please try again.',
            ], 500);
        }
    }

    /**
     * Get error details (AJAX).
     * Data is GLOBAL - any user can view any error.
     */
    public function show(Request $request)
    {
        $id = $request->get('id');

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Error ID is required.',
            ], 422);
        }

        try {
            $error = AiChatError::where('id', $id)
                ->where('delete_status', 'active')
                ->with('user:id,name')
                ->first();

            if (!$error) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error not found.',
                ], 404);
            }

            // Format chat thread for display
            $chatThread = $error->chatThread;
            if (is_string($chatThread)) {
                $chatThread = json_decode($chatThread, true);
            }

            // Format flow logs for display
            $flowLogs = $error->flowLogs;
            if (is_string($flowLogs)) {
                $flowLogs = json_decode($flowLogs, true);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $error->id,
                    'formattedDate' => $error->formatted_date,
                    'status' => $error->status,
                    'statusBadge' => $error->status_badge,
                    'chatThread' => $chatThread,
                    'flowLogs' => $flowLogs,
                    'errorDescription' => $error->errorDescription,
                    'messageCount' => $error->message_count,
                    'hasFlowLogs' => $error->has_flow_logs,
                    'userName' => $error->user->name ?? 'Unknown',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get chat error details failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load error details.',
            ], 500);
        }
    }

    /**
     * Update error status (AJAX).
     * Data is GLOBAL - any user can update any error's status.
     */
    public function updateStatus(Request $request)
    {
        $id = $request->get('id');

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Error ID is required.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,fixed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status value.',
            ], 422);
        }

        try {
            $error = AiChatError::where('id', $id)
                ->where('delete_status', 'active')
                ->first();

            if (!$error) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error not found.',
                ], 404);
            }

            $error->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated to ' . ucfirst($request->status) . '.',
                'data' => [
                    'status' => $error->status,
                    'statusBadge' => $error->status_badge,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Update chat error status failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status.',
            ], 500);
        }
    }

    /**
     * Delete error (soft delete, AJAX).
     * Data is GLOBAL - any user can delete any error.
     */
    public function destroy(Request $request)
    {
        $id = $request->get('id');

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Error ID is required.',
            ], 422);
        }

        try {
            $error = AiChatError::where('id', $id)
                ->where('delete_status', 'active')
                ->first();

            if (!$error) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error not found.',
                ], 404);
            }

            $error->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Error deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete chat error failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete error.',
            ], 500);
        }
    }

    /**
     * Bulk delete errors (AJAX).
     * Data is GLOBAL - any user can bulk delete errors.
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid selection.',
            ], 422);
        }

        try {
            $deleted = AiChatError::whereIn('id', $request->ids)
                ->where('delete_status', 'active')
                ->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => $deleted . ' error(s) deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk delete chat errors failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete errors.',
            ], 500);
        }
    }

    /**
     * Bulk update status (AJAX).
     * Data is GLOBAL - any user can bulk update status.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'status' => 'required|in:pending,fixed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid selection or status.',
            ], 422);
        }

        try {
            $updated = AiChatError::whereIn('id', $request->ids)
                ->where('delete_status', 'active')
                ->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => $updated . ' error(s) marked as ' . ucfirst($request->status) . '.',
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk update chat errors status failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status.',
            ], 500);
        }
    }
}
