<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomOrder;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class OrdersController extends Controller
{
    /**
     * Display the orders page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('ecommerce.orders.index');
    }

    /**
     * Get orders data for DataTables.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        $query = EcomOrder::active()
            ->orderBy('created_at', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('formatted_date', function ($order) {
                return $order->formatted_date;
            })
            ->addColumn('formatted_time', function ($order) {
                return $order->formatted_time;
            })
            ->addColumn('formatted_payment_amount', function ($order) {
                return $order->formatted_payment_amount;
            })
            ->addColumn('formatted_payment_discount', function ($order) {
                return $order->formatted_payment_discount;
            })
            ->addColumn('formatted_shipping_amount', function ($order) {
                return $order->formatted_shipping_amount;
            })
            ->addColumn('formatted_total_to_pay', function ($order) {
                return $order->formatted_total_to_pay;
            })
            ->rawColumns(['formatted_date', 'formatted_time', 'formatted_payment_amount', 'formatted_payment_discount', 'formatted_shipping_amount', 'formatted_total_to_pay'])
            ->make(true);
    }
}
