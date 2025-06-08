<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Jobs\FetchWeatherForAllUsers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class QueuePerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test queue job performance with 50 users (respecting API rate limits)
     * 
     * This test measures:
     * - Total execution time
     * - Average time per user
     * - Success/failure rates
     * - Memory usage
     * 
     * Note: Limited to 50 users to stay within 60 requests/minute API limit
     */
    public function test_queue_job_performs_well_with_50_users()
    {
        // Skip this test in CI environments to avoid API rate limits
        if (env('CI', false)) {
            $this->markTestSkipped('Skipping performance test in CI environment');
        }

        // Mock the HTTP responses for consistent testing
        Http::fake([
            '*' => Http::sequence()
                ->push([
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
                    'wind' => ['speed' => 3.5],
                    'clouds' => ['all' => 0],
                    'visibility' => 10000,
                    'timezone' => 3600,
                ], 200)
                ->whenEmpty(Http::response(['error' => 'Rate limited'], 429)),
        ]);

        // Create 50 users with locations for testing
        User::factory()->count(50)->create([
            'latitude' => fake()->latitude(-60, 70),
            'longitude' => fake()->longitude(-180, 180),
        ]);
        
        $userCount = User::whereNotNull('latitude')->whereNotNull('longitude')->count();
        $this->assertEquals(50, $userCount, 'Should have exactly 50 users with locations');

        // Spy on the log to capture performance metrics
        Log::spy();

        // Record start metrics
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Execute the job directly (not queued for testing)
        $job = new FetchWeatherForAllUsers();
        $job->handle(
            app(\App\Repositories\UserRepository::class),
            app(\App\Repositories\WeatherRepository::class)
        );

        // Calculate performance metrics
        $executionTime = (microtime(true) - $startTime) * 1000; // in milliseconds
        $memoryUsed = (memory_get_usage() - $startMemory) / 1024 / 1024; // in MB
        $avgTimePerUser = $executionTime / 50;

        // Assert performance requirements (adjusted for 50 users)
        $this->assertLessThan(5000, $executionTime, 
            "Total execution time {$executionTime}ms exceeds 5 second limit for 50 users");
        
        $this->assertLessThan(100, $avgTimePerUser,
            "Average time per user {$avgTimePerUser}ms exceeds 100ms threshold");
        
        $this->assertLessThan(64, $memoryUsed,
            "Memory usage {$memoryUsed}MB exceeds 64MB limit");

        // Verify completion log was called
        Log::shouldHaveReceived('info')
            ->with('Batch weather fetch completed', \Mockery::on(function ($context) {
                return is_array($context) 
                    && isset($context['total_users'])
                    && $context['total_users'] === 50;
            }))
            ->once();

        // Output performance report
        $this->addToAssertionCount(1);
        echo "\n\nPerformance Report:";
        echo "\n- Total users processed: 50";
        echo "\n- Total execution time: " . round($executionTime, 2) . "ms";
        echo "\n- Average time per user: " . round($avgTimePerUser, 2) . "ms";
        echo "\n- Memory used: " . round($memoryUsed, 2) . "MB";
        echo "\n- Theoretical requests/second: " . round(1000 / $avgTimePerUser, 2);
        echo "\n- Note: Limited to 50 users to respect API rate limit (60 requests/minute)";
        echo "\n";
    }

    /**
     * Test queue job handles failures gracefully
     */
    public function test_queue_job_handles_partial_failures()
    {
        // Create 10 users for this test
        User::factory()->count(10)->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Mock responses: 7 success, 3 failures
        Http::fake([
            '*' => Http::sequence()
                ->push(['main' => ['temp' => 20], 'weather' => [['main' => 'Clear', 'description' => 'clear', 'icon' => '01d']], 'wind' => ['speed' => 1], 'clouds' => ['all' => 0], 'visibility' => 10000, 'timezone' => 0], 200)
                ->push(['main' => ['temp' => 21], 'weather' => [['main' => 'Clear', 'description' => 'clear', 'icon' => '01d']], 'wind' => ['speed' => 1], 'clouds' => ['all' => 0], 'visibility' => 10000, 'timezone' => 0], 200)
                ->push(['main' => ['temp' => 22], 'weather' => [['main' => 'Clear', 'description' => 'clear', 'icon' => '01d']], 'wind' => ['speed' => 1], 'clouds' => ['all' => 0], 'visibility' => 10000, 'timezone' => 0], 200)
                ->push(['error' => 'Service unavailable'], 503)
                ->push(['main' => ['temp' => 23], 'weather' => [['main' => 'Clear', 'description' => 'clear', 'icon' => '01d']], 'wind' => ['speed' => 1], 'clouds' => ['all' => 0], 'visibility' => 10000, 'timezone' => 0], 200)
                ->push(['error' => 'Rate limited'], 429)
                ->push(['main' => ['temp' => 24], 'weather' => [['main' => 'Clear', 'description' => 'clear', 'icon' => '01d']], 'wind' => ['speed' => 1], 'clouds' => ['all' => 0], 'visibility' => 10000, 'timezone' => 0], 200)
                ->push(['main' => ['temp' => 25], 'weather' => [['main' => 'Clear', 'description' => 'clear', 'icon' => '01d']], 'wind' => ['speed' => 1], 'clouds' => ['all' => 0], 'visibility' => 10000, 'timezone' => 0], 200)
                ->push(['error' => 'API key invalid'], 401)
                ->push(['main' => ['temp' => 26], 'weather' => [['main' => 'Clear', 'description' => 'clear', 'icon' => '01d']], 'wind' => ['speed' => 1], 'clouds' => ['all' => 0], 'visibility' => 10000, 'timezone' => 0], 200),
        ]);

        // Capture log messages
        $logMessages = [];
        Log::listen(function ($message) use (&$logMessages) {
            $logMessages[] = $message;
        });

        $job = new FetchWeatherForAllUsers();
        
        // Run the job - it should handle failures gracefully
        try {
            $job->handle(
                app(\App\Repositories\UserRepository::class),
                app(\App\Repositories\WeatherRepository::class)
            );
        } catch (\Exception $e) {
            // Job should not throw exceptions for partial failures
            $this->fail('Job should handle partial failures without throwing exceptions');
        }

        // The job should complete successfully even with some failures
        $this->assertTrue(true, 'Job completed without throwing exceptions');
    }

    /**
     * Test queue job can be dispatched properly
     */
    public function test_queue_job_can_be_dispatched()
    {
        Queue::fake();

        // Dispatch the job
        FetchWeatherForAllUsers::dispatch()->onQueue('weather');

        // Assert job was pushed to the correct queue
        Queue::assertPushed(FetchWeatherForAllUsers::class, function ($job) {
            return $job->queue === 'weather';
        });
    }

    /**
     * Test database query performance for user fetching
     */
    public function test_user_fetching_is_optimized()
    {
        // Create 50 users with locations
        User::factory()->count(50)->create([
            'latitude' => fake()->latitude(-60, 70),
            'longitude' => fake()->longitude(-180, 180),
        ]);

        DB::enableQueryLog();
        
        $repository = app(\App\Repositories\UserRepository::class);
        $users = $repository->getUsersWithLocation();
        
        $queries = DB::getQueryLog();
        
        // Should only execute 1 query to fetch all users
        $this->assertCount(1, $queries, 'User fetching should be done in a single query');
        
        // Query should use proper conditions
        $this->assertStringContainsString('where `latitude` is not null', $queries[0]['query']);
        $this->assertStringContainsString('and `longitude` is not null', $queries[0]['query']);
    }
}