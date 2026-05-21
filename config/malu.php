<?php

return [

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

];
