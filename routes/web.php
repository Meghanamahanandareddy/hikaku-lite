<?php

use App\Http\Controllers\HotelController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('hotels');
});

Route::get('/api/stats', [StatsController::class, 'index']);
Route::get('/api/hotels', [HotelController::class, 'index']);
Route::get('/api/hotels/{hotel}', [HotelController::class, 'show'])->whereNumber('hotel');

// Click-out to a (mock) booking site
Route::get('/go/{hotel}/{slug}', [PartnerController::class, 'show'])->whereNumber('hotel');