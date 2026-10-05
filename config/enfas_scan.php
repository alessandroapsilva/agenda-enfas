<?php

return [
    'enabled' => (bool) env('ENFAS_SCAN_ENABLED', true),
    'agent_url' => env('ENFAS_SCAN_AGENT_URL', 'http://127.0.0.1:19876'),
    'request_timeout_ms' => (int) env('ENFAS_SCAN_TIMEOUT_MS', 4000),
];
