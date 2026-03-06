<?php

namespace App\Http\Controllers;

use App\Events\TrackingPositionReceived;
use App\Models\Location;
use App\Models\Tracking;
use App\Models\TrackingSession;
use App\Services\TrackingBatchService;
use App\Services\TrackingLiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TrackingController extends Controller
{
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo   = deg2rad($lat2);
        $lonTo   = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) *
                pow(sin($lonDelta / 2), 2)
        ));

        return $angle * $earthRadius; // meters
    }

    // NOT USED — single-point endpoint removed from routes.
    // Route was: POST /api/trackings
    // Kept for reference only.
    /*
    public function storeTracking(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'session_id' => 'required|exists:tracking_sessions,id',
            'latitude'   => 'required|numeric|between:-90,90',
            'longitude'  => 'required|numeric|between:-180,180',
            'accuracy'   => 'required|numeric|min:0',
            'duration'   => 'required|numeric|min:0',
            'tracking_time' => 'nullable|date',
            'speed' => 'nullable|numeric|min:0',
            'last_lat' => 'nullable|numeric|between:-90,90',
            'last_lng' => 'nullable|numeric|between:-180,180',
            'last_timestamp' => 'nullable|date',
            'idempotency_key' => 'nullable|string|max:255',
        ]);

        $accuracy = (float) $request->accuracy;
        if ($accuracy > config('tracking.max_accuracy_meters')) {
            return response()->json([
                'message' => 'Accuracy too low. Maximum allowed accuracy is ' . config('tracking.max_accuracy_meters') . ' meters.',
            ], 422);
        }

        if ($request->filled('last_lat') && $request->filled('last_lng') && $request->filled('last_timestamp')) {
            $distance = $this->calculateDistance(
                (float) $request->last_lat,
                (float) $request->last_lng,
                (float) $request->latitude,
                (float) $request->longitude
            );
            if ($distance < config('tracking.min_movement_meters')) {
                return response()->json([
                    'message' => 'Movement below minimum (' . config('tracking.min_movement_meters') . ' m).',
                ], 422);
            }

            $speedKmh = $request->filled('speed') ? (float) $request->speed : null;
            if ($speedKmh !== null) {
                $maxAtLowSpeed = config('tracking.jump_max_displacement_at_low_speed_m');
                $lowSpeedKmh = config('tracking.jump_low_speed_kmh');
                if ($distance > $maxAtLowSpeed && $speedKmh < $lowSpeedKmh) {
                    return response()->json([
                        'message' => 'GPS jump detected: large displacement with low reported speed.',
                    ], 422);
                }
                $lastTs = \Carbon\Carbon::parse($request->last_timestamp);
                $nowTs = $request->filled('tracking_time')
                    ? \Carbon\Carbon::parse($request->tracking_time)
                    : now();
                $seconds = $lastTs->diffInSeconds($nowTs);
                if ($seconds > 0) {
                    $expectedMeters = ($speedKmh / 3.6) * $seconds;
                    $ratio = config('tracking.jump_displacement_vs_expected_ratio');
                    if ($expectedMeters > 0 && $distance > $ratio * $expectedMeters) {
                        return response()->json([
                            'message' => 'GPS jump detected: displacement inconsistent with speed.',
                        ], 422);
                    }
                }
            }
        }

        $idempotencyKey = $request->header('Idempotency-Key') ?? $request->input('idempotency_key');
        if ($idempotencyKey) {
            $cacheKey = 'tracking:idempotency:' . hash('sha256', $idempotencyKey);
            $ttlSeconds = config('tracking.idempotency_ttl_hours', 48) * 3600;
            $existing = Cache::get($cacheKey);
            if ($existing !== null) {
                return response()->json([
                    'status' => 'success',
                    'tracking' => $existing,
                    'idempotent_replay' => true,
                ], 200);
            }
        }

        $useBatch = config('tracking.use_batch_write', true);

        if ($useBatch) {
            try {
                app(TrackingLiveService::class)->setLivePosition(
                    (int) $request->session_id,
                    (int) Auth::user()->id,
                    $request->device_id,
                    (float) $request->latitude,
                    (float) $request->longitude,
                    (float) $request->accuracy,
                    $request->filled('tracking_time') ? $request->tracking_time : now()
                );
            } catch (\Throwable $e) {
                // Non-fatal
            }
            $trackingTime = $request->filled('tracking_time') ? $request->tracking_time : now()->toIso8601String();
            try {
                event(new TrackingPositionReceived(
                    (int) $request->session_id,
                    (int) Auth::user()->id,
                    $request->device_id,
                    (float) $request->latitude,
                    (float) $request->longitude,
                    (float) $request->accuracy,
                    is_string($trackingTime) ? $trackingTime : \Carbon\Carbon::parse($trackingTime)->toIso8601String(),
                    $request->filled('speed') ? (float) $request->speed : null
                ));
            } catch (\Throwable $e) {
                // Non-fatal
            }
            $payload = [
                'session_id' => $request->session_id,
                'user_id' => Auth::user()->id,
                'device_id' => $request->device_id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy,
                'duration' => $request->duration,
                'tracking_time' => $request->filled('tracking_time') ? $request->tracking_time : now()->toIso8601String(),
            ];
            if ($idempotencyKey) {
                $payload['idempotency_key'] = $idempotencyKey;
            }
            app(TrackingBatchService::class)->pushPoint($payload);
            if ($idempotencyKey) {
                $cacheKey = 'tracking:idempotency:' . hash('sha256', $idempotencyKey);
                $ttlSeconds = config('tracking.idempotency_ttl_hours', 48) * 3600;
                Cache::put($cacheKey, ['status' => 'accepted', 'message' => 'Point queued for persistence'], $ttlSeconds);
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Tracking point queued for persistence.',
                'tracking' => (object) $payload,
            ], 201);
        }

        DB::beginTransaction();
        try {
            $tracking = Tracking::create([
                'user_id' => Auth::user()->id,
                'device_id' => $request->device_id,
                'session_id' => $request->session_id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'accuracy' => $request->accuracy,
                'duration' => $request->duration,
                'tracking_time' => $request->filled('tracking_time') ? $request->tracking_time : now(),
            ]);

            $openLocation = Location::where('session_id', $request->session_id)->whereNull('end_tracking_id')->latest()->first();

            if (! $openLocation) {
                Location::create([
                    'device_id' => $tracking->device_id,
                    'user_id' => Auth::user()->id,
                    'session_id' => $tracking->session_id,
                    'start_tracking_id' => $tracking->id,
                    'speed' => 0,
                    'distance' => 0,
                    'duration' => 0,
                    'timestamp' => now(),
                ]);
            } else {
                $startTracking = Tracking::find($openLocation->start_tracking_id);
                $endTracking = $tracking;
                $distance = $this->calculateDistance(
                    $startTracking->latitude,
                    $startTracking->longitude,
                    $endTracking->latitude,
                    $endTracking->longitude
                );
                $durationSeconds = $startTracking->created_at->diffInSeconds($endTracking->created_at);
                $speed = $durationSeconds > 0 ? ($distance / 1000) / ($durationSeconds / 3600) : 0;
                $openLocation->update([
                    'end_tracking_id' => $tracking->id,
                    'distance' => round($distance, 2),
                    'duration' => $durationSeconds,
                    'speed' => round($speed, 2),
                ]);
                Location::create([
                    'device_id' => $tracking->device_id,
                    'user_id' => Auth::user()->id,
                    'session_id' => $tracking->session_id,
                    'start_tracking_id' => $tracking->id,
                    'speed' => 0,
                    'distance' => 0,
                    'duration' => 0,
                    'timestamp' => now(),
                ]);
            }
            DB::commit();

            if ($idempotencyKey ?? false) {
                $cacheKey = 'tracking:idempotency:' . hash('sha256', $idempotencyKey);
                $ttlSeconds = config('tracking.idempotency_ttl_hours', 48) * 3600;
                Cache::put($cacheKey, $tracking->fresh(), $ttlSeconds);
            }

            try {
                app(TrackingLiveService::class)->setLivePosition(
                    (int) $tracking->session_id,
                    (int) $tracking->user_id,
                    $tracking->device_id,
                    (float) $tracking->latitude,
                    (float) $tracking->longitude,
                    (float) $tracking->accuracy,
                    $tracking->tracking_time
                );
            } catch (\Throwable $e) {
                // Non-fatal
            }
            try {
                event(new TrackingPositionReceived(
                    (int) $tracking->session_id,
                    (int) $tracking->user_id,
                    $tracking->device_id,
                    (float) $tracking->latitude,
                    (float) $tracking->longitude,
                    (float) $tracking->accuracy,
                    $tracking->tracking_time?->toIso8601String() ?? now()->toIso8601String(),
                    null
                ));
            } catch (\Throwable $e) {
                // Non-fatal
            }

            return response()->json([
                'status' => 'success',
                'tracking' => $tracking,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to store tracking.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    */ // end storeTracking

    public function storeBatch(Request $request)
    {
        $request->validate([
            'points' => 'required|array',
            'points.*.device_id' => 'required|string',
            'points.*.session_id' => 'required|exists:tracking_sessions,id',
            'points.*.latitude' => 'required|numeric|between:-90,90',
            'points.*.longitude' => 'required|numeric|between:-180,180',
            'points.*.accuracy' => 'required|numeric|min:0',
            'points.*.duration' => 'required|numeric|min:0',
            'points.*.tracking_time' => 'nullable|date',
            'points.*.speed' => 'nullable|numeric|min:0',
            'points.*.last_lat' => 'nullable|numeric|between:-90,90',
            'points.*.last_lng' => 'nullable|numeric|between:-180,180',
            'points.*.last_timestamp' => 'nullable|date',
            'points.*.idempotency_key' => 'nullable|string|max:255',
        ]);

        $userId = Auth::id();
        $liveService = app(TrackingLiveService::class);
        $batchService = app(TrackingBatchService::class);
        $accepted = 0;
        $failed = [];
        $fallbackPayloads = [];

        foreach ($request->points as $index => $p) {
            $sessionId = (int) $p['session_id'];
            $session = TrackingSession::find($sessionId);
            if (! $session || (int) $session->user_id !== $userId) {
                $failed[] = ['index' => $index, 'reason' => 'session not found or unauthorized'];
                continue;
            }

            $accuracy = (float) ($p['accuracy'] ?? 0);
            if ($accuracy > config('tracking.max_accuracy_meters')) {
                $failed[] = ['index' => $index, 'reason' => 'accuracy too low'];
                continue;
            }

            if (! empty($p['last_lat']) && ! empty($p['last_lng']) && ! empty($p['last_timestamp'])) {
                $distance = $this->calculateDistance(
                    (float) $p['last_lat'],
                    (float) $p['last_lng'],
                    (float) $p['latitude'],
                    (float) $p['longitude']
                );
                if ($distance < config('tracking.min_movement_meters')) {
                    $failed[] = ['index' => $index, 'reason' => 'movement below minimum'];
                    continue;
                }
                $speedKmh = isset($p['speed']) ? (float) $p['speed'] : null;
                if ($speedKmh !== null) {
                    $maxAtLowSpeed = config('tracking.jump_max_displacement_at_low_speed_m');
                    $lowSpeedKmh = config('tracking.jump_low_speed_kmh');
                    if ($distance > $maxAtLowSpeed && $speedKmh < $lowSpeedKmh) {
                        $failed[] = ['index' => $index, 'reason' => 'GPS jump detected'];
                        continue;
                    }
                    $lastTs = \Carbon\Carbon::parse($p['last_timestamp']);
                    $nowTs = ! empty($p['tracking_time']) ? \Carbon\Carbon::parse($p['tracking_time']) : now();
                    $seconds = $lastTs->diffInSeconds($nowTs);
                    if ($seconds > 0) {
                        $expectedMeters = ($speedKmh / 3.6) * $seconds;
                        $ratio = config('tracking.jump_displacement_vs_expected_ratio');
                        if ($expectedMeters > 0 && $distance > $ratio * $expectedMeters) {
                            $failed[] = ['index' => $index, 'reason' => 'GPS jump detected'];
                            continue;
                        }
                    }
                }
            }

            $idempotencyKey = $p['idempotency_key'] ?? null;
            if ($idempotencyKey) {
                $cacheKey = 'tracking:idempotency:' . hash('sha256', $idempotencyKey);
                $ttlSeconds = config('tracking.idempotency_ttl_hours', 48) * 3600;
                if (Cache::get($cacheKey) !== null) {
                    $accepted++;
                    continue;
                }
            }

            $trackingTime = ! empty($p['tracking_time']) ? $p['tracking_time'] : now()->toIso8601String();
            $payload = [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'device_id' => $p['device_id'],
                'latitude' => $p['latitude'],
                'longitude' => $p['longitude'],
                'accuracy' => $p['accuracy'],
                'duration' => $p['duration'],
                'tracking_time' => is_string($trackingTime) ? $trackingTime : \Carbon\Carbon::parse($trackingTime)->toIso8601String(),
            ];
            if ($idempotencyKey) {
                $payload['idempotency_key'] = $idempotencyKey;
            }
            try {
                $batchService->pushPoint($payload);
            } catch (\Throwable $e) {
                $fallbackPayloads[] = $payload;
            }

            try {
                $liveService->setLivePosition(
                    $sessionId,
                    $userId,
                    $p['device_id'],
                    (float) $p['latitude'],
                    (float) $p['longitude'],
                    (float) $p['accuracy'],
                    $trackingTime,
                    isset($p['speed']) ? (float) $p['speed'] : null
                );
            } catch (\Throwable $e) {
                // Non-fatal
            }
            try {
                event(new TrackingPositionReceived(
                    $sessionId,
                    $userId,
                    $p['device_id'],
                    (float) $p['latitude'],
                    (float) $p['longitude'],
                    (float) $p['accuracy'],
                    is_string($trackingTime) ? $trackingTime : \Carbon\Carbon::parse($trackingTime)->toIso8601String(),
                    isset($p['speed']) ? (float) $p['speed'] : null
                ));
            } catch (\Throwable $e) {
                // Non-fatal
            }

            if ($idempotencyKey) {
                $cacheKey = 'tracking:idempotency:' . hash('sha256', $idempotencyKey);
                $ttlSeconds = config('tracking.idempotency_ttl_hours', 48) * 3600;
                Cache::put($cacheKey, ['status' => 'accepted', 'message' => 'Point queued'], $ttlSeconds);
            }
            $accepted++;
        }

        if (! empty($fallbackPayloads)) {
            try {
                $batchService->writePointsDirect($fallbackPayloads);
            } catch (\Throwable $e) {
                // Log but do not fail the request; points were already counted as accepted
            }
        }

        return response()->json([
            'status' => 'success',
            'accepted' => $accepted,
            'failed' => $failed,
        ], 201);
    }

    // NOT USED — raw points endpoint removed from routes.
    // Route was: GET /api/sessions/{id}/points
    // Kept for reference only.
    /*
    public function sessionPoints(Request $request, int $sessionId)
    {
        $session = TrackingSession::findOrFail($sessionId);
        if ($session->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $points = Tracking::where('session_id', $sessionId)
            ->orderBy('tracking_time')
            ->get([
                'id',
                'session_id',
                'latitude',
                'longitude',
                'accuracy',
                'duration',
                'tracking_time',
            ]);

        return response()->json($points);
    }
    */ // end sessionPoints
}
