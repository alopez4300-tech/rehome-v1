<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\Agent\GenerateDailySummaryJob;
use App\Jobs\Agent\GenerateWeeklySummaryJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily summaries to run at 1 AM
Schedule::job(GenerateDailySummaryJob::class)
    ->daily()
    ->at('01:00')
    ->withoutOverlapping();

// Schedule weekly summaries to run on Sundays at 2 AM
Schedule::job(GenerateWeeklySummaryJob::class)
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->withoutOverlapping();
