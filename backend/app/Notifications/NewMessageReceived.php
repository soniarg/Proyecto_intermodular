<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMessageReceived extends Notification
{
    use Queueable;

    public $orderId;
    public $senderName;

    public function __construct($orderId, $senderName)
    {
        $this->orderId = $orderId;       
        $this->senderName = $senderName; 
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->orderId,
            'message' => 'Nuevo mensaje de ' . $this->senderName, 
            'url' => '/chat/' . $this->orderId
        ];
    }
}