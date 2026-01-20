<?php

namespace App\Http\Controllers;

use App\Models\PickupPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http; // Necesario para peticiones a APIs externas

class PickupPointController extends Controller
{
    /**
     * Listar los puntos de recogida.
     * Al estar protegido por Middleware, sabemos que el usuario es Vendedor.
     */
    public function index()
    {
<<<<<<< HEAD
        // Devolvemos solo los puntos que pertenecen al vendedor logueado
        return PickupPoint::where('seller_id', Auth::id())->get();
=======
        // 1. Obtenemos el perfil de vendedor del usuario logueado
        $sellerProfile = Auth::user()->sellerProfile;

        // Seguridad: Si el usuario no es vendedor, devolvemos lista vacía o error
        if (!$sellerProfile) {
            return []; 
        }

        // 2. Buscamos los puntos que coincidan con ese ID DE PERFIL
        return PickupPoint::where('user_id', $sellerProfile->user_id)->get();
>>>>>>> origin/sprint4-Sonia
    }

    /**
     * Crear un nuevo punto de recogida.
     * Recibe dirección, calcula coordenadas automáticamente y guarda.
     */
    public function store(Request $request)
    {
        // 1. Validamos datos de dirección (No pedimos lat/long al usuario)
        $request->validate([
            'address'       => 'required|string|max:255',
            'city'          => 'required|string|max:100',
            'postal_code'   => 'required|string|max:20',
        ]);

        // 2. Preparamos la dirección para buscarla en Nominatim
        $direccionBusqueda = sprintf(
            "%s, %s, %s, España",
            $request->address,
            $request->city,
            $request->postal_code
        );

        // 3. Obtenemos coordenadas (Latitud/Longitud)
        $coords = $this->obtenerCoordenadas($direccionBusqueda);

        if (!$coords) {
            return response()->json([
                'error' => 'No pudimos localizar esa dirección exacta. Por favor, revisa la calle o el código postal.'
            ], 422); // 422 Unprocessable Entity
        }

<<<<<<< HEAD
        // 4. Creamos el registro en la Base de Datos
        $punto = PickupPoint::create([
            'seller_id' => Auth::id(), // Asignación automática al usuario actual
            'address'   => $request->address . ', ' . $request->city, // Guardamos formato legible
            'latitude'  => $coords['lat'],
            'longitude' => $coords['lon']
        ]);
=======
        // 3. Añadimos el ID del perfil a los datos validados
        // IMPORTANTE: En tu DB 'user_id' hace referencia a 'seller_profiles'
        $validated['user_id'] = $sellerProfile->user_id;
>>>>>>> origin/sprint4-Sonia

        return response()->json($punto, 201);
    }

    /**
     * Actualizar un punto existente.
     * Si cambia la dirección, recalcula las coordenadas.
     */
    public function update(Request $request, PickupPoint $pickupPoint)
    {
<<<<<<< HEAD
        // 1. SEGURIDAD: Verificar propiedad (¿Es mi punto?)
        if ($pickupPoint->seller_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado. Este punto no te pertenece.'], 403);
=======
        // 1. SEGURIDAD: ¿Este punto pertenece a mi perfil de vendedor?
        $myProfileId = Auth::user()->sellerProfile->user_id ?? null;

        if ($pickupPoint->user_id !== $myProfileId) {
            return response()->json(['error' => 'No autorizado. Este punto no es tuyo.'], 403);
>>>>>>> origin/sprint4-Sonia
        }

        // 2. Validación (campos opcionales 'nullable' por si solo edita uno)
        $request->validate([
            'address'       => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:100',
            'postal_code'   => 'nullable|string|max:20',
        ]);

        // 3. Lógica de Recálculo de Coordenadas
        // Si el usuario envía algún dato de dirección, intentamos geolocalizar de nuevo
        if ($request->hasAny(['address', 'city', 'postal_code'])) {
            
            // Reconstruimos la dirección mezclando datos nuevos con los viejos (si faltan)
            // Nota: Esto asume que si guardaste "Calle, Ciudad" en address, tendrás que ajustar esto según tu lógica exacta de guardado.
            // Para simplificar, aquí asumo que los envías por separado o reconstruyes string.
            $direccionBusqueda = sprintf(
                "%s, %s, %s, España",
                $request->address ?? $pickupPoint->address, 
                $request->city ?? '', // Idealmente deberías tener columna city en BD para recuperar el valor viejo
                $request->postal_code ?? '' // Igual con el CP
            );

            $coords = $this->obtenerCoordenadas($direccionBusqueda);

            if ($coords) {
                $pickupPoint->latitude = $coords['lat'];
                $pickupPoint->longitude = $coords['lon'];
            }
        }

        // 4. Actualizamos campos de texto si se enviaron
        if ($request->has('address')) {
             $pickupPoint->address = $request->address; 
             if ($request->has('city')) {
                 $pickupPoint->address .= ', ' . $request->city;
             }
        }

        $pickupPoint->save();

        return response()->json($pickupPoint);
    }

    /**
     * Eliminar un punto.
     */
    public function destroy(PickupPoint $pickupPoint)
    {
<<<<<<< HEAD
        // 1. SEGURIDAD: Verificar propiedad
        if ($pickupPoint->seller_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado.'], 403);
=======
        // 1. SEGURIDAD: ¿Este punto es mío?
        $myProfileId = Auth::user()->sellerProfile->user_id ?? null;

        if ($pickupPoint->user_id !== $myProfileId) {
            return response()->json(['error' => 'No autorizado. Este punto no es tuyo.'], 403);
>>>>>>> origin/sprint4-Sonia
        }

        // 2. Borrar
        $pickupPoint->delete();

        return response()->noContent();
    }

    /* -------------------------------------------------------------------------- */
    /* MÉTODOS PRIVADOS (HELPERS)                                                 */
    /* -------------------------------------------------------------------------- */

    /**
     * Conecta con la API pública de Nominatim (OpenStreetMap)
     * Devuelve ['lat' => 0.0, 'lon' => 0.0] o null.
     */
    private function obtenerCoordenadas($direccionCompleta)
    {
        $url = "https://nominatim.openstreetmap.org/search";

        try {
            $response = Http::withHeaders([
                // IMPORTANTE: Cambia este email por el tuyo real o Nominatim podría bloquearte
                'User-Agent' => 'ProyectoIntermodular/1.0 (admin@tudominio.com)' 
            ])->get($url, [
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
            // En producción podrías guardar el error en log: Log::error($e->getMessage());
            return null;
        }

        return null;
    }
}