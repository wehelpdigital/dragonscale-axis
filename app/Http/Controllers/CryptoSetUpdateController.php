<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Task;

class CryptoSetUpdateController extends Controller
{
    /**
     * Display the crypto set update form.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $taskId = $request->get('id');

        if (!$taskId) {
            return redirect()->route('crypto-set')->with('error', 'Task ID is required.');
        }

        $task = Task::where('id', $taskId)
                   ->where('usersId', Auth::user()->id)
                   ->where('status', 'current')
                   ->first();

        if (!$task) {
            return redirect()->route('crypto-set')->with('error', 'Task not found or you do not have permission to edit it.');
        }

        return view('crypto-set-update', compact('task'));
    }

    /**
     * Update the existing crypto set task.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:task,id',
            'taskType' => 'required|in:to sell,to buy',
        ]);

        $task = Task::where('id', $request->task_id)
                   ->where('usersId', Auth::user()->id)
                   ->where('status', 'current')
                   ->first();

        if (!$task) {
            return redirect()->route('crypto-set')->with('error', 'Task not found or you do not have permission to edit it.');
        }

        // Update the existing task with the form data
        $updateData = [
            'taskType' => $request->taskType,
        ];

        // Update task based on type
        if ($request->taskType === 'to sell') {
            $request->validate([
                'currentCoinValue' => 'required|numeric|min:0',
                'startingPhpValue' => 'required|numeric|min:0',
                'minThreshold' => 'required|numeric|min:0',
                'intervalThreshold' => 'required|numeric|min:0',
            ]);

            $updateData = array_merge($updateData, [
                'currentCoinValue' => $request->currentCoinValue,
                'startingPhpValue' => $request->startingPhpValue,
                'minThreshold' => $request->minThreshold,
                'intervalThreshold' => $request->intervalThreshold,
                // Clear to buy fields
                'toBuyCurrentCashValue' => null,
                'toBuyStartingCoinValue' => null,
                'toBuyMinThreshold' => null,
                'toBuyIntervalThreshold' => null,
            ]);
        } else {
            $request->validate([
                'toBuyCurrentCashValue' => 'required|numeric|min:0',
                'toBuyStartingCoinValue' => 'required|numeric|min:0',
                'toBuyMinThreshold' => 'required|numeric|min:0',
                'toBuyIntervalThreshold' => 'required|numeric|min:0',
            ]);

            $updateData = array_merge($updateData, [
                'toBuyCurrentCashValue' => $request->toBuyCurrentCashValue,
                'toBuyStartingCoinValue' => $request->toBuyStartingCoinValue,
                'toBuyMinThreshold' => $request->toBuyMinThreshold,
                'toBuyIntervalThreshold' => $request->toBuyIntervalThreshold,
                // Clear to sell fields
                'currentCoinValue' => null,
                'startingPhpValue' => null,
                'minThreshold' => null,
                'intervalThreshold' => null,
            ]);
        }

        // Update the existing task
        $task->update($updateData);

        return redirect()->route('crypto-set')->with('success', 'Crypto set updated successfully!');
    }
}
