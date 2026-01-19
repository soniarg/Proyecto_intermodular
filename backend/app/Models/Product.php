<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',         
        'title', 
        'price', 
        'unit', 
        'estimated_weight', 
        'stock', 
        'image_url',       
        'is_active'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orderLines()
    {
        return $this->hasMany(OrderLine::class);
    }
    
    public function sellerProfile()
    {
        return $this->belongsTo(SellerProfile::class, 'user_id', 'user_id');
    }
}