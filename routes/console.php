<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('iot:work-center')->cron('0 0 * * 1');
Schedule::command('iot:item-class')->cron('0 1 * * 1');
Schedule::command('iot:part-number')->cron('0 2 * * 1');
Schedule::command('iot:production-plan')->dailyAt('07:30');
