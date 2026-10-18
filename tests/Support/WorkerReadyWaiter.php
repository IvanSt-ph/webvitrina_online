<?php

namespace Tests\Support;

use Symfony\Component\Process\Process;

final class WorkerReadyWaiter
{
    public static function wait(Process $worker): bool
    {
        if ($worker->getTimeout() === null) {
            throw new \InvalidArgumentException('READY waiting requires a finite process timeout.');
        }

        while (true) {
            $worker->checkTimeout();
            // Status polling may itself consume stdout on Windows. Always inspect
            // the accumulated buffer, including bytes read before this wait began.
            $output = $worker->getOutput();
            $running = $worker->isRunning();
            $worker->checkTimeout();

            if (! $running) {
                return false;
            }
            if (preg_match('/(?:\A|\n)READY\r?\n/', $output) === 1) {
                return true;
            }

            // Polling interval only; readiness is proven by stdout and liveness.
            usleep(1000);
        }
    }
}
