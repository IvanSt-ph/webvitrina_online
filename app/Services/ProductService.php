<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSlug;
use App\Models\User;
use App\Rules\ImageUploadConstraints;
use App\Repositories\ProductCrudRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductService
{

    public function __construct(
        protected ProductCrudRepository $repo,
        protected ImageService $images,
        protected AttributeService $attributes,
    ) {}

    /* ============================================================
     |  СОЗДАНИЕ ТОВАРА
     ============================================================ */
    public function create(array $data, ?UploadedFile $image = null, array $gallery = [], array $attrs = [], string $quotaErrorField = 'user_id'): Product
    {
        return app(BackupWriteBarrier::class)->transaction(function () use ($data, $image, $gallery, $attrs, $quotaErrorField) {
            // Match account deletion/checkout order: User before Product rows.
            // Every creation path (including admin) shares the seller's mutex.
            $seller = User::whereKey($data['user_id'] ?? null)->lockForUpdate()->firstOrFail();
            app(SellerPlanService::class)->assertCanCreateProduct($seller, $quotaErrorField);
            $files = new ProductImageOperation($this->images, DB::connection());

            /* ---------- 1. Подготовка данных ---------- */
            $payload = $this->prepareData($data);

            /* ---------- 2. Генерация SKU ---------- */
            $payload['sku'] = $payload['sku'] ?? $this->generateSku();

            /* ---------- 3. Загрузка главного фото ---------- */
            if ($image) {
                $payload['image'] = $files->upload($image, 'products/' . date('Y/m'));
            }
            // Если нет изображения - оставляем null (НЕ сохраняем путь к no-image.png)

            /* ---------- 4. Создание товара ---------- */
            $product = $this->repo->create($payload);

            /* ---------- 5. Галерея ---------- */
            if ($gallery) {
                $this->appendGallery($product, $gallery, $files);
            }

            /* ---------- 6. Атрибуты ---------- */
            if ($attrs) {
                $this->attributes->sync($product, $attrs);
            }

            return $product;
        });
    }

    /* ============================================================
     |  ОБНОВЛЕНИЕ ТОВАРА
     ============================================================ */
    public function update(Product $product, array $data, ?UploadedFile $image = null, array $galleryNew = [], array $galleryToDelete = [], array $attrs = [], ?User $actor = null): Product
    {
        return app(BackupWriteBarrier::class)->transaction(function () use ($product, $data, $image, $galleryNew, $galleryToDelete, $attrs, $actor) {
            $payload = $this->prepareData($data, updating: true);
            $owners = null;
            if (isset($payload['user_id'])) {
                // Discover persisted ownership, not the possibly stale route model.
                $sourceId = Product::whereKey($product->id)->value('user_id');
                $ownerIds = array_unique([(int) $sourceId, (int) $payload['user_id']]);
                // Both sides participate: opposing transfers must never lock Products
                // and only then wait for each other's seller/catalog locks.
                $owners = User::withTrashed()->whereIn('id', $ownerIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            }
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $this->authorizeMutation($actor, 'update', $product);
            if (array_key_exists('stock', $payload)) {
                $originalStock = filter_var($data['original_stock'] ?? null, FILTER_VALIDATE_INT);
                if ($originalStock === false || $originalStock < 0) {
                    throw ValidationException::withMessages([
                        'stock' => 'Обновите страницу товара перед изменением остатка.',
                    ]);
                }
                if ((int) $payload['stock'] === $originalStock) {
                    // An untouched form field must not undo checkout/restocking.
                    unset($payload['stock']);
                } elseif ((int) $product->stock !== $originalStock) {
                    throw ValidationException::withMessages([
                        'stock' => 'Остаток товара изменился после открытия формы. Обновите страницу и повторите изменение остатка.',
                    ]);
                }
            }
            if ($owners !== null) {
                // Do not acquire a newly discovered User lock after locking Product.
                abort_unless($owners->has((int) $product->user_id), 409,
                    'Владелец товара изменился. Обновите страницу и повторите действие.');
                if ((int) $payload['user_id'] !== (int) $product->user_id) {
                    $recipient = $owners->get((int) $payload['user_id']);
                    abort_unless($recipient && ! $recipient->trashed(), 404);
                    app(SellerPlanService::class)->assertCanCreateProduct($recipient);
                }
            }
            // Sellers may correct a blocked card, but only admins may republish it.
            if ($actor !== null && $actor->role !== 'admin' && $product->isBlocked()) {
                $payload['status'] = Product::STATUS_BLOCKED;
            }
            $files = new ProductImageOperation($this->images, DB::connection());

            /* ---------- 2. Сохранение старого slug ---------- */
            $this->handleSlugHistory($product, $payload);

            /* ---------- 3. Обновление главного фото ---------- */
            if ($image) {
                $newImagePath = $files->upload($image, 'products/' . date('Y/m'));

                // Старое фото удаляется только после окончательного commit.
                $files->deleteAfterCommit($product->image);
                $payload['image'] = $newImagePath;
            }

            /* ---------- 4. Обновление товара ---------- */
            $product = $this->repo->update($product, $payload);

            /* ---------- 5. Удаление файлов галереи ---------- */
            if ($galleryToDelete) {
                foreach ($galleryToDelete as $path) {
                    $this->removeGalleryImage($product, $path, $files);
                }
            }

            /* ---------- 6. Добавление новых фото ---------- */
            if ($galleryNew) {
                $this->appendGallery($product, $galleryNew, $files);
            }

            /* ---------- 7. Атрибуты ---------- */
            // Shared active values remain; incompatible rows become immutable history.
            // Returning to an earlier category never unarchives rows implicitly.
            $this->attributes->sync($product, $attrs);

            return $product;
        });
    }

    /* ============================================================
     |  УДАЛЕНИЕ ТОВАРА
     ============================================================ */
    public function delete(Product $product, ?User $actor = null): void
    {
        app(BackupWriteBarrier::class)->transaction(function () use ($product, $actor) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $this->authorizeMutation($actor, 'delete', $product);
            $files = new ProductImageOperation($this->images, DB::connection());

            // Планируем очистку файлов после окончательного commit.
            $files->deleteAfterCommit($product->image);

            // Галерея использует ту же отложенную очистку.
            foreach ((array)$product->gallery as $path) {
                $files->deleteAfterCommit($path);
            }

            // Удаляем товар
            $this->repo->delete($product);
            
            Log::info("✅ Товар удален: ID {$product->id} - {$product->title}");
        });
    }

    public function purge(Product $product, \DateTimeInterface $deletedBefore): bool
    {
        return app(BackupWriteBarrier::class)->transaction(function () use ($product, $deletedBefore) {
            $product = Product::onlyTrashed()->whereKey($product->id)
                ->where('deleted_at', '<', $deletedBefore)->lockForUpdate()->first();
            if (! $product) {
                return false;
            }

            // Keep a current read under the product lock, even in an outer transaction.
            // Historical products must never enter the destructive image lifecycle.
            if ($product->orderItems()->lockForUpdate()->exists()) {
                Log::info('Product purge skipped: required by order history', ['product_id' => $product->id]);

                return false;
            }

            $files = new ProductImageOperation($this->images, DB::connection());
            $reviewFiles = new ReviewImageOperation(
                $this->images,
                app(ReviewImageStorage::class),
                DB::connection(),
            );
            foreach (array_filter([$product->image, ...(array) $product->gallery]) as $path) {
                $files->deleteAfterCommit($path);
            }
            $product->reviews()->withTrashed()->with('images')->get()
                ->each(function ($review) use ($reviewFiles): void {
                    $review->images->each(
                        fn ($image) => $reviewFiles->deleteAfterCommit($image->path)
                    );
                });
            $product->forceDelete();

            return true;
        });
    }

    /* ============================================================
     |  ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
     ============================================================ */

    protected function prepareData(array $data, bool $updating = false): array
    {
        $allowed = [
            'title', 'slug', 'sku', 'price', 'old_price', 'stock', 'description',
            'category_id', 'city_id', 'country_id', 'address',
            'latitude', 'longitude', 'status', 'active', 'user_id',
            'currency_base', 'price_prb', 'old_price_prb', 'price_mdl', 'old_price_mdl', 'price_uah', 'old_price_uah'
        ];

        $payload = array_intersect_key($data, array_flip($allowed));
        $clearable = ['old_price', 'old_price_prb', 'old_price_mdl', 'old_price_uah'];

        foreach ($clearable as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] === '') {
                $payload[$field] = null;
            }
        }

        if ($updating) {
            $payload = array_filter(
                $payload,
                fn($v, $key) => in_array($key, $clearable, true) || ($v !== null && $v !== ''),
                ARRAY_FILTER_USE_BOTH
            );
        }

        return $payload;
    }

    protected function generateSku(): string
    {
        do {
            $sku = 'PRD-' . random_int(10000, 99999);
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    protected function handleSlugHistory(Product $product, array & $payload): void
    {
        if (!empty($payload['slug']) && $payload['slug'] !== $product->slug) {

            ProductSlug::create([
                'product_id' => $product->id,
                'slug'       => $product->slug,
            ]);
        }

        if (empty($payload['slug'])) {
            $payload['slug'] = $product->slug;
        }
    }

    protected function appendGallery(Product $product, array $uploads, ProductImageOperation $files): void
    {
        if (count((array) $product->gallery) + count($uploads) > ImageUploadConstraints::MAX_GALLERY_IMAGES) {
            throw ValidationException::withMessages([
                'gallery' => 'В галерее товара может быть не более ' . ImageUploadConstraints::MAX_GALLERY_IMAGES . ' изображений.',
            ]);
        }

        $paths = [];
        foreach ($uploads as $upload) {
            if ($upload instanceof UploadedFile) {
                $paths[] = $files->upload($upload, 'products/gallery/' . date('Y/m'));
            }
        }

        $gallery = array_unique(array_merge(
            (array)$product->gallery,
            $paths
        ));

        $product->update(['gallery' => array_values($gallery)]);
    }

    protected function removeGalleryImage(Product $product, string $path, ProductImageOperation $files): void
    {
        $gallery = (array) $product->gallery;
        $cleanPath = $this->normalizeGalleryPath($path);

        if (!in_array($cleanPath, $gallery, true)) {
            abort(403, 'Изображение не принадлежит галерее этого товара.');
        }

        // Файл сохраняется до окончательного commit удаления ссылки.
        $files->deleteAfterCommit($cleanPath);

        $gallery = array_filter($gallery, fn($p) => $p !== $cleanPath);

        $product->update(['gallery' => array_values($gallery)]);
    }

    protected function normalizeGalleryPath(string $path): string
    {
        return ltrim(str_replace(['storage/', '/storage/'], '', $path), '/');
    }

    public function deleteFromGallery(Product $product, string $path, ?User $actor = null): void
    {
        app(BackupWriteBarrier::class)->transaction(function () use ($product, $path, $actor) {
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $this->authorizeMutation($actor, 'update', $product);
            $files = new ProductImageOperation($this->images, DB::connection());
            $this->removeGalleryImage($product, $path, $files);
        });
    }

    private function authorizeMutation(?User $actor, string $ability, Product $product): void
    {
        // Seller endpoints must pass their actor. Null retains the trusted
        // administrative/internal service API. Check before any file or DB mutation.
        if ($actor !== null) {
            Gate::forUser($actor)->authorize($ability, $product);
        }
    }
}
