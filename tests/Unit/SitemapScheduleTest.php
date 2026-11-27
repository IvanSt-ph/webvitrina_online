<?php

namespace Tests\Unit;

use Illuminate\Console\Scheduling\EventMutex;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\SchedulingMutex;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Fluent;
use Tests\Support\SitemapGeneratorTestCase;

final class SitemapScheduleTest extends SitemapGeneratorTestCase
{
    public function test_registers_one_daily_sitemap_command_with_local_timezone_and_overlap_lock(): void
    {
        // Only register events in the minimal container; never bootstrap or execute Artisan.
        $this->app->instance(Kernel::class, new class
        {
            public function command(string $signature, \Closure $callback): Fluent
            {
                return new Fluent();
            }
        });
        $this->app->instance(EventMutex::class, $this->createMock(EventMutex::class));
        $this->app->instance(SchedulingMutex::class, $this->createMock(SchedulingMutex::class));
        $this->app->bind('db', static function () {
            throw new \LogicException('Schedule registration must not access the database.');
        });
        $schedule = new Schedule('UTC');
        $this->app->instance(Schedule::class, $schedule);

        require dirname(__DIR__, 2).'/routes/console.php';

        $events = array_values(array_filter($schedule->events(),
            static fn ($event) => str_contains($event->command ?? '', 'sitemap:generate')));
        $this->assertCount(1, $events);
        $this->assertSame('30 3 * * *', $events[0]->expression);
        $this->assertSame('Europe/Chisinau', $events[0]->timezone);
        $this->assertTrue($events[0]->withoutOverlapping);
        $this->assertTrue($events[0]->runInBackground);
    }
}
