<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\TrackingSession;
use Illuminate\Http\Request;

use function Symfony\Component\Clock\now;

class LocationController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'device_id' => 'required|string',
            'session_id' => 'required|exists:tracking_sessions,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'required|numeric',
            'altitude' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'distance' => 'nullable|numeric',
            'timestamp' => 'nullable|date',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['timestamp'] = $data['timestamp'] ?? now();

        $location = Location::create($data);

        return response()->json($location, 201);
    }

    public function sessionLocations($sessionId)
    {
        return Location::where('session_id', $sessionId)
            ->orderBy('timestamp')
            ->get();
    }
}
