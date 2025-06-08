<?php

return [
    /*
    * Weather Service config
    */

    'default' => env('WEATHER_SERVICE', 'openweathermap'),

    'services' => [
        'openweathermap' => [
            'api_key' => env('OPENWEATHER_API_KEY'),
            'base_url' => 'https://api.openweathermap.org/data/2.5',
            'timeout' => 5,
            'units' => 'metric',
        ],
    ],

    /*
    * Cache config
    */

    'cache' => [
        'enabled' => env('WEATHER_CACHE_ENABLED', true),
        'ttl' => env('WEATHER_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'weather',
    ],

    /*
    * Performance settings
    */

    'performance' => [
        'max_response_time' => 500, // ms
        'log_slow_requests' => true,
        'slow_request_threshold' => 300, // ms
    ],
];
