<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class MapMatchingService
{
    public function snapPoints(array $points)
    {
       

        return Http::timeout(120)
            ->retry(3, 2000)
            ->post('https://map-matching-uu0f.onrender.com/batch_snap', [
                'points' => $points
            ])
            ->json();
        
    }
}