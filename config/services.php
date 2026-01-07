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
    | Environmental Data APIs
    |--------------------------------------------------------------------------
    */

    'openweathermap' => [
        'api_key' => env('OPENWEATHERMAP_API_KEY'),
        'base_url' => 'https://api.openweathermap.org/data/2.5',
        'cache_ttl' => 3600, // 60 minutes
    ],

    'waqi' => [
        'api_key' => env('WAQI_API_KEY'),
        'base_url' => 'https://api.waqi.info',
        'cache_ttl' => 3600, // 60 minutes
    ],

    'copernicus_dataspace' => [
        'client_id' => env('COPERNICUS_CLIENT_ID'),
        'client_secret' => env('COPERNICUS_CLIENT_SECRET'),
        'token_url' => 'https://identity.dataspace.copernicus.eu/auth/realms/CDSE/protocol/openid-connect/token',
        'process_url' => 'https://sh.dataspace.copernicus.eu/api/v1/process',
        'cache_ttl' => env('COPERNICUS_CACHE_TTL', 3600),
    ],

];
