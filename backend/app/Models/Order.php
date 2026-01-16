<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'pickup_id',
        'status',
        'total_price',
        'rejection_reason'
    ];

    // Relación: Un pedido pertenece a un Comprador (User)
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    // Relación: Un pedido pertenece a un Vendedor (User)
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // Relación: Un pedido tiene muchas líneas de pedido (productos)
    public function lines()
    {
        return $this->hasMany(OrderLine::class);
    }
}