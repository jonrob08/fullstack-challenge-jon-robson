<?php

namespace App\Repositories;

use App\Contracts\WeatherServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WeatherRepository
{
    protected WeatherServiceInterface $weatherService;

    public function __construct(WeatherServiceInterface $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Get weather for a specific user
     */
    public function getWeatherForUser(User $user): array
    {
        return $this->weatherService->getWeather($user->latitude, $user->longitude);
    }

    /**
     * Get weather for multiple users
     */
    public function getWeatherForUsers($users): array
    {
        $locations = $users->map(function ($user) {
            return [$user->latitude, $user->longitude];
        })->toArray();

        $weatherData = $this->weatherService->getMultipleWeather($locations);

        // Map weather data back to users
        $result = [];
        foreach ($users as $index => $user) {
            $result[] = [
                'user' => $user,
                'weather' => $weatherData[$index]['weather'] ?? null,
                'error' => $weatherData[$index]['error'] ?? null
            ];
        }

        return $result;
    }

    /**
     * Get cache key for a user's weather data
     */
    protected function getCacheKey(int $userId): string
    {
        $prefix = config('weather.cache.prefix', 'weather');
        return "{$prefix}.user.{$userId}";
    }

    /**
     * Clear weather cache for a user
     */
    public function clearCacheForUser(User $user): void
    {
        $cacheKey = $this->getCacheKey($user->id);

        if (config('weather.cache.enabled')) {
            Cache::forget($cacheKey);
        }
    }
}
