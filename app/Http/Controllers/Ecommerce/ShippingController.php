<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    /**
     * Display the shipping settings page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('ecommerce.shipping');
    }
}
