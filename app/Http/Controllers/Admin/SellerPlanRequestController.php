<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SellerPlanRequest;
use App\Models\User;
use App\Services\BackupWriteBarrier;
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

        $plans = $this->sellerPlans->plans();
        $requestContext = $requests->getCollection()->mapWithKeys(function (SellerPlanRequest $item) use ($plans) {
            if (! $item->user) {
                return [$item->id => ['profile' => null, 'assignable' => false, 'target_limit' => '—']];
            }

            $targetLimit = $plans[$item->requested_plan]['limit'] ?? null;

            return [$item->id => [
                'profile' => $this->sellerPlans->profileFor($item->user),
                'assignable' => $this->sellerPlans->canAssignPlan($item->user, $item->requested_plan),
                'target_limit' => $targetLimit === null ? 'unlimited' : (string) $targetLimit,
            ]];
        });

        return view('admin.seller-plan-requests.index', [
            'requests' => $requests,
            'counts' => $counts,
            'status' => $status,
            'plans' => $plans,
            'requestContext' => $requestContext,
        ]);
    }

    public function approve(Request $request, SellerPlanRequest $planRequest)
    {
        abort_unless($planRequest->isPending(), 422, 'Эта заявка уже обработана.');

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:700'],
        ]);

        [$user, $planRequest, $oldPlan] = app(BackupWriteBarrier::class)->transaction(function () use ($request, $planRequest, $data): array {
            // Same seller mutex as product creation/transfers, before any Product lock.
            $user = User::whereKey($planRequest->user_id)->lockForUpdate()->firstOrFail();
            $current = SellerPlanRequest::whereKey($planRequest->id)->lockForUpdate()->firstOrFail();
            abort_unless($current->user_id === $user->id && $current->isPending(), 422, 'Эта заявка уже обработана.');
            $this->sellerPlans->assertCanAssignPlan($user, $current->requested_plan, 'requested_plan');
            $oldPlan = $user->seller_plan;
            $user->forceFill(['seller_plan' => $current->requested_plan])->save();
            $current->update([
                'status' => SellerPlanRequest::STATUS_APPROVED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'admin_note' => trim((string) ($data['admin_note'] ?? '')) ?: null,
            ]);

            return [$user, $current, $oldPlan];
        });

        $this->activity->log('seller_plan_request.approved', $planRequest, 'Одобрено изменение уровня магазина.', [
            'user_id' => $user->id,
            'from' => $oldPlan,
            'to' => $planRequest->requested_plan,
        ]);

        return back()->with('success', 'Уровень магазина изменён.');
    }

    public function reject(Request $request, SellerPlanRequest $planRequest)
    {
        abort_unless($planRequest->isPending(), 422, 'Эта заявка уже обработана.');

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:700'],
        ]);

        $planRequest = app(BackupWriteBarrier::class)->transaction(function () use ($request, $planRequest, $data): SellerPlanRequest {
            // Match approval/account deletion: never acquire User after the request.
            $user = User::withTrashed()->whereKey($planRequest->user_id)->lockForUpdate()->firstOrFail();
            $current = SellerPlanRequest::whereKey($planRequest->id)->lockForUpdate()->firstOrFail();
            abort_unless($current->user_id === $user->id && $current->isPending(), 422, 'Эта заявка уже обработана.');
            $current->update([
                'status' => SellerPlanRequest::STATUS_REJECTED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'admin_note' => trim((string) ($data['admin_note'] ?? '')) ?: null,
            ]);

            return $current;
        });

        $this->activity->log('seller_plan_request.rejected', $planRequest, 'Отклонена заявка на изменение уровня магазина.', [
            'user_id' => $planRequest->user_id,
            'requested_plan' => $planRequest->requested_plan,
        ]);

        return back()->with('success', 'Заявка отклонена.');
    }
}
