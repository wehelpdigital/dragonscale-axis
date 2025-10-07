<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AffiliatesController extends Controller
{
    /**
     * Display the affiliates page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('ecommerce.affiliates.index');
    }
}

