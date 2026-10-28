<?php

namespace Tests\Support;

use App\Models\{Category, Order, OrderItem, Product, Review, User};

trait ReviewFixtures
{
    private function reviewFixture(): array
    {
        $seller = User::factory()->seller()->create();
        $buyer = User::factory()->create(['role' => 'buyer']);
        $admin = User::factory()->admin()->create();
        $product = Product::create([
            'user_id' => $seller->id, 'category_id' => Category::factory()->create()->id,
            'title' => 'Reviewed product', 'price' => 100, 'stock' => 10,
            'currency_base' => 'PRB', 'status' => Product::STATUS_ACTIVE,
        ]);
        $order = Order::create([
            'user_id' => $buyer->id, 'seller_id' => $seller->id, 'number' => Order::generateNumber(),
            'status' => Order::STATUS_DELIVERED, 'payment_method' => 'cash',
            'delivery_method' => 'pickup', 'total_price' => 100, 'currency' => 'PRB',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1,
            'price' => 100, 'total' => 100, 'source_price' => 100, 'source_currency' => 'PRB', 'exchange_rate' => 1,
        ]);
        $review = Review::create([
            'user_id' => $buyer->id, 'product_id' => $product->id,
            'rating' => 2, 'body' => 'Inspected text', 'status' => Review::STATUS_PENDING,
        ]);
        $review->images()->create(['path' => 'reviews/medium/original.webp']);

        return [$buyer, $admin, $product, $review];
    }
}
