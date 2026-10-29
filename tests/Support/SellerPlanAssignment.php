<?php

namespace Tests\Support;

use App\Http\Controllers\Admin\{SellerPlanRequestController, UserController};
use App\Models\{SellerPlanRequest, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class SellerPlanAssignment
{
    public static function run(User $admin, User $seller, string $plan, ?SellerPlanRequest $planRequest): void
    {
        Auth::guard('web')->setUser($admin);
        $request = Request::create('/admin/plan', 'POST', [
            'name' => $seller->name, 'email' => $seller->email,
            'role' => 'seller', 'seller_plan' => $plan,
        ]);
        $request->setUserResolver(fn () => $admin);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);
        if ($planRequest) {
            app(SellerPlanRequestController::class)->approve($request, $planRequest);
        } else {
            app(UserController::class)->update($request, $seller);
        }
    }
}
