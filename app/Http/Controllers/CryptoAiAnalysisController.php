<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CryptoAiAnalysisController extends Controller
{
    /**
     * Display the crypto AI analysis page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('crypto-ai-analysis');
    }
}
