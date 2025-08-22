<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistoricalPrice;
use Carbon\Carbon;

class CryptoPricingHistoryController extends Controller
{
    /**
     * Display the crypto pricing history page.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        // Build query for historical prices
        $query = HistoricalPrice::orderBy('created_at', 'desc');

        // Apply date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        } else {
            // Default to last 24 hours if no date filter
            $query->where('created_at', '>=', Carbon::now()->subHours(24));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Apply coin type filter (default to btc)
        $coinType = $request->get('coin_type', 'btc');
        $query->where('coinType', $coinType);

        // Get paginated results (100 per page)
        $historicalPrices = $query->paginate(100);

        // Get data for ladder chart
        $ladderData = $this->getLadderData($request);

        return view('crypto-pricing-history', compact('historicalPrices', 'ladderData', 'coinType'));
    }

    /**
     * Get ladder data for the chart
     *
     * @param Request $request
     * @return array
     */
    private function getLadderData(Request $request)
    {
        $query = HistoricalPrice::orderBy('created_at', 'asc');

        // Apply date range filter for chart
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        } else {
            // Default to last 24 hours if no date filter
            $query->where('created_at', '>=', Carbon::now()->subHours(24));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Apply coin type filter
        $coinType = $request->get('coin_type', 'btc');
        $query->where('coinType', $coinType);

        $prices = $query->get();

        if ($prices->isEmpty()) {
            return [
                'labels' => [],
                'values' => [],
                'dates' => [],
                'ladderValues' => []
            ];
        }

        // Get the most recent price as current price
        $currentPrice = $prices->last()->valueInPhp;

        $chartData = [
            'labels' => [],
            'values' => [],
            'dates' => [],
            'ladderValues' => []
        ];

        // Calculate ladder values (percentage changes from current price)
        foreach ($prices as $price) {
            // Convert to Philippines timezone for display
            $philippinesTime = $price->created_at->setTimezone('Asia/Manila');
            $chartData['labels'][] = $philippinesTime->format('M j H:i');
            $chartData['values'][] = $price->valueInPhp;
            $chartData['dates'][] = $philippinesTime->format('F j, Y g:iA');

            // Calculate percentage change: (historical price - current price) / current price * 100
            $pctChange = (($price->valueInPhp - $currentPrice) / $currentPrice) * 100; // Convert to percentage
            $chartData['ladderValues'][] = round($pctChange, 2);
        }

        return $chartData;
    }

    /**
     * Calculate difference between two historical price points
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateDifference(Request $request)
    {
        try {
            // Validate request
            $validationRules = [
                'dateA' => 'required|date',
                'timeA' => 'required|string',
                'dateB' => 'required|date',
                'timeB' => 'required|string',
                'valueType' => 'required|in:crypto,php',
                'coinType' => 'required|string'
            ];

            // Only require values if crypto is selected
            if ($request->valueType === 'crypto') {
                $validationRules['valueA'] = 'required|numeric';
                $validationRules['valueB'] = 'required|numeric';
            }

            $request->validate($validationRules);

            // Parse dates and times
            $dateTimeA = Carbon::createFromFormat('Y-m-d g:i A', $request->dateA . ' ' . $request->timeA);
            $dateTimeB = Carbon::createFromFormat('Y-m-d g:i A', $request->dateB . ' ' . $request->timeB);

            // Find nearest historical prices
            $nearestA = $this->findNearestHistoricalPrice($dateTimeA, $request->coinType);
            $nearestB = $this->findNearestHistoricalPrice($dateTimeB, $request->coinType);

            if (!$nearestA || !$nearestB) {
                return response()->json([
                    'success' => false,
                    'message' => 'No historical data found for the specified dates and times.'
                ], 404);
            }

            // Calculate coin price difference
            $coinPriceDifference = $nearestB->valueInPhp - $nearestA->valueInPhp;

            // Calculate value differences based on value type
            $valueDifference = 0;
            $valueTypeLabel = '';

            if ($request->valueType === 'php') {
                // For PHP values, use the historical prices directly
                $valueDifference = $nearestB->valueInPhp - $nearestA->valueInPhp;
                $valueTypeLabel = 'PHP Value Difference';
                $valueAInPhp = $nearestA->valueInPhp;
                $valueBInPhp = $nearestB->valueInPhp;
            } else {
                // For crypto values, multiply by historical prices to get PHP equivalent
                $valueAInPhp = $request->valueA * $nearestA->valueInPhp;
                $valueBInPhp = $request->valueB * $nearestB->valueInPhp;
                $valueDifference = $valueBInPhp - $valueAInPhp;
                $valueTypeLabel = 'Crypto Value Difference (in PHP)';
            }

            // Format the results
            $results = [
                'success' => true,
                'comparisonA' => [
                    'date' => $nearestA->created_at->setTimezone('Asia/Manila')->format('F j, Y'),
                    'time' => $nearestA->created_at->setTimezone('Asia/Manila')->format('g:iA'),
                    'historicalPrice' => number_format($nearestA->valueInPhp, 2),
                    'userValue' => $request->valueType === 'crypto' ? $request->valueA : null,
                    'userValueInPhp' => number_format($valueAInPhp, 2),
                    'valueType' => $request->valueType
                ],
                'comparisonB' => [
                    'date' => $nearestB->created_at->setTimezone('Asia/Manila')->format('F j, Y'),
                    'time' => $nearestB->created_at->setTimezone('Asia/Manila')->format('g:iA'),
                    'historicalPrice' => number_format($nearestB->valueInPhp, 2),
                    'userValue' => $request->valueType === 'crypto' ? $request->valueB : null,
                    'userValueInPhp' => number_format($valueBInPhp, 2),
                    'valueType' => $request->valueType
                ],
                'coinPriceDifference' => [
                    'value' => $coinPriceDifference,
                    'formatted' => number_format($coinPriceDifference, 2),
                    'direction' => $coinPriceDifference >= 0 ? 'positive' : 'negative'
                ],
                'valueDifference' => [
                    'value' => $valueDifference,
                    'formatted' => number_format($valueDifference, 2),
                    'direction' => $valueDifference >= 0 ? 'positive' : 'negative',
                    'label' => $valueTypeLabel
                ]
            ];

            return response()->json($results);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while calculating the difference: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find the nearest historical price to a given date and time
     *
     * @param Carbon $targetDateTime
     * @param string $coinType
     * @return HistoricalPrice|null
     */
    private function findNearestHistoricalPrice($targetDateTime, $coinType)
    {
        // Get all historical prices for the coin type
        $prices = HistoricalPrice::where('coinType', $coinType)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($prices->isEmpty()) {
            return null;
        }

        $nearest = null;
        $smallestDiff = PHP_INT_MAX;

        foreach ($prices as $price) {
            $diff = abs($targetDateTime->diffInMinutes($price->created_at));

            if ($diff < $smallestDiff) {
                $smallestDiff = $diff;
                $nearest = $price;
            }
        }

        return $nearest;
    }
}
