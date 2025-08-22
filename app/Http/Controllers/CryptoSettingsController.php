<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CryptoSettingsController extends Controller
{
    /**
     * Display the crypto settings page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('crypto-settings');
    }
}
