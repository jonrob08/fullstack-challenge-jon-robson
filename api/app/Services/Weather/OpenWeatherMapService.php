<?php

namespace App\Services\Weather;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class OpenWeatherMapService extends AbstractWeatherService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('weather.services.openweathermap.api_key');
        $this->baseUrl = config('weather.services.openweathermap.base_url');
        $this->timeout = config('weather.services.openweathermap.timeout', 5);
    }

    /**
     * Fetch weather data from OpenWeatherMap API
     */
    protected function fetchWeatherData(float $latitude, float $longitude): array
    {
        $url = $this->baseUrl . '/weather';  // Changed from /onecall to /weather

        try {
            $response = Http::timeout($this->timeout)
                ->get($url, [
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'appid' => $this->apiKey,
                    'units' => 'metric'
                ]);

            if (!$response->successful()) {
                throw new RequestException($response);
            }

            $data = $response->json();

            return $this->transformResponse($data);
        } catch (\Exception $e) {
            Log::error('OpenWeatherMap API error', [
                'error' => $e->getMessage(),
                'latitude' => $latitude,
                'longitude' => $longitude
            ]);

            throw new \Exception('Weather service unavailable: ' . $e->getMessage());
        }
    }

    /**
     * Transform OpenWeatherMap response to our format
     *
     * TODO: Refactor to return WeatherData DTO instead of array
     *       Need to update AbstractWeatherService to work with DTOs
     */
    protected function transformResponse(array $data): array
    {
        $main = $data['main'] ?? [];
        $weather = $data['weather'][0] ?? [];
        $wind = $data['wind'] ?? [];
        $rain = $data['rain'] ?? [];
        $snow = $data['snow'] ?? [];
        $clouds = $data['clouds'] ?? [];

        return [
            'temperature' => round($main['temp'] ?? 0, 1),
            'feels_like' => round($main['feels_like'] ?? 0, 1),
            'description' => $weather['description'] ?? 'Unknown',
            'main' => $weather['main'] ?? 'Unknown',
            'icon' => $weather['icon'] ?? '01d',
            'humidity' => $main['humidity'] ?? 0,
            'wind_speed' => round($wind['speed'] ?? 0, 1),
            'pressure' => $main['pressure'] ?? 0,
            'rain' => $rain['1h'] ?? null,
            'snow' => $snow['1h'] ?? null,
            'clouds' => $clouds['all'] ?? 0,
            'visibility' => $data['visibility'] ?? 10000,
            'uvi' => null,
            'timezone' => $data['timezone'] ?? 0,
            'timezone_offset' => $data['timezone'] ?? 0,
        ];
    }

    /**
     * Check if the service is available
     */
    public function isAvailable(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        try {
            $response = Http::timeout(2)
                ->get($this->baseUrl . '/weather', [
                    'q' => 'London',
                    'appid' => $this->apiKey
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
