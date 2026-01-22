<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PickupPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Necesario para cálculos matemáticos crudos

class MapController extends Controller
{
    public function index(Request $request)
    {
        $pickupPoints = null;
        $radioKm = 50; // Buscar en 50km a la redonda (ajustable)

        // 1. ¿Nos envían coordenadas desde el Frontend?
        if ($request->has(['lat', 'lng'])) {
            
            $lat = $request->lat;
            $lng = $request->lng;

            // FÓRMULA HAVERSINE (Matemáticas para calcular distancia en esfera)
            // 6371 es el radio de la Tierra en km
            $pickupPoints = PickupPoint::select('*')
                ->selectRaw(
                    "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", 
                    [$lat, $lng, $lat]
                )
                ->with('seller') // Cargamos datos del vendedor (nombre tienda)
                ->having('distance', '<', $radioKm)
                ->orderBy('distance')
                ->get();
        
        } else {
            // 2. Si NO hay coordenadas (ej: usuario denegó permiso), devolvemos TODOS los puntos
            // o los limitamos a 50 para no saturar el mapa
            $pickupPoints = PickupPoint::with('seller')->limit(50)->get();
        }

        // 3. Formateo de respuesta JSON
        $resultado = $pickupPoints->map(function ($punto) {
            return [
                'latitude'  => $punto->latitude,
                'longitude' => $punto->longitude,
                'store_name'=> $punto->seller->store_name ?? 'Tienda ProxiMarkt',
                'address'   => $punto->address,
                'city'      => $punto->city,
                // Si existe distancia calculada, la añadimos redondeada
                'distance'  => isset($punto->distance) ? round($punto->distance, 1) . ' km' : null
            ];
        });
        
        return response()->json($resultado);
    }
}