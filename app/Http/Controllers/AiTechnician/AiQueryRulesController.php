<?php

namespace App\Http\Controllers\AiTechnician;

use App\Http\Controllers\Controller;
use App\Models\AiQueryRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AiQueryRulesController extends Controller
{
    /**
     * Display the Query Rules listing page.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Ensure default rules exist for this user
        AiQueryRule::createDefaultRulesForUser($userId);

        // Get query parameters for filtering
        $categoryFilter = $request->get('category');
        $enabledFilter = $request->get('enabled');

        // Build query
        $query = AiQueryRule::active()
            ->forUser($userId)
            ->byPriority();

        if ($categoryFilter) {
            $query->byCategory($categoryFilter);
        }

        if ($enabledFilter !== null && $enabledFilter !== '') {
            $query->where('isEnabled', $enabledFilter === '1');
        }

        $rules = $query->get();
        $categories = AiQueryRule::getCategories();

        // Get stats
        $totalRules = AiQueryRule::active()->forUser($userId)->count();
        $enabledRules = AiQueryRule::active()->forUser($userId)->enabled()->count();

        return view('ai-technician.query-rules.index', compact(
            'rules',
            'categories',
            'categoryFilter',
            'enabledFilter',
            'totalRules',
            'enabledRules'
        ));
    }

    /**
     * Show the create rule form.
     */
    public function create()
    {
        $categories = AiQueryRule::getCategories();
        return view('ai-technician.query-rules.create', compact('categories'));
    }

    /**
     * Store a new rule.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ruleName' => 'required|string|max:255',
            'ruleCategory' => 'required|string|max:100',
            'ruleDescription' => 'nullable|string|max:1000',
            'rulePrompt' => 'required|string|max:5000',
            'priority' => 'nullable|integer|min:0|max:1000',
        ], [
            'ruleName.required' => 'Please provide a name for this rule.',
            'rulePrompt.required' => 'Please provide the rule prompt/instruction.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            AiQueryRule::create([
                'usersId' => Auth::id(),
                'ruleName' => $request->ruleName,
                'ruleCategory' => $request->ruleCategory,
                'ruleDescription' => $request->ruleDescription,
                'rulePrompt' => $request->rulePrompt,
                'priority' => $request->priority ?? 50,
                'isEnabled' => true,
                'isSystemRule' => false,
                'delete_status' => 'active',
            ]);

            return redirect()
                ->route('ai-technician.query-rules')
                ->with('success', 'Query rule created successfully.');
        } catch (\Exception $e) {
            Log::error('Create query rule error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create query rule. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show the edit rule form.
     */
    public function edit($id)
    {
        $rule = AiQueryRule::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$rule) {
            return redirect()
                ->route('ai-technician.query-rules')
                ->with('error', 'Rule not found.');
        }

        $categories = AiQueryRule::getCategories();
        return view('ai-technician.query-rules.edit', compact('rule', 'categories'));
    }

    /**
     * Update a rule.
     */
    public function update(Request $request, $id)
    {
        $rule = AiQueryRule::where('id', $id)
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->first();

        if (!$rule) {
            return redirect()
                ->route('ai-technician.query-rules')
                ->with('error', 'Rule not found.');
        }

        $validator = Validator::make($request->all(), [
            'ruleName' => 'required|string|max:255',
            'ruleCategory' => 'required|string|max:100',
            'ruleDescription' => 'nullable|string|max:1000',
            'rulePrompt' => 'required|string|max:5000',
            'priority' => 'nullable|integer|min:0|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $rule->update([
                'ruleName' => $request->ruleName,
                'ruleCategory' => $request->ruleCategory,
                'ruleDescription' => $request->ruleDescription,
                'rulePrompt' => $request->rulePrompt,
                'priority' => $request->priority ?? 50,
            ]);

            return redirect()
                ->route('ai-technician.query-rules')
                ->with('success', 'Query rule updated successfully.');
        } catch (\Exception $e) {
            Log::error('Update query rule error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update query rule. Please try again.')
                ->withInput();
        }
    }

    /**
     * Toggle rule enabled status (AJAX).
     */
    public function toggleStatus($id)
    {
        try {
            $rule = AiQueryRule::where('id', $id)
                ->where('usersId', Auth::id())
                ->where('delete_status', 'active')
                ->first();

            if (!$rule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rule not found.',
                ], 404);
            }

            $rule->update(['isEnabled' => !$rule->isEnabled]);

            return response()->json([
                'success' => true,
                'message' => $rule->isEnabled ? 'Rule enabled.' : 'Rule disabled.',
                'data' => [
                    'isEnabled' => $rule->isEnabled,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Toggle query rule status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle rule status.',
            ], 500);
        }
    }

    /**
     * Delete a rule (soft delete, AJAX).
     */
    public function destroy($id)
    {
        try {
            $rule = AiQueryRule::where('id', $id)
                ->where('usersId', Auth::id())
                ->where('delete_status', 'active')
                ->first();

            if (!$rule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rule not found.',
                ], 404);
            }

            // System rules cannot be deleted, only disabled
            if ($rule->isSystemRule) {
                return response()->json([
                    'success' => false,
                    'message' => 'System rules cannot be deleted. You can disable them instead.',
                ], 403);
            }

            $rule->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Rule deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete query rule error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete rule.',
            ], 500);
        }
    }

    /**
     * Reset to default rules (removes custom rules, recreates system rules).
     */
    public function resetToDefaults()
    {
        try {
            $userId = Auth::id();

            // Soft delete all existing rules for this user
            AiQueryRule::where('usersId', $userId)
                ->where('delete_status', 'active')
                ->update(['delete_status' => 'deleted']);

            // Recreate default rules
            AiQueryRule::createDefaultRulesForUser($userId);

            return response()->json([
                'success' => true,
                'message' => 'Rules reset to defaults successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Reset query rules error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset rules.',
            ], 500);
        }
    }

    /**
     * Get compiled rules as text (for preview/testing).
     */
    public function getCompiled()
    {
        try {
            $compiled = AiQueryRule::getCompiledRulesForUser(Auth::id());

            return response()->json([
                'success' => true,
                'data' => [
                    'compiled' => $compiled,
                    'isEmpty' => empty($compiled),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get compiled rules error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to compile rules.',
            ], 500);
        }
    }
}
