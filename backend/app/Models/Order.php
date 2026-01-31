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
        'total_price',
        'status',
        'rejection_reason'
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {

        return $this->belongsTo(User::class, 'seller_id');
    }

    public function pickupPoint()
    {
        return $this->belongsTo(PickupPoint::class, 'pickup_id');
    }

    public function lines()
    {
        return $this->hasMany(OrderLine::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}