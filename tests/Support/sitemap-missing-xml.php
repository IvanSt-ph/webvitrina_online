<?php

// Invoked only by the separately selected preflight test. No Composer/Laravel bootstrap.
if (class_exists('XMLWriter') && class_exists('XMLReader')) {
    exit(77);
}
require dirname(__DIR__, 2).'/app/Services/SitemapGenerator.php';
try {
    (new \App\Services\SitemapGenerator())->generate();
    exit(1);
} catch (\RuntimeException $exception) {
    if ($exception->getCode() !== 7100 || ! str_contains($exception->getMessage(), 'XMLWriter and XMLReader')) {
        exit(2);
    }
    echo $exception->getMessage();
}
