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

    
    'whatsapp' => [
        'version'          => env('WHATSAPP_VERSION', 'v21.0'),
        'phone_number_id'  => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token'     => env('WHATSAPP_ACCESS_TOKEN'),
        'verify_token'     => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'app_secret'       => env('WHATSAPP_APP_SECRET'),
    ],

    'gemma' => [
        'api_key'  => env('GEMMA_API_KEY'),
        'endpoint' => env('GEMMA_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
        'model'    => env('GEMMA_MODEL', 'gemma-4-26b-a4b-it'),
    ],
    'telegram'=>[
        'bot_token'=>env('TELEGRAM_BOT_TOKEN'),
        'secret'=>env('TELEGRAM_WEBHOOK_SECRET'),
    ],
    
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
    'mapbox' => [
    'token' => env('MAPBOX_ACCESS_TOKEN'),
    ],

    'locationiq' => [
        'key' => env('LOCATIONIQ_API_KEY'),
    ],

    'map_defaults' => [
        'lat' => env('DEFAULT_MAP_LAT', -1.286389),
        'lng' => env('DEFAULT_MAP_LNG', 36.817223),
        'zoom' => env('DEFAULT_MAP_ZOOM', 12),
    ],

];
