<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WeatherDataExpiring
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public float $latitude;
    public float $longitude;
    public int $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(float $latitude, float $longitude, int $userId)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->userId = $userId;
    }
}