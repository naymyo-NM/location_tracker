<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'location_id' => $this->id,
            'device_id' => $this->device_id,
            'session_id' => $this->session_id,
            // 'speed' => $this->speed,
            // 'distance' => $this->distance,
            // 'duration' => $this->duration,
            'timestamp' => $this->timestamp,
            'start_tracking' => new StartTrackingResource($this->whenLoaded('startTracking')),
            'end_tracking' => new EndTrackingResource($this->whenLoaded('endTracking')),

        ];
    }
}
