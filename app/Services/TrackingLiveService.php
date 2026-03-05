<?php

namespace App\Services;

use App\Models\Tracking;
use App\Models\TrackingSession;
use Illuminate\Support\Facades\Redis;

class TrackingLiveService
{
    private const KEY_SESSION_PREFIX = 'tracking:live:session:';

    private const KEY_USER_PREFIX = 'tracking:live:user:';

    private const LIST_RECENT_PREFIX = 'tracking:live:recent:';

    public function setLivePosition(
        int $sessionId,
        int $userId,
        string $deviceId,
        float $latitude,
        float $longitude,
        float $accuracy,
        $trackingTime = null,
        ?float $speed = null
    ): void {
        try {
            $ttl = config('tracking.live_ttl_seconds', 600);
            $payload = [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'device_id' => $deviceId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
                'tracking_time' => $trackingTime ? (is_string($trackingTime) ? $trackingTime : $trackingTime->toIso8601String()) : now()->toIso8601String(),
                'speed' => $speed,
                'updated_at' => now()->toIso8601String(),
            ];
            $json = json_encode($payload);

            $redis = Redis::connection();
            $redis->setex(self::KEY_SESSION_PREFIX . $sessionId, $ttl, $json);
            $redis->setex(self::KEY_USER_PREFIX . $userId, $ttl, $json);

            $listKey = self::LIST_RECENT_PREFIX . $sessionId;
            $redis->rpush($listKey, $json);
            $max = config('tracking.live_recent_points_max', 100);
            $redis->ltrim($listKey, -$max, -1);
            $redis->expire($listKey, $ttl);
        } catch (\Throwable $e) {
            // Redis unavailable; skip live update
        }
    }

    public function getSessionLive(int $sessionId): ?array
    {
        try {
            $redis = Redis::connection();
            $raw = $redis->get(self::KEY_SESSION_PREFIX . $sessionId);
            if ($raw !== null) {
                $data = json_decode($raw, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        } catch (\Throwable $e) {
            // Fall through to DB fallback
        }

        $last = Tracking::where('session_id', $sessionId)
            ->orderByDesc('tracking_time')
            ->first();
        if ($last) {
            return [
                'session_id' => $last->session_id,
                'user_id' => $last->user_id,
                'device_id' => $last->device_id,
                'latitude' => (float) $last->latitude,
                'longitude' => (float) $last->longitude,
                'accuracy' => (float) $last->accuracy,
                'tracking_time' => $last->tracking_time?->toIso8601String(),
                'speed' => null,
                'updated_at' => $last->updated_at?->toIso8601String(),
                'from_db' => true,
            ];
        }

        return null;
    }

    public function getUserLive(int $userId): ?array
    {
        try {
            $redis = Redis::connection();
            $raw = $redis->get(self::KEY_USER_PREFIX . $userId);
            if ($raw !== null) {
                $data = json_decode($raw, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        } catch (\Throwable $e) {
            // Fall through to DB fallback
        }

        $last = Tracking::where('user_id', $userId)
            ->orderByDesc('tracking_time')
            ->first();
        if ($last) {
            return [
                'session_id' => $last->session_id,
                'user_id' => $last->user_id,
                'device_id' => $last->device_id,
                'latitude' => (float) $last->latitude,
                'longitude' => (float) $last->longitude,
                'accuracy' => (float) $last->accuracy,
                'tracking_time' => $last->tracking_time?->toIso8601String(),
                'speed' => null,
                'updated_at' => $last->updated_at?->toIso8601String(),
                'from_db' => true,
            ];
        }

        return null;
    }

    public function getSessionRecentPoints(int $sessionId, int $limit = 50): array
    {
        try {
            $redis = Redis::connection();
            $listKey = self::LIST_RECENT_PREFIX . $sessionId;
            $items = $redis->lrange($listKey, -$limit, -1);
            $out = [];
            foreach ($items as $item) {
                $decoded = json_decode($item, true);
                if (is_array($decoded)) {
                    $out[] = $decoded;
                }
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
