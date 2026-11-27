<?php

namespace App\Console\Commands;

use App\Services\SitemapGenerator;
use Illuminate\Console\Command;
use Throwable;

class GenerateSitemaps extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Prepare a validated private sitemap generation without changing public routes.';

    public function handle(SitemapGenerator $generator): int
    {
        try {
            $result = $generator->generate();
            $this->info('Prepared sitemap generation: '.$result['generation']);
            $this->line('Files: '.$result['files'].'; URLs: '.$result['urls']);
            $this->line('Public sitemap and scheduler were not changed.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            // Do not print QueryException SQL/bindings or connection credentials.
            $this->error($exception instanceof \RuntimeException && $exception->getCode() === 7100
                ? $exception->getMessage()
                : 'Sitemap generation failed. No new generation was published; check database, storage and runtime configuration.');

            return self::FAILURE;
        }
    }
}
