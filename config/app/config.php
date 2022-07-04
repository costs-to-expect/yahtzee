<?php
declare(strict_types=1);

return [
    'api_url' => env('API_URL', 'https://api.costs-to-expect.com'),
    'api_url_dev' => env('API_URL_DEV', 'https://api.costs-to-expect.com'),
    'dev' => env('APP_DEV', false),
    'cache' => env('APP_CACHE', false),
    'cookie_user' => env('SESSION_NAME_USER'),
    'cookie_bearer' => env('SESSION_NAME_BEARER'),
    'version' => '0.1.0',
    'release_date' => 'xx ordinal Month 2022'
];