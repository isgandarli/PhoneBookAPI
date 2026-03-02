<?php

return [
    'secret' => env('JWT_SECRET'),
    'ttl' => (int) env('JWT_TTL', 60), // minutes
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 20160), // 14 days in minutes
];
