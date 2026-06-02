<?php

declare(strict_types=1);

return [
    'admin_sample_rate' => (float) env('DEVICE_LOGS_ADMIN_SAMPLE_RATE', 0.01),
    'admin_enabled' => (bool) env('DEVICE_LOGS_ADMIN_ENABLED', true),
];
