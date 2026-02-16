<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Order;

class NewOrderReceived extends Notification
{
    use Queueable;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            // Mensaje corto y claro, igual que el de mensajes
            'message' => 'Nueva venta de ' . $this->order->total_price . '€ (Pedido #' . $this->order->id . ')',
            // La ruta para ver el pedido en el panel de vendedor
            'url' => '/seller/orders/' . $this->order->id 
        ];
    }
}