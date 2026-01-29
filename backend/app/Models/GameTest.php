<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameTest extends Model
{
    use HasFactory;
    
    // Esto permite rellenar estos campos de golpe
    protected $fillable = ['name', 'cost', 'image'];
}
