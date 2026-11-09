<?php

namespace App\Http\Controllers\Streamr;

use App\Services\Streamr\StreamrSales;
use App\Services\Streamr\StreamrTime;
use Illuminate\Http\Request;

/**
 * Streamr > Sales dashboard: revenue, orders, plans vs credits, refunds,
 * payments waiting, subscribers per plan and the latest orders, all from
 * /ecom-orders (the orders of the Streamr store), with a CSV of the sales.
 */
class StreamrSalesController extends StreamrController
{
    public function __construct(private StreamrSales $sales) {}

    public function index(Request $request)
    {
        if (! $this->installed()) {
            return $this->notInstalled('Sales dashboard');
        }

        return view('streamr.sales', [
            'ranges' => StreamrSales::RANGES,
            'range' => StreamrSales::range($request->query('range')),
        ]);
    }

    /** JSON for the page: {kpis, series, subscribers, recent, range}. */
    public function data(Request $request)
    {
        if (! $this->installed()) {
            return $this->fail('The Streamr tables are not installed yet.', 503);
        }

        return $this->json($this->sales->summary(StreamrSales::range($request->query('range'))));
    }

    /** The sales of the range (complete or refunded orders), as CSV. */
    public function export(Request $request)
    {
        if (! $this->installed()) {
            return $this->notInstalled('Sales dashboard');
        }
        $range = StreamrSales::range($request->query('range'));
        $query = $this->sales->sales($range);

        return $this->csv('streamr-sales-' . $range . '-' . StreamrTime::now()->format('Ymd-His') . '.csv', [
            'Order', 'Date (Manila)', 'Client', 'Email', 'Item', 'SKU', 'Kind', 'Method', 'Total (PHP)', 'Refunded (PHP)', 'Net (PHP)',
            'Order status', 'Payment status',
        ], function (callable $emit) use ($query) {
            $query->orderBy('o.id')->chunk(500, function ($rows) use ($emit) {
                foreach ($rows as $o) {
                    $total = (float) $o->grandTotal;
                    $refunded = min($total, (float) $o->refunded);
                    $emit([
                        $o->orderNumber,
                        StreamrTime::show($o->created_at, '', 'Y-m-d H:i:s'),
                        trim(trim((string) $o->clientFirstName) . ' ' . trim((string) $o->clientLastName)),
                        $o->clientEmail,
                        $o->variantName ?: $o->productName,
                        $o->variantSku,
                        StreamrSales::kindOf($o->productName) === 'credits' ? 'Credits' : 'Plan',
                        $o->paymentMethod,
                        number_format($total, 2, '.', ''),
                        number_format($refunded, 2, '.', ''),
                        number_format($total - $refunded, 2, '.', ''),
                        $o->orderStatus,
                        $o->paymentVerificationStatus,
                    ]);
                }
            });
        });
    }
}
