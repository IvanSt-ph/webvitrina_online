<?php

namespace App\Services;

use RuntimeException;

/** Called only while the generator owns generation.lock. */
final class SitemapPublication
{
    public const MARKER = '.pointer-managed';

    public function publish(string $root, string $generation): void
    {
        if (! preg_match('/^'.PublishedSitemaps::GENERATION.'$/D', $generation)) {
            throw new RuntimeException('Invalid sitemap publication identifier.', 7100);
        }

        $reader = new PublishedSitemaps();
        $previous = $reader->index();
        if ($reader->generationIndex($generation) === null) {
            throw new RuntimeException('Cannot publish an incomplete sitemap generation.', 7100);
        }
        if ($previous !== null) {
            $this->replace($root, 'previous', basename(dirname($previous)));
        }
        // Never unlink current first: failed replacement must leave it intact.
        $this->replace($root, 'current', $generation);
    }

    private function replace(string $root, string $name, string $generation): void
    {
        $temporary = $root.DIRECTORY_SEPARATOR.'.pointer-'.bin2hex(random_bytes(16));
        $stream = @fopen($temporary, 'xb');
        if ($stream === false) {
            throw new RuntimeException('Cannot prepare sitemap publication pointer.', 7100);
        }
        try {
            $data = $generation."\n";
            if (fwrite($stream, $data) !== strlen($data) || ! fflush($stream)) {
                throw new RuntimeException('Cannot write sitemap publication pointer.', 7100);
            }
            $closed = fclose($stream);
            $stream = null;
            if (! $closed || ! @rename($temporary, $root.DIRECTORY_SEPARATOR.$name)) {
                throw new RuntimeException('Cannot replace sitemap publication pointer; previous publication is unchanged.', 7100);
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (is_file($temporary) && ! is_link($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
