<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Banner;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Repositories\ProductCrudRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_update_order_status_through_admin_route(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);

        $order = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-TEST-1',
            'status' => Order::STATUS_PENDING,
            'total_price' => 100,
            'currency' => 'RUB',
        ]);

        $this->actingAs($buyer)
            ->postJson(route('admin.orders.updateStatus', $order), ['status' => Order::STATUS_PAID])
            ->assertForbidden();

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
    }

    public function test_quick_checkout_rejects_invalid_quantity(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->postJson(route('checkout.quick', $product), ['qty' => -100])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');
    }

    public function test_checkout_rejects_unknown_payment_and_delivery_methods(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->withSession([
                'checkout_cart' => [[
                    'cart_id' => null,
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'price' => 100,
                    'qty' => 1,
                    'image' => $product->image,
                ]],
            ])
            ->postJson(route('checkout.create'), [
                'payment_method' => 'lol',
                'delivery_method' => 'free_delivery',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_method', 'delivery_method']);
    }

    public function test_buyer_cannot_create_or_update_shop_profile(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)
            ->patchJson(route('profile.shop.update'), [
                'name' => 'Buyer shop',
                'city' => 'Test city',
                'description' => 'Should not be created',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('shops', [
            'user_id' => $buyer->id,
            'name' => 'Buyer shop',
        ]);
    }

    public function test_seller_cannot_delete_gallery_image_from_another_product(): void
    {
        Storage::fake('public');

        $seller = User::factory()->create(['role' => 'seller']);
        $otherSeller = User::factory()->create(['role' => 'seller']);

        $ownImage = 'products/gallery/own.jpg';
        $otherImage = 'products/gallery/other.jpg';

        Storage::disk('public')->put($ownImage, 'own image');
        Storage::disk('public')->put($otherImage, 'other image');

        $ownProduct = $this->createProduct($seller, ['gallery' => [$ownImage]]);
        $otherProduct = $this->createProduct($otherSeller, ['gallery' => [$otherImage]]);

        $this->actingAs($seller)
            ->deleteJson(route('seller.products.gallery.delete', $ownProduct), [
                'path' => $otherImage,
            ])
            ->assertForbidden();

        Storage::disk('public')->assertExists($otherImage);
        $this->assertSame([$otherImage], $otherProduct->fresh()->gallery);
    }

    public function test_draft_product_is_not_publicly_viewable(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['status' => 'draft']);

        $this->get(route('product.show', $product->slug))
            ->assertNotFound();
    }

    public function test_product_page_cache_is_cleared_when_product_becomes_draft(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['status' => 'active']);

        Cache::put("product_page:{$product->slug}", $product, 600);

        app(ProductCrudRepository::class)->update($product, ['status' => 'draft']);

        $this->assertFalse(Cache::has("product_page:{$product->slug}"));

        $this->get(route('product.show', $product->slug))
            ->assertNotFound();
    }

    public function test_draft_product_cannot_be_added_to_cart_or_quick_checkout(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, ['status' => 'draft']);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $product), ['qty' => 1])
            ->assertNotFound();

        $this->actingAs($buyer)
            ->postJson(route('checkout.quick', $product), ['qty' => 1])
            ->assertNotFound();
    }

    public function test_draft_product_cannot_be_favorited_or_shown_on_public_seller_page(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => 'Public seller shop']);
        $draft = $this->createProduct($seller, [
            'title' => 'Hidden draft product',
            'status' => 'draft',
        ]);
        $active = $this->createProduct($seller, [
            'title' => 'Visible active product',
            'status' => 'active',
        ]);

        $this->actingAs($buyer)
            ->postJson(route('favorites.toggle', $draft))
            ->assertNotFound();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $buyer->id,
            'product_id' => $draft->id,
        ]);

        $this->get(route('seller.show', $seller))
            ->assertOk()
            ->assertSee($active->title)
            ->assertDontSee($draft->title);
    }

    public function test_seller_can_create_active_product(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $payload = $this->validSellerProductPayload([
            'status' => 'active',
        ]);

        $this->actingAs($seller)
            ->post(route('seller.products.store'), $payload)
            ->assertRedirect(route('seller.products.index'));

        $this->assertDatabaseHas('products', [
            'user_id' => $seller->id,
            'title' => $payload['title'],
            'status' => 'active',
        ]);
    }

    public function test_cart_rejects_quantity_above_product_stock(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'stock' => 2,
            'status' => 'active',
        ]);

        $this->actingAs($buyer)
            ->postJson(route('cart.add', $product), ['qty' => 3])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_checkout_rejects_cart_quantity_above_current_stock(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'stock' => 1,
            'status' => 'active',
        ]);

        $cartItem = CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'qty' => 2,
        ]);

        $this->actingAs($buyer)
            ->post(route('checkout.prepare'), [
                'selected_items' => [$cartItem->id],
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('orders', [
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);
    }

    public function test_checkout_decrements_stock_when_order_is_created(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'stock' => 3,
            'status' => 'active',
        ]);

        $this->actingAs($buyer)
            ->withSession([
                'checkout_cart' => [[
                    'cart_id' => null,
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'price' => 100,
                    'qty' => 2,
                    'image' => $product->image,
                ]],
            ])
            ->post(route('checkout.create'), [
                'payment_method' => 'cash',
                'delivery_method' => 'pickup',
            ])
            ->assertRedirect();

        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_phone_verification_routes_require_authentication(): void
    {
        $this->postJson(route('phone.send'))
            ->assertUnauthorized();

        $this->postJson(route('phone.verify'), ['code' => '123456'])
            ->assertUnauthorized();
    }

    public function test_private_local_storage_routes_are_not_registered_by_default(): void
    {
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));
    }

    public function test_web_responses_include_security_headers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('x-frame-options', 'SAMEORIGIN')
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('referrer-policy', 'strict-origin-when-cross-origin')
            ->assertHeader('permissions-policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader(
                'content-security-policy',
                "default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; img-src 'self' data: https:; font-src 'self' data: https://fonts.bunny.net https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdn.jsdelivr.net https://unpkg.com; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com; connect-src 'self' https://nominatim.openstreetmap.org"
            );
    }

    public function test_currency_proxy_returns_json_not_external_html(): void
    {
        Http::fake([
            'www.agroprombank.com/*' => Http::response('<html><script>alert(1)</script> UAH 0.3650 / 0.4000 MDL 0.9500 / 1.0600</html>'),
        ]);

        $this->getJson('/internal/currency/agroprombank')
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('rates.PRB.PRB', 1)
            ->assertJsonMissing(['script' => 'alert(1)']);
    }

    public function test_currency_switch_rejects_unknown_currency(): void
    {
        $this->postJson(route('currency.set'), ['currency' => '<script>'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency');
    }

    public function test_admin_category_rejects_svg_icon_upload(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.categories.store'), [
                'name' => 'Unsafe icon category',
                'slug' => 'unsafe-icon-category',
                'icon' => UploadedFile::fake()->create('icon.svg', 1, 'image/svg+xml'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('icon');

        $this->assertDatabaseMissing('categories', [
            'slug' => 'unsafe-icon-category',
        ]);
    }

    public function test_admin_category_images_are_converted_to_webp(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Optimized category',
                'slug' => 'optimized-category',
                'icon' => UploadedFile::fake()->image('icon.png', 512, 512)->size(800),
                'image' => UploadedFile::fake()->image('tile.jpg', 1200, 800)->size(1800),
            ])
            ->assertRedirect(route('admin.categories.index'));

        $category = Category::where('slug', 'optimized-category')->firstOrFail();

        $this->assertStringStartsWith('categories/icons/', $category->icon);
        $this->assertStringEndsWith('.webp', $category->icon);
        $this->assertStringStartsWith('categories/thumbs/', $category->image);
        $this->assertStringEndsWith('.webp', $category->image);
        Storage::disk('public')->assertExists($category->icon);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_admin_category_update_removes_previous_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $oldIcon = 'categories/icons/old.webp';
        $oldImage = 'categories/thumbs/old.webp';
        Storage::disk('public')->put($oldIcon, 'old icon');
        Storage::disk('public')->put($oldImage, 'old image');

        $category = Category::factory()->create([
            'icon' => $oldIcon,
            'image' => $oldImage,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => UploadedFile::fake()->image('new-icon.png', 512, 512)->size(800),
                'image' => UploadedFile::fake()->image('new-tile.jpg', 1200, 800)->size(1800),
            ])
            ->assertRedirect(route('admin.categories.index'));

        $category->refresh();

        Storage::disk('public')->assertMissing($oldIcon);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($category->icon);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_admin_category_cannot_be_moved_under_itself_or_descendant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->actingAs($admin)
            ->putJson(route('admin.categories.update', $parent), [
                'name' => $parent->name,
                'slug' => $parent->slug,
                'parent_id' => $child->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_admin_category_attribute_changes_clear_filter_cache(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        Cache::put("cat.filters.{$category->id}", collect(['stale']), 3600);

        $this->actingAs($admin)
            ->post(route('admin.categories.attributes.store', $category), [
                'name' => 'Filterable attribute',
                'type' => 'select',
                'options' => 'one,two',
            ])
            ->assertRedirect();

        $this->assertFalse(Cache::has("cat.filters.{$category->id}"));
    }

    public function test_user_cannot_review_product_without_completed_purchase(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);

        $this->actingAs($buyer)
            ->postJson(route('review.store', $product), [
                'rating' => 5,
                'body' => 'Fake review',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('review');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_user_can_review_product_after_delivery(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_DELIVERED);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($buyer)
            ->post(route('review.store', $product), [
                'rating' => 5,
                'body' => 'Real review',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'status' => Review::STATUS_PENDING,
        ]);
    }

    public function test_review_images_are_converted_to_webp_with_thumbnails(): void
    {
        Storage::fake('public');

        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_DELIVERED);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($buyer)
            ->post(route('review.store', $product), [
                'rating' => 5,
                'body' => 'Review with optimized image',
                'images' => [
                    UploadedFile::fake()->image('review.jpg', 1600, 1200)->size(1200),
                ],
            ])
            ->assertRedirect();

        $review = Review::where('user_id', $buyer->id)
            ->where('product_id', $product->id)
            ->firstOrFail();
        $image = $review->images()->firstOrFail();

        $this->assertStringContainsString('/medium/', $image->path);
        $this->assertStringEndsWith('.webp', $image->path);

        Storage::disk('public')->assertExists($image->path);
        Storage::disk('public')->assertExists(\App\Services\ImageService::thumbPath($image->path));
    }

    public function test_review_rejects_svg_upload(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_DELIVERED);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);

        $this->actingAs($buyer)
            ->postJson(route('review.store', $product), [
                'rating' => 5,
                'body' => 'Unsafe image',
                'images' => [
                    UploadedFile::fake()->create('review.svg', 1, 'image/svg+xml'),
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('images.0');

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_deleting_review_removes_optimized_images(): void
    {
        Storage::fake('public');

        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller);
        $review = Review::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'rating' => 5,
            'body' => 'Review to delete',
            'status' => Review::STATUS_APPROVED,
        ]);

        $path = 'reviews/2026/05/medium/review.webp';
        $thumb = \App\Services\ImageService::thumbPath($path);
        Storage::disk('public')->put($path, 'medium');
        Storage::disk('public')->put($thumb, 'thumb');
        $review->images()->create(['path' => $path]);

        $review->delete();

        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing($thumb);
    }

    public function test_seller_cannot_cancel_order_after_shipping(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $order = $this->createOrder($buyer, $seller, Order::STATUS_SHIPPED);

        $this->actingAs($seller)
            ->post(route('seller.orders.updateStatus', $order), [
                'status' => Order::STATUS_CANCELED,
            ])
            ->assertSessionHas('error');

        $this->assertSame(Order::STATUS_SHIPPED, $order->fresh()->status);
    }

    public function test_last_admin_cannot_demote_or_delete_self(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patchJson(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'role' => 'buyer',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertSame('admin', $admin->fresh()->role);

        $this->actingAs($admin)
            ->deleteJson(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);
    }

    public function test_admin_banner_rejects_javascript_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.banners.store'), [
                'title' => 'Unsafe banner',
                'link' => 'javascript:alert(1)',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('link');

        $this->assertDatabaseMissing('banners', [
            'title' => 'Unsafe banner',
        ]);
    }

    public function test_admin_banner_create_page_can_be_rendered(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.banners.create'))
            ->assertOk()
            ->assertSee('Новый баннер')
            ->assertSee('Создать баннер');
    }

    public function test_admin_banner_rejects_svg_upload(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('admin.banners.store'), [
                'title' => 'Unsafe banner image',
                'image_desktop' => UploadedFile::fake()->create('banner.svg', 1, 'image/svg+xml'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image_desktop');

        $this->assertDatabaseMissing('banners', [
            'title' => 'Unsafe banner image',
        ]);
    }

    public function test_admin_banner_images_are_converted_to_webp(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.banners.store'), [
                'title' => 'Optimized banner',
                'active' => '1',
                'image_desktop' => UploadedFile::fake()->image('desktop.jpg', 2400, 800)->size(2500),
            ])
            ->assertRedirect(route('admin.banners.index'));

        $banner = Banner::where('title', 'Optimized banner')->firstOrFail();

        $this->assertStringStartsWith('banners/desktop/', $banner->image_desktop);
        $this->assertStringEndsWith('.webp', $banner->image_desktop);
        Storage::disk('public')->assertExists($banner->image_desktop);

        $image = (new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver()))
            ->read(Storage::disk('public')->path($banner->image_desktop));

        $this->assertSame(1920, $image->width());
        $this->assertSame(720, $image->height());
    }

    public function test_admin_banner_single_source_creates_all_device_images(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.banners.store'), [
                'title' => 'Single source banner',
                'active' => '1',
                'crop_x' => '10',
                'crop_y' => '10',
                'crop_w' => '80',
                'crop_h' => '70',
                'image_source' => UploadedFile::fake()->image('hero.jpg', 2400, 1200)->size(2500),
            ])
            ->assertRedirect(route('admin.banners.index'));

        $banner = Banner::where('title', 'Single source banner')->firstOrFail();

        foreach (['desktop', 'tablet', 'mobile'] as $device) {
            $path = $banner->{"image_{$device}"};
            $this->assertStringStartsWith("banners/{$device}/", $path);
            $this->assertStringEndsWith('.webp', $path);
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_admin_banner_mobile_upload_overrides_single_source_mobile_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.banners.store'), [
                'title' => 'Banner with mobile override',
                'active' => '1',
                'image_source' => UploadedFile::fake()->image('hero.jpg', 2400, 1200)->size(2500),
                'mobile_crop_x' => '20',
                'mobile_crop_y' => '10',
                'mobile_crop_w' => '60',
                'mobile_crop_h' => '80',
                'image_mobile' => UploadedFile::fake()->image('mobile.jpg', 2400, 1600)->size(1200),
            ])
            ->assertRedirect(route('admin.banners.index'));

        $banner = Banner::where('title', 'Banner with mobile override')->firstOrFail();

        $this->assertStringStartsWith('banners/desktop/', $banner->image_desktop);
        $this->assertStringStartsWith('banners/tablet/', $banner->image_tablet);
        $this->assertStringStartsWith('banners/mobile/', $banner->image_mobile);
        Storage::disk('public')->assertExists($banner->image_mobile);

        $image = (new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver()))
            ->read(Storage::disk('public')->path($banner->image_mobile));

        $this->assertSame(960, $image->width());
        $this->assertSame(480, $image->height());
    }

    public function test_admin_banner_update_removes_previous_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $oldPath = 'banners/desktop/old.webp';
        Storage::disk('public')->put($oldPath, 'old banner');

        $banner = Banner::create([
            'title' => 'Banner with old image',
            'image_desktop' => $oldPath,
            'active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.banners.update', $banner), [
                'title' => 'Banner with new image',
                'active' => '1',
                'image_desktop' => UploadedFile::fake()->image('new.jpg', 2400, 800)->size(2500),
            ])
            ->assertRedirect(route('admin.banners.index'));

        $banner->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        $this->assertStringStartsWith('banners/desktop/', $banner->image_desktop);
        $this->assertStringEndsWith('.webp', $banner->image_desktop);
        Storage::disk('public')->assertExists($banner->image_desktop);
    }

    public function test_seller_shop_rejects_javascript_social_link(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)
            ->patchJson(route('profile.shop.update'), [
                'name' => 'Seller shop',
                'facebook' => 'javascript:alert(1)',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('facebook');
    }

    public function test_seller_shop_rejects_svg_banner_upload(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $this->actingAs($seller)
            ->patchJson(route('profile.shop.update'), [
                'name' => 'Seller shop',
                'banner' => UploadedFile::fake()->create('banner.svg', 1, 'image/svg+xml'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('banner');
    }

    public function test_admin_cannot_update_attribute_through_unrelated_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = \App\Models\Category::factory()->create();
        $otherCategory = \App\Models\Category::factory()->create();
        $attribute = \App\Models\Attribute::create([
            'name' => 'Size',
            'type' => 'select',
            'options' => ['S', 'M'],
        ]);

        $category->attributes()->attach($attribute->id);

        $this->actingAs($admin)
            ->putJson(route('admin.categories.attributes.update', [$otherCategory, $attribute]), [
                'name' => 'Hacked size',
                'type' => 'select',
                'options' => 'L,XL',
            ])
            ->assertNotFound();

        $this->assertSame('Size', $attribute->fresh()->name);
    }

    public function test_admin_detaching_shared_attribute_keeps_color_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = \App\Models\Category::factory()->create();
        $otherCategory = \App\Models\Category::factory()->create();
        $color = \App\Models\Color::create(['name' => 'Red', 'hex' => '#ff0000']);
        $attribute = \App\Models\Attribute::create([
            'name' => 'Color',
            'type' => 'color',
            'options' => null,
        ]);

        $category->attributes()->attach($attribute->id);
        $otherCategory->attributes()->attach($attribute->id);
        $attribute->colors()->attach($color->id);

        $this->actingAs($admin)
            ->delete(route('admin.categories.attributes.destroy', [$category, $attribute]))
            ->assertRedirect();

        $this->assertDatabaseHas('attribute_category', [
            'category_id' => $otherCategory->id,
            'attribute_id' => $attribute->id,
        ]);
        $this->assertDatabaseHas('attribute_color', [
            'attribute_id' => $attribute->id,
            'color_id' => $color->id,
        ]);
    }

    public function test_currency_switcher_stores_supported_currency_in_session(): void
    {
        $this->postJson(route('currency.set'), ['currency' => 'RUB'])
            ->assertOk()
            ->assertJson(['currency' => 'PRB'])
            ->assertSessionHas('currency', 'PRB');

        $this->post(route('currency.set'), ['currency' => 'UAH'])
            ->assertRedirect()
            ->assertSessionHas('currency', 'UAH');

        $this->postJson(route('currency.set'), ['currency' => 'EUR'])
            ->assertUnprocessable();
    }

    public function test_product_price_uses_selected_session_currency(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $product = $this->createProduct($seller, [
            'price' => 200,
            'currency_base' => 'RUB',
            'price_prb' => 200,
            'price_mdl' => 220,
            'price_uah' => 580,
        ]);

        session(['currency' => 'UAH']);

        $price = $product->price_for_current_currency;

        $this->assertSame(580.0, $price['amount']);
        $this->assertSame('UAH', $price['code']);
        $this->assertSame('₴', $price['symbol']);
    }

    private function createProduct(User $seller, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $seller->id,
            'title' => 'Security test product ' . uniqid(),
            'sku' => 'TEST-' . uniqid(),
            'price' => 100,
            'currency_base' => 'MDL',
            'price_prb' => 100,
            'price_mdl' => 100,
            'price_uah' => 100,
            'stock' => 10,
            'image' => 'default/no-image.png',
            'description' => 'Test product',
            'status' => 'active',
        ], $overrides));
    }

    private function createOrder(User $buyer, User $seller, string $status = Order::STATUS_PENDING): Order
    {
        return Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => 'ORD-SEC-' . uniqid(),
            'status' => $status,
            'total_price' => 100,
            'currency' => 'RUB',
        ]);
    }

    private function validSellerProductPayload(array $overrides = []): array
    {
        $category = \App\Models\Category::factory()->create();
        $country = \App\Models\Country::create(['name' => 'Security country ' . uniqid()]);
        $city = \App\Models\City::create([
            'country_id' => $country->id,
            'name' => 'Security city ' . uniqid(),
        ]);

        return array_merge([
            'title' => 'Seller status product ' . uniqid(),
            'price' => 100,
            'stock' => 5,
            'description' => 'Valid product',
            'category_id' => $category->id,
            'country_id' => $country->id,
            'city_id' => $city->id,
            'status' => 'draft',
        ], $overrides);
    }
}
