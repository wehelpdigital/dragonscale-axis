<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CryptoTutorialsController extends Controller
{
    /**
     * Display the crypto tutorials page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('crypto-tutorials');
    }
}
