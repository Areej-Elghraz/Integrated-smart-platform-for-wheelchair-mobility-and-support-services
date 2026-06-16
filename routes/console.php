<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\AggregateSensorReadings;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// // IMPORTANT: redis:sync-to-mysql MUST run BEFORE AggregateSensorReadings
// // because sensor readings are buffered in Redis first, then synced to MySQL,
// // then aggregated from MySQL into sensor_readings_aggregated for the dashboard.
// Schedule::command('redis:sync-to-mysql')->everyFiveMinutes();
Schedule::job(new AggregateSensorReadings)->everyTenMinutes();

Schedule::command('data:cleanup')->daily();
Schedule::command('redis:sync-to-mysql')->everyFiveMinutes();
