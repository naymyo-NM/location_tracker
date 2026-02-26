<?php

namespace App\Jobs;

use App\Services\TrackingBatchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessTrackingBatchJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public function handle(TrackingBatchService $batchService): void
    {
        if (! config('tracking.use_batch_write', true)) {
            return;
        }
        try {
            $processed = $batchService->processBatch();
            if ($processed > 0) {
                Log::info("ProcessTrackingBatchJob: processed {$processed} tracking point(s).");
            }
        } catch (\Throwable $e) {
            Log::error('ProcessTrackingBatchJob failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
