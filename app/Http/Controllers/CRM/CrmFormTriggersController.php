<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CrmForm;
use App\Models\CrmFormTrigger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CrmFormTriggersController extends Controller
{
    /**
     * Display triggers for a form
     */
    public function index(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->formId);

        $triggers = CrmFormTrigger::active()
            ->forForm($request->formId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('crm.forms.triggers', compact('form', 'triggers'));
    }

    /**
     * Show trigger builder
     */
    public function builder(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->formId);

        $trigger = null;
        if ($request->triggerId) {
            $trigger = CrmFormTrigger::active()
                ->forForm($request->formId)
                ->findOrFail($request->triggerId);
        }

        $availableActions = CrmFormTrigger::getAvailableActions();

        return view('crm.forms.trigger-builder', compact('form', 'trigger', 'availableActions'));
    }

    /**
     * Store a new trigger
     */
    public function store(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->formId);

        $validator = Validator::make($request->all(), [
            'triggerName' => 'required|string|max:255',
            'triggerDescription' => 'nullable|string|max:1000',
            'triggerEvent' => 'required|in:on_submit,on_status_change',
            'triggerStatus' => 'required|in:active,inactive',
            'triggerFlow' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $trigger = CrmFormTrigger::create([
            'formId' => $form->id,
            'triggerName' => $request->triggerName,
            'triggerDescription' => $request->triggerDescription,
            'triggerEvent' => $request->triggerEvent,
            'triggerStatus' => $request->triggerStatus,
            'triggerFlow' => $request->triggerFlow ?? [],
            'delete_status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trigger created successfully',
            'data' => $trigger,
        ]);
    }

    /**
     * Update a trigger
     */
    public function update(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->formId);

        $trigger = CrmFormTrigger::active()
            ->forForm($request->formId)
            ->findOrFail($request->triggerId);

        $validator = Validator::make($request->all(), [
            'triggerName' => 'required|string|max:255',
            'triggerDescription' => 'nullable|string|max:1000',
            'triggerEvent' => 'required|in:on_submit,on_status_change',
            'triggerStatus' => 'required|in:active,inactive',
            'triggerFlow' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $trigger->update([
            'triggerName' => $request->triggerName,
            'triggerDescription' => $request->triggerDescription,
            'triggerEvent' => $request->triggerEvent,
            'triggerStatus' => $request->triggerStatus,
            'triggerFlow' => $request->triggerFlow ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trigger updated successfully',
            'data' => $trigger,
        ]);
    }

    /**
     * Delete a trigger
     */
    public function destroy(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->formId);

        $trigger = CrmFormTrigger::active()
            ->forForm($request->formId)
            ->findOrFail($request->triggerId);

        $trigger->update(['delete_status' => 'deleted']);

        return response()->json([
            'success' => true,
            'message' => 'Trigger deleted successfully',
        ]);
    }

    /**
     * Toggle trigger status
     */
    public function toggleStatus(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->formId);

        $trigger = CrmFormTrigger::active()
            ->forForm($request->formId)
            ->findOrFail($request->triggerId);

        $newStatus = $trigger->triggerStatus === 'active' ? 'inactive' : 'active';
        $trigger->update(['triggerStatus' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'Trigger status updated',
            'status' => $newStatus,
        ]);
    }

    /**
     * Get trigger logs
     */
    public function logs(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->formId);

        $trigger = CrmFormTrigger::active()
            ->forForm($request->formId)
            ->findOrFail($request->triggerId);

        $logs = $trigger->logs()
            ->with('submission')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
