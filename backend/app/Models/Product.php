<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',  // <--- ¡AQUÍ ESTABA EL PROBLEMA! Antes ponía seller_id
        'title', 
        'price', 
        'unit', 
        'estimated_weight', 
        'stock', 
        'image_url', 
        'is_active'
    ];

    // Relación: El producto pertenece a un Vendedor (User)
    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    // Si usas SellerProfile, también puedes tener esta relación
    public function sellerProfile() 
    {
        return $this->belongsTo(SellerProfile::class, 'user_id', 'user_id');
    }
}