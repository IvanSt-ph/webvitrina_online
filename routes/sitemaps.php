<?php

use App\Http\Controllers\SitemapController;
use App\Services\PublishedSitemaps;
use Illuminate\Support\Facades\Route;

// Stateless public endpoints: no authentication, session persistence or web middleware.
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemaps/{generation}/{file}', [SitemapController::class, 'part'])
    ->where('generation', PublishedSitemaps::GENERATION)
    ->where('file', PublishedSitemaps::PART)
    ->name('sitemap.part');
