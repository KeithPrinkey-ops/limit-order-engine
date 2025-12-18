<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;


Route::get('/profile', [ProfileController::class, 'show']);

Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

Route::post('/login', [RegisterController::class, 'login']);
