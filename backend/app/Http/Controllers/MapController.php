<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PickupPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Necesario para saber quién es el usuario

class MapController extends Controller
{

    // AÑADIR UNA BÚSQUEDA POR POBLACIÓN EN EL MAPA, PARA NO SOLO BUSCAR LOS CERCANOS, SINO TAMBIÉN POR CIUDAD/PUEBLO

    public function index()
    {
        // 1. Configuración inicial
        $radioKm = 10; // Radio de búsqueda (puedes ajustar esto)
        $pickupPoints = null;
        
        // Inicializamos la consulta base cargando siempre la relación 'seller'
        $query = PickupPoint::with('seller');

        // 2. Intentamos obtener la ubicación guardada del usuario logueado
        $user = Auth::user();
        
        // Buscamos en la tabla 'customer-locations' la ubicación de este usuario
        // OJO: Como tu tabla tiene un guion, usamos DB::table o asegúrate que tu modelo lo soporte
        $userLocation = null;
        
        if ($user) {
            $userLocation = User::with('locations')
                ->where('user_id', $user->id)
                ->first();
        }

        // 3. Si encontramos coordenadas del usuario, aplicamos el filtro de distancia
        if ($userLocation && $userLocation->latitude && $userLocation->longitude) {
            
            $lat = $userLocation->latitude;
            $lng = $userLocation->longitude;

            $pickupPoints = $query->select('*')
                ->selectRaw(
                    "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance", 
                    [$lat, $lng, $lat]
                )
                ->having('distance', '<', $radioKm)
                ->orderBy('distance')
                ->get();

        } else {
            // 4. Si el usuario NO tiene ubicación guardada o no está logueado
            // Devolvemos todos los puntos (o podrías devolver vacío si prefieres)
            $pickupPoints = $query->get();
        }

        // 5. Formateamos los datos para enviarlos a Vue (JSON)
        // Usamos la variable $pickupPoints como pediste
        $resultado = $pickupPoints->map(function ($punto) {
            return [
                'latitude'  => $punto->latitude,
                'longitude' => $punto->longitude,
                'store_name'=> $punto->seller->store_name ?? 'Tienda',
                'distance'  => isset($punto->distance) ? round($punto->distance, 2) . ' km' : null
            ];
        });
        
        return response()->json($resultado);
    }
}