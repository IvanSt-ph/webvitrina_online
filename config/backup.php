<?php

return [
    'path' => env('BACKUP_DIR', '/var/backups/webvitrina'),
    'max_age_hours' => (int) env('BACKUP_MAX_AGE_HOURS', 30),
];
