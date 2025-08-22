<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Task;

class CryptoSetChangeController extends Controller
{
    /**
     * Display the crypto set change form.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $id = $request->get('id');

        if (!$id) {
            return redirect()->route('crypto-set')->with('error', 'ID is required.');
        }

        // First try to find an existing task with this ID
        $task = Task::where('id', $id)
                   ->where('usersId', Auth::user()->id)
                   ->where('status', 'current')
                   ->first();

        // If no task found, check if the ID matches the current user's ID (for creating new task)
        if (!$task && $id == Auth::user()->id) {
            // Check if user has any current tasks
            $existingTasks = Task::where('usersId', Auth::user()->id)
                               ->where('status', 'current')
                               ->count();

            if ($existingTasks == 0) {
                // User has no current tasks, allow creating new task
                $task = null; // This will indicate to the view that we're creating a new task
                return view('crypto-set-change', compact('task'));
            } else {
                return redirect()->route('crypto-set')->with('error', 'You already have active tasks. Please manage them first.');
            }
        }

        if (!$task && $id != Auth::user()->id) {
            return redirect()->route('crypto-set')->with('error', 'Task not found or you do not have permission to edit it.');
        }

        return view('crypto-set-change', compact('task'));
    }

    /**
     * Update the crypto set task.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        // Check if this is creating a new task or updating an existing one
        $isNewTask = !$request->has('task_id');

        if ($isNewTask) {
            // Validation for new task
            $request->validate([
                'taskCoin' => 'required|in:btc,eth',
                'taskType' => 'required|in:to sell,to buy',
            ]);
        } else {
            // Validation for existing task update
            $request->validate([
                'task_id' => 'required|exists:task,id',
                'taskType' => 'required|in:to sell,to buy',
            ]);
        }

        $task = null;
        if (!$isNewTask) {
            $task = Task::where('id', $request->task_id)
                       ->where('usersId', Auth::user()->id)
                       ->where('status', 'current')
                       ->first();

            if (!$task) {
                return redirect()->route('crypto-set')->with('error', 'Task not found or you do not have permission to edit it.');
            }

            // Update the current task status to 'done'
            $task->update(['status' => 'done']);
        }

        // Create a new task with the form data
        $newTaskData = [
            'usersId' => Auth::user()->id,
            'taskCoin' => $isNewTask ? $request->taskCoin : $task->taskCoin,
            'status' => 'current',
        ];

        // Update task based on type
        if ($request->taskType === 'to sell') {
            $request->validate([
                'currentCoinValue' => 'required|numeric|min:0',
                'startingPhpValue' => 'required|numeric|min:0',
                'minThreshold' => 'required|numeric|min:0',
                'intervalThreshold' => 'required|numeric|min:0',
            ]);

            $newTaskData = array_merge($newTaskData, [
                'taskType' => 'to sell',
                'currentCoinValue' => $request->currentCoinValue,
                'startingPhpValue' => $request->startingPhpValue,
                'minThreshold' => $request->minThreshold,
                'intervalThreshold' => $request->intervalThreshold,
            ]);
        } else {
            $request->validate([
                'toBuyCurrentCashValue' => 'required|numeric|min:0',
                'toBuyStartingCoinValue' => 'required|numeric|min:0',
                'toBuyMinThreshold' => 'required|numeric|min:0',
                'toBuyIntervalThreshold' => 'required|numeric|min:0',
            ]);

            $newTaskData = array_merge($newTaskData, [
                'taskType' => 'to buy',
                'toBuyCurrentCashValue' => $request->toBuyCurrentCashValue,
                'toBuyStartingCoinValue' => $request->toBuyStartingCoinValue,
                'toBuyMinThreshold' => $request->toBuyMinThreshold,
                'toBuyIntervalThreshold' => $request->toBuyIntervalThreshold,
            ]);
        }

        // Create the new task
        Task::create($newTaskData);

        $message = $isNewTask ? 'Crypto task created successfully!' : 'Crypto set updated successfully!';
        return redirect()->route('crypto-set')->with('success', $message);
    }
}
