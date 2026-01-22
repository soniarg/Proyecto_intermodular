<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupPoint extends Model
{
    use HasFactory;

    protected $table = 'pickup_points';
    
    protected $fillable = [
        'seller_id',
        'address',
        'city',         
        'postal_code',  
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