<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    // Tu migración dice que la PK es 'id', así que no hace falta tocar $primaryKey ni $incrementing

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

    // Relación inversa: Un producto aparece en muchas líneas de pedido
    public function orderLines()
    {
        return $this->hasMany(OrderLine::class);
    }
    
    public function sellerUser()
    {
        return $this->hasOneThrough(User::class, sellerProfile::class, 'seller_id');
    }
    
    // El producto pertenece a un perfil de vendedor
    public function sellerProfile()
    {
        return $this->belongsTo(SellerProfile::class, 'seller_id', 'seller_id');
    }
}