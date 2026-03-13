<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrackingSnappedPoints extends Model
{
    use HasFactory;

    protected  $table = "tracking_snapped_points";

    protected $fillable = [
            'tracking_id',
            'road_id',
            'road_type',
            'snapped_lat',
            'snapped_lon',
        ];

    protected $casts = [
        'snapped_lat' => 'float',
        'snapped_lon' => 'float',
    ];

    public function tracking()
    {
        return $this->belongsTo(Tracking::class, 'tracking_id');
    }

  
}
