<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Obtenemos el usuario logueado
        $user = $request->user();

        // Buscamos productos donde 'seller_id' coincida con el ID del usuario
        // (Asumiendo que el User ID es igual al Seller ID según tu modelo SellerProfile)
        return Product::where('seller_id', $user->id)->latest()->get();
    }

    public function store(Request $request)
    {
        // 1. Validamos los datos
        $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit'  => 'required|string|in:unit,kg,box', 
            'stock' => 'required|numeric|min:0',
            // Validamos que sea un archivo de imagen real (jpg, png, etc)
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048' 
        ]);

        // 2. Preparamos los datos básicos
        $data = $request->except(['image']);
        
        // AQUÍ ESTÁ LA CLAVE: Asignamos el ID del usuario a 'seller_id'
        $data['seller_id'] = $request->user()->id; 
        
        $data['is_active'] = true;
        $data['estimated_weight'] = 0; // Valor por defecto si no viene del front

        // 3. Lógica de subida de imagen (si existe)
        if ($request->hasFile('image')) {
            // Guarda el archivo en storage/app/public/products
            $path = $request->file('image')->store('products', 'public');
            // Guardamos la ruta generada en la columna 'image_url'
            $data['image_url'] = $path; 
        }

        // 4. Creamos el producto
        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        // Verificación de seguridad: ¿El producto pertenece a este vendedor?
        if ($product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado. Este producto no es tuyo.'], 403);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'unit'  => 'sometimes|string|in:unit,kg,box',
            'stock' => 'sometimes|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $data = $request->except(['image']);

        // Si suben una nueva imagen, reemplazamos la anterior
        if ($request->hasFile('image')) {
            // Borrar imagen vieja del disco para no acumular basura
            if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
                Storage::disk('public')->delete($product->image_url);
            }

            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = $path;
        }

        $product->update($data);
        return $product;
    }

    public function destroy(Request $request, Product $product)
    {
        if ($product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Borrar imagen asociada al eliminar producto
        if ($product->image_url && Storage::disk('public')->exists($product->image_url)) {
             Storage::disk('public')->delete($product->image_url);
        }

        $product->delete();
        return response()->noContent();
    }
}