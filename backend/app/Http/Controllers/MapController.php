<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PickupPoint;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function index(Request $request)
    {
        $pickupPoints = null;
        $radioKm = 50; 

        // 1. ¿Nos envían coordenadas desde el Frontend?
        if ($request->has(['lat', 'lng'])) {
            
            $lat = $request->lat;
            $lng = $request->lng;

            // FÓRMULA HAVERSINE
            $pickupPoints = PickupPoint::select('*')
                ->selectRaw(
                    "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", 
                    [$lat, $lng, $lat]
                )
                ->with('seller') // Cargamos datos del vendedor
                ->having('distance', '<', $radioKm)
                ->orderBy('distance')
                ->get();
        
        } else {
            // 2. Si no hay coordenadas, devolvemos un límite para no saturar
            $pickupPoints = PickupPoint::with('seller')->limit(50)->get();
        }

        // 3. Formateo de respuesta JSON
        $resultado = $pickupPoints->map(function ($punto) {
            return [
                'latitude'  => $punto->latitude,
                'longitude' => $punto->longitude,
                // Usamos el operador seguro ?? por si no tiene perfil de vendedor aún
                'store_name'=> $punto->seller->store_name ?? 'Tienda ProxiMarkt',
                'address'   => $punto->address,
                'city'      => $punto->city,
                'distance'  => isset($punto->distance) ? round($punto->distance, 1) . ' km' : null
            ];
        });
        
        return response()->json($resultado);
    }
}