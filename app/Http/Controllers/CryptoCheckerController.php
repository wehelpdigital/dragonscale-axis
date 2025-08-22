<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CryptoCheckerController extends Controller
{
    /**
     * Display the crypto checker page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('crypto-checker');
    }
}
