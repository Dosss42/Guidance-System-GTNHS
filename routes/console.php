<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Nightly encrypted backup, verified straight away. The server must run
 * `php artisan schedule:run` every minute (Task Scheduler on Windows,
 * cron on Linux) for this to happen.
 */
Schedule::command('guidance:backup --verify')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();
