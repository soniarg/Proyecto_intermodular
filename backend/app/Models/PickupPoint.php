<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupPoint extends Model
{
    protected $table = 'pickup_points';
    
    protected $fillable = [
        'latitude',
        'longitude',
        'address'
    ];

    protected $hidden = [
        'seller_id',
        'created_at',
        'updated_at'
    ];

    public function sellerProfile(){
        return $this->belongsTo(SellerProfile::class, 'seller_id');
    }
}
