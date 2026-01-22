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
        'city',         // <--- Asegúrate de que esté aquí
        'postal_code',  // <--- Asegúrate de que esté aquí
        'latitude',
        'longitude',
    ];

    // Relación estándar con el Usuario
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // RELACIÓN CLAVE PARA EL MAPA 🗺️
    public function sellerProfile()
    {
        // Explicación:
        // El punto tiene un 'seller_id' (que es un User ID, ej: 5).
        // Queremos el perfil donde 'user_id' sea 5.
        return $this->belongsTo(SellerProfile::class, 'seller_id', 'user_id');
    }
}