<?php

declare(strict_types=1);

// Offline fixture checks only. Do not load Composer, Laravel or PHPUnit.
require dirname(__DIR__).'/tests/Support/DisposableTestEnvironment.php';
require dirname(__DIR__).'/tests/Support/DockerInitMountCases.php';

$cases = \Tests\Support\DockerInitMountCases::cases();
foreach ($cases as $name => [$mount, $type, $options, $evidence, $expected]) {
    $accepted = true;
    try {
        \Tests\Support\DisposableTestEnvironment::assertDockerInitMount($mount, $type, $options, $evidence);
    } catch (RuntimeException) {
        $accepted = false;
    }
    if ($accepted !== $expected) {
        fwrite(STDERR, 'Init policy fixture failed: '.$name.PHP_EOL);
        exit(1);
    }
}
// Verify the init exception does not admit overlays elsewhere in the mount table.
foreach (['/app', '/app/tests', '/usr/other', '/sbin/other', '/run/docker.sock'] as $point) {
    $rejected = false;
    try {
        \Tests\Support\DisposableTestEnvironment::assertMounts([
            '1 0 0:1 / / rw - overlay overlay rw',
            '2 1 0:2 / '.$point.' ro - overlay overlay rw',
        ]);
    } catch (RuntimeException) {
        $rejected = true;
    }
    if (! $rejected) {
        fwrite(STDERR, 'Unexpected overlay admitted by mount policy.'.PHP_EOL);
        exit(1);
    }
}
fwrite(STDOUT, count($cases).' offline init policy fixtures passed.'.PHP_EOL);
fwrite(STDOUT, '5 forbidden overlay mount fixtures passed.'.PHP_EOL);
