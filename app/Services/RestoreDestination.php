<?php

namespace App\Services;

class RestoreDestination
{
    /** Drill accepts one existing, empty, private directory; child roots are fixed. */
    public function isolated(string $destination, string $backup): array
    {
        if ($destination === '' || ! preg_match('~^(?:/|[A-Za-z]:[/\\\\])~', $destination)
            || preg_match('~(?:^|[/\\\\])\.{1,2}(?:[/\\\\]|$)~', $destination)) {
            throw new \RuntimeException('Drill requires an absolute destination without traversal.');
        }
        $canonical = realpath($destination);
        if ($canonical === false || ! is_dir($canonical)
            || $this->key($canonical) !== $this->key($destination)) {
            throw new \RuntimeException('Drill destination must exist and must not use symlinks or aliases.');
        }
        $key = $this->key($canonical);
        if ($key === '' || preg_match('~^[a-z]:$~i', $key)) {
            throw new \RuntimeException('Filesystem root is not a drill destination.');
        }
        foreach ([
            (string) config('filesystems.disks.public.root'),
            (string) config('filesystems.disks.local.root'),
            storage_path('app/public'), storage_path('app/private'),
            $backup,
        ] as $protected) {
            $protected = $this->canonicalFuturePath($protected);
            if ($this->overlaps($key, $this->key($protected))) {
                throw new \RuntimeException('Drill destination overlaps live storage or the backup source.');
            }
        }
        if ($key === $this->key(realpath(base_path()) ?: base_path())) {
            throw new \RuntimeException('Project root is not a drill destination.');
        }
        if (array_diff(scandir($canonical) ?: [], ['.', '..']) !== []) {
            throw new \RuntimeException('Drill destination must be empty. Use a new private directory.');
        }
        return [$canonical . DIRECTORY_SEPARATOR . 'public', $canonical . DIRECTORY_SEPARATOR . 'private'];
    }

    public function storageRoots(string $public, string $private, string $backup): array
    {
        $public = $this->canonicalFuturePath($public);
        $private = $this->canonicalFuturePath($private);
        if ($this->overlaps($this->key($public), $this->key($private))
            || $this->key(dirname($public)) !== $this->key(dirname($private))) {
            throw new \RuntimeException('Restore roots must be distinct, non-overlapping siblings.');
        }
        $chat = $private . DIRECTORY_SEPARATOR . 'chat-images';
        if ($this->key($this->canonicalFuturePath($chat)) !== $this->key($chat)) {
            throw new \RuntimeException('Private chat root must not be a symlink or alias.');
        }
        $project = $this->key(realpath(base_path()) ?: base_path());
        $source = $this->key($this->canonicalFuturePath($backup));
        foreach ([$public, $chat] as $target) {
            $key = $this->key($target);
            if ($key === '' || preg_match('~^[a-z]:$~i', $key)
                || $project === $key || str_starts_with($project . '/', $key . '/')
                || $this->overlaps($source, $key)) {
                throw new \RuntimeException('Restore root overlaps the project or backup source.');
            }
        }
        return [$public, $private];
    }

    private function canonicalFuturePath(string $path): string
    {
        $suffix = [];
        while (($real = realpath($path)) === false) {
            $parent = dirname($path);
            if ($parent === $path || is_link($path)) {
                throw new \RuntimeException('Cannot resolve protected storage path.');
            }
            array_unshift($suffix, basename($path));
            $path = $parent;
        }
        return $real . ($suffix ? DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $suffix) : '');
    }

    private function key(string $path): string
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        return PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path;
    }

    private function overlaps(string $a, string $b): bool
    {
        return $a === $b || str_starts_with($a . '/', $b . '/') || str_starts_with($b . '/', $a . '/');
    }
}
