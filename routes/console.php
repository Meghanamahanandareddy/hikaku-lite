<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ingest the day's hotel prices every night at 2:00 AM
Schedule::command('prices:ingest')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/ingest.log'));

// Then check the price alerts at 2:10 AM and email anyone whose target was reached
Schedule::command('alerts:check')
    ->dailyAt('02:10')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/alerts.log'));