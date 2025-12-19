<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RegisterController;

// AUTH (SESSION-BASED)


// SPA ENTRY — MUST BE LAST
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
