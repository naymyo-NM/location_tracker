<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GPS validation
    |--------------------------------------------------------------------------
    */

    'max_accuracy_meters' => (float) env('TRACKING_MAX_ACCURACY_METERS', 20),

    'min_movement_meters' => (float) env('TRACKING_MIN_MOVEMENT_METERS', 5),

    /*
    |--------------------------------------------------------------------------
    | Jump detection (GPS jump vs real movement)
    |--------------------------------------------------------------------------
    */

    'jump_max_displacement_at_low_speed_m' => (float) env('TRACKING_JUMP_MAX_DISPLACEMENT_M', 15),

    'jump_low_speed_kmh' => (float) env('TRACKING_JUMP_LOW_SPEED_KMH', 5),

    'jump_displacement_vs_expected_ratio' => (float) env('TRACKING_JUMP_DISPLACEMENT_RATIO', 2.5),

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    */

    'idempotency_ttl_hours' => (int) env('TRACKING_IDEMPOTENCY_TTL_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Live position (Redis)
    |--------------------------------------------------------------------------
    */

    'live_ttl_seconds' => (int) env('TRACKING_LIVE_TTL_SECONDS', 600),

    'live_recent_points_max' => (int) env('TRACKING_LIVE_RECENT_POINTS_MAX', 100),

    /*
    |--------------------------------------------------------------------------
    | Batch / queue
    |--------------------------------------------------------------------------
    | When use_batch_write is true, points are pushed to Redis and written to
    | MySQL by the scheduled ProcessTrackingBatchJob. When false, each request
    | writes to MySQL immediately (legacy behaviour).
    */

    'use_batch_write' => env('TRACKING_USE_BATCH_WRITE', true),

    'batch_window_seconds' => (int) env('TRACKING_BATCH_WINDOW_SECONDS', 15),

    'batch_max_points' => (int) env('TRACKING_BATCH_MAX_POINTS', 100),

    'batch_redis_list_key' => env('TRACKING_BATCH_REDIS_LIST_KEY', 'tracking:batch:pending'),

];
