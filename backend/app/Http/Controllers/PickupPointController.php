<?php

namespace App\Http\Controllers;

use App\Models\PickupPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http; 

class PickupPointController extends Controller
{
    /**
     * Listar puntos.
     * Uso latest() para que el vendedor vea primero los que acaba de crear.
     */
    public function index()
    {
        return PickupPoint::where('seller_id', Auth::id())
            ->latest() // Equivalente a orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Guardar nuevo punto.
     */
    public function store(Request $request)
    {
        // 1. Validación
        $validated = $request->validate([
            'address'     => 'required|string|max:255',
            'city'        => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
        ]);

        // 2. Construir dirección para la API
        $direccionBusqueda = sprintf(
            "%s, %s, %s, España",
            $validated['address'],
            $validated['city'],
            $validated['postal_code']
        );

        // 3. Obtener coordenadas
        $coords = $this->obtenerCoordenadas($direccionBusqueda);

        if (!$coords) {
            return response()->json([
                'message' => 'No pudimos localizar esa dirección. Verifica que la calle y el CP sean correctos.'
            ], 422); // Error de validación semántica
        }

        // 4. Crear registro
        $punto = PickupPoint::create([
            'seller_id'   => Auth::id(),
            'address'     => $validated['address'],
            'city'        => $validated['city'],
            'postal_code' => $validated['postal_code'],
            'latitude'    => $coords['lat'],
            'longitude'   => $coords['lon']
        ]);

        return response()->json($punto, 201);
    }

    /**
     * Actualizar punto existente.
     * MEJORA: Evita inconsistencias entre dirección y coordenadas.
     */
    public function update(Request $request, PickupPoint $pickupPoint)
    {
        // 1. Seguridad: ¿Es dueño del punto?
        if ($pickupPoint->seller_id !== Auth::id()) {
            return response()->json(['message' => 'No tienes permiso para editar este punto.'], 403);
        }

        // 2. Validación (nullable porque a veces solo quieren corregir una cosa)
        $validated = $request->validate([
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        // 3. Detectar si hay cambios geográficos
        // hasAny verifica si AL MENOS UNO de estos campos está presente en la petición
        if ($request->hasAny(['address', 'city', 'postal_code'])) {
            
            // Usamos los datos nuevos si vienen, si no, los viejos de la BD
            $calle  = $request->address ?? $pickupPoint->address;
            $ciudad = $request->city ?? $pickupPoint->city;
            $cp     = $request->postal_code ?? $pickupPoint->postal_code;

            $direccionBusqueda = "$calle, $ciudad, $cp, España";
            
            // Intentamos obtener nuevas coordenadas
            $coords = $this->obtenerCoordenadas($direccionBusqueda);

            if (!$coords) {
                // MEJORA IMPORTANTE: Si la dirección nueva no es válida, 
                // DETENEMOS todo. No guardamos el texto nuevo para evitar
                // que la dirección diga una cosa y el mapa muestre otra.
                return response()->json([
                    'message' => 'La nueva dirección no se pudo localizar en el mapa. No se han guardado los cambios.'
                ], 422);
            }

            // Si hay éxito, asignamos las nuevas coordenadas
            $pickupPoint->latitude = $coords['lat'];
            $pickupPoint->longitude = $coords['lon'];
        }

        // 4. Actualizar textos (Solo si llegamos aquí, significa que las coordenadas son válidas)
        // fill() actualiza el modelo en memoria con los datos que hayan llegado, pero no guarda aún
        $pickupPoint->fill($request->only(['address', 'city', 'postal_code']));
        
        // 5. Guardar todo en BD
        $pickupPoint->save();

        return response()->json($pickupPoint);
    }

    public function destroy(PickupPoint $pickupPoint)
    {
        if ($pickupPoint->seller_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $pickupPoint->delete();
        return response()->noContent(); // Devuelve 204 (Éxito sin contenido)
    }

    /**
     * Helper Privado
     */
    private function obtenerCoordenadas($direccionCompleta)
    {
        try {
            // Nominatim requiere un User-Agent válido para no bloquearte
            $response = Http::withHeaders([
                'User-Agent' => 'ProxiMarkt/1.0 (contacto@proximarkt.com)' 
            ])->timeout(5) // Esperar máximo 5 segundos
              ->get("https://nominatim.openstreetmap.org/search", [
                'q'      => $direccionCompleta,
                'format' => 'json',
                'limit'  => 1
            ]);

            if ($response->successful() && !empty($response->json())) {
                $data = $response->json()[0];
                return [
                    'lat' => $data['lat'],
                    'lon' => $data['lon']
                ];
            }
        } catch (\Exception $e) {
            // Si falla la conexión (timeout, DNS, etc), capturamos el error para que no explote la app
            // return null hará que el controlador devuelva el error 422
            return null;
        }

        return null;
    }
}