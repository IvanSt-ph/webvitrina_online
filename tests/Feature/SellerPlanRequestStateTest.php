<?php

namespace Tests\Feature;

use App\Models\{AdminActivityLog, SellerPlanRequest, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\SellerPlanDecision;
use Tests\TestCase;

class SellerPlanRequestStateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Limiters may already be resolved by providers; fail closed on shared cache.
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('cache.limiter') ?? config('cache.default'));

        // Retain the real write barrier on a test-owned inode through rollback.
        $lock = tmpfile();
        $this->assertIsResource($lock);
        $this->beforeApplicationDestroyed(static fn () => fclose($lock));
        $path = stream_get_meta_data($lock)['uri'];
        $this->assertFileExists($path);
        $this->assertFalse(is_link($path));
        config([
            'backup.lock_path' => $path,
            'cache.default' => 'array', 'cache.limiter' => 'array',
            'session.driver' => 'array', 'queue.default' => 'sync',
            'mail.default' => 'array', 'logging.default' => 'null',
        ]);
    }

    public static function decisions(): array
    {
        return [['approve', 'approve'], ['approve', 'reject'], ['reject', 'approve'], ['reject', 'reject']];
    }

    #[DataProvider('decisions')]
    public function test_stale_decision_cannot_reapply_a_plan_or_overwrite_terminal_state(string $first, string $second): void
    {
        $seller = User::factory()->seller()->create(['seller_plan' => 'starter']);
        $winner = User::factory()->admin()->create();
        $loser = User::factory()->admin()->create();
        $request = SellerPlanRequest::create([
            'user_id' => $seller->id, 'current_plan' => 'starter', 'requested_plan' => 'basic',
            'status' => SellerPlanRequest::STATUS_PENDING,
        ]);
        $stale = $request->fresh();
        $this->actingAs($winner)->post(route('admin.seller-plan-requests.'.$first, $request), ['admin_note' => 'Winner'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $before = $request->fresh()->getAttributes();
        $this->assertSame($first === 'approve' ? 'basic' : 'starter', $seller->fresh()->seller_plan);
        // A later legitimate plan change must not be undone by replaying approval.
        $seller->update(['seller_plan' => 'pro']);
        try {
            SellerPlanDecision::run($loser, $stale, $second, 'Loser');
            $this->fail('A stale pending model must not authorize another decision.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame('Эта заявка уже обработана.', $exception->getMessage());
        }
        $this->actingAs($loser)->postJson(route('admin.seller-plan-requests.'.$second, $request))
            ->assertUnprocessable();
        $this->assertSame($before, $request->fresh()->getAttributes());
        $this->assertSame('pro', $seller->fresh()->seller_plan);
        $logs = AdminActivityLog::where('subject_type', SellerPlanRequest::class)->where('subject_id', $request->id)->get();
        $this->assertCount(1, $logs);
        $this->assertSame('seller_plan_request.'.($first === 'approve' ? 'approved' : 'rejected'), $logs[0]->action);
        $this->assertSame($winner->id, $logs[0]->admin_id);
    }

    public function test_seller_cannot_make_either_administrative_decision(): void
    {
        $seller = User::factory()->seller()->create(['seller_plan' => 'starter']);
        $request = SellerPlanRequest::create([
            'user_id' => $seller->id, 'current_plan' => 'starter', 'requested_plan' => 'basic',
            'status' => SellerPlanRequest::STATUS_PENDING,
        ]);
        foreach (['approve', 'reject'] as $action) {
            $this->actingAs($seller)->postJson(route('admin.seller-plan-requests.'.$action, $request))->assertForbidden();
        }
        $this->assertSame('pending', $request->fresh()->status);
        $this->assertSame('starter', $seller->fresh()->seller_plan);
    }
}
