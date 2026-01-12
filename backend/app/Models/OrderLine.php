<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'weight_at_moment',
        'real_weight',
        'price_at_moment'
    ];

    // Relación: Una línea pertenece a un pedido
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relación: Una línea referencia a un producto específico
    // Esto es crucial para poder sacar el NOMBRE del producto (ej: "Tomates")
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}