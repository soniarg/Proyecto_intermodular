<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    // 1. Obtener los mensajes que hay en un chat (relacionados con el id del pedido)
    public function index($orderId)
    {

        Message::where('order_id', $orderId)
            ->where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);


        // Busco los mensajes de ese pedido y cargo los nombres de quien los envía
        return Message::where('order_id', $orderId)
            ->with('sender:id,name') 
            ->orderBy('created_at', 'asc')
            ->get();
    }

    // 2. Enviar un mensaje
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

        // Devolvemos el mensaje con los datos del remitente para que Vue lo pinte al momento
        return $message->load('sender:id,name');
    }
}