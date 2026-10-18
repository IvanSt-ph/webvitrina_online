<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\TestFileSafety;

class TestFileSafetyTest extends TestCase
{
    public static function redirectedTargets(): array
    {
        return ['fake root' => ['disk'], 'products child' => ['disk/products']];
    }

    #[DataProvider('redirectedTargets')]
    public function test_redirected_fake_root_and_deletion_child_are_rejected(string $relative): void
    {
        // Only disposable fixtures under testing; no Laravel boot or database.
        $root = dirname(__DIR__, 2).'/storage/framework/testing/cleanup-safety-'.bin2hex(random_bytes(8));
        TestFileSafety::assertDirectory($root, mustExist: false);
        mkdir($root.'/outside', 0777, true);
        $link = $root.'/'.$relative;
        try {
            file_put_contents($root.'/outside/canary.txt', 'preserve');
            if ($relative === 'disk/products') {
                mkdir($root.'/disk');
            }
            if (! @symlink($root.'/outside', $link)) {
                $this->markTestSkipped('Directory symlinks require permission on this platform.');
            }
            try {
                TestFileSafety::assertDirectory($link, mustExist: false);
                $this->fail('A redirected cleanup target was accepted.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('redirected', $exception->getMessage());
            }
            $this->assertSame('preserve', file_get_contents($root.'/outside/canary.txt'));
            TestFileSafety::assertDirectory($root.'/outside');
            TestFileSafety::assertDirectory($root.'/missing/child', mustExist: false);
        } finally {
            // Remove only the explicitly created link and fixtures; never recurse through it.
            if (is_link($link)) {
                PHP_OS_FAMILY === 'Windows' ? rmdir($link) : unlink($link);
            }
            if (is_file($root.'/outside/canary.txt')) {
                unlink($root.'/outside/canary.txt');
            }
            if (is_dir($root.'/disk') && ! is_link($root.'/disk')) {
                rmdir($root.'/disk');
            }
            rmdir($root.'/outside');
            rmdir($root);
        }
    }
}
