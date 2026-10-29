<?php

namespace Tests\Feature;

use App\Models\{Product, SellerPlanRequest, User};
use App\Services\SellerPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ProductQuotaFixtures;
use Tests\TestCase;

class SellerPlanQuotaTest extends TestCase
{
    use RefreshDatabase, ProductQuotaFixtures;

    public static function assignments(): array
    {
        $cases = [];
        foreach (['user', 'request'] as $path) {
            foreach ([['basic', 'starter', 10, true], ['basic', 'starter', 11, false],
                ['starter', 'basic', 10, true], ['business', 'enterprise', 100, true],
                ['enterprise', 'business', 101, false]] as $case) {
                $cases[] = [$path, ...$case];
            }
        }
        return $cases;
    }

    #[DataProvider('assignments')]
    public function test_assignment_preserves_limits_messages_and_atomicity(string $path, string $old, string $next, int $used, bool $allowed): void
    {
        [$seller] = $this->quotaFixture($used, $old);
        // Deleted products must not prevent a downgrade at exact capacity.
        $deleted = Product::where('user_id', $seller->id)->firstOrFail()->replicate();
        $deleted->slug = 'deleted-'.$seller->id;
        $deleted->save();
        $deleted->delete();
        $planRequest = SellerPlanRequest::create([
            'user_id' => $seller->id, 'current_plan' => $old,
            'requested_plan' => $next, 'status' => SellerPlanRequest::STATUS_PENDING,
        ]);
        $this->actingAs(User::factory()->admin()->create());
        $response = $path === 'user'
            ? $this->patchJson(route('admin.users.update', $seller), [
                'name' => $seller->name, 'email' => $seller->email, 'role' => 'seller', 'seller_plan' => $next,
            ])
            : $this->postJson(route('admin.seller-plan-requests.approve', $planRequest));
        if ($allowed) {
            $response->assertRedirect();
        } else {
            $field = $path === 'user' ? 'seller_plan' : 'requested_plan';
            $response->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->assertSame([app(SellerPlanService::class)->assignmentLimitMessage($seller, $next)], $response->json('errors.'.$field));
        }
        $this->assertSame($allowed ? $next : $old, $seller->fresh()->seller_plan);
        $this->assertSame($path === 'request' && $allowed ? SellerPlanRequest::STATUS_APPROVED : SellerPlanRequest::STATUS_PENDING,
            $planRequest->fresh()->status);
        $this->assertSame($used, Product::where('user_id', $seller->id)->count());
    }

    public function test_non_seller_role_reset_keeps_existing_exemption(): void
    {
        [$seller] = $this->quotaFixture(11, 'basic');
        $this->actingAs(User::factory()->admin()->create())
            ->patchJson(route('admin.users.update', $seller), [
                'name' => $seller->name, 'email' => $seller->email, 'role' => 'buyer',
            ])->assertRedirect();
        $this->assertSame('buyer', $seller->fresh()->role);
        $this->assertSame('starter', $seller->fresh()->seller_plan);
    }
}
