<?php

namespace App\Http\Controllers\AiTechnician;

use App\Http\Controllers\Controller;
use App\Models\AiApiSetting;
use App\Models\AiReplyFlow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiReplyFlowsController extends Controller
{
    /**
     * Display the Reply Flow settings page.
     * Gets or creates a single flow for the current user.
     */
    public function index()
    {
        $flow = AiReplyFlow::getOrCreate();
        $nodeTypes = AiReplyFlow::getNodeTypesByCategory();
        $mergeFields = AiReplyFlow::getMergeFields();

        // Get available AI APIs for the user
        $aiApis = AiApiSetting::active()
                        ->orderBy('provider')
            ->get();

        return view('ai-technician.reply-flows.settings', [
            'flow' => $flow,
            'nodeTypes' => $nodeTypes,
            'mergeFields' => $mergeFields,
            'aiApis' => $aiApis,
        ]);
    }

    /**
     * Save the Reply Flow settings.
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'flowData' => 'nullable|array',
            'isActive' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $flow = AiReplyFlow::getOrCreate();

            $flow->update([
                'flowData' => $request->flowData,
                'isActive' => $request->boolean('isActive', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reply Flow saved successfully.',
                'data' => [
                    'id' => $flow->id,
                    'isActive' => $flow->isActive,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Reply Flow save error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save Reply Flow. Please try again.',
            ], 500);
        }
    }

    /**
     * Toggle flow active status.
     */
    public function toggleStatus()
    {
        try {
            $flow = AiReplyFlow::getOrCreate();
            $flow->update(['isActive' => !$flow->isActive]);

            return response()->json([
                'success' => true,
                'message' => $flow->isActive ? 'Reply Flow enabled.' : 'Reply Flow disabled.',
                'data' => [
                    'isActive' => $flow->isActive,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Reply Flow toggle error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle Reply Flow status.',
            ], 500);
        }
    }

    /**
     * Reset flow to default (empty with just start node).
     */
    public function reset()
    {
        try {
            $flow = AiReplyFlow::getOrCreate();

            $flow->update([
                'flowData' => [
                    'nodes' => [
                        [
                            'id' => 'node_start',
                            'type' => 'start',
                            'position' => ['x' => 300, 'y' => 50],
                            'data' => []
                        ]
                    ],
                    'connections' => [],
                    'nodeIdCounter' => 0
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reply Flow reset to default.',
                'data' => [
                    'flowData' => $flow->flowData,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AI Reply Flow reset error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset Reply Flow.',
            ], 500);
        }
    }
}
