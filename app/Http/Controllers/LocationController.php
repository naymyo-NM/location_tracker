<?php

namespace App\Http\Controllers;

use App\Models\Location;

class LocationController extends Controller
{
    public function sessionLocations($sessionId)
    {
        return Location::where('session_id', $sessionId)
            ->with(['startTracking', 'endTracking'])
            ->orderBy('timestamp')
            ->get();
    }
}
