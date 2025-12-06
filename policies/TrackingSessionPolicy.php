<?php

namespace App\Policies;

use App\Models\TrackingSession;
use App\Models\User;

class TrackingSessionPolicy
{
    public function view(User $user, TrackingSession $session): bool
    {
        return $user->id === $session->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TrackingSession $session): bool
    {
        return $user->id === $session->user_id;
    }

    public function delete(User $user, TrackingSession $session): bool
    {
        return $user->id === $session->user_id;
    }
}
