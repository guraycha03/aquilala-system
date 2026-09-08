<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenWeatherMap
    |--------------------------------------------------------------------------
    |
    | Free real-time weather API. Sign up at https://openweathermap.org/api
    | to obtain an API key, then set it in your .env file:
    |
    |   OPENWEATHER_API_KEY=your_api_key_here
    |
    */
    'openweather' => [
        'key' => env('OPENWEATHER_API_KEY'),
        // Free-tier real-time current weather: http://.../data/2.5/weather
        'url' => env('OPENWEATHER_API_URL', 'https://api.openweathermap.org/data/2.5/weather'),
        // Free-tier 3-hour / 5-day forecast: http://.../data/2.5/forecast
        'forecast_url' => env('OPENWEATHER_FORECAST_API_URL', 'https://api.openweathermap.org/data/2.5/forecast'),
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
    ],

];
