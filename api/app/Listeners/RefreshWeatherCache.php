<?php

namespace App\Listeners;

use App\Events\WeatherDataExpiring;
use App\Contracts\WeatherServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RefreshWeatherCache implements ShouldQueue
{
    use InteractsWithQueue;

    protected WeatherServiceInterface $weatherService;

    /**
     * Create the event listener.
     */
    public function __construct(WeatherServiceInterface $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Handle the event.
     */
    public function handle(WeatherDataExpiring $event): void
    {
        try {
            // Force cache refresh by clearing the specific cache entry first
            $cacheKey = sprintf('weather:%s:%s',
                round($event->latitude, 4),
                round($event->longitude, 4)
            );

            Cache::forget($cacheKey);

            // Prod: Would use cache tags for targeted cache invalidation
            // Cache::tags(['weather', "location:{$event->latitude},{$event->longitude}"])
            //     ->forget($cacheKey);

            // Fetch fresh data (will be cached)
            $this->weatherService->getWeather($event->latitude, $event->longitude);

            Log::info('Weather cache refreshed', [
                'user_id' => $event->userId,
                'latitude' => $event->latitude,
                'longitude' => $event->longitude
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to refresh weather cache', [
                'user_id' => $event->userId,
                'latitude' => $event->latitude,
                'longitude' => $event->longitude,
                'error' => $e->getMessage()
            ]);
        }
    }
}
