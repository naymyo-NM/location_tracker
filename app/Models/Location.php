<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    // protected $table = 'locations';

    protected $fillable = [
        'device_id',
        'user_id',
        'session_id',
        'start_tracking_id',
        'end_tracking_id',
        'speed',
        'distance',
        'duration',
        'timestamp',
    ];

    protected $casts = [

        'timestamp' => 'datetime',


    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function session()
    {
        return $this->belongsTo(TrackingSession::class, 'session_id');
    }
    public function startTracking()
    {
        return $this->belongsTo(Tracking::class, 'start_tracking_id');
    }

    public function endTracking()
    {
        return $this->belongsTo(Tracking::class, 'end_tracking_id');
    }
}
