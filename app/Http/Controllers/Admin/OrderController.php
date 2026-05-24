<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:' . implode(',', Order::allStatuses())],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort' => ['nullable', 'in:latest,oldest,amount_desc,amount_asc'],
        ]);

        $statusCounts = Order::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $query = Order::with(['user', 'seller.shop', 'items.product']);

        if ($request->filled('status') && in_array($request->string('status')->toString(), Order::allStatuses(), true)) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));

            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('items.product', function ($productQuery) use ($search) {
                        $productQuery->where('title', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('seller', function ($sellerQuery) use ($search) {
                        $sellerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });

                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $filtered = clone $query;

        $summary = [
            'total' => (clone $filtered)->count(),
            'revenue' => (clone $filtered)
                ->where('status', '!=', Order::STATUS_CANCELED)
                ->sum('total_price'),
            'attention' => (clone $filtered)
                ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PROCESSING])
                ->count(),
            'today' => Order::query()
                ->whereDate('created_at', today())
                ->count(),
        ];

        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'oldest' => $query->oldest(),
            'amount_desc' => $query->orderByDesc('total_price'),
            'amount_asc' => $query->orderBy('total_price'),
            default => $query->latest(),
        };

        $orders = $query->paginate(12)->withQueryString();

        return view('admin.orders.index', compact('orders', 'statusCounts', 'summary', 'sort'));
    }
}
