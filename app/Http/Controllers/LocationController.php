<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\TrackingSession;
use App\Services\TrackingLiveService;
use Illuminate\Http\Request;

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

    public function userLocationsHistory($userId)
    {
        return Location::where('user_id', $userId)
            ->orderBy('timestamp', 'desc')
            ->get();
    }

    public function sessionLive(Request $request, $id)
    {
        $session = TrackingSession::findOrFail($id);
        if ($session->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
        $live = app(TrackingLiveService::class)->getSessionLive((int) $id);
        if ($live === null) {
            return response()->json(['message' => 'No live position for this session.'], 404);
        }
        return response()->json($live);
    }

    public function userLive(Request $request, $id)
    {
        if ((int) $id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
        $live = app(TrackingLiveService::class)->getUserLive((int) $id);
        if ($live === null) {
            return response()->json(['message' => 'No live position for this user.'], 404);
        }
        return response()->json($live);
    }
}
