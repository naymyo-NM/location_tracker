<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tracking extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $table = 'trackings';

    protected $fillable = [
        'user_id',
        'device_id',
        'session_id',
        'latitude',
        'longitude',
        'accuracy',
        'duration',
        'tracking_time',
    ];

    protected $casts = [
        'latitude'  => 'float',
        'longitude' => 'float',
        'tracking_time' => 'datetime',


    ];

    public function session()
    {
        return $this->belongsTo(TrackingSession::class, 'session_id');
    }


    public function locationsAsStart()
    {
        return $this->hasMany(Location::class, 'start_tracking_id');
    }

    public function locationsAsEnd()
    {
        return $this->hasMany(Location::class, 'end_tracking_id');
    }

    public function tracking_snapped_points()
    {
        return $this->hasMany(TrackingSnappedPoints::class, 'tracking_id');
    }
}
