<?php

return [
    'openweathermap' => [
        'base_url' => 'https://api.openweathermap.org/data/2.5/',
        'api_key' => getenv('OPENWEATHER_API_KEY') ?: 'demo_key_for_testing',
        'timeout' => 10,
        'retry_limit' => 3,
        'cache_ttl' => 300,
    ],
];