<?php

namespace Tests\Support;

use RuntimeException;
use Throwable;

/** Attempt every close/stop even when an earlier resource fails. Never kill by port/name. */
final class WorkerProcessCleanup
{
    public static function stop(array $processes, array $inputs): void
    {
        $failed = false;
        foreach ($inputs as $input) {
            try { $input->close(); } catch (Throwable) { $failed = true; }
        }
        foreach ($processes as $process) {
            try {
                $process->stop(0);
                if ($process->isRunning()) { $failed = true; }
            } catch (Throwable) { $failed = true; }
        }
        if ($failed) {
            throw new RuntimeException('Worker cleanup incomplete; do not start another test run.');
        }
    }
}
