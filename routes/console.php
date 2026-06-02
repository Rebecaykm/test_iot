<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('iot:work-center')->dailyAt('00:00');
Schedule::command('iot:item-class')->dailyAt('00:20');
Schedule::command('iot:standard-pack')->dailyAt('00:40');
Schedule::command('iot:part-number')->cron('0 2 2-30/2 * *');
Schedule::command('infor:standard-pack')->cron('*/30 * * * *');

Schedule::command('iot:sync-production-records')->cron('36 17 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records')->cron('06 20 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records')->cron('56 04 * * *')->withoutOverlapping();
Schedule::command('iot:sync-production-records')->cron('56 07 * * *')->withoutOverlapping();
