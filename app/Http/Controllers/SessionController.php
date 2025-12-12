<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TrackingSession;
use Illuminate\Http\Request;

class SessionController extends Controller
{

    public function index(Request $request)
    {
        return TrackingSession::where('user_id', $request->user()->id)
            ->orderByDesc('started_at')
            ->get();
    }

    public function start(Request $request)
    {
        $session = TrackingSession::create([
            'user_id' => $request->user()->id,
            'name'    => $request->name ?? 'New Session',
            'started_at' => now(),
            'is_active'  => true,
        ]);

        return response()->json($session, 201);
    }

    public function stop($id)
    {
        $session = TrackingSession::findOrFail($id);

        $session->update([
            'is_active' => false,
            'ended_at' => now(),
        ]);

        return response()->json(['message' => 'Session stopped']);
    }
}
