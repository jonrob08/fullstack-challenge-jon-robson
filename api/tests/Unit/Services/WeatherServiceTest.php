<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Weather\OpenWeatherMapService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class WeatherServiceTest extends TestCase
{
    protected OpenWeatherMapService $weatherService;

    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('weather.services.openweathermap.api_key', 'test-api-key');
        Config::set('weather.services.openweathermap.base_url', 'https://api.openweathermap.org/data/2.5');
        Config::set('weather.services.openweathermap.timeout', 5);
        Config::set('cache.default', 'array');
        Config::set('weather.cache.enabled', true);
        
        $this->weatherService = new OpenWeatherMapService();
    }

    public function test_it_fetches_weather_data_successfully()
    {
        $mockResponse = [
            'main' => [
                'temp' => 20.5,
                'feels_like' => 19.8,
                'humidity' => 65,
                'pressure' => 1013,
            ],
            'weather' => [
                [
                    'main' => 'Clear',
                    'description' => 'clear sky',
                    'icon' => '01d',
                ]
            ],
            'wind' => [
                'speed' => 3.5,
            ],
            'clouds' => [
                'all' => 0,
            ],
            'visibility' => 10000,
            'timezone' => 3600,
        ];

        Http::fake([
            '*' => Http::response($mockResponse, 200),
        ]);

        $result = $this->weatherService->getWeather(40.7128, -74.0060);

        $this->assertEquals(20.5, $result['temperature']);
        $this->assertEquals(19.8, $result['feels_like']);
        $this->assertEquals('clear sky', $result['description']);
        $this->assertEquals('Clear', $result['main']);
        $this->assertEquals('01d', $result['icon']);
        $this->assertEquals(65, $result['humidity']);
        $this->assertEquals(3.5, $result['wind_speed']);
        $this->assertEquals(1013, $result['pressure']);
    }

    public function test_it_caches_weather_data()
    {
        Cache::flush();
        
        $mockResponse = [
            'main' => ['temp' => 20.5, 'feels_like' => 19.8, 'humidity' => 65, 'pressure' => 1013],
            'weather' => [['main' => 'Clear', 'description' => 'clear sky', 'icon' => '01d']],
            'wind' => ['speed' => 3.5],
            'clouds' => ['all' => 0],
            'visibility' => 10000,
            'timezone' => 3600,
        ];

        Http::fake([
            '*' => Http::response($mockResponse, 200),
        ]);

        // First call - should hit the API
        $result1 = $this->weatherService->getWeather(40.7128, -74.0060);
        
        // Second call - should use cache
        $result2 = $this->weatherService->getWeather(40.7128, -74.0060);
        
        // Results should be identical
        $this->assertEquals($result1['temperature'], $result2['temperature']);
        
        // Verify only one HTTP call was made
        Http::assertSentCount(1);
    }

    public function test_it_handles_api_errors_gracefully()
    {
        Http::fake([
            '*' => Http::response(['error' => 'API key invalid'], 401),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Weather service unavailable/');

        $this->weatherService->getWeather(40.7128, -74.0060);
    }

    public function test_it_checks_service_availability()
    {
        Http::fake([
            '*' => Http::response(['data' => 'ok'], 200),
        ]);

        $isAvailable = $this->weatherService->isAvailable();
        
        $this->assertTrue($isAvailable);
    }

    public function test_it_returns_false_when_api_key_is_missing()
    {
        Config::set('weather.services.openweathermap.api_key', '');
        
        $service = new OpenWeatherMapService();
        $isAvailable = $service->isAvailable();
        
        $this->assertFalse($isAvailable);
    }

    public function test_it_handles_multiple_weather_requests()
    {
        $mockResponse = [
            'main' => ['temp' => 20.5, 'feels_like' => 19.8, 'humidity' => 65, 'pressure' => 1013],
            'weather' => [['main' => 'Clear', 'description' => 'clear sky', 'icon' => '01d']],
            'wind' => ['speed' => 3.5],
            'clouds' => ['all' => 0],
            'visibility' => 10000,
            'timezone' => 3600,
        ];

        Http::fake([
            '*' => Http::response($mockResponse, 200),
        ]);

        $locations = [
            [40.7128, -74.0060],
            [51.5074, -0.1278],
            [35.6762, 139.6503],
        ];

        $results = $this->weatherService->getMultipleWeather($locations);
        
        $this->assertCount(3, $results);
        $this->assertNotNull($results[0]['weather']);
        $this->assertNull($results[0]['error']);
        $this->assertEquals(40.7128, $results[0]['latitude']);
        $this->assertEquals(-74.0060, $results[0]['longitude']);
    }
}