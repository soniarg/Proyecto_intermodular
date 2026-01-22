<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    // 1. PARA EL COMPRADOR (Marketplace)
    // Devuelve TODOS los productos activos de cualquier vendedor
    public function index()
    {   
        return Product::where('is_active', true)
                      ->with('seller') // Cargamos datos del vendedor (nombre, etc)
                      ->get();
    }

    // 2. PARA EL VENDEDOR (Panel de Gestión - Sprint 3)
    // Devuelve solo MIS productos
    public function myProducts()
    {
        $userId = auth()->id();
        return Product::where('user_id', $userId)->get();
    }

    public function store(Request $request)
    {
        // Validación
        $validated = $request->validate([
            'title' => 'required|string',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string', // kg, box, unit
            'stock' => 'required|numeric|min:0',
        ]);

        // Creación (Usando user_id como corregimos antes)
        return Product::create([
            'user_id' => auth()->id(), // <--- CORREGIDO: Usamos user_id
            'title' => $validated['title'],
            'price' => $validated['price'],
            'unit' => $validated['unit'],
            'estimated_weight' => 1.0, // Valor por defecto si no lo envían
            'stock' => $validated['stock'],
            'is_active' => true
        ]);
    }

    public function update(Request $request, Product $product)
    {
        // Seguridad: Solo el dueño puede editar
        if ($product->user_id !== auth()->id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $product->update($request->only(['title','price','stock','is_active']));
        return $product;
    }

    public function destroy(Product $product)
    {
        // Seguridad: Solo el dueño puede borrar
        if ($product->user_id !== auth()->id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $product->delete();
        return response()->noContent();
    }
}