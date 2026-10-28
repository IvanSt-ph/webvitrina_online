<?php

namespace Tests\Support;

use App\Http\Controllers\Seller\ProductManageController;
use App\Http\Requests\Seller\ProductUpdateRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/** Invoke the real seller controller with a deliberately retained route model. */
final class SellerProductMutation
{
    public static function run(User $seller, Product $product, string $action): void
    {
        Auth::guard('web')->setUser($seller);
        $data = [
            'title' => 'Seller correction', 'price' => 125, 'stock' => 4,
            'status' => Product::STATUS_ACTIVE, 'category_id' => $product->category_id,
            'country_id' => $product->city->country_id, 'city_id' => $product->city_id,
            'currency_base' => 'MDL', 'path' => $product->gallery[0] ?? '',
        ];
        $files = match ($action) {
            'main' => ['image' => UploadedFile::fake()->image('main.jpg', 24, 24)],
            'gallery-add' => ['gallery' => [UploadedFile::fake()->image('gallery.jpg', 24, 24)]],
            default => [],
        };
        if ($action === 'gallery-update-delete') {
            $data['delete_gallery'] = $product->gallery;
        }
        $request = ProductUpdateRequest::create('/seller/products/'.$product->id, 'PATCH', $data, files: $files);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->setUserResolver(fn () => $seller);
        $request->setLaravelSession(app('session')->driver());
        $request->setValidator(Validator::make($request->all(), $request->rules()));
        app()->instance('request', $request);
        $controller = app(ProductManageController::class);
        match ($action) {
            'delete' => $controller->destroy($product),
            'gallery-delete' => $controller->deleteGalleryImage($product, $request),
            default => $controller->update($request, $product),
        };
    }
}
