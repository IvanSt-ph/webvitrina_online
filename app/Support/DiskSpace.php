<?php

namespace App\Support;

class DiskSpace
{
    public function free(string $path): float|false
    {
        return @disk_free_space($path);
    }
}
