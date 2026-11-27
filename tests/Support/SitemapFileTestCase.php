<?php

namespace Tests\Support;

use PHPUnit\Framework\TestCase;

abstract class SitemapFileTestCase extends TestCase
{
    protected string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists(\XMLWriter::class) || ! class_exists(\XMLReader::class)) {
            $this->markTestSkipped('XMLWriter and XMLReader are required.');
        }
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wv-sitemap-test-'.bin2hex(random_bytes(12));
        mkdir($this->directory, 0700);
        $this->directory = realpath($this->directory);
    }

    protected function tearDown(): void
    {
        if (isset($this->directory)) {
            $root = $this->directory;
            $this->assertSame($root, realpath($root));
            $this->assertStringStartsWith('wv-sitemap-test-', basename($root));
            $this->assertSame(realpath(sys_get_temp_dir()), realpath(dirname($root)));
            $remove = function (string $path) use (&$remove, $root): void {
                if (is_link($path)) {
                    unlink($path);
                    return;
                }
                $resolved = realpath($path);
                if ($resolved === false || ($resolved !== $root && ! str_starts_with($resolved, $root.DIRECTORY_SEPARATOR))) {
                    throw new \RuntimeException('Refusing cleanup outside owned sitemap test directory.');
                }
                if (is_dir($path)) {
                    foreach (scandir($path) as $item) {
                        if ($item !== '.' && $item !== '..') {
                            $remove($path.DIRECTORY_SEPARATOR.$item);
                        }
                    }
                    rmdir($path);
                } else {
                    unlink($path);
                }
            };
            $remove($root);
        }
        parent::tearDown();
    }

    protected function locations(string $file): array
    {
        $xml = simplexml_load_file($file);
        $this->assertNotFalse($xml);
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return array_map('strval', $xml->xpath('//s:loc'));
    }
}
