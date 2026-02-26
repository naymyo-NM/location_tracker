<?php

use App\Services\TrackingBatchService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    if (config('tracking.use_batch_write', true)) {
        app(TrackingBatchService::class)->processBatch();
    }
})->everyFifteenSeconds()->name('tracking:process-batch')->withoutOverlapping(30);
