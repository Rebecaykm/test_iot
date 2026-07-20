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
Schedule::command('iot:part-number')->cron('0 2 2-30/2 * *')->withoutOverlapping();
Schedule::command('iot:die-identifier')->cron('0 0 * * *')->withoutOverlapping();
Schedule::command('iot:part-shots')->cron('5 0 * * *')->withoutOverlapping();
Schedule::command('iot:production-plan')->cron('0 * * * *')->withoutOverlapping();

Schedule::command('iot:sync-production-records-live')->cron('36 17 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records-live')->cron('06 20 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records-live')->cron('56 04 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records-live')->cron('56 07 * * *')->withoutOverlapping();

Schedule::command('iot:sync-production-records-proto')->cron('36 17 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records-proto')->cron('06 20 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records-proto')->cron('56 04 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records-proto')->cron('56 07 * * *')->withoutOverlapping();
