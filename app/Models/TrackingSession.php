<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class TrackingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'started_at',
        'ended_at',
        'is_active',

    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_active' => 'boolean',
    ];




    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->hasMany(Location::class, 'session_id');
    }
}
