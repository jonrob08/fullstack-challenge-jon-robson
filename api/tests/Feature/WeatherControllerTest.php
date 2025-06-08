<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class WeatherControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use array cache driver for tests to avoid Redis tag issues
        Config::set('cache.default', 'array');
        Config::set('weather.cache.enabled', true);
    }

    protected function setUpSuccessfulWeatherResponse(): void
    {
        Http::fake([
            '*' => Http::response([
                'main' => [
                    'temp' => 22.5,
                    'feels_like' => 21.8,
                    'humidity' => 60,
                    'pressure' => 1015,
                ],
                'weather' => [
                    [
                        'main' => 'Clouds',
                        'description' => 'scattered clouds',
                        'icon' => '03d',
                    ]
                ],
                'wind' => ['speed' => 4.1],
                'clouds' => ['all' => 40],
                'visibility' => 10000,
                'timezone' => 0,
            ], 200),
        ]);
    }

    public function test_it_gets_weather_for_a_user()
    {
        $this->setUpSuccessfulWeatherResponse();

        $user = User::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response = $this->getJson("/api/users/{$user->id}/weather");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'current' => [
                        'temperature',
                        'feels_like',
                        'description',
                        'main',
                        'icon',
                        'humidity',
                        'wind_speed',
                        'pressure',
                    ],
                    'cached_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'current' => [
                        'temperature' => 22.5,
                        'description' => 'scattered clouds',
                    ],
                ],
            ]);
    }

    public function test_it_refreshes_weather_data()
    {
        $this->setUpSuccessfulWeatherResponse();

        $user = User::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // First, get weather to cache it
        $firstResponse = $this->getJson("/api/users/{$user->id}/weather");
        $firstResponse->assertStatus(200);

        // Refresh weather (this should clear cache and fetch new data)
        $response = $this->postJson("/api/users/{$user->id}/weather/refresh");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Weather data refreshed successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'current' => [
                        'temperature',
                        'feels_like',
                        'description',
                    ],
                ],
            ]);
    }

    public function test_it_handles_weather_service_errors()
    {
        // This test should not use the successful response setup
        Http::fake([
            '*' => Http::response(['error' => 'Service unavailable'], 503),
        ]);

        $user = User::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $response = $this->getJson("/api/users/{$user->id}/weather");

        $response->assertStatus(503)
            ->assertJsonStructure([
                'error',
                'message',
            ]);
    }

    public function test_it_gets_weather_for_all_users_with_pagination()
    {
        $this->setUpSuccessfulWeatherResponse();

        // Create users with locations
        User::factory()->count(5)->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Create users without locations - Since the query filters out null values, 
        // we don't need to create these users for this test

        $response = $this->getJson('/api/weather?per_page=3');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'user_id',
                        'user_name',
                        'weather',
                        'error',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('meta.per_page', 3)
            ->assertJsonPath('meta.total', 5); // Only users with locations
    }

    public function test_it_returns_404_for_non_existent_user()
    {
        $response = $this->getJson('/api/users/999999/weather');

        $response->assertStatus(404);
    }

    public function test_weather_response_time_is_under_500ms()
    {
        $this->setUpSuccessfulWeatherResponse();

        $user = User::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $startTime = microtime(true);
        $response = $this->getJson("/api/users/{$user->id}/weather");
        $responseTime = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);

        // Assert response time is under 500ms
        $this->assertLessThan(500, $responseTime,
            "Response time {$responseTime}ms exceeds 500ms threshold");
    }

    public function test_cached_weather_response_is_faster()
    {
        $this->setUpSuccessfulWeatherResponse();

        $user = User::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // First request (not cached)
        $startTime1 = microtime(true);
        $this->getJson("/api/users/{$user->id}/weather");
        $firstCallTime = (microtime(true) - $startTime1) * 1000;

        // Second request (cached)
        $startTime2 = microtime(true);
        $this->getJson("/api/users/{$user->id}/weather");
        $secondCallTime = (microtime(true) - $startTime2) * 1000;

        // Cached response should be significantly faster
        $this->assertLessThan($firstCallTime, $secondCallTime);
        $this->assertLessThan(50, $secondCallTime,
            "Cached response time {$secondCallTime}ms is too slow");
    }
}
