<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index($orderId)
    {
        // Marcar como leídos los mensajes que YO recibo
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

        // --- CORRECCIÓN AQUÍ ---
        // El pedido tiene 'buyer_id' y 'seller_id'. NO tiene 'user_id'.
        
        // Si yo soy el comprador, el destinatario es el vendedor ($order->seller_id)
        // Si yo soy el vendedor, el destinatario es el comprador ($order->buyer_id)
        $receiverId = ($order->buyer_id === $user->id) ? $order->seller_id : $order->buyer_id;

        $message = Message::create([
            'order_id'    => $orderId,
            'sender_id'   => $user->id,
            'receiver_id' => $receiverId,
            'content'     => $request->content,
            'is_read'     => false
        ]);

        return $message->load('sender:id,name');
    }
}