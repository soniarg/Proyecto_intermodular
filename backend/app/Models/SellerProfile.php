<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerProfile extends Model
{
    protected $table = 'seller_profiles'; 
    protected $primaryKey = 'seller_id';

    protected $fillable = [
        'store_name',
        'description',
        'nif',
        'banner_url'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

}
