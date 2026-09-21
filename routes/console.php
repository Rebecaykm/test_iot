<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('iot:work-center')->cron('0 0 2-30/2 * *')->withoutOverlapping();
Schedule::command('iot:item-class')->cron('0 1 2-30/2 * *')->withoutOverlapping();
Schedule::command('iot:standard-pack')->dailyAt('00:40')->withoutOverlapping();
Schedule::command('iot:part-number')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('iot:die-identifier')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('iot:part-shots')->dailyAt('03:10')->withoutOverlapping();
Schedule::command('iot:part-number-relations')->dailyAt('03:20')->withoutOverlapping();
Schedule::command('iot:production-plan')->hourly()->withoutOverlapping();

// Schedule::command('iot:recover-production-order-numbers-live')->hourly()->withoutOverlapping();
// Schedule::command('iot:recover-production-order-numbers-proto')->hourly()->withoutOverlapping();

Schedule::command('iot:sync-production-records-live')->cron('38 17 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records-live')->cron('08 20 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records-live')->cron('58 04 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records-live')->cron('58 07 * * *')->withoutOverlapping();

Schedule::command('iot:sync-production-records-proto')->cron('38 17 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records-proto')->cron('08 20 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records-proto')->cron('58 04 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records-proto')->cron('58 07 * * *')->withoutOverlapping();
