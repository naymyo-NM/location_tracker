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
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'distance',
        'timestamp',
    ];

    protected $casts = [

        'latitude' => 'float',
        'longitude' => 'float',

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
