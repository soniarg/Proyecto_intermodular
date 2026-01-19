<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    // ... (Login y Register se quedan igual) ...

    public function login(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
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

    // --- CORRECCIÓN AQUÍ ---
    public function updateProfile(Request $request) {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            // Validamos 'avatar_url' porque así lo llamarás desde el frontend
            'avatar_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
        ]);

        // Buscamos el archivo con la clave 'avatar_url'
        if ($request->hasFile('avatar_url')) {
            // Borramos foto vieja usando el nombre correcto de la columna
            if ($user->avatar_url) {
                Storage::disk('public')->delete($user->avatar_url);
            }
            
            $path = $request->file('avatar_url')->store('avatars', 'public');
            
            // ✅ CORREGIDO: Usamos el nombre real de la columna en la BD
            $user->avatar_url = $path; 
        }

        $user->name = $request->name;
        $user->surname = $request->surname;
        $user->email = $request->email;
        
        $user->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user' => $user
        ]);
    }
}