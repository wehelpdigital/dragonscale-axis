<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistoricalPrice;
use Carbon\Carbon;

class CryptoHistoryController extends Controller
{
    /**
     * Display the crypto history page.
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

        // Get data for chart (last 30 days by default)
        $chartData = $this->getChartData($request);

        return view('crypto-history', compact('historicalPrices', 'chartData', 'coinType'));
    }

    /**
     * Get chart data for the bar graph
     *
     * @param Request $request
     * @return array
     */
    private function getChartData(Request $request)
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

        $chartData = [
            'labels' => [],
            'values' => [],
            'dates' => []
        ];

        foreach ($prices as $price) {
            // Convert to Philippines timezone for display
            $philippinesTime = $price->created_at->setTimezone('Asia/Manila');
            $chartData['labels'][] = $philippinesTime->format('M j H:i');
            $chartData['values'][] = $price->valueInPhp;
            $chartData['dates'][] = $philippinesTime->format('F j, Y g:iA');
        }

        // Calculate y-axis range based on latest valueInPhp ± 500k
        if (!empty($chartData['values'])) {
            $latestValue = end($chartData['values']);
            $chartData['yAxisMin'] = $latestValue - 500000; // minus 500k
            $chartData['yAxisMax'] = $latestValue + 500000; // plus 500k
        } else {
            // Default range if no data
            $chartData['yAxisMin'] = 0;
            $chartData['yAxisMax'] = 1000000;
        }

        return $chartData;
    }
}
