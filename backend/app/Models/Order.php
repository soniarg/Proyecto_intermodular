<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',   // <--- Antes user_id
        'seller_id',  // <--- Nuevo
        'pickup_id',
        'total',      // O total_price, según pusieras en la migración
        'status',
        'rejection_reason'
    ];

    // Relación: El comprador
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    // Relación: El vendedor
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // Relación: El punto de recogida
    public function pickupPoint()
    {
        return $this->belongsTo(PickupPoint::class, 'pickup_id');
    }

    // Relación: Líneas de pedido
    public function lines()
    {
        return $this->hasMany(OrderLine::class);
    }
}