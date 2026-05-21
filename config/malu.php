<?php

return [

    'rate_limit' => [
        'store' => (int) env('DOWNLOAD_RATE_LIMIT_STORE', 10),
        'read' => (int) env('DOWNLOAD_RATE_LIMIT_READ', 60),
    ],

];
