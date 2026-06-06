<?php

return [
    'path' => env('BACKUP_DIR', '/var/backups/webvitrina'),
    'max_age_hours' => (int) env('BACKUP_MAX_AGE_HOURS', 30),
    'command' => env('BACKUP_COMMAND'),
    'daily_at' => env('BACKUP_DAILY_AT', '03:15'),
];
