<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Purge hebdomadaire des vues de pages (necessite le cron `php artisan schedule:run` chaque minute).
Schedule::command('page-views:prune')->weekly();
