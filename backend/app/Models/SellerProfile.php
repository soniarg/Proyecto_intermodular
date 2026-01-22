<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerProfile extends Model
{
    use HasFactory;

    protected $table = 'seller_profiles'; 
    protected $primaryKey = 'seller_id';
    public $incrementing = false;
    protected $keyType = 'int';

    // CORRECCIÓN 2: Permitimos rellenar 'user_id' en lugar de 'seller_id'
    protected $fillable = [
        'seller_id', 
        'store_name',
        'description',
        'nif',
        'banner_url'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    // CORRECCIÓN 3: Ajustamos las relaciones para usar la clave correcta

    public function user()
    {
        // Relación inversa: Este perfil pertenece al User con el mismo ID
        return $this->belongsTo(User::class, 'seller_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'seller_id', 'seller_id'); 
    }

    public function products()
    {
        // Un producto tiene un 'seller_id' que coincide con mi 'seller_id'
        return $this->hasMany(Product::class, 'seller_id', 'seller_id');
    }
}