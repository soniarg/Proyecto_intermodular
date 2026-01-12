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
        'status',          // draft, pending, ready, completed, rejected
        'total_price',
        'rejection_reason'
    ];

    // El comprador es un Usuario
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    // El vendedor es un Perfil de Vendedor
    public function seller()
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id', 'seller_id');
    }
    
    // Relación con la valoración (Acción 7)
    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function lines()
    {
        return $this->hasMany(OrderLine::class);
    }
}
