<?php

namespace Tests\Support;

use App\Http\Controllers\Admin\SellerPlanRequestController;
use App\Models\{SellerPlanRequest, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class SellerPlanDecision
{
    public static function run(User $admin, SellerPlanRequest $planRequest, string $action, string $note): void
    {
        if (! in_array($action, ['approve', 'reject'], true)) {
            throw new \InvalidArgumentException('Unknown plan decision.');
        }
        Auth::guard('web')->setUser($admin);
        $request = Request::create('/admin/seller-plan-requests/'.$planRequest->id.'/'.$action, 'POST', ['admin_note' => $note]);
        $request->setUserResolver(fn () => $admin);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);
        app(SellerPlanRequestController::class)->{$action}($request, $planRequest);
    }
}
