<?php

namespace App\Http\Controllers;

use App\Models\PickupPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http; 

class PickupPointController extends Controller
{
    /**
     * 1. Listar MIS puntos (Para el panel del Vendedor)
     */
    public function index()
    {
        return PickupPoint::where('seller_id', Auth::id())
            ->latest()
            ->get();
    }

    /**
     * 2. (IMPORTANTE DEL COMPAÑERO) Obtener puntos de un vendedor específico.
     * Esto se usa en el Checkout cuando un cliente compra productos del Vendedor X.
     */
    public function getBySeller($id)
    {
        return PickupPoint::where('seller_id', $id)->get();
    }

    /**
     * 3. Guardar nuevo punto (CON TU LÓGICA DE MAPAS)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'address'     => 'required|string|max:255',
            'city'        => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
        ]);

        $direccionBusqueda = sprintf("%s, %s, %s, España", $validated['address'], $validated['city'], $validated['postal_code']);

        $coords = $this->obtenerCoordenadas($direccionBusqueda);

        if (!$coords) {
            return response()->json([
                'message' => 'No pudimos localizar esa dirección. Verifica calle y CP.'
            ], 422); 
        }

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
     * 4. Actualizar punto (CON TU LÓGICA DE ACTUALIZAR COORDENADAS)
     */
    public function update(Request $request, PickupPoint $pickupPoint)
    {
        if ($pickupPoint->seller_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $request->validate([
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
        ]);

        // Si cambia la dirección, recalculamos coordenadas
        if ($request->hasAny(['address', 'city', 'postal_code'])) {
            
            $calle  = $request->address ?? $pickupPoint->address;
            $ciudad = $request->city ?? $pickupPoint->city;
            $cp     = $request->postal_code ?? $pickupPoint->postal_code;

            $direccionBusqueda = "$calle, $ciudad, $cp, España";
            $coords = $this->obtenerCoordenadas($direccionBusqueda);

            if (!$coords) {
                return response()->json(['message' => 'La nueva dirección no se encuentra en el mapa.'], 422);
            }

            $pickupPoint->latitude = $coords['lat'];
            $pickupPoint->longitude = $coords['lon'];
        }

        $pickupPoint->fill($request->only(['address', 'city', 'postal_code']));
        $pickupPoint->save();

        return response()->json($pickupPoint);
    }

    public function destroy(PickupPoint $pickupPoint)
    {
        if ($pickupPoint->seller_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $pickupPoint->delete();
        return response()->noContent();
    }

    // Helper Nominatim
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
                return ['lat' => $data['lat'], 'lon' => $data['lon']];
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }
}