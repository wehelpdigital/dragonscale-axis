<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TriggersController extends Controller
{
    /**
     * Display the triggers page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('ecommerce.triggers.index');
    }
}

