<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\WeatherServiceInterface;
use App\Services\Weather\OpenWeatherMapService;

class WeatherServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(WeatherServiceInterface::class, function ($app) {
            $service = config('weather.default');
            
            switch ($service) {
                case 'openweathermap':
                    return new OpenWeatherMapService();
                default:
                    throw new \Exception("Unknown weather service: {$service}");
            }
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}