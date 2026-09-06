<?php

use App\Console\Commands\GenerateDailyTaskOccurrences;
use App\Models\Task;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(GenerateDailyTaskOccurrences::class)
    ->dailyAt('00:00')
    ->timezone(Task::TIMEZONE)
    ->withoutOverlapping(30)
    ->onOneServer();
