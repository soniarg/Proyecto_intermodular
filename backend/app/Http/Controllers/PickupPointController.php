<?php

namespace App\Http\Controllers;

use App\Models\PickupPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PickupPointController extends Controller
{
    /**
     * Listar los puntos de recogida de ESTE vendedor.
     */
    public function index()
    {
        // 1. Obtenemos el perfil de vendedor del usuario logueado
        $sellerProfile = Auth::user()->sellerProfile;

        // Seguridad: Si el usuario no es vendedor, devolvemos lista vacía o error
        if (!$sellerProfile) {
            return []; 
        }

        // 2. Buscamos los puntos que coincidan con ese ID DE PERFIL
        return PickupPoint::where('seller_id', $sellerProfile->seller_id)->get();
    }

    /**
     * Crear un nuevo punto de recogida.
     */
    public function store(Request $request)
    {
        // 1. Validamos los datos del formulario
        $validated = $request->validate([
            'address'   => 'required|string|max:255',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // 2. Obtenemos el ID del perfil de vendedor
        $sellerProfile = Auth::user()->sellerProfile;

        if (!$sellerProfile) {

            $sellerProfile = Auth::user()->sellerProfile()->create([
            'store_name' => Auth::user()->name,
            'nif' => '00000000X'
        ]);

        Auth::user()->load('sellerProfile');
        $sellerProfile = Auth::user()->sellerProfile;
        
        }

        // 3. Añadimos el ID del perfil a los datos validados
        // IMPORTANTE: En tu DB 'seller_id' hace referencia a 'seller_profiles'
        $validated['seller_id'] = $sellerProfile->seller_id;

        // 4. Creamos el registro
        return PickupPoint::create($validated);
    }

    /**
     * Actualizar un punto existente.
     */
    public function update(Request $request, PickupPoint $pickupPoint)
    {
        // 1. SEGURIDAD: ¿Este punto pertenece a mi perfil de vendedor?
        $myProfileId = Auth::user()->sellerProfile->seller_id ?? null;

        if ($pickupPoint->seller_id !== $myProfileId) {
            return response()->json(['error' => 'No autorizado. Este punto no es tuyo.'], 403);
        }

        // 2. Validamos solo lo que se envía (nullable por si no quiere cambiar todo)
        $validated = $request->validate([
            'address'   => 'string|max:255',
            'latitude'  => 'numeric',
            'longitude' => 'numeric',
        ]);

        // 3. Actualizamos
        $pickupPoint->update($validated);

        return $pickupPoint;
    }

    /**
     * Eliminar un punto.
     */
    public function destroy(PickupPoint $pickupPoint)
    {
        // 1. SEGURIDAD: ¿Este punto es mío?
        $myProfileId = Auth::user()->sellerProfile->seller_id ?? null;

        if ($pickupPoint->seller_id !== $myProfileId) {
            return response()->json(['error' => 'No autorizado. Este punto no es tuyo.'], 403);
        }

        // 2. Borramos
        $pickupPoint->delete();

        return response()->noContent(); // Devuelve 204 (Éxito sin contenido)
    }
}