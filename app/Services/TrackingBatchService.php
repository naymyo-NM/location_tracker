<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Tracking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class TrackingBatchService
{
    public function pushPoint(array $payload): void
    {
        $key = config('tracking.batch_redis_list_key', 'tracking:batch:pending');
        Redis::connection()->rpush($key, json_encode($payload));
    }

    /**
     * Write points directly to the database (e.g. when Redis is unavailable).
     * Payloads must include user_id, device_id, session_id, latitude, longitude, accuracy, duration, tracking_time.
     */
    public function writePointsDirect(array $payloads): void
    {
        if (empty($payloads)) {
            return;
        }
        $bySession = [];
        foreach ($payloads as $p) {
            if (empty($p['session_id']) || empty($p['user_id'])) {
                continue;
            }
            $sid = (int) $p['session_id'];
            if (! isset($bySession[$sid])) {
                $bySession[$sid] = [];
            }
            $bySession[$sid][] = $p;
        }
        foreach ($bySession as $sessionId => $points) {
            $this->processSessionBatch($sessionId, $points);
        }
    }

    public function processBatch(): int
    {
        $key = config('tracking.batch_redis_list_key', 'tracking:batch:pending');
        $redis = Redis::connection();
        $max = config('tracking.batch_max_points', 100);
        $items = $redis->lrange($key, 0, $max - 1);
        if (empty($items)) {
            return 0;
        }
        $redis->ltrim($key, count($items), -1);

        $deduped = [];
        $seenKeys = [];
        foreach ($items as $item) {
            $decoded = json_decode($item, true);
            if (! is_array($decoded) || empty($decoded['session_id']) || empty($decoded['user_id'])) {
                continue;
            }
            $ik = $decoded['idempotency_key'] ?? null;
            if ($ik !== null && isset($seenKeys[$ik])) {
                continue;
            }
            if ($ik !== null) {
                $seenKeys[$ik] = true;
            }
            $deduped[] = $decoded;
        }

        $bySession = [];
        foreach ($deduped as $p) {
            $sid = (int) $p['session_id'];
            if (! isset($bySession[$sid])) {
                $bySession[$sid] = [];
            }
            $bySession[$sid][] = $p;
        }

        foreach ($bySession as $sessionId => $points) {
            $this->processSessionBatch($sessionId, $points);
        }

        return count($deduped);
    }

    private function processSessionBatch(int $sessionId, array $points): void
    {
        usort($points, function ($a, $b) {
            $t1 = isset($a['tracking_time']) ? strtotime($a['tracking_time']) : 0;
            $t2 = isset($b['tracking_time']) ? strtotime($b['tracking_time']) : 0;
            return $t1 <=> $t2;
        });

        DB::beginTransaction();
        try {
            $trackingIds = [];
            foreach ($points as $p) {
                $tracking = Tracking::create([
                    'user_id' => (int) $p['user_id'],
                    'device_id' => (string) $p['device_id'],
                    'session_id' => $sessionId,
                    'latitude' => (float) $p['latitude'],
                    'longitude' => (float) $p['longitude'],
                    'accuracy' => (float) ($p['accuracy'] ?? 0),
                    'duration' => (float) ($p['duration'] ?? 0),
                    'tracking_time' => isset($p['tracking_time']) ? $p['tracking_time'] : now(),
                ]);
                $trackingIds[] = $tracking->id;
            }

            $openLocation = Location::where('session_id', $sessionId)->whereNull('end_tracking_id')->latest('id')->first();
            $deviceId = $points[0]['device_id'] ?? '';
            $userId = (int) $points[0]['user_id'];

            foreach ($trackingIds as $i => $trackingId) {
                $tracking = Tracking::find($trackingId);
                if (! $tracking) {
                    continue;
                }
                if ($openLocation) {
                    $startT = Tracking::find($openLocation->start_tracking_id);
                    $distance = $startT ? $this->calculateDistance(
                        $startT->latitude,
                        $startT->longitude,
                        $tracking->latitude,
                        $tracking->longitude
                    ) : 0;
                    $durationSeconds = $startT ? $startT->created_at->diffInSeconds($tracking->created_at) : 0;
                    $speed = $durationSeconds > 0 ? ($distance / 1000) / ($durationSeconds / 3600) : 0;
                    $openLocation->update([
                        'end_tracking_id' => $trackingId,
                        'distance' => round($distance, 2),
                        'duration' => $durationSeconds,
                        'speed' => round($speed, 2),
                    ]);
                    $openLocation = null;
                }
                Location::create([
                    'device_id' => $deviceId,
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'start_tracking_id' => $trackingId,
                    'end_tracking_id' => null,
                    'speed' => 0,
                    'distance' => 0,
                    'duration' => 0,
                    'timestamp' => $tracking->tracking_time ?? now(),
                ]);
                $openLocation = Location::where('session_id', $sessionId)->whereNull('end_tracking_id')->latest('id')->first();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));
        return $angle * $earthRadius;
    }
}
