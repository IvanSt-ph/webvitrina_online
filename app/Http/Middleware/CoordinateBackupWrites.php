<?php

namespace App\Http\Middleware;

use App\Services\BackupWriteBarrier;
use Closure;
use Illuminate\Http\Request;

class CoordinateBackupWrites
{
    public function handle(Request $request, Closure $next): mixed
    {
        // This controller takes the exclusive barrier itself; it performs no uploads.
        if ($request->isMethodSafe() || $request->routeIs('admin.backup.run')) {
            return $next($request);
        }
        $barrier = app(BackupWriteBarrier::class);
        try {
            $barrier->acquire();
        } catch (\RuntimeException $exception) {
            return response('Изменения временно недоступны. Повторите попытку через минуту.', 503, ['Retry-After' => '60']);
        }
        try {
            return $next($request);
        } finally {
            $barrier->release();
        }
    }
}
