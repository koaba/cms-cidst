<?php

use App\Services\Environment\EnvironmentProbe;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Purge hebdomadaire des vues de pages (necessite le cron `php artisan schedule:run` chaque minute).
Schedule::command('page-views:prune')->weekly();

Schedule::command('audience:prune')->daily();

// Battement lu par app:check-environment : prouve que le cron tourne (voir EnvironmentProbe).
Schedule::call(fn () => Cache::forever(EnvironmentProbe::SCHEDULER_HEARTBEAT_KEY, time()))
    ->everyMinute()
    ->name('scheduler-heartbeat');
