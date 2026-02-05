<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\CustomerLocation;
use App\Models\SellerProfile;
use App\Models\Review; // ⬅️ Importamos el modelo Review

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'surname',
        'email',
        'password',
        'role',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Esto hace que el campo 'average_rating' aparezca siempre en el JSON del usuario.
     */
    protected $appends = ['average_rating']; // ⬅️ NUEVO

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- Relaciones Existentes ---

    public function locations()
    {
        return $this->hasMany(CustomerLocation::class);
    }

    public function seller()
    {
        return $this->hasOne(SellerProfile::class, 'seller_id', 'id');
    }

    // --- SISTEMA DE REVIEWS (NUEVO) ---

    /**
     * Obtener todas las reseñas que ha RECIBIDO este usuario.
     * La lógica es: Buscamos reseñas asociadas a pedidos donde este usuario
     * participaba (como comprador o vendedor), pero donde él NO fue el autor.
     */
    public function receivedReviews()
    {
        return Review::whereHas('order', function ($query) {
            $query->where('buyer_id', $this->id)
                  ->orWhere('seller_id', $this->id);
        })
        ->where('author_id', '!=', $this->id);
    }

    /**
     * Calcula la media de estrellas automáticamente.
     * Se podrá acceder como $user->average_rating
     */
    public function getAverageRatingAttribute()
    {
        // Calcula la media de la columna 'rating', redondeada a 1 decimal.
        // Si no tiene reviews, devuelve 0.
        return round($this->receivedReviews()->avg('rating'), 1) ?? 0;
    }
}