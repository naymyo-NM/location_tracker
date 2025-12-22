<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'user_id',
        'session_id',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'accuracy',
        'speed',
        'distance',
        'interval_seconds',
        'timestamp',
    ];

    protected $casts = [

        'start_latitude' => 'float',
        'start_longitude' => 'float',
        'end_latitude' => 'float',
        'end_longitude' => 'float',

    ];


    public function user()
    {
        $this->belongsTo(User::class);
    }

    public function session()
    {
        $this->belongsTo(TrackingSession::class, 'session_id');
    }
}
