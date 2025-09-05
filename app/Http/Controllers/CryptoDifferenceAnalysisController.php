<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HistoricalPrice;
use App\Models\Task;
use Carbon\Carbon;

class CryptoDifferenceAnalysisController extends Controller
{
    /**
     * Display the crypto difference analysis page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('crypto-difference-analysis');
    }

    /**
     * Generate analysis graph data
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateAnalysis(Request $request)
    {
        try {
            $coinType = $request->input('coinType');
            $taskType = $request->input('taskType');
            $dateFrom = $request->input('dateFrom');
            $dateTo = $request->input('dateTo');
            $currentCoinValue = $request->input('currentCoinValue');
            $currentPhpValue = $request->input('currentPhpValue');
            $lastPhpValue = $request->input('lastPhpValue');
            $lastCoinValue = $request->input('lastCoinValue');

            // Build query for historical prices
            $query = HistoricalPrice::where('coinType', $coinType);

            // Apply date range filter
            if ($dateFrom && $dateTo) {
                $query->whereDate('created_at', '>=', $dateFrom)
                      ->whereDate('created_at', '<=', $dateTo);
            } else {
                // If no date range, show last 1 day
                $query->where('created_at', '>=', Carbon::now()->subDays(1));
            }

            $historicalPrices = $query->orderBy('created_at', 'asc')->get();

            // Prepare chart data
            $chartData = [];
            $labels = [];
            $values = [];
            $differenceData = [];

            foreach ($historicalPrices as $price) {
                $labels[] = $price->created_at->format('M j, g:iA');

                                if ($taskType === 'sell') {
                    // For sell: maintain current calculation
                    $calculatedValue = $price->valueInPhp * $currentCoinValue;
                    $values[] = round($calculatedValue, 2);

                    // Calculate difference for sell: calculated value - last PHP value (swapped)
                    $difference = $calculatedValue - $lastPhpValue;
                    $differenceData[] = round($difference, 2);
                } else {
                    // For buy: valueInPhp * lastCoinValue (Your Supposed BTC Price)
                    $supposedBtcPrice = $price->valueInPhp * $lastCoinValue;
                    $values[] = round($supposedBtcPrice, 2);

                    // Calculate difference for buy: supposed BTC price - current PHP value (reversed)
                    $difference = $supposedBtcPrice - $currentPhpValue;
                    $differenceData[] = round($difference, 2);
                }
            }

            // Prepare reference lines
            $referenceLines = [];
            if ($taskType === 'sell') {
                // For sell: show only Last PHP Value Before Buying
                $referenceLines[] = [
                    'label' => 'Last PHP Value Before Buying',
                    'value' => $lastPhpValue,
                    'color' => '#dc3545'
                ];
            } else {
                // For buy: show Current PHP Value as horizontal line
                $referenceLines[] = [
                    'label' => 'Current PHP Value',
                    'value' => $currentPhpValue,
                    'color' => '#007bff'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'labels' => $labels,
                    'values' => $values,
                    'differenceData' => $differenceData,
                    'referenceLines' => $referenceLines,
                    'taskType' => $taskType,
                    'coinType' => $coinType
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating analysis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current task data for the logged-in user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentTask()
    {
        try {
            $userId = Auth::id();

            // Get the current task for the logged-in user
            $currentTask = Task::where('usersId', $userId)
                              ->where('status', 'current')
                              ->first();

            if (!$currentTask) {
                return response()->json([
                    'success' => false,
                    'message' => 'No current task found for the user.'
                ], 404);
            }

            // Map database task type to form values
            $formTaskType = '';
            if ($currentTask->taskType === 'to buy') {
                $formTaskType = 'buy';
            } elseif ($currentTask->taskType === 'to sell') {
                $formTaskType = 'sell';
            }

            // Prepare the response data based on task type
            $responseData = [
                'taskType' => $formTaskType,
                'taskCoin' => $currentTask->taskCoin,
            ];

            // Add task-specific values based on task type
            if ($currentTask->taskType === 'to buy') {
                $responseData['currentPhpValue'] = $currentTask->toBuyCurrentCashValue;
                $responseData['lastCoinValue'] = $currentTask->toBuyStartingCoinValue;
            } elseif ($currentTask->taskType === 'to sell') {
                $responseData['currentCoinValue'] = $currentTask->currentCoinValue;
                $responseData['lastPhpValue'] = $currentTask->startingPhpValue;
            }

            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching current task: ' . $e->getMessage()
            ], 500);
        }
    }
}
