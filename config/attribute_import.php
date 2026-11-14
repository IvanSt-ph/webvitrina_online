<?php

return [
    // Deployment-only setting. Missing/empty configuration must deny writes.
    'allowed_database' => env('ATTRIBUTE_IMPORT_ALLOWED_DATABASE', 'webv3_attributes_test'),
];
