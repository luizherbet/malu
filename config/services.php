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

    'ytdlp' => [
        'binary' => env('YTDLP_BINARY', 'yt-dlp'),
        'timeout' => (int) env('YTDLP_TIMEOUT', 600),
        'playlist_timeout' => (int) env('YTDLP_PLAYLIST_TIMEOUT', 3600),
        'preview_timeout' => (int) env('YTDLP_PREVIEW_TIMEOUT', 120),
        'js_runtimes' => env('YTDLP_JS_RUNTIMES', 'node'),
        'cookies_file' => env('YTDLP_COOKIES_FILE'),
        'cookies_from_browser' => env('YTDLP_COOKIES_FROM_BROWSER'),
        'sleep_requests' => (int) env('YTDLP_SLEEP_REQUESTS', 1),
    ],

];
