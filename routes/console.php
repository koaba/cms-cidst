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

Schedule::command('contact:prune-images')->daily();

Schedule::command('contact:prune-messages')->daily();

// Traite les sauvegardes demandees depuis l'administration (hors requete web).
// Demande la sauvegarde automatique quand elle est due (options de l'administration).
Schedule::command('backup:auto')->hourlyAt(5)->withoutOverlapping();
Schedule::command('backup:process')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('backup:sync-files')->everyFiveMinutes()->withoutOverlapping()->runInBackground();

// Battement lu par app:check-environment : prouve que le cron tourne (voir EnvironmentProbe).
Schedule::call(fn () => Cache::forever(EnvironmentProbe::SCHEDULER_HEARTBEAT_KEY, time()))
    ->everyMinute()
    ->name('scheduler-heartbeat');
