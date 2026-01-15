<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Tracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function Symfony\Component\Clock\now;

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
        ]);

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
                'tracking_time' => now(),
            ]);

            $openLocation = Location::where('session_id', $request->session_id)->whereNull('end_tracking_id')->latest()->first();

            if (!$openLocation) {
                Location::create([
                    'device_id' => $tracking->device_id,
                    'user_id' => Auth::user()->id,
                    'session_id' => $tracking->session_id,
                    'start_tracking_id' => $tracking->id,
                    'speed' => 0,
                    'distance' => 0,
                    'duration' => 0,
                    'timestamp' => now()
                ]);
            } else {

                $startTracking = Tracking::find($openLocation->start_tracking_id);
                $endTracking   = $tracking;

                /** Distance (meters) */
                $distance = $this->calculateDistance(
                    $startTracking->latitude,
                    $startTracking->longitude,
                    $endTracking->latitude,
                    $endTracking->longitude
                );

                $durationSeconds = $startTracking->created_at->diffInSeconds($endTracking->created_at);


                /** Speed in km/h */
                $speed = $durationSeconds > 0
                    ? ($distance / 1000) / ($durationSeconds / 3600)
                    : 0;

                /** 1️⃣ Close previous location */
                $openLocation->update([
                    'end_tracking_id' => $tracking->id,
                    'distance'        => round($distance, 2),
                    'duration'        => $durationSeconds,
                    'speed'           => round($speed, 2),
                ]);

                Location::create([
                    'device_id' => $tracking->device_id,
                    'user_id' => Auth::user()->id,
                    'session_id' => $tracking->session_id,
                    'start_tracking_id' => $tracking->id,
                    'speed' => 0,
                    'distance' => 0,
                    'duration' => 0,
                    'timestamp' => now()
                ]);
            }
            DB::commit();

            return response()->json([
                'status' => 'success',
                'tracking' => $tracking,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
        }
    }
}
