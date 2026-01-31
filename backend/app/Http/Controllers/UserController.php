<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\SellerProfile;

class UserController extends Controller
{   
    // Les llamo de esta manera predefinida a las funciones para que la estructura usada en api.php sea capaz de llamarlas de manera sencilla

    // Get
    public function index() {

        return User::all();
    }

    // Post
    public function store(Request $request) {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'in:seller,buyer,admin',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'surname' => $validated['surname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? 'buyer',
        ]);

        return response()->json($user, 201);
    }

    // Get (por ID)
    public function show(string $id) {

        $user = User::findOrFail($id);

        // ⬇️ MODIFICACIÓN: Cargamos las reseñas recibidas
        // Usamos la relación 'receivedReviews' que añadimos al modelo User
        if (method_exists($user, 'receivedReviews')) {
            $reviews = $user->receivedReviews()
                            // Traemos solo los datos básicos del autor de la reseña
                            ->with('author:id,name,surname,avatar_url') 
                            ->orderBy('created_at', 'desc')
                            ->get();

            // Las adjuntamos al objeto usuario para que el frontend las reciba
            $user->reviews = $reviews;
        }

        return $user;
    }

    // Put
    public function update(Request $request, string $id) {

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'surname' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|in:seller,buyer,admin',
        ]);

        $user->update($validated);
        
        return response()->json($user, 200);
    }

    // Delete
    public function destroy(string $id) {
        
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado'], 204);
    }

    public function becomeSeller(Request $request) {
        $user = $request->user();

        if ($user->role === 'seller' || $user->seller()->exists()) {
            return response()->json(['message' => 'Ya eres vendedor o tienes una tienda creada'], 400);
        }

        $validated = $request->validate([
            'store_name' => 'required|string|max:255',
            'nif'        => 'required|string|max:20', 
            'description'=> 'nullable|string'
        ]);

        SellerProfile::create([
            'seller_id'   => $user->id, 
            'store_name'  => $validated['store_name'],
            'nif'         => $validated['nif'],
            'description' => $validated['description'] ?? null,
            'avatar_url'  => null 
        ]);

        $user->role = 'seller';
        $user->save();

        return response()->json([
            'message' => '¡Tienda creada con éxito!',
            'user'    => $user->load('seller') 
        ]);
    }
}