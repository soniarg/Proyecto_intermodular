<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id', // Quién escribe la reseña (Vendedor en este caso)
        'order_id',  // A qué pedido corresponde
        'rating',    // 1 a 5
        'comment'
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}