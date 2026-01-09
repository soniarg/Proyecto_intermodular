<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
}
