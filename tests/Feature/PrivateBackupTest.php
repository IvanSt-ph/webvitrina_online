<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Services\BackupStorageService;
use App\Support\BackupHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class PrivateBackupTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/private-backup-' . uniqid());
        File::ensureDirectoryExists($this->root);
    }

    protected function tearDown(): void
    {
        Storage::forgetDisk('local');
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_backup_contains_public_and_private_chat_files_and_restores_controlled_access(): void
    {
        $publicRoot = $this->root . DIRECTORY_SEPARATOR . 'public';
        $privateRoot = $this->root . DIRECTORY_SEPARATOR . 'private';
        $backupRoot = $privateRoot . DIRECTORY_SEPARATOR . 'backups';
        File::ensureDirectoryExists($publicRoot . DIRECTORY_SEPARATOR . 'products');
        File::ensureDirectoryExists($privateRoot . DIRECTORY_SEPARATOR . 'chat-images' . DIRECTORY_SEPARATOR . '2026/09');
        File::ensureDirectoryExists($backupRoot . DIRECTORY_SEPARATOR . 'existing');
        file_put_contents($publicRoot . DIRECTORY_SEPARATOR . 'products/item.webp', 'public-image');
        file_put_contents($privateRoot . DIRECTORY_SEPARATOR . 'chat-images/2026/09/chat.webp', 'private-image');
        file_put_contents($backupRoot . DIRECTORY_SEPARATOR . 'existing/must-not-be-archived.txt', 'backup-data');

        config()->set([
            'filesystems.disks.public.root' => $publicRoot,
            'filesystems.disks.local.root' => $privateRoot,
        ]);
        // Keep the command's actual error in PHPUnit output instead of only its exit code.
        $output = new BufferedOutput();
        $exitCode = Artisan::call('backup:run', ['--path' => $backupRoot, '--keep-days' => 14], $output);
        $this->assertSame(0, $exitCode, $output->fetch());
        $backup = collect(File::directories($backupRoot))
            ->first(fn ($directory) => basename($directory) !== 'existing');
        $this->assertNotNull($backup);
        $health = BackupHealth::inspectDirectory($backup);

        $this->assertTrue($health['ok'], implode(' ', $health['issues']));
        $this->assertSame(1, $health['manifest']['storage']['public']['files']);
        $this->assertSame(1, $health['manifest']['storage']['private_chat_images']['files']);
        $this->assertStringContainsString('storage-private-chat-images.tar.gz', file_get_contents($backup . DIRECTORY_SEPARATOR . 'SHA256SUMS'));

        $publicArchive = new \PharData($backup . DIRECTORY_SEPARATOR . 'storage-public.tar.gz');
        $privateArchive = new \PharData($backup . DIRECTORY_SEPARATOR . 'storage-private-chat-images.tar.gz');
        $this->assertSame('public-image', $publicArchive['public/products/item.webp']->getContent());
        $this->assertSame('private-image', $privateArchive['private/chat-images/2026/09/chat.webp']->getContent());
        $this->assertFalse(isset($privateArchive['private/backups/existing/must-not-be-archived.txt']));
        unset($publicArchive, $privateArchive);

        File::deleteDirectory($publicRoot);
        File::deleteDirectory($privateRoot . DIRECTORY_SEPARATOR . 'chat-images');
        file_put_contents($backup . DIRECTORY_SEPARATOR . 'must-not-be-archived.txt', 'backup-still-present');

        app(BackupStorageService::class)->restore($backup, $publicRoot, $privateRoot);

        $this->assertSame('public-image', file_get_contents($publicRoot . DIRECTORY_SEPARATOR . 'products/item.webp'));
        $restoredPath = $privateRoot . DIRECTORY_SEPARATOR . 'chat-images/2026/09/chat.webp';
        $this->assertSame('private-image', file_get_contents($restoredPath));
        $this->assertFileExists($backup . DIRECTORY_SEPARATOR . 'must-not-be-archived.txt');

        config()->set('filesystems.disks.local.root', $privateRoot);
        Storage::forgetDisk('local');
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $outsider = User::factory()->create(['role' => 'buyer']);
        $conversation = Conversation::create(['buyer_id' => $buyer->id, 'seller_id' => $seller->id]);
        $message = $conversation->messages()->create([
            'sender_id' => $buyer->id,
            'body' => '',
            'image_path' => 'chat-images/2026/09/chat.webp',
        ]);

        $response = $this->actingAs($buyer)
            ->get(route('chats.messages.image', [$conversation, $message]))
            ->assertOk();
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->actingAs($outsider)
            ->get(route('chats.messages.image', [$conversation, $message]))
            ->assertNotFound();
    }

    public function test_missing_or_corrupted_private_archive_and_legacy_backup_are_rejected(): void
    {
        $publicRoot = $this->root . DIRECTORY_SEPARATOR . 'public';
        $privateRoot = $this->root . DIRECTORY_SEPARATOR . 'private';
        $backup = $this->root . DIRECTORY_SEPARATOR . 'backups/complete';
        File::ensureDirectoryExists($publicRoot);
        File::ensureDirectoryExists($privateRoot . DIRECTORY_SEPARATOR . 'chat-images');
        File::ensureDirectoryExists($backup);
        file_put_contents($privateRoot . DIRECTORY_SEPARATOR . 'chat-images/chat.webp', 'private-image');
        $this->createCompleteBackup($backup, $publicRoot, $privateRoot);

        file_put_contents($backup . DIRECTORY_SEPARATOR . 'storage-private-chat-images.tar.gz', 'corrupted');
        $health = BackupHealth::inspectDirectory($backup);
        $this->assertFalse($health['ok']);
        $this->assertStringContainsString('Checksum не совпадает', implode(' ', $health['issues']));

        unlink($backup . DIRECTORY_SEPARATOR . 'storage-private-chat-images.tar.gz');
        $health = BackupHealth::inspectDirectory($backup);
        $this->assertFalse($health['ok']);
        $this->assertStringContainsString('Нет файла storage-private-chat-images.tar.gz', implode(' ', $health['issues']));

        file_put_contents($backup . DIRECTORY_SEPARATOR . 'manifest.json', json_encode(['version' => 1]));
        $health = BackupHealth::inspectDirectory($backup, false);
        $this->assertFalse($health['ok']);
        $this->assertStringContainsString('Старый backup', implode(' ', $health['issues']));
    }

    public function test_restore_failure_does_not_replace_existing_storage_or_report_success(): void
    {
        $publicRoot = $this->root . DIRECTORY_SEPARATOR . 'public';
        $privateRoot = $this->root . DIRECTORY_SEPARATOR . 'private';
        $backup = $this->root . DIRECTORY_SEPARATOR . 'backups/complete';
        File::ensureDirectoryExists($publicRoot);
        File::ensureDirectoryExists($privateRoot . DIRECTORY_SEPARATOR . 'chat-images');
        File::ensureDirectoryExists($backup);
        file_put_contents($publicRoot . DIRECTORY_SEPARATOR . 'current.txt', 'current-public');
        file_put_contents($privateRoot . DIRECTORY_SEPARATOR . 'chat-images/current.webp', 'current-private');
        $this->createCompleteBackup($backup, $publicRoot, $privateRoot);

        file_put_contents($backup . DIRECTORY_SEPARATOR . 'storage-private-chat-images.tar.gz', 'corrupted');

        config()->set([
            'filesystems.disks.public.root' => $publicRoot,
            'filesystems.disks.local.root' => $privateRoot,
        ]);
        $this->artisan('backup:restore-files', ['backup' => $backup, '--force' => true])
            ->assertFailed();

        try {
            app(BackupStorageService::class)->restore($backup, $publicRoot, $privateRoot);
            $this->fail('Corrupted restore was reported as successful.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('неполный или повреждён', $exception->getMessage());
        }

        $this->assertSame('current-public', file_get_contents($publicRoot . DIRECTORY_SEPARATOR . 'current.txt'));
        $this->assertSame('current-private', file_get_contents($privateRoot . DIRECTORY_SEPARATOR . 'chat-images/current.webp'));
    }

    private function createCompleteBackup(string $backup, string $publicRoot, string $privateRoot): void
    {
        $storage = app(BackupStorageService::class);
        $publicStats = $storage->archiveDirectory(
            $publicRoot,
            'public',
            $backup . DIRECTORY_SEPARATOR . 'storage-public.tar',
            $backup . DIRECTORY_SEPARATOR . 'storage-public.tar.gz',
        );
        $privateStats = $storage->archiveDirectory(
            $privateRoot . DIRECTORY_SEPARATOR . 'chat-images',
            'private/chat-images',
            $backup . DIRECTORY_SEPARATOR . 'storage-private-chat-images.tar',
            $backup . DIRECTORY_SEPARATOR . 'storage-private-chat-images.tar.gz',
        );

        file_put_contents($backup . DIRECTORY_SEPARATOR . 'database.sql.gz', 'database');
        file_put_contents($backup . DIRECTORY_SEPARATOR . 'manifest.json', json_encode([
            'version' => 2,
            'storage' => [
                'public' => ['archive' => 'storage-public.tar.gz', 'root' => 'public', ...$publicStats],
                'private_chat_images' => [
                    'archive' => 'storage-private-chat-images.tar.gz',
                    'root' => 'private/chat-images',
                    ...$privateStats,
                ],
            ],
        ]));

        $checksummed = [
            'database.sql.gz',
            'storage-public.tar.gz',
            'storage-private-chat-images.tar.gz',
            'manifest.json',
        ];
        $lines = array_map(
            fn ($file) => hash_file('sha256', $backup . DIRECTORY_SEPARATOR . $file) . '  ' . $file,
            $checksummed,
        );
        file_put_contents($backup . DIRECTORY_SEPARATOR . 'SHA256SUMS', implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
