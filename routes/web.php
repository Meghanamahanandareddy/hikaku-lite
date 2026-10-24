<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\StatsController;
use App\Mail\PriceDropAlert;
use App\Models\Hotel;
use App\Models\PriceAlert;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('hotels');
});

Route::get('/api/stats', [StatsController::class, 'index']);
Route::get('/api/hotels', [HotelController::class, 'index']);
Route::get('/api/hotels/{hotel}', [HotelController::class, 'show'])->whereNumber('hotel');

// Price drop alerts (max 10 requests per minute per visitor)
Route::post('/api/alerts', [AlertController::class, 'store'])->middleware('throttle:10,1');

// One-click unsubscribe from the alert email (the link is signed, so it can't be guessed)
Route::get('/alerts/{alert}/unsubscribe', [AlertController::class, 'unsubscribe'])
    ->whereNumber('alert')
    ->middleware('signed')
    ->name('alerts.unsubscribe');

// Click-out to a (mock) booking site
Route::get('/go/{hotel}/{slug}', [PartnerController::class, 'show'])->whereNumber('hotel');

// Email preview in the browser (local development only)
Route::get('/dev/email-preview', function () {
    abort_unless(app()->environment('local'), 404);

    $alert = PriceAlert::with('hotel')->first();

    if ($alert) {
        $hotel = $alert->hotel;
    } else {
        $hotel = Hotel::firstOrFail();
        $alert = (new PriceAlert)->forceFill([
            'id'           => 0,
            'hotel_id'     => $hotel->id,
            'email'        => 'demo@example.com',
            'target_price' => 6900,
        ]);
    }

    return (new PriceDropAlert(
        $alert,
        $hotel,
        7000.0,
        'TripNest',
        url("/go/{$hotel->id}/tripnest")
    ))->render();
});