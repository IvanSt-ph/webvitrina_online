<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Category;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'products'   => Product::count(),
            'categories' => Category::count(),
            'orders'     => Order::count(),
            'users'      => User::count(),
        ];

$products = Product::with(['category', 'seller'])
    ->latest()
    ->paginate(10);   // 👈 вместо take/get


        $orders = Order::with('user')
            ->latest()
            ->take(8)
            ->get();

        $newUsers = User::latest()
            ->take(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'products', 'orders', 'newUsers'));
    }
}
