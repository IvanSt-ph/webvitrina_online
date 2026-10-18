<?php

namespace Tests\Support;

use RuntimeException;

final class TestFileSafety
{
    public static function assertDirectory(string $directory, bool $mustExist = true): void
    {
        $base = dirname(__DIR__, 2).'/storage/framework/testing';
        $normalize = static fn (string $path): string => PHP_OS_FAMILY === 'Windows'
            ? strtolower(str_replace('\\', '/', rtrim($path, '/\\')))
            : rtrim($path, '/');
        // Check before Storage::fake() too: it cleans its root before returning.
        if (! str_starts_with($normalize($directory), $normalize($base).'/')) {
            throw new RuntimeException('Refusing file cleanup outside an explicit test directory.');
        }
        $ancestor = $directory;
        while (! file_exists($ancestor) && ! is_link($ancestor)) {
            if ($mustExist) {
                throw new RuntimeException('Test cleanup directory does not exist.');
            }
            $parent = dirname($ancestor);
            if ($parent === $ancestor) {
                throw new RuntimeException('Cannot resolve test directory.');
            }
            $ancestor = $parent;
        }
        $resolved = realpath($ancestor);
        // Also rejects a redirected ancestor, a junction and ../ components.
        if ($resolved === false || ! is_dir($ancestor)
            || $normalize($resolved) !== $normalize($ancestor)) {
            throw new RuntimeException('Refusing redirected test cleanup directory.');
        }
    }
}
