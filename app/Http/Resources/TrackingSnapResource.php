<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingSnapResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'snap_id' => $this->id,
            'snapped_lat' => $this->snapped_lat,
            'snapped_lon' => $this->snapped_lon,
        ];
    }
}
