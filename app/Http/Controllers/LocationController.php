<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Http\Resources\LocationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{

    // public function history(Request $request)
    // {
    //     $query = Location::query();


    //     if ($request->filled('user_id')) {
    //         $query->where('user_id', $request->user_id);
    //     }


    //     if ($request->filled('session_id')) {
    //         $query->where('session_id', $request->session_id);
    //     }


    //     $locations = $query->with([
    //         'startTracking.tracking_snapped_points',
    //         'endTracking.tracking_snapped_points'
    //     ])->get();

    //     return LocationResource::collection($locations);
    // }
    public function history(Request $request, $id)
    {
        return LocationResource::collection(
            Location::where('session_id', $id)
                ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
                ->with([
                    'startTracking.tracking_snapped_points',
                    'endTracking.tracking_snapped_points'
                ])
                ->get()
        );
    }
}
