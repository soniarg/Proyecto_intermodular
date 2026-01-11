<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PickupPoint;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function index()
    {
        $puntos_vendedor = PickupPoint::with('sellerProfile')->get();

        $marcadores = $puntos_vendedor->map(function ($punto){
            return[
                'latitude' => $punto->latitude,
                'longitude' => $punto->longitude,
                'store_name' => $punto->sellerProfile->store_name
            ];
        });
        
        return response()->json($marcadores);
    }
}