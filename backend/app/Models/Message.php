<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'sender_id',
        'receiver_id',
        'content',
        'is_read'
    ];

    // Relación: Un mensaje pertenece a un Enviador (User)
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Relación: Un mensaje pertenece a un Receptor (User)
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}