<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Task;

class CryptoSetController extends Controller
{
    /**
     * Display the crypto set page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        $tasks = Task::where('usersId', $user->id)
                    ->where('status', 'current')
                    ->get();

        return view('crypto-set', compact('tasks'));
    }
}
