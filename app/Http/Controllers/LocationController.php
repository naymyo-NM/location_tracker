<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Tracking;

use Illuminate\Http\Request;

use function Symfony\Component\Clock\now;

class LocationController extends Controller
{



    public function index()
    {
        $location = Location::query()->get();

        return response()->json($location, 200);
    }

    public function sessionLocations($sessionId)
    {
        return Location::where('session_id', $sessionId)
            ->orderBy('timestamp')
            ->get();
    }
}
