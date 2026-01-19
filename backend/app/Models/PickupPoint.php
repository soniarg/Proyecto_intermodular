<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasFactory;

class PickupPoint extends Model
{
    protected $table = 'pickup_points';
    
    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'address'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function seller(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sellerProfile(){
        return $this->belongsTo(SellerProfile::class, 'user_id');
    }
}
