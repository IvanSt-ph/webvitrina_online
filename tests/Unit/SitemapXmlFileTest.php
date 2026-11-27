<?php

namespace Tests\Unit;

use App\Services\SitemapGenerator;
use App\Services\SitemapXmlFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SitemapFileTestCase;

final class SitemapXmlFileTest extends SitemapFileTestCase
{
    public static function roots(): array
    {
        return [['urlset', 'url'], ['sitemapindex', 'sitemap']];
    }

    #[DataProvider('roots')]
    public function test_root_namespace_escaping_and_close(string $root, string $entry): void
    {
        $path = $this->directory.'/map.xml';
        $url = 'https://shop.test/товар?a=1&b="<значение>"';
        $writer = new SitemapXmlFile($path, $root, 5000, 10000);
        $this->assertTrue($writer->append($url));
        $this->assertSame(1, $writer->finish());
        $writer->close();
        $writer->close();
        $this->assertSame([$url], $this->locations($path));
        $xml = simplexml_load_file($path);
        $this->assertSame($root, $xml->getName());
        $this->assertSame('http://www.sitemaps.org/schemas/sitemap/0.9', $xml->getDocNamespaces()['']);
        $this->assertCount(1, $xml->{$entry});
        $this->assertStringContainsString('&amp;', file_get_contents($path));
        $this->assertStringContainsString('&lt;', file_get_contents($path));
        $this->assertTrue(rename($path, $this->directory.'/closed.xml'));
    }

    public function test_url_limit_rejects_next_entry_without_writing_it(): void
    {
        $writer = new SitemapXmlFile($this->directory.'/map.xml', 'urlset', 2, 10000);
        $this->assertTrue($writer->append('https://shop.test/1'));
        $this->assertTrue($writer->append('https://shop.test/2'));
        $this->assertFalse($writer->append('https://shop.test/3'));
        $this->assertSame(2, $writer->finish());
        $this->assertSame(['https://shop.test/1', 'https://shop.test/2'], $this->locations($this->directory.'/map.xml'));
    }

    public function test_size_limit_includes_footer_and_escaped_bytes(): void
    {
        $url = 'https://shop.test/?q='.str_repeat('&', 40);
        $probe = new SitemapXmlFile($this->directory.'/probe.xml', 'urlset', 100, 10000);
        $probe->append($url);
        $probe->finish();
        $limit = filesize($this->directory.'/probe.xml');
        $writer = new SitemapXmlFile($this->directory.'/map.xml', 'urlset', 100, $limit);
        $this->assertTrue($writer->append($url));
        $this->assertFalse($writer->append($url));
        $writer->finish();
        $this->assertSame($limit, filesize($this->directory.'/map.xml'));
        $tooSmall = new SitemapXmlFile($this->directory.'/small.xml', 'urlset', 100, $limit - 1);
        try {
            $tooSmall->append($url);
            $this->fail('Oversized first entry must fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(7100, $exception->getCode());
        } finally {
            $tooSmall->close();
        }
    }

    public static function invalidCharacters(): array
    {
        return [["\0"], ["\x01"], ["\xFF"], ["\xEF\xBF\xBE"]];
    }

    #[DataProvider('invalidCharacters')]
    public function test_invalid_xml_characters_are_rejected(string $character): void
    {
        $writer = new SitemapXmlFile($this->directory.'/map.xml', 'urlset', 5, 10000);
        try {
            $writer->append('https://shop.test/'.$character);
            $this->fail('Invalid character accepted.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('UTF-8/XML', $exception->getMessage());
        } finally {
            $writer->close();
        }
    }

    public function test_part_rotation_preserves_every_url_and_creates_no_empty_tail(): void
    {
        // Only filesystem/XML private methods: no Laravel boot, configuration or DB.
        $service = new SitemapGenerator();
        $method = new \ReflectionMethod($service, 'writeParts');
        $files = [];
        $urls = array_map(fn ($i) => 'https://shop.test/p/'.$i, range(1, 6));
        $method->invokeArgs($service, [$this->directory, 'products', $urls, 2, 10000, &$files]);
        $this->assertSame(['products-0001.xml' => 2, 'products-0002.xml' => 2, 'products-0003.xml' => 2], $files);
        $actual = [];
        foreach ($files as $name => $count) {
            $actual = array_merge($actual, $this->locations($this->directory.'/'.$name));
            (new \ReflectionMethod($service, 'validateXml'))->invoke($service, $this->directory.'/'.$name, $count, 10000, 'urlset');
        }
        $this->assertSame($urls, $actual);
        $this->assertCount(3, glob($this->directory.'/*.xml'));
    }

    public function test_empty_collection_does_not_leave_a_file(): void
    {
        $files = [];
        (new \ReflectionMethod(SitemapGenerator::class, 'writeParts'))->invokeArgs(new SitemapGenerator(), [$this->directory, 'shops', [], 2, 10000, &$files]);
        $this->assertSame([], $files);
        $this->assertSame([], glob($this->directory.'/*.xml'));
    }

    public function test_parts_rotate_on_bytes_before_url_limit(): void
    {
        $files = [];
        $urls = array_map(fn ($i) => 'https://shop.test/'.str_repeat('x', 160).$i, range(1, 3));
        (new \ReflectionMethod(SitemapGenerator::class, 'writeParts'))->invokeArgs(new SitemapGenerator(), [$this->directory, 'products', $urls, 5000, 350, &$files]);
        $this->assertCount(3, $files);
        $actual = [];
        foreach ($files as $name => $count) {
            $this->assertSame(1, $count);
            $this->assertLessThanOrEqual(350, filesize($this->directory.'/'.$name));
            $actual = array_merge($actual, $this->locations($this->directory.'/'.$name));
        }
        $this->assertSame($urls, $actual);
    }

    public static function malformedStructures(): array
    {
        $ns = ' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        return [
            ['<urlset><url><loc>https://shop.test/</loc></url></urlset>'],
            ['<sitemapindex'.$ns.'><sitemap><loc>https://shop.test/</loc></sitemap></sitemapindex>'],
            ['<urlset'.$ns.'><url><loc/></url></urlset>'],
            ['<urlset'.$ns.'><url><loc>a</loc><loc>b</loc></url></urlset>'],
            ['<urlset'.$ns.'><loc>https://shop.test/</loc></urlset>'],
            ['<urlset'.$ns.'><url></url></urlset>'],
            ['<urlset'.$ns.'><url><loc>https://shop.test/</loc></url>'],
        ];
    }

    #[DataProvider('malformedStructures')]
    public function test_streaming_validator_rejects_invalid_structure(string $xml): void
    {
        $file = $this->directory.'/invalid.xml';
        file_put_contents($file, $xml);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(7100);
        (new \ReflectionMethod(SitemapGenerator::class, 'validateXml'))->invoke(new SitemapGenerator(), $file, 1, 10000, 'urlset');
    }
}
