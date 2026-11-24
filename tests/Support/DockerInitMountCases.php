<?php

namespace Tests\Support;

/** Synthetic evidence only: no filesystem, application or database access. */
final class DockerInitMountCases
{
    public static function cases(): array
    {
        $path = '/usr/sbin/docker-init';
        $stat = ['dev' => 42, 'ino' => 73, 'mode' => 0100755, 'uid' => 0];
        $evidence = ['legacy' => $path, 'merged' => $path, 'target' => $path,
            'pid1' => $path, 'comm' => 'docker-init', 'file' => $stat, 'process' => $stat];
        $cases = [
            'merged init' => [$path, 'overlay', ['ro', 'relatime'], $evidence, true],
            'legacy init alias' => ['/sbin/docker-init', 'overlay', ['ro'], $evidence, true],
            'arbitrary overlay' => ['/usr/other', 'overlay', ['ro'], $evidence, false],
            'nested path' => [$path.'/other', 'overlay', ['ro'], $evidence, false],
            'writable' => [$path, 'overlay', ['rw'], $evidence, false],
            'contradictory flags' => [$path, 'overlay', ['ro', 'rw'], $evidence, false],
            'missing ro' => [$path, 'overlay', ['relatime'], $evidence, false],
            'unexpected fs' => [$path, 'ext4', ['ro'], $evidence, false],
        ];
        foreach (['legacy', 'merged', 'target', 'pid1'] as $key) {
            $cases['redirected '.$key] = [$path, 'overlay', ['ro'], array_replace($evidence, [$key => '/untrusted']), false];
            $cases['unavailable '.$key] = [$path, 'overlay', ['ro'], array_replace($evidence, [$key => false]), false];
        }
        foreach (['ino' => 74, 'dev' => 43] as $key => $value) {
            $cases['replacement '.$key] = [$path, 'overlay', ['ro'], array_replace($evidence,
                ['process' => array_replace($stat, [$key => $value])]), false];
        }
        foreach (['writable file' => ['mode' => 0100777], 'directory' => ['mode' => 0040755],
            'non-executable' => ['mode' => 0100644], 'non-root owner' => ['uid' => 33]] as $name => $change) {
            $cases[$name] = [$path, 'overlay', ['ro'], array_replace($evidence,
                ['file' => array_replace($stat, $change)]), false];
        }
        $cases['wrong process'] = [$path, 'overlay', ['ro'], array_replace($evidence, ['comm' => 'php']), false];
        $cases['unavailable stat'] = [$path, 'overlay', ['ro'], array_replace($evidence, ['process' => false]), false];

        return $cases;
    }
}
