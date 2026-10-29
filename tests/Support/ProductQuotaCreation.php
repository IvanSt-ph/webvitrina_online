<?php

namespace Tests\Support;

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Seller\ProductManageController;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\Seller\ProductStoreRequest as SellerProductStoreRequest;
use App\Models\User;
use App\Services\ProductService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Auth, Validator};

final class ProductQuotaCreation
{
    public static function run(User $actor, array $data, string $path): void
    {
        $image = UploadedFile::fake()->image('quota.jpg', 24, 24);
        $gallery = [UploadedFile::fake()->image('gallery.jpg', 24, 24)];
        if ($path === 'service') {
            app(ProductService::class)->create($data, $image, $gallery);
            return;
        }
        Auth::guard('web')->setUser($actor);
        $class = $path === 'seller' ? SellerProductStoreRequest::class : ProductStoreRequest::class;
        $request = $class::create('/products', 'POST', $data, files: ['image' => $image, 'gallery' => $gallery]);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->setUserResolver(fn () => $actor);
        $request->setLaravelSession(app('session')->driver());
        $request->setValidator(Validator::make($request->all(), $request->rules()));
        app()->instance('request', $request);
        $response = $path === 'seller'
            ? app(ProductManageController::class)->store($request)
            : app(ProductController::class)->store($request);
        $errors = $request->session()->get('errors');
        if ($errors?->has('product_limit')) {
            if ($response->getStatusCode() !== 302 || ! $request->session()->hasOldInput('title')) {
                throw new \RuntimeException('Seller quota rejection lost its redirect/old input.');
            }
            throw \Illuminate\Validation\ValidationException::withMessages($errors->getMessages());
        }
    }
}
