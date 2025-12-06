<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TrackingSession;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = $request->user()->sessions()->orderBy('started_at', 'desc')->get();

        return response()->json($sessions);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:225',
        ]);

        $session = $request->user()->sessions()->create([
            'name' => $request->name,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Session created successfully',
            'session' => $session,
        ], 201);
    }



    public function update(Request $request, TrackingSession $session)
    {
        if ($request->user()->id !== $session->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'ended_at' => 'sometimes|nullable|date',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->has('ended_at') && $request->ended_at !== null) {
            $validated['is_active'] = false;
        }

        if ($request->has('is_active') && $request->is_active === true) {
            $validated['ended_at'] = null;
        }

        $session->update($validated);

        return response()->json([
            'message' => 'Session updated successfully',
            'session' => $session,
        ]);
    }

    public function destroy(TrackingSession $session)
    {
        $session->delete();

        return response()->json([
            'message' => 'Session deleted successfully'
        ]);
    }
}
