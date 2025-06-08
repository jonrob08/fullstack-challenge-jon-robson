<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeatherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'current' => [
                'temperature' => $this->resource['temperature'],
                'feels_like' => $this->resource['feels_like'],
                'description' => $this->resource['description'],
                'main' => $this->resource['main'],
                'icon' => $this->resource['icon'],
                'humidity' => $this->resource['humidity'],
                'wind_speed' => $this->resource['wind_speed'],
                'pressure' => $this->resource['pressure'],
                'clouds' => $this->resource['clouds'],
                'visibility' => $this->resource['visibility'],
                'rain' => $this->when(isset($this->resource['rain']), $this->resource['rain']),
                'snow' => $this->when(isset($this->resource['snow']), $this->resource['snow']),
                'uvi' => $this->when(isset($this->resource['uvi']), $this->resource['uvi']),
            ],
            'cached_at' => $this->resource['cached_at'] ?? null,
            'response_time_ms' => $this->when(
                isset($this->resource['response_time_ms']), 
                $this->resource['response_time_ms']
            ),
        ];
    }
}