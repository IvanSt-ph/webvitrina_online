<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ShopBannerLifecycleTest extends TestCase
{
    use DatabaseTruncation;

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // afterCommit cleanup must be observed after a real database commit.
        $this->beforeApplicationDestroyed(function (): void {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_successful_shop_banner_replacement_commits_new_file_before_removing_old_file(): void
    {
        Storage::fake('public');
        [$seller, $shop, $oldPath] = $this->sellerWithBanner();

        $this->actingAs($seller)
            ->patch(route('profile.shop.update'), [
                'name' => 'Updated shop',
                'banner' => UploadedFile::fake()->image('new-banner.jpg', 1200, 600),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $newPath = $shop->fresh()->banner;

        $this->assertNotNull($newPath);
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertExists(\App\Services\ImageService::thumbPath($newPath));
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertMissing(\App\Services\ImageService::thumbPath($oldPath));
    }

    public function test_failed_shop_save_rolls_back_new_banner_and_preserves_old_file(): void
    {
        Storage::fake('public');
        [$seller, $shop, $oldPath] = $this->sellerWithBanner();
        $event = 'eloquent.updating: '.Shop::class;

        Event::listen($event, static function (): never {
            throw new RuntimeException('forced shop save failure');
        });

        try {
            $this->withoutExceptionHandling()
                ->actingAs($seller)
                ->patch(route('profile.shop.update'), [
                    'name' => 'Must roll back',
                    'banner' => UploadedFile::fake()->image('failed-banner.jpg', 1200, 600),
                ]);

            $this->fail('The forced shop save failure should be propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('forced shop save failure', $exception->getMessage());
        } finally {
            Event::forget($event);
        }

        $shop->refresh();
        $this->assertSame($oldPath, $shop->banner);
        $this->assertSame('Original shop', $shop->name);
        Storage::disk('public')->assertExists($oldPath);
        Storage::disk('public')->assertExists(\App\Services\ImageService::thumbPath($oldPath));
        $this->assertSame(
            [$oldPath, \App\Services\ImageService::thumbPath($oldPath)],
            collect(Storage::disk('public')->allFiles('banners'))->sort()->values()->all(),
        );
    }

    /** @return array{User, Shop, string} */
    private function sellerWithBanner(): array
    {
        $seller = User::factory()->seller()->create(['email_verified_at' => now()]);
        $oldPath = 'banners/medium/original.webp';
        Storage::disk('public')->put($oldPath, 'old medium');
        Storage::disk('public')->put(\App\Services\ImageService::thumbPath($oldPath), 'old thumb');
        $shop = $seller->shop()->create([
            'name' => 'Original shop',
            'banner' => $oldPath,
        ]);

        return [$seller, $shop, $oldPath];
    }
}
