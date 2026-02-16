<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Order;

class OrderStatusUpdated extends Notification
{
    use Queueable;

    protected $order;

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
        $statusMessage = '';

        switch ($this->order->status) {
            case 'pending':
                $statusMessage = "Tu pedido #{$this->order->id} ha sido aceptado por el vendedor.";
                break;
            case 'ready':
                $statusMessage = "¡El pedido #{$this->order->id} está LISTO para recoger!";
                break;
            case 'completed':
                $statusMessage = "Pedido #{$this->order->id} entregado. ¡Gracias!";
                break;
            case 'rejected':
                $statusMessage = "Tu pedido #{$this->order->id} ha sido rechazado.";
                break;
            default:
                $statusMessage = "Actualización en pedido #{$this->order->id}: {$this->order->status}";
        }

        return [
            'order_id' => $this->order->id,
            'message' => $statusMessage,
            'status' => $this->order->status,
            'url' => '/my-purchases' 
        ];
    }
}