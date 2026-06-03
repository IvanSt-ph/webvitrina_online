{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    @foreach([
        ['route' => 'faq', 'priority' => '0.8'],
        ['route' => 'about', 'priority' => '0.6'],
        ['route' => 'contacts', 'priority' => '0.6'],
        ['route' => 'legal.rules', 'priority' => '0.5'],
        ['route' => 'legal.privacy', 'priority' => '0.5'],
        ['route' => 'legal.delivery-returns', 'priority' => '0.5'],
        ['route' => 'legal.seller-terms', 'priority' => '0.5'],
    ] as $staticPage)
        <url>
            <loc>{{ route($staticPage['route']) }}</loc>
            <changefreq>monthly</changefreq>
            <priority>{{ $staticPage['priority'] }}</priority>
        </url>
    @endforeach
    @foreach($products as $product)
        <url>
            <loc>{{ route('product.show', $product->slug) }}</loc>
            <lastmod>{{ optional($product->updated_at)->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach
    @foreach($categories as $category)
        <url>
            <loc>{{ route('category.show', $category->slug) }}</loc>
            <lastmod>{{ optional($category->updated_at)->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.7</priority>
        </url>
    @endforeach
    @foreach($shops as $shop)
        <url>
            <loc>{{ route('seller.show', $shop->slug) }}</loc>
            <lastmod>{{ optional($shop->updated_at)->toAtomString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.6</priority>
        </url>
    @endforeach
</urlset>
