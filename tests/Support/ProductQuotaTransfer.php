<?php

namespace Tests\Support;

use App\Http\Controllers\Admin\ProductController;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\{Product, User};
use Illuminate\Support\Facades\{Auth, Validator};

final class ProductQuotaTransfer
{
    public static function run(User $admin, Product $product, int $recipientId): void
    {
        Auth::guard('web')->setUser($admin);
        $request = ProductUpdateRequest::create('/admin/products/'.$product->id, 'PATCH', ['user_id' => $recipientId]);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->setUserResolver(fn () => $admin);
        $request->setLaravelSession(app('session')->driver());
        $request->setValidator(Validator::make($request->all(), $request->rules()));
        app()->instance('request', $request);
        app(ProductController::class)->update($request, $product);
    }
}
