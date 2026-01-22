<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'title', 
        'price', 
        'unit', 
        'estimated_weight', 
        'stock', 
        'image_url',       
        'is_active'
    ];

    public function seller() 
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id', 'seller_id');
    }
}