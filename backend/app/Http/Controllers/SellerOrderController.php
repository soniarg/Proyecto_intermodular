<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\User;
use App\Models\SellerProfile;

class SellerOrderController extends Controller
{
    public function index(){
        $sellerId = Auth::id();

        $orders_seller = Order::with(['buyer', 'lines.product'])->where('seller_id', $sellerId)->get();

        $orders = $orders_seller->map(function ($order){
            return [
                'id' => $order->id,
                'buyer_name' => $order->buyer->name,
                'products' => $order->lines->pluck('product.title')
            ];
        });

        return response()->json($orders, 200);
    }
}
