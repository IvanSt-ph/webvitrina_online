<?php

return [
    'scheduler_max_age_minutes' => (int) env('HEALTH_SCHEDULER_MAX_AGE_MINUTES', 5),
    'disk_warning_bytes' => (int) env('HEALTH_DISK_WARNING_BYTES', 1073741824),
    'disk_critical_bytes' => (int) env('HEALTH_DISK_CRITICAL_BYTES', 268435456),
];
