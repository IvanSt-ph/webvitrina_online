<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Services\PublishedSitemaps;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SitemapController extends Controller
{
    private const HEADERS = [
        'Content-Type' => 'application/xml; charset=UTF-8',
        'X-Content-Type-Options' => 'nosniff',
        'Cache-Control' => 'no-cache',
    ];

    public function index(PublishedSitemaps $sitemaps): Response|BinaryFileResponse
    {
        $path = $sitemaps->index();

        return $path === null ? $this->legacy() : new BinaryFileResponse($path, 200, self::HEADERS);
    }

    public function part(PublishedSitemaps $sitemaps, string $generation, string $file): Response|BinaryFileResponse
    {
        $path = $sitemaps->part($generation, $file);

        return $path === null
            ? new Response('<?xml version="1.0" encoding="UTF-8"?><error>Not found</error>', 404, self::HEADERS)
            : new BinaryFileResponse($path, 200, self::HEADERS);
    }

    protected function legacy(): Response
    {
        $products = Product::query()->active()->latest('updated_at')->limit(1000)->get(['slug', 'updated_at']);
        $categories = Category::query()->where('is_active', true)->latest('updated_at')->limit(1000)->get(['slug', 'updated_at']);
        $shops = Shop::query()->whereNotNull('slug')->latest('updated_at')->limit(1000)->get(['slug', 'updated_at']);

        return new Response(view('sitemap', compact('products', 'categories', 'shops'))->render(), 200, self::HEADERS);
    }
}
