<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
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
    | Supabase de Sucursales
    |--------------------------------------------------------------------------
    |
    | Estas variables deben apuntar al proyecto Supabase de Sucursales,
    | no al proyecto de Banco Central.
    |
    */

    'sucursales_supabase' => [
        'url' => env('SUCURSALES_SUPABASE_URL'),
        'key' => env('SUCURSALES_SUPABASE_ANON_KEY'),
    ],

    'sucursales_supabase' => [
        'url' => env('SUCURSALES_SUPABASE_URL'),
        'key' => env('SUCURSALES_SUPABASE_ANON_KEY'),
        'service_key' => env('SUCURSALES_SUPABASE_SERVICE_KEY'),
    ],

];
