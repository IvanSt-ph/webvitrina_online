<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerPlanRequest;
use App\Services\SellerPlanService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlanController extends Controller
{
    public function __construct(private readonly SellerPlanService $sellerPlans)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $plans = $this->sellerPlans->plans();
        $profile = $this->sellerPlans->profileFor($user);
        $requests = SellerPlanRequest::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(8)
            ->get();
        $pendingRequest = $requests->firstWhere('status', SellerPlanRequest::STATUS_PENDING);

        return view('seller.plans.index', compact('plans', 'profile', 'requests', 'pendingRequest'));
    }

    public function requestUpgrade(Request $request)
    {
        $data = $request->validate([
            'requested_plan' => ['required', Rule::in($this->sellerPlans->allowedKeys())],
            'message' => ['nullable', 'string', 'max:700'],
        ]);

        $user = $request->user();

        $currentPlan = in_array($user->seller_plan, $this->sellerPlans->allowedKeys(), true)
            ? $user->seller_plan
            : SellerPlanService::STARTER;

        if ($currentPlan === $data['requested_plan']) {
            throw ValidationException::withMessages([
                'requested_plan' => 'Выберите тариф, который отличается от текущего.',
            ]);
        }

        $hasPending = SellerPlanRequest::query()
            ->where('user_id', $user->id)
            ->where('status', SellerPlanRequest::STATUS_PENDING)
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'requested_plan' => 'У вас уже есть заявка на повышение тарифа. Дождитесь решения администратора.',
            ]);
        }

        SellerPlanRequest::create([
            'user_id' => $user->id,
            'current_plan' => $currentPlan,
            'requested_plan' => $data['requested_plan'],
            'message' => trim((string) ($data['message'] ?? '')) ?: null,
        ]);

        return back()->with('success', 'Заявка на изменение тарифа отправлена администратору.');
    }
}
