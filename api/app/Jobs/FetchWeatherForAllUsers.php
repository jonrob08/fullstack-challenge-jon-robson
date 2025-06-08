<?php

namespace App\Jobs;

use App\Repositories\UserRepository;
use App\Repositories\WeatherRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FetchWeatherForAllUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * Execute the job.
     */
    public function handle(UserRepository $userRepository, WeatherRepository $weatherRepository): void
    {
        $startTime = microtime(true);
        
        Log::info('Starting batch weather fetch for all users');
        
        // Get all users with location data
        $users = $userRepository->getUsersWithLocation();
        
        if ($users->isEmpty()) {
            Log::info('No users with location data found');
            return;
        }
        
        Log::info("Fetching weather for {$users->count()} users");
        
        // Prepare locations for batch processing
        $locations = $users->map(function ($user) {
            return [$user->latitude, $user->longitude];
        })->toArray();
        
        // Fetch weather data for all locations
        try {
            $weatherData = $weatherRepository->getWeatherForUsers($users);
            
            $successCount = collect($weatherData)->filter(function ($item) {
                return $item['weather'] !== null;
            })->count();
            
            $failureCount = collect($weatherData)->filter(function ($item) {
                return $item['error'] !== null;
            })->count();
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            Log::info('Batch weather fetch completed', [
                'total_users' => $users->count(),
                'successful' => $successCount,
                'failed' => $failureCount,
                'duration_ms' => $duration,
                'avg_time_per_user_ms' => round($duration / $users->count(), 2),
            ]);
            
            // Log any failures for monitoring
            if ($failureCount > 0) {
                $failures = collect($weatherData)->filter(function ($item) {
                    return $item['error'] !== null;
                });
                
                foreach ($failures as $failure) {
                    Log::warning('Weather fetch failed for user', [
                        'user_id' => $failure['user']->id,
                        'error' => $failure['error'],
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Batch weather fetch failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FetchWeatherForAllUsers job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}