<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    // 1. PARA EL COMPRADOR (Página "Explorar")
    // Muestra todos los productos activos de todos los vendedores
    public function index()
    {   
        return Product::where('is_active', true)
                      ->with('seller') // Cargamos nombre de tienda
                      ->latest()
                      ->get();
    }

    // 2. PARA EL VENDEDOR (Su Panel de Control)
    // Muestra solo sus productos para editar/borrar
    public function myProducts(Request $request)
    {
        return Product::where('seller_id', $request->user()->id)
                      ->latest()
                      ->get();
    }

    // 3. Crear Producto (Con TU lógica de imágenes)
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit'  => 'required|string|in:unit,kg,box', 
            'stock' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048' 
        ]);

        $data = $request->except(['image']);
        
        // Asignamos el vendedor
        $data['seller_id'] = Auth::id();
        $data['is_active'] = true;
        $data['estimated_weight'] = 1.0; 

        // Subida de imagen
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = $path; 
        }

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        if ($product->seller_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|numeric',
            'image' => 'nullable|image|max:2048'
        ]);

        $data = $request->except(['image']);

        // Gestión de imagen (Borrar vieja, subir nueva)
        if ($request->hasFile('image')) {
            if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                Storage::disk('public')->delete($product->image_url);
            }
            $data['image_url'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);
        return $product;
    }

    public function destroy(Product $product)
    {
        if ($product->seller_id !== Auth::id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
             Storage::disk('public')->delete($product->image_url);
        }

        $product->delete();
        return response()->noContent();
    }
}