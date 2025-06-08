<?php

namespace App\ValueObjects;

class WeatherData
{
    public function __construct(
        public readonly float $temperature,
        public readonly float $feelsLike,
        public readonly string $description,
        public readonly string $icon,
        public readonly int $humidity,
        public readonly float $windSpeed,
        public readonly int $pressure,
        public readonly ?float $rain = null,
        public readonly ?float $snow = null,
        public readonly array $raw = []
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            temperature: $data['temperature'] ?? 0,
            feelsLike: $data['feels_like'] ?? 0,
            description: $data['description'] ?? '',
            icon: $data['icon'] ?? '',
            humidity: $data['humidity'] ?? 0,
            windSpeed: $data['wind_speed'] ?? 0,
            pressure: $data['pressure'] ?? 0,
            rain: $data['rain'] ?? null,
            snow: $data['snow'] ?? null,
            raw: $data
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'temperature' => $this->temperature,
            'feels_like' => $this->feelsLike,
            'description' => $this->description,
            'icon' => $this->icon,
            'humidity' => $this->humidity,
            'wind_speed' => $this->windSpeed,
            'pressure' => $this->pressure,
            'rain' => $this->rain,
            'snow' => $this->snow,
        ];
    }

    /**
     * Get temperature in Fahrenheit
     */
    public function getTemperatureInFahrenheit(): float
    {
        return ($this->temperature * 9/5) + 32;
    }
}