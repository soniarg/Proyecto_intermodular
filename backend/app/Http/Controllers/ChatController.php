<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewMessageReceived;

class ChatController extends Controller
{
    public function index($orderId)
    {
        Message::where('order_id', $orderId)
            ->where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return Message::where('order_id', $orderId)
            ->with('sender:id,name') 
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function store(Request $request, $orderId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $order = Order::findOrFail($orderId);

        $receiverId = ($order->buyer_id === $user->id) ? $order->seller_id : $order->buyer_id;

        $message = Message::create([
            'order_id'    => $orderId,
            'sender_id'   => $user->id,
            'receiver_id' => $receiverId,
            'content'     => $request->content,
            'is_read'     => false
        ]);

        $order = Order::find($orderId);

        if ($order) {
            $receiver = null;

            if (Auth::id() === $order->buyer_id) {
                $receiver = $order->seller;
            } elseif (Auth::id() === $order->seller_id) {
                $receiver = $order->buyer;
            }

            if ($receiver && $receiver->id !== Auth::id()) {
                $receiver->notify(new NewMessageReceived($order->id, Auth::user()->name));
            }
        }

        return $message->load('sender:id,name');
    }
}