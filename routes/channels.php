<?php

use App\Models\TrackingSession;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('session.{id}', function ($user, $id) {
    $session = TrackingSession::find($id);
    return $session && (int) $session->user_id === (int) $user->id;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
