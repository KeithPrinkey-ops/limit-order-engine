<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RegisterController;

// AUTH (SESSION-BASED)
Route::get('/register', [RegisterController::class, 'register']);
Route::get('/login', [RegisterController::class, 'login']);
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['message' => 'Logged out']);
});

// SPA ENTRY — MUST BE LAST
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
