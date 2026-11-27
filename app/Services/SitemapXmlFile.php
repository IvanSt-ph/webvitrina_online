<?php

namespace App\Services;

use RuntimeException;
use XMLWriter;

/** Bounded, incremental XML output; one entry is buffered at a time. */
final class SitemapXmlFile
{
    private $stream;
    private int $bytes;
    private int $count = 0;
    private string $footer;

    public function __construct(
        string $path,
        private readonly string $root,
        private readonly int $maxUrls,
        private readonly int $maxBytes,
    ) {
        $this->stream = fopen($path, 'xb');
        if ($this->stream === false) {
            throw new RuntimeException('Cannot create sitemap XML file.', 7100);
        }
        $header = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<'.$root.' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        $this->footer = '</'.$root.'>'."\n";
        $this->bytes = strlen($header);
        try {
            $this->write($header);
        } catch (\Throwable $exception) {
            $this->close();
            throw $exception;
        }
    }

    public function append(string $url): bool
    {
        // XML 1.0 disallows control characters, even if UTF-8 is valid.
        if (! mb_check_encoding($url, 'UTF-8') || preg_match('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', $url)) {
            throw new RuntimeException('Invalid UTF-8/XML characters in sitemap URL.', 7100);
        }
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->startElement($this->root === 'sitemapindex' ? 'sitemap' : 'url');
        $xml->writeElement('loc', $url);
        $xml->endElement();
        $entry = $xml->outputMemory()."\n";
        if ($this->count >= $this->maxUrls || $this->bytes + strlen($entry) + strlen($this->footer) > $this->maxBytes) {
            if ($this->count === 0) {
                throw new RuntimeException('A sitemap entry exceeds the configured XML size limit.', 7100);
            }

            return false;
        }
        $this->write($entry);
        $this->bytes += strlen($entry);
        $this->count++;

        return true;
    }

    public function finish(): int
    {
        $this->write($this->footer);
        if (! fflush($this->stream)) {
            throw new RuntimeException('Cannot flush sitemap XML file.', 7100);
        }
        $stream = $this->stream;
        $this->stream = null;
        if (! fclose($stream)) {
            throw new RuntimeException('Cannot close sitemap XML file; generation was not published.', 7100);
        }

        return $this->count;
    }

    private function write(string $data): void
    {
        while ($data !== '') {
            $written = fwrite($this->stream, $data);
            if ($written === false || $written === 0) {
                throw new RuntimeException('Cannot write sitemap XML file (check disk space and permissions).', 7100);
            }
            $data = substr($data, $written);
        }
    }

    public function close(): void
    {
        $stream = $this->stream;
        $this->stream = null;
        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
