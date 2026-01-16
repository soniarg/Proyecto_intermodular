<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {   
        $seller_id = auth()->user()->sellerProfile->seller_id;
        return Product::where('seller_id', $seller_id)->get();
    }

    public function store(Request $request)
    {
        $seller_id = auth()->user()->sellerProfile->seller_id;
        return Product::create([
            ...$request->validate([
                'title' => 'required|string',
                'price' => 'required|numeric|min:0',
                'unit' => 'required|string',
                'stock' => 'required|numeric|min:0',
            ]),
            'seller_id' => auth()->id()
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $product->update($request->only(['title','price','stock','is_active']));
        return $product;
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->noContent();
    }
}
