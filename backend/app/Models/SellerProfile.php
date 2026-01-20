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

    public function user()
    {
        return $this->belongsTo(User::class, 'seller_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'seller_id', 'seller_id'); 
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id', 'seller_id');
    }
}