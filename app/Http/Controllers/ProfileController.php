<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = User::with('assets')->findOrFail(4);
        return response()->json([
            'balance' => $user->balance,
            'assets' => $user->assets()
            ->select('symbol', 'amount', 'locked_amount')
            ->orderBy('symbol')
            ->get(),
        ]);
    }

}
