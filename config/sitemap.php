<?php

return [
    // Explicit public origin; deliberately no localhost / APP_URL fallback.
    'origin' => env('SITEMAP_ORIGIN'),
    'batch_size' => 1000,
    'max_urls' => 5000,
    'max_bytes' => 45 * 1024 * 1024,
    // Must stay inside storage/app/private. HTTP exposes only finalized XML generations.
    'directory' => storage_path('app/private/sitemaps'),
    // Reserved for future retention; generation never deletes old generations.
    'retention_days' => 7,
];
