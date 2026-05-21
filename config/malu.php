<?php

return [

    'require_auth' => (bool) env('MALU_REQUIRE_AUTH', false),

    'allow_registration' => (bool) env('MALU_ALLOW_REGISTRATION', true),

    'rate_limit' => [
        'store' => (int) env('DOWNLOAD_RATE_LIMIT_STORE', 10),
        'read' => (int) env('DOWNLOAD_RATE_LIMIT_READ', 60),
    ],

    'stale' => [
        'queued_minutes' => (int) env('DOWNLOAD_STALE_QUEUED_MINUTES', 30),
        'processing_minutes' => (int) env('DOWNLOAD_STALE_PROCESSING_MINUTES', 15),
    ],

    'retention_hours' => (int) env('DOWNLOAD_RETENTION_HOURS', 24),

    'prune_records' => (bool) env('DOWNLOAD_PRUNE_RECORDS', true),

    /*
    | Redis/database queue retry_after must exceed the longest download job.
    | Otherwise the worker is still running yt-dlp but Redis re-queues the job.
    */
    'queue' => [
        'retry_after' => (int) env(
            'QUEUE_RETRY_AFTER',
            (int) env('YTDLP_PLAYLIST_TIMEOUT', 3600) + 120,
        ),
        'worker_timeout' => (int) env(
            'QUEUE_WORKER_TIMEOUT',
            (int) env('YTDLP_PLAYLIST_TIMEOUT', 3600) + 120,
        ),
    ],

];
