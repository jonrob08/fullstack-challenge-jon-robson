<?php

namespace App\Services\Weather;

use App\Contracts\WeatherServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

abstract class AbstractWeatherService implements WeatherServiceInterface
{
    protected int $cacheTtl = 3600; // 1 hour
    protected string $cachePrefix = 'weather';

    /**
     * Get weather with caching
     */
    public function getWeather(float $latitude, float $longitude): array
    {
        $cacheKey = $this->getCacheKey($latitude, $longitude);

        // Using standard cache in tests
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude) {
            try {
                $startTime = microtime(true);
                $weather = $this->fetchWeatherData($latitude, $longitude);
                $responseTime = (microtime(true) - $startTime) * 1000;

                // Log response time for monitoring
                // TODO: Fix storage permissions in Docker
                // Log::info('Weather API response time', [
                //     'service' => static::class,
                //     'latitude' => $latitude,
                //     'longitude' => $longitude,
                //     'response_time_ms' => $responseTime
                // ]);

                return array_merge($weather, [
                    'cached_at' => now()->toIso8601String(),
                    'response_time_ms' => $responseTime
                ]);
            } catch (\Exception $e) {
                // TODO: Fix storage permissions in Docker
                // Log::error('Weather API error', [
                //     'service' => static::class,
                //     'error' => $e->getMessage(),
                //     'latitude' => $latitude,
                //     'longitude' => $longitude
                // ]);

                throw $e;
            }
        });

        // Prod code will utilize Redis cache tags for better cache management
        /*
        return Cache::tags(['weather', "location:{$latitude},{$longitude}"])
            ->remember($cacheKey, $this->cacheTtl, function () use ($latitude, $longitude) {
                // ...
            });
        */
    }

    /**
     * Get weather for multiple locations
     */
    public function getMultipleWeather(array $locations): array
    {
        $results = [];

        foreach ($locations as $location) {
            try {
                $results[] = [
                    'latitude' => $location[0],
                    'longitude' => $location[1],
                    'weather' => $this->getWeather($location[0], $location[1]),
                    'error' => null
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'latitude' => $location[0],
                    'longitude' => $location[1],
                    'weather' => null,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(float $latitude, float $longitude): string
    {
        return sprintf('%s:%s:%s', $this->cachePrefix, round($latitude, 4), round($longitude, 4));
    }

    /**
     * Abstract method to fetch weather data from the API
     */
    abstract protected function fetchWeatherData(float $latitude, float $longitude): array;
}
