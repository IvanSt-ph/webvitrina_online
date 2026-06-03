<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReport;
use App\Repositories\ProductCrudRepository;
use App\Services\AdminActivityLogger;
use App\Services\UserNotificationService;
use Illuminate\Http\Request;

class ProductReportController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    public function index(Request $request)
    {
        $status = $request->get('status', ProductReport::STATUS_OPEN);
        $action = $request->get('action', 'all');
        $productStatus = $request->get('product_status', 'all');
        $focus = $request->get('focus', 'all');
        $q = trim((string) $request->get('q', ''));

        if (! in_array($status, array_merge(['all'], ProductReport::statuses()), true)) {
            $status = ProductReport::STATUS_OPEN;
        }

        if (! in_array($action, array_merge(['all', 'none'], ProductReport::actions()), true)) {
            $action = 'all';
        }

        if (! in_array($productStatus, array_merge(['all'], Product::statuses()), true)) {
            $productStatus = 'all';
        }

        if (! in_array($focus, ['all', 'active_open', 'blocked', 'repeated', 'restored'], true)) {
            $focus = 'all';
        }

        $rawCounters = ProductReport::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counters = [
            'all' => $rawCounters->sum(),
            ProductReport::STATUS_OPEN => (int) ($rawCounters[ProductReport::STATUS_OPEN] ?? 0),
            ProductReport::STATUS_RESOLVED => (int) ($rawCounters[ProductReport::STATUS_RESOLVED] ?? 0),
            ProductReport::STATUS_DISMISSED => (int) ($rawCounters[ProductReport::STATUS_DISMISSED] ?? 0),
        ];

        $actionCounters = ProductReport::selectRaw('action_taken, COUNT(*) as total')
            ->whereNotNull('action_taken')
            ->groupBy('action_taken')
            ->pluck('total', 'action_taken');

        $productStatusCounters = ProductReport::query()
            ->join('products', 'product_reports.product_id', '=', 'products.id')
            ->selectRaw('products.status, COUNT(*) as total')
            ->groupBy('products.status')
            ->pluck('total', 'products.status');

        $productReportCounts = ProductReport::selectRaw('product_id, COUNT(*) as total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $openProductReportCounts = ProductReport::where('status', ProductReport::STATUS_OPEN)
            ->selectRaw('product_id, COUNT(*) as total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        $repeatedProductIds = ProductReport::selectRaw('product_id, COUNT(*) as total')
            ->groupBy('product_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('product_id');

        $riskCounters = [
            'active_open' => ProductReport::where('status', ProductReport::STATUS_OPEN)
                ->whereHas('product', fn ($product) => $product->where('status', Product::STATUS_ACTIVE))
                ->count(),
            'blocked_products' => ProductReport::whereHas('product', fn ($product) => $product->where('status', Product::STATUS_BLOCKED))
                ->distinct('product_id')
                ->count('product_id'),
            'repeated_products' => $repeatedProductIds->count(),
            'restored_products' => ProductReport::where('action_taken', ProductReport::ACTION_PRODUCT_RESTORED)
                ->distinct('product_id')
                ->count('product_id'),
        ];

        $reports = ProductReport::with(['product.seller', 'user', 'reviewer'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($action !== 'all', function ($query) use ($action) {
                if ($action === 'none') {
                    $query->whereNull('action_taken');

                    return;
                }

                $query->where('action_taken', $action);
            })
            ->when($productStatus !== 'all', fn ($query) => $query
                ->whereHas('product', fn ($product) => $product->where('status', $productStatus)))
            ->when($focus !== 'all', function ($query) use ($focus, $repeatedProductIds) {
                match ($focus) {
                    'active_open' => $query
                        ->where('status', ProductReport::STATUS_OPEN)
                        ->whereHas('product', fn ($product) => $product->where('status', Product::STATUS_ACTIVE)),
                    'blocked' => $query
                        ->whereHas('product', fn ($product) => $product->where('status', Product::STATUS_BLOCKED)),
                    'repeated' => $query->whereIn('product_id', $repeatedProductIds),
                    'restored' => $query->where('action_taken', ProductReport::ACTION_PRODUCT_RESTORED),
                    default => null,
                };
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('reason', 'like', "%{$q}%")
                        ->orWhere('details', 'like', "%{$q}%")
                        ->orWhereHas('product', fn ($product) => $product
                            ->where('title', 'like', "%{$q}%")
                            ->orWhere('sku', 'like', "%{$q}%"))
                        ->orWhereHas('user', fn ($user) => $user
                            ->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%"));
                });
            })
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [ProductReport::STATUS_OPEN])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.product-reports.index', compact(
            'reports',
            'status',
            'action',
            'productStatus',
            'focus',
            'q',
            'counters',
            'actionCounters',
            'productStatusCounters',
            'productReportCounts',
            'openProductReportCounts',
            'riskCounters'
        ));
    }

    public function resolve(Request $request, ProductReport $report, UserNotificationService $notifications)
    {
        $data = $request->validate([
            'resolution' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update([
            'status' => ProductReport::STATUS_RESOLVED,
            'resolution' => trim((string) ($data['resolution'] ?? '')),
            'action_taken' => ProductReport::ACTION_REVIEWED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->activity->log('product_report.resolved', $report, 'Жалоба на товар принята администратором.', [
            'product_id' => $report->product_id,
        ]);

        if ($report->user) {
            $notifications->create(
                $report->user,
                'product_report_resolved',
                'Жалоба рассмотрена',
                'Администратор проверил вашу жалобу на товар и принял её к работе.',
                $report->product ? route('product.show', $report->product->slug, false) : null,
                ['report_id' => $report->id, 'product_id' => $report->product_id]
            );
        }

        return back()->with('success', 'Жалоба отмечена как принятая к работе.');
    }

    public function hideProduct(
        Request $request,
        ProductReport $report,
        ProductCrudRepository $products,
        UserNotificationService $notifications
    ) {
        $data = $request->validate([
            'resolution' => ['required', 'string', 'max:1000'],
        ]);

        $product = $report->product;
        $previousStatus = $product->status;

        if ($product->status !== \App\Models\Product::STATUS_BLOCKED) {
            $products->update($product, ['status' => \App\Models\Product::STATUS_BLOCKED]);
        }

        $report->update([
            'status' => ProductReport::STATUS_RESOLVED,
            'resolution' => trim($data['resolution']),
            'action_taken' => ProductReport::ACTION_PRODUCT_HIDDEN,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->activity->log('product_report.product_hidden', $report, 'Товар скрыт администратором по жалобе.', [
            'product_id' => $report->product_id,
            'previous_status' => $previousStatus,
        ]);

        if ($report->user) {
            $notifications->create(
                $report->user,
                'product_report_product_hidden',
                'Жалоба рассмотрена',
                'Администратор проверил вашу жалобу и скрыл товар с витрины.',
                null,
                ['report_id' => $report->id, 'product_id' => $report->product_id]
            );
        }

        if ($product->seller?->exists) {
            $notifications->create(
                $product->seller,
                'product_hidden_by_report',
                'Товар скрыт администратором',
                "Товар «{$product->title}» заблокирован после жалобы и снят с витрины. Причина: {$data['resolution']} Исправьте карточку; вернуть товар в продажу сможет администратор после проверки.",
                route('seller.products.edit', $product, false),
                [
                    'report_id' => $report->id,
                    'product_id' => $report->product_id,
                    'action' => 'edit_hidden_product',
                    'previous_status' => $previousStatus,
                ]
            );
        }

        return back()->with('success', 'Товар скрыт с витрины, жалоба закрыта.');
    }

    public function restoreProduct(
        Request $request,
        ProductReport $report,
        ProductCrudRepository $products,
        UserNotificationService $notifications
    ) {
        $data = $request->validate([
            'resolution' => ['required', 'string', 'max:1000'],
            'status' => ['required', 'in:active,draft'],
        ]);

        $product = $report->product;
        $previousStatus = $product->status;

        $products->update($product, ['status' => $data['status']]);

        $report->update([
            'status' => ProductReport::STATUS_RESOLVED,
            'resolution' => trim($data['resolution']),
            'action_taken' => ProductReport::ACTION_PRODUCT_RESTORED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->activity->log('product_report.product_restored', $report, 'Товар возвращён администратором после проверки жалобы.', [
            'product_id' => $report->product_id,
            'previous_status' => $previousStatus,
            'new_status' => $data['status'],
        ]);

        if ($product->seller?->exists) {
            $notifications->create(
                $product->seller,
                'product_unblocked_by_admin',
                'Продажи товара возобновлены',
                "Администратор проверил товар «{$product->title}» после жалобы и вернул возможность публикации. Текущий статус: " . ($data['status'] === Product::STATUS_ACTIVE ? 'опубликован' : 'черновик') . '.',
                route('seller.products.edit', $product, false),
                [
                    'report_id' => $report->id,
                    'product_id' => $report->product_id,
                    'previous_status' => $previousStatus,
                    'new_status' => $data['status'],
                ]
            );
        }

        return back()->with('success', 'Товар возвращён, продавец получил уведомление.');
    }

    public function dismiss(Request $request, ProductReport $report, UserNotificationService $notifications)
    {
        $data = $request->validate([
            'resolution' => ['nullable', 'string', 'max:1000'],
        ]);

        $report->update([
            'status' => ProductReport::STATUS_DISMISSED,
            'resolution' => trim((string) ($data['resolution'] ?? '')),
            'action_taken' => ProductReport::ACTION_DISMISSED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->activity->log('product_report.dismissed', $report, 'Жалоба на товар отклонена администратором.', [
            'product_id' => $report->product_id,
        ]);

        if ($report->user) {
            $notifications->create(
                $report->user,
                'product_report_dismissed',
                'Жалоба рассмотрена',
                'Администратор проверил вашу жалобу на товар. Нарушение не подтверждено.',
                $report->product ? route('product.show', $report->product->slug, false) : null,
                ['report_id' => $report->id, 'product_id' => $report->product_id]
            );
        }

        return back()->with('success', 'Жалоба отклонена.');
    }
}
