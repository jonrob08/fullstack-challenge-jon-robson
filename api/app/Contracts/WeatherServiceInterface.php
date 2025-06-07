<?php

namespace App\Contracts;

interface WeatherServiceInterface
{
    /**
     * Get weather data for a specific location
     * 
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function getWeather(float $latitude, float $longitude): array;

    /**
     * Get weather data for multiple locations
     * 
     * @param array $locations Array of [latitude, longitude] pairs
     * @return array
     */
    public function getMultipleWeather(array $locations): array;

    /**
     * Check if the service is available
     * 
     * @return bool
     */
    public function isAvailable(): bool;
}