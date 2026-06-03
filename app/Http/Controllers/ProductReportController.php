<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReport;
use App\Models\User;
use App\Services\UserNotificationService;
use Illuminate\Http\Request;

class ProductReportController extends Controller
{
    public function store(Request $request, Product $product, UserNotificationService $notifications)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $report = ProductReport::create([
            'product_id' => $product->id,
            'user_id' => $request->user()?->id,
            'reason' => trim($data['reason']),
            'details' => trim((string) ($data['details'] ?? '')),
        ]);

        $admin = User::where('role', 'admin')->orderBy('id')->first();
        if ($admin) {
            $notifications->create(
                $admin,
                'product_report',
                'Жалоба на товар',
                "Поступила жалоба на товар «{$product->title}».",
                route('admin.products.edit', $product, false),
                ['product_id' => $product->id, 'report_id' => $report->id]
            );
        }

        return back()->with('success', 'Жалоба отправлена на проверку.');
    }
}
