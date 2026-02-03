<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\AsCourse;
use App\Models\EcomProductStore;
use App\Models\EcomTriggerFlow;
use App\Models\EcomTriggerTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TriggersController extends Controller
{
    /**
     * Display the trigger flows index page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $flows = EcomTriggerFlow::with('triggerTag')
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ecommerce.triggers.index', compact('flows'));
    }

    /**
     * Show the flow builder page for creating a new flow.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $triggerTags = EcomTriggerTag::active()
            ->orderBy('triggerTagName', 'asc')
            ->get();

        // Get course access tags from axis_tags
        $courseAccessTags = DB::table('axis_tags')
            ->where('deleteStatus', 1)
            ->orderBy('tagName', 'asc')
            ->get();

        // Get stores for affiliate assignment
        $stores = EcomProductStore::where('deleteStatus', 1)
            ->where('isActive', 1)
            ->orderBy('storeName', 'asc')
            ->get();

        // Get Ani-Senso courses for subscription management
        $courses = AsCourse::where('deleteStatus', 1)
            ->where('isActive', 1)
            ->orderBy('courseName', 'asc')
            ->get();

        $mergeTags = EcomTriggerFlow::getMergeTags();

        return view('ecommerce.triggers.builder', compact('triggerTags', 'courseAccessTags', 'stores', 'courses', 'mergeTags'));
    }

    /**
     * Show the flow builder page for editing an existing flow.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function edit(Request $request)
    {
        $id = $request->query('id');
        $flow = EcomTriggerFlow::with('triggerTag')
            ->active()
            ->findOrFail($id);

        $triggerTags = EcomTriggerTag::active()
            ->orderBy('triggerTagName', 'asc')
            ->get();

        // Get course access tags from axis_tags
        $courseAccessTags = DB::table('axis_tags')
            ->where('deleteStatus', 1)
            ->orderBy('tagName', 'asc')
            ->get();

        // Get stores for affiliate assignment
        $stores = EcomProductStore::where('deleteStatus', 1)
            ->where('isActive', 1)
            ->orderBy('storeName', 'asc')
            ->get();

        // Get Ani-Senso courses for subscription management
        $courses = AsCourse::where('deleteStatus', 1)
            ->where('isActive', 1)
            ->orderBy('courseName', 'asc')
            ->get();

        $mergeTags = EcomTriggerFlow::getMergeTags();

        return view('ecommerce.triggers.builder', compact('flow', 'triggerTags', 'courseAccessTags', 'stores', 'courses', 'mergeTags'));
    }

    /**
     * Store a new trigger flow.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $flowType = $request->flowType ?? 'trigger';

            // Validation rules depend on flow type
            $rules = [
                'flowName' => 'required|string|max:255',
                'flowDescription' => 'nullable|string',
                'flowType' => 'required|in:trigger,expiration,order_not_complete,shipping_complete,affiliate_earning',
                'flowData' => 'required|array',
            ];

            $messages = [
                'flowName.required' => 'Flow name is required.',
                'flowType.required' => 'Please select a flow type.',
                'flowType.in' => 'Invalid flow type selected.',
                'flowData.required' => 'Flow data is required.',
            ];

            // triggerTagId is required for trigger flows, optional for other flow types
            if ($flowType === 'trigger') {
                $rules['triggerTagId'] = 'required|integer|exists:ecom_trigger_tags,id';
                $messages['triggerTagId.required'] = 'Please select a trigger tag to start the flow.';
                $messages['triggerTagId.exists'] = 'Selected trigger tag not found.';
            } else {
                // For expiration, order_not_complete, shipping_complete, affiliate_earning
                // triggerTagId stores the course tag ID
                $rules['triggerTagId'] = 'nullable|integer';
            }

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            $flow = EcomTriggerFlow::create([
                'usersId' => Auth::id(),
                'flowName' => $request->flowName,
                'flowDescription' => $request->flowDescription,
                'flowType' => $flowType,
                'triggerTagId' => $request->triggerTagId,
                'flowData' => $request->flowData,
                'isActive' => filter_var($request->isActive, FILTER_VALIDATE_BOOLEAN),
                'deleteStatus' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trigger flow created successfully!',
                'flow' => $flow
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating trigger flow: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the flow: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing trigger flow.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $id = $request->query('id');
            $flow = EcomTriggerFlow::active()->findOrFail($id);

            // Use the existing flow type (cannot be changed after creation)
            $flowType = $flow->flowType ?? 'trigger';

            // Validation rules depend on flow type
            $rules = [
                'flowName' => 'required|string|max:255',
                'flowDescription' => 'nullable|string',
                'flowData' => 'required|array',
            ];

            $messages = [
                'flowName.required' => 'Flow name is required.',
                'flowData.required' => 'Flow data is required.',
            ];

            // triggerTagId is required for trigger flows, optional for expiration flows
            if ($flowType === 'trigger') {
                $rules['triggerTagId'] = 'required|integer|exists:ecom_trigger_tags,id';
                $messages['triggerTagId.required'] = 'Please select a trigger tag to start the flow.';
                $messages['triggerTagId.exists'] = 'Selected trigger tag not found.';
            } else {
                $rules['triggerTagId'] = 'nullable|integer';
            }

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            $flow->update([
                'flowName' => $request->flowName,
                'flowDescription' => $request->flowDescription,
                'triggerTagId' => $request->triggerTagId,
                'flowData' => $request->flowData,
                'isActive' => filter_var($request->isActive, FILTER_VALIDATE_BOOLEAN),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trigger flow updated successfully!',
                'flow' => $flow->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating trigger flow: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the flow: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle flow active status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request)
    {
        try {
            $id = $request->query('id');
            $flow = EcomTriggerFlow::active()->findOrFail($id);

            $flow->update([
                'isActive' => !$flow->isActive
            ]);

            $status = $flow->isActive ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "Flow {$status} successfully!",
                'isActive' => $flow->isActive
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling flow status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the flow status.'
            ], 500);
        }
    }

    /**
     * Delete (soft) a trigger flow.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        try {
            $id = $request->query('id');
            $flow = EcomTriggerFlow::active()->findOrFail($id);

            $flow->update([
                'deleteStatus' => 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trigger flow deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting trigger flow: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the flow.'
            ], 500);
        }
    }

    /**
     * Get flow data for AJAX loading.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFlowData(Request $request)
    {
        try {
            $id = $request->query('id');
            $flow = EcomTriggerFlow::with('triggerTag')
                ->active()
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'flow' => $flow
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Flow not found.'
            ], 404);
        }
    }

    /**
     * Duplicate a trigger flow.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function duplicate(Request $request)
    {
        try {
            $id = $request->query('id');
            $originalFlow = EcomTriggerFlow::active()->findOrFail($id);

            $newFlow = EcomTriggerFlow::create([
                'usersId' => Auth::id(),
                'flowName' => $originalFlow->flowName . ' (Copy)',
                'flowDescription' => $originalFlow->flowDescription,
                'triggerTagId' => $originalFlow->triggerTagId,
                'flowData' => $originalFlow->flowData,
                'isActive' => false, // Start as inactive
                'deleteStatus' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Flow duplicated successfully!',
                'flow' => $newFlow
            ]);

        } catch (\Exception $e) {
            Log::error('Error duplicating trigger flow: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while duplicating the flow.'
            ], 500);
        }
    }

    /**
     * Upload image for email editor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ], [
                'file.required' => 'No image file provided.',
                'file.image' => 'The file must be an image.',
                'file.mimes' => 'Only JPEG, PNG, JPG, GIF and WebP images are allowed.',
                'file.max' => 'Image size must not exceed 2MB.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $file = $request->file('file');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Store in public/images/triggers/emails directory
            $destinationPath = public_path('images/triggers/emails');

            // Create directory if it doesn't exist
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);

            // Return the URL for TinyMCE
            $imageUrl = url('images/triggers/emails/' . $filename);

            return response()->json([
                'success' => true,
                'location' => $imageUrl
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading image for trigger email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading the image.'
            ], 500);
        }
    }
}
