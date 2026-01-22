<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PickupPoint;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function index()
    {
        // Traemos los puntos y la información del vendedor
        $puntos_vendedor = PickupPoint::with('sellerProfile')->get();

        $marcadores = $puntos_vendedor->map(function ($punto){
            return [
                'latitude'  => $punto->latitude,
                'longitude' => $punto->longitude,
                
                // 🛡️ CORRECCIÓN AQUÍ:
                // Usamos '?->' para decir "si existe el perfil, dame el nombre".
                // Usamos '??' para decir "si no existe, pon 'Comercio Local'".
                'store_name' => $punto->sellerProfile?->store_name ?? 'Comercio Local'
            ];
        });
        
        return response()->json($marcadores);
    }
}