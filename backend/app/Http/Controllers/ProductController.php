<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return Product::where('user_id', $request->user()->id)->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'unit'  => 'required|string|in:unit,kg,box', 
            'stock' => 'required|numeric|min:0', 
            'image_url' => 'nullable|string|max:255' 
        ]);

        $product = Product::create([
            'user_id'   => $request->user()->id,
            'title'     => $validated['title'],
            'price'     => $validated['price'],
            'unit'      => $validated['unit'],
            'stock'     => $validated['stock'],
            'estimated_weight' => 0, 
            'image_url' => $validated['image_url'] ?? null, 
            'is_active' => true
        ]);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        if ($product->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'unit'  => 'sometimes|string|in:unit,kg,box',
            'stock' => 'sometimes|numeric',
            'image_url' => 'nullable|string|max:255'
        ]);

        $product->update($request->only(['title','price','stock','unit','is_active', 'image_url']));
        return $product;
    }

    public function destroy(Request $request, Product $product)
    {
        if ($product->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $product->delete();
        return response()->noContent();
    }
}