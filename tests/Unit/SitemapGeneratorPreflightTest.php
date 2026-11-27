<?php

namespace Tests\Unit;

use App\Services\SitemapGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\Support\SitemapGeneratorTestCase;

final class SitemapGeneratorPreflightTest extends SitemapGeneratorTestCase
{
    public static function invalidOrigins(): array
    {
        return [[null], [''], ['http://shop.org'], ['https://localhost'], ['https://127.0.0.1'],
            ['https://example.com'], ['https://shop.org/path'], ['https://user:pass@shop.org'], ['https://shop.org/?x=1']];
    }

    #[DataProvider('invalidOrigins')]
    public function test_invalid_origin_fails_before_database_or_output_creation(?string $origin): void
    {
        $this->app['config']->set('sitemap.origin', $origin);
        $this->app->bind('db', static function () { throw new \LogicException('Database must not be resolved.'); });
        try {
            (new SitemapGenerator())->generate();
            $this->fail('Invalid origin accepted.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(7100, $exception->getCode());
            $this->assertStringContainsString('SITEMAP_ORIGIN', $exception->getMessage());
        }
        $this->assertDirectoryDoesNotExist(config('sitemap.directory'));
    }

    public function test_missing_xml_extension_fails_before_bootstrap(): void
    {
        $process = new Process([PHP_BINARY, '-n', dirname(__DIR__).'/Support/sitemap-missing-xml.php']);
        $process->setTimeout(15);
        $process->run();
        if ($process->getExitCode() === 77) {
            $this->markTestSkipped('XML extensions are compiled in; php -n cannot remove them.');
        }
        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString('XMLWriter and XMLReader', $process->getOutput());
    }
}
