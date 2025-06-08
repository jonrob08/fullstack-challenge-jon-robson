<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Contracts\WeatherServiceInterface;

class TestWeatherService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weather:test {latitude=40.7128} {longitude=-74.0060}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the weather service implementation';

    /**
     * Execute the console command.
     */
    public function handle(WeatherServiceInterface $weatherService)
    {
        $latitude = (float) $this->argument('latitude');
        $longitude = (float) $this->argument('longitude');

        $this->info("Testing weather service for coordinates: {$latitude}, {$longitude}");
        
        try {
            // Check if service is available
            if (!$weatherService->isAvailable()) {
                $this->error('Weather service is not available. Check your API key.');
                return 1;
            }

            $this->info('Weather service is available.');
            
            // Fetch weather data
            $startTime = microtime(true);
            $weather = $weatherService->getWeather($latitude, $longitude);
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            $this->info("Response time: {$responseTime}ms");
            
            // Display weather data
            $this->line('');
            $this->info('Weather Data:');
            $this->line('Temperature: ' . $weather['temperature'] . '°C');
            $this->line('Feels Like: ' . $weather['feels_like'] . '°C');
            $this->line('Description: ' . $weather['description']);
            $this->line('Humidity: ' . $weather['humidity'] . '%');
            $this->line('Wind Speed: ' . $weather['wind_speed'] . ' m/s');
            $this->line('Pressure: ' . $weather['pressure'] . ' hPa');
            
            if (isset($weather['cached_at'])) {
                $this->line('');
                $this->info('Cached at: ' . $weather['cached_at']);
            }
            
            // Test cache
            $this->line('');
            $this->info('Testing cache (second call should be faster)...');
            
            $startTime = microtime(true);
            $cachedWeather = $weatherService->getWeather($latitude, $longitude);
            $cacheResponseTime = (microtime(true) - $startTime) * 1000;
            
            $this->info("Cache response time: {$cacheResponseTime}ms");
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}