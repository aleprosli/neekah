<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('neekah:pro-reminders')->dailyAt('09:00');
Schedule::command('neekah:expire-booking-holds')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('neekah:requery-payments')->everyTenMinutes()->withoutOverlapping();
Schedule::command('neekah:calendar-reminders')->dailyAt('09:00');
Schedule::command('neekah:sync-ical')->hourly()->withoutOverlapping();
Schedule::command('neekah:camera-retention')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('neekah:vendor-popularity')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('neekah:boost-grants')->dailyAt('09:00')->withoutOverlapping();
