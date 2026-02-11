<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReviewReceived extends Notification
{
    use Queueable;

    protected $review;
    protected $authorName;

    public function __construct($review, $authorName)
    {
        $this->review = $review;
        $this->authorName = $authorName;
    }

    public function via($notifiable)
    {
        return ['database']; 
    }

    public function toArray($notifiable)
    {
        $stars = str_repeat('⭐', $this->review->rating);

        return [
            'order_id' => $this->review->order_id,
            'message' => "Nueva valoración de {$this->authorName}: {$stars}", 
            'url' => '/perfil' 
        ];
    }
}