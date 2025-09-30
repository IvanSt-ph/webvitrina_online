<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(){
        $orders = Order::where('user_id',auth()->id())->latest()->with('items.product')->get();
        return view('shop.orders', compact('orders'));
    }

    public function checkout(){
        $userId = auth()->id();
        $items = CartItem::with('product')->where('user_id',$userId)->get();
        abort_if($items->isEmpty(), 400, 'Корзина пуста');
        return DB::transaction(function() use($items,$userId){
            $total = $items->sum(fn($i)=>$i->qty*$i->product->price);
            $order = Order::create(['user_id'=>$userId,'total'=>$total,'status'=>'new']);
            foreach($items as $i){
                OrderItem::create([
                    'order_id'=>$order->id,
                    'product_id'=>$i->product_id,
                    'price'=>$i->product->price,
                    'qty'=>$i->qty
                ]);
            }
            CartItem::where('user_id',$userId)->delete();
            return redirect()->route('orders.index')->with('success','Заказ создан');
        });
    }
}
