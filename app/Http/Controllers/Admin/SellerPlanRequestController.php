<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SellerPlanRequest;
use App\Services\AdminActivityLogger;
use App\Services\SellerPlanService;
use Illuminate\Http\Request;

class SellerPlanRequestController extends Controller
{
    public function __construct(
        private readonly SellerPlanService $sellerPlans,
        private readonly AdminActivityLogger $activity
    ) {
    }

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), [
            SellerPlanRequest::STATUS_PENDING,
            SellerPlanRequest::STATUS_APPROVED,
            SellerPlanRequest::STATUS_REJECTED,
        ], true) ? $request->query('status') : SellerPlanRequest::STATUS_PENDING;

        $requests = SellerPlanRequest::query()
            ->with(['user.shop', 'reviewer'])
            ->where('status', $status)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = SellerPlanRequest::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.seller-plan-requests.index', [
            'requests' => $requests,
            'counts' => $counts,
            'status' => $status,
            'plans' => $this->sellerPlans->plans(),
        ]);
    }

    public function approve(Request $request, SellerPlanRequest $planRequest)
    {
        abort_unless($planRequest->isPending(), 422, 'Эта заявка уже обработана.');

        $user = $planRequest->user;
        $oldPlan = $user->seller_plan;

        $user->forceFill(['seller_plan' => $planRequest->requested_plan])->save();
        $planRequest->update([
            'status' => SellerPlanRequest::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_note' => $request->input('admin_note'),
        ]);

        $this->activity->log('seller_plan_request.approved', $planRequest, 'Одобрено повышение тарифа продавца.', [
            'user_id' => $user->id,
            'from' => $oldPlan,
            'to' => $planRequest->requested_plan,
        ]);

        return back()->with('success', 'Тариф продавца повышен.');
    }

    public function reject(Request $request, SellerPlanRequest $planRequest)
    {
        abort_unless($planRequest->isPending(), 422, 'Эта заявка уже обработана.');

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:700'],
        ]);

        $planRequest->update([
            'status' => SellerPlanRequest::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_note' => trim((string) ($data['admin_note'] ?? '')) ?: null,
        ]);

        $this->activity->log('seller_plan_request.rejected', $planRequest, 'Отклонена заявка на повышение тарифа.', [
            'user_id' => $planRequest->user_id,
            'requested_plan' => $planRequest->requested_plan,
        ]);

        return back()->with('success', 'Заявка отклонена.');
    }
}
