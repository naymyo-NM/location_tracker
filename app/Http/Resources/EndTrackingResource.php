<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EndTrackingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'end_tracking_id' => $this->id,
            'user_id' => $this->user_id,
            // 'accuracy' => $this->accuracy,
            // 'duration' => $this->duration,
            'tracking_time' => $this->tracking_time,
            'snapped_points' => TrackingSnapResource::collection($this->whenLoaded('tracking_snapped_points')),
        ];
    }
}
