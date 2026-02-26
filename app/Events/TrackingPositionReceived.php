<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrackingPositionReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $sessionId,
        public int $userId,
        public string $deviceId,
        public float $latitude,
        public float $longitude,
        public float $accuracy,
        public string $trackingTime,
        public ?float $speed = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('session.' . $this->sessionId),
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'tracking.position';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'accuracy' => $this->accuracy,
            'tracking_time' => $this->trackingTime,
            'speed' => $this->speed,
        ];
    }
}
