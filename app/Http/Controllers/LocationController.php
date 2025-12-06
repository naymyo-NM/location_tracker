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
        $request->validate([
            'device_id' => 'required|string',
            'session_id' => 'required|exists:tracking_sessions,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'timestamp' => 'nullable|date',

        ]);



        $location = $request->user()->locations()->create([
            'device_id' => $request->device_id,
            'session_id' => $request->session_id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'speed' => $request->speed,
            'timestamp' => $request->timestamp ?? now(),
        ]);

        return response()->json([
            'message' => 'Location saved successfully',
            'location' => $location,
        ], 201);
    }

    public function index(Request $request)
    {
        $locations = $request->user()->locations()->get();

        return response()->json($locations);
    }
}
