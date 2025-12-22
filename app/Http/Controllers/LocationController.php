<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\TrackingSession;
use Illuminate\Http\Request;

use function Symfony\Component\Clock\now;

class LocationController extends Controller
{

    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // meters

        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);
        $lon1 = deg2rad($lon1);
        $lon2 = deg2rad($lon2);

        return $earthRadius * acos(
            cos($lat1) * cos($lat2) * cos($lon2 - $lon1)
                + sin($lat1) * sin($lat2)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'device_id' => 'required|string',
            'session_id' => 'required|exists:tracking_sessions,id',
            'start_latitude' => 'required|numeric',
            'start_longitude' => 'required|numeric',
            'end_latitude' => 'required|numeric',
            'end_longitude' => 'required|numeric',
            'accuracy' => 'required|numeric',
            'interval_seconds' => 'nullable|numeric|min:1',
            'timestamp' => 'nullable|date',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['timestamp'] = $data['timestamp'] ?? now();
        $data['interval_seconds'] = $data['interval_seconds'] ?? 30;


        $distance = $this->calculateDistance(
            $data['start_latitude'],
            $data['start_longitude'],
            $data['end_latitude'],
            $data['end_longitude']
        );

        if ($distance < 3 || $data['accuracy'] > 1) {
            $distance = 0;
            $speed = 0;
        } else {

            $speed = round(($distance / $data['interval_seconds']) * 3.6, 2);
        }

        $data['distance'] = round($distance / 1000, 2);
        $data['speed'] = $speed;

        $location = Location::create($data);

        return response()->json($location, 201);
    }

    public function index()
    {
        $locations = Location::query()->get();

        return response()->json($locations, 200);
    }

    public function sessionLocations($sessionId)
    {
        return Location::where('session_id', $sessionId)
            ->orderBy('timestamp')
            ->get();
    }
}
