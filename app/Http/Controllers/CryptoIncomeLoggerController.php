<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\IncomeLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class CryptoIncomeLoggerController extends Controller
{
    /**
     * Display the crypto income logger page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $userId = Auth::user()->id;

        // Get filter parameters
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $taskType = $request->get('task_type');
        $coinType = $request->get('coin_type');

        // Build query for filtered data
        $query = IncomeLogger::active()->forUser($userId);

        // Apply filters
        if ($startDate && $endDate) {
            $query->whereBetween('transactionDateTime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        if ($taskType) {
            $query->byTaskType($taskType);
        }

        if ($coinType) {
            $query->byCoinType($coinType);
        }

        // Get paginated results (max 200 rows)
        $incomeLogs = $query->orderBy('transactionDateTime', 'desc')->paginate(200);

        // Calculate totals for all data (unfiltered)
        $totalToBuyDifference = IncomeLogger::active()
            ->forUser($userId)
            ->byTaskType('to buy')
            ->get()
            ->sum(function($item) {
                return $item->newPhpValue - $item->originalPhpValue;
            });

        $totalToSellDifference = IncomeLogger::active()
            ->forUser($userId)
            ->byTaskType('to sell')
            ->get()
            ->sum(function($item) {
                return $item->newPhpValue - $item->originalPhpValue;
            });

        // Calculate totals for filtered data
        $filteredQuery = IncomeLogger::active()->forUser($userId);

        // Apply the same filters to filtered totals
        if ($startDate && $endDate) {
            $filteredQuery->whereBetween('transactionDateTime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        if ($taskType) {
            $filteredQuery->byTaskType($taskType);
        }

        if ($coinType) {
            $filteredQuery->byCoinType($coinType);
        }

        $filteredToBuyDifference = $filteredQuery->clone()
            ->byTaskType('to buy')
            ->get()
            ->sum(function($item) {
                return $item->newPhpValue - $item->originalPhpValue;
            });

        $filteredToSellDifference = $filteredQuery->clone()
            ->byTaskType('to sell')
            ->get()
            ->sum(function($item) {
                return $item->newPhpValue - $item->originalPhpValue;
            });

        // Get unique values for filter dropdowns
        $taskTypes = IncomeLogger::active()
            ->forUser($userId)
            ->distinct()
            ->pluck('taskType')
            ->filter()
            ->values();

        $coinTypes = IncomeLogger::active()
            ->forUser($userId)
            ->distinct()
            ->pluck('taskCoin')
            ->filter()
            ->values();

        return view('crypto-income-logger', compact(
            'incomeLogs',
            'totalToBuyDifference',
            'totalToSellDifference',
            'filteredToBuyDifference',
            'filteredToSellDifference',
            'taskTypes',
            'coinTypes',
            'startDate',
            'endDate',
            'taskType',
            'coinType'
        ));
    }

    /**
     * Show the form for creating a new income log.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create()
    {
        return view('crypto-income-logger-add');
    }

    /**
     * Store a newly created income log in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'taskCoin' => 'required|string|max:10',
            'taskType' => 'required|in:to buy,to sell',
            'transactionDate' => 'required|date',
            'transactionTime' => 'required|date_format:H:i',
            'originalPhpValue' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
            'newPhpValue' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
        ], [
            'taskCoin.required' => 'Task Coin is required.',
            'taskType.required' => 'Task Type is required.',
            'taskType.in' => 'Task Type must be either "To Buy" or "To Sell".',
            'transactionDate.required' => 'Transaction Date is required.',
            'transactionDate.date' => 'Transaction Date must be a valid date.',
            'transactionTime.required' => 'Transaction Time is required.',
            'transactionTime.date_format' => 'Transaction Time must be a valid time.',
            'originalPhpValue.required' => 'Original PHP Value is required.',
            'originalPhpValue.numeric' => 'Original PHP Value must be a number.',
            'originalPhpValue.min' => 'Original PHP Value must be greater than or equal to 0.',
            'originalPhpValue.regex' => 'Original PHP Value must have maximum 2 decimal places.',
            'newPhpValue.required' => 'New PHP Value is required.',
            'newPhpValue.numeric' => 'New PHP Value must be a number.',
            'newPhpValue.min' => 'New PHP Value must be greater than or equal to 0.',
            'newPhpValue.regex' => 'New PHP Value must have maximum 2 decimal places.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Combine date and time into datetime
            $transactionDateTime = $request->transactionDate . ' ' . $request->transactionTime . ':00';

            // Create the income log
            $incomeLog = IncomeLogger::create([
                'usersId' => Auth::user()->id,
                'taskCoin' => $request->taskCoin,
                'taskType' => $request->taskType,
                'transactionDateTime' => $transactionDateTime,
                'originalPhpValue' => $request->originalPhpValue,
                'newPhpValue' => $request->newPhpValue,
                'delete_status' => 'active',
            ]);

            return redirect()->route('crypto-income-logger')
                ->with('success', 'Income log added successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'An error occurred while saving the income log. Please try again.')
                ->withInput();
        }
    }

    /**
     * Soft delete the specified income log.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $userId = Auth::user()->id;

            // Find the income log and ensure it belongs to the current user
            $incomeLog = IncomeLogger::where('id', $id)
                ->where('usersId', $userId)
                ->where('delete_status', 'active')
                ->first();

            if (!$incomeLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Income log not found or you do not have permission to delete it.'
                ], 404);
            }

            // Soft delete by updating delete_status
            $incomeLog->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Income log deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the income log.'
            ], 500);
        }
    }
}
