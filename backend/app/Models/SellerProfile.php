<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerProfile extends Model
{
    use HasFactory;

    protected $table = 'seller_profiles'; 
    
    // CORRECCIÓN 1: Tu base de datos dice que la clave es 'user_id'
    protected $primaryKey = 'user_id';
    
    public $incrementing = false;
    protected $keyType = 'int';

    // CORRECCIÓN 2: Permitimos rellenar 'user_id' en lugar de 'seller_id'
    protected $fillable = [
        'user_id', 
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
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function orders()
    {
        // Un pedido tiene un 'seller_id' que coincide con mi 'user_id'
        return $this->hasMany(Order::class, 'seller_id', 'user_id');
    }

    public function products()
    {
        // Un producto tiene un 'user_id' que coincide con mi 'user_id'
        return $this->hasMany(Product::class, 'user_id', 'user_id');
    }
}