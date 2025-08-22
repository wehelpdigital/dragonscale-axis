<?php

namespace App\Http\Controllers;

use App\Models\DifferenceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CryptoDifferenceHistoryToSellController extends Controller
{
    /**
     * Display the crypto difference history to sell page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get date range from request
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));

        // Query difference history for current user and to sell tasks with pagination
        $perPage = 10; // Number of records per page
        $query = DifferenceHistory::where('usersId', $user->id)
            ->where('taskType', 'to sell')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy('created_at', 'desc');

        $differenceHistory = $query->paginate($perPage);
        $differenceHistory->appends($request->query());

        // Calculate movement percentage for each row
        $differenceHistoryWithMovement = [];
        $previousDifference = null;

        foreach ($differenceHistory as $index => $record) {
            $movement = null;
            if ($previousDifference !== null && $previousDifference != 0) {
                $movement = (($record->cashDifference - $previousDifference) / abs($previousDifference)) * 100;
            }

            $differenceHistoryWithMovement[] = [
                'id' => $record->id,
                'date' => $record->created_at->format('F j, Y'),
                'time' => $record->created_at->format('g:ia'),
                'current_cash_value' => $record->toSellCurrentCoinValue,
                'starting_coin_value' => $record->toSellStartingPhpValue,
                'difference' => $record->cashDifference,
                'movement' => $movement,
                'created_at' => $record->created_at
            ];

            $previousDifference = $record->cashDifference;
        }

        // Prepare data for chart
        $chartData = $differenceHistory->map(function ($record) {
            return [
                'x' => $record->created_at->format('Y-m-d H:i:s'),
                'y' => (float) $record->cashDifference
            ];
        })->reverse()->values();

        return view('crypto-difference-history-to-sell', compact(
            'differenceHistoryWithMovement',
            'differenceHistory',
            'chartData',
            'startDate',
            'endDate'
        ));
    }
}
