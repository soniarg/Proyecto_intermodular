<?php

namespace App\Http\Controllers;

use App\Models\PickupPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http; 

class PickupPointController extends Controller
{
    /**
     * 1. Listar MIS puntos (Para el Vendedor)
     * Uso latest() para que vea primero los que acaba de crear.
     */
    public function index()
    {
        return PickupPoint::where('seller_id', Auth::id())
            ->latest()
            ->get();
    }

    /**
     * 2. (NUEVO) Obtener puntos de OTRO vendedor (Para el Comprador)
     * Esta función es la que usa el botón "Comprar" para mostrar el select.
     */
    public function getBySeller($id)
    {
        // Buscamos puntos donde el 'seller_id' coincida con el ID del vendedor del producto
        return PickupPoint::where('seller_id', $id)->get();
    }

    /**
     * 3. Guardar nuevo punto.
     */
    public function store(Request $request)
    {
        // Validación
        $validated = $request->validate([
            'address'     => 'required|string|max:255',
            'city'        => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
        ]);

        // Construir dirección para la API
        $direccionBusqueda = sprintf(
            "%s, %s, %s, España",
            $validated['address'],
            $validated['city'],
            $validated['postal_code']
        );

        // Obtener coordenadas
        $coords = $this->obtenerCoordenadas($direccionBusqueda);

        if (!$coords) {
            return response()->json([
                'message' => 'No pudimos localizar esa dirección. Verifica que la calle y el CP sean correctos.'
            ], 422); 
        }

        // Crear registro
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
     * 4. Actualizar punto existente.
     */
    public function update(Request $request, PickupPoint $pickupPoint)
    {
        // Seguridad: ¿Es dueño del punto?
        if ($pickupPoint->seller_id !== Auth::id()) {
            return response()->json(['message' => 'No tienes permiso para editar este punto.'], 403);
        }

        // Validación
        $request->validate([
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        // Detectar si hay cambios geográficos
        if ($request->hasAny(['address', 'city', 'postal_code'])) {
            
            $calle  = $request->address ?? $pickupPoint->address;
            $ciudad = $request->city ?? $pickupPoint->city;
            $cp     = $request->postal_code ?? $pickupPoint->postal_code;

            $direccionBusqueda = "$calle, $ciudad, $cp, España";
            
            $coords = $this->obtenerCoordenadas($direccionBusqueda);

            if (!$coords) {
                return response()->json([
                    'message' => 'La nueva dirección no se pudo localizar en el mapa. No se han guardado los cambios.'
                ], 422);
            }

            $pickupPoint->latitude = $coords['lat'];
            $pickupPoint->longitude = $coords['lon'];
        }

        // Actualizar textos
        $pickupPoint->fill($request->only(['address', 'city', 'postal_code']));
        $pickupPoint->save();

        return response()->json($pickupPoint);
    }

    /**
     * 5. Borrar punto
     */
    public function destroy(PickupPoint $pickupPoint)
    {
        if ($pickupPoint->seller_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $pickupPoint->delete();
        return response()->noContent();
    }

    /**
     * Helper Privado (Nominatim)
     */
    private function obtenerCoordenadas($direccionCompleta)
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'ProxiMarkt/1.0 (contacto@proximarkt.com)' 
            ])->timeout(5) 
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
            return null;
        }

        return null;
    }
}