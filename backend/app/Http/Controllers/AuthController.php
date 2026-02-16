<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    // ... (Login, Register, Logout y User se quedan IGUAL, no los toques) ...

    public function login(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'error' => ['Las credenciales son incorrectas.'],
            ]);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Login exitoso',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 200);
    }

    public function register(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ], 
        [
            'email.unique' => "El correo electrónico ya está registrado",
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'surname' => $validated['surname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function user(Request $request){
        return response()->json($request->user()->load('seller'));
    }

    // -------------------------------------------------------------------------
    // AQUÍ ESTÁ LA FUNCIÓN MODIFICADA CON TODO LO QUE PEDISTE
    // -------------------------------------------------------------------------
    public function updateProfile(Request $request) {
        $user = $request->user();

        // 1. Reglas de validación (ahora incluye store_name condicionalmente)
        $rules = [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ];

        // Si es vendedor, permitimos que llegue el nombre de la tienda
        if ($user->role === 'seller' || $user->role === 'vendedor') {
            $rules['store_name'] = 'sometimes|string|max:255';
        }

        $request->validate($rules);

        // 2. LÓGICA NUEVA: Borrar foto de perfil
        // Si el frontend envía delete_avatar: true, borramos la foto
        if ($request->boolean('delete_avatar')) {
            if ($user->avatar_url) {
                Storage::disk('public')->delete($user->avatar_url);
            }
            $user->avatar_url = null;
        }

        // 3. LÓGICA EXISTENTE: Cambiar foto de perfil (Subir nueva)
        if ($request->hasFile('avatar_url')) {
            if ($user->avatar_url) {
                Storage::disk('public')->delete($user->avatar_url);
            }
            $path = $request->file('avatar_url')->store('avatars', 'public');
            $user->avatar_url = $path; 
        }

        // 4. Actualizar datos del usuario
        $user->name = $request->name;
        $user->surname = $request->surname;
        $user->email = $request->email;
        $user->save();

        // 5. LÓGICA NUEVA: Actualizar nombre de tienda (si corresponde)
        if (($user->role === 'seller' || $user->role === 'vendedor') && $request->has('store_name')) {
            // Actualiza la tabla seller_profiles vinculada
            $user->seller()->update([
                'store_name' => $request->store_name
            ]);
        }

        // Devolvemos el usuario con los datos de la tienda recargados (load seller)
        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user' => $user->load('seller')
        ]);
    }
}