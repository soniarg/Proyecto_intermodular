<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasFactory;

class PickupPoint extends Model
{
    protected $table = 'pickup_points';
    
    protected $fillable = [
        'seller_id',
        'latitude',
        'longitude',
        'address',
        'city',
        'postal_code'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function seller(){
        return $this->belongsTo(SellerProfile::class, 'seller_id', 'seller_id');
    }
}
