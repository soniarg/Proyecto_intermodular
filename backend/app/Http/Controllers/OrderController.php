<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // 🛒 1. FUNCIÓN PARA COMPRAR
    public function store(Request $request)
    {
        // 1. Validamos datos de entrada
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'pickup_id'  => 'required|exists:pickup_points,id' // Obligatorio Sprint 4
        ]);

        // 2. Buscamos el producto
        $product = Product::findOrFail($request->product_id);

        // 3. Calculamos totales
        $total = $product->price * $request->quantity;
        $sellerId = $product->user_id; 

        // 4. Guardamos todo dentro de una transacción (si falla algo, no se guarda nada)
        return DB::transaction(function () use ($request, $product, $total, $sellerId) {
            
            // A. CREAR CABECERA DEL PEDIDO (Tabla 'orders')
            // Nota: Asegúrate de que en tu BD la columna sea 'total' o 'total_price'. 
            // Usamos 'total' basándonos en la última migración corregida.
            $order = Order::create([
                'buyer_id'  => Auth::id(),
                'seller_id' => $sellerId,
                'pickup_id' => $request->pickup_id,
                'status'    => 'pending',
                'total'     => $total, 
            ]);

            // B. CREAR LÍNEA DE PEDIDO (Tabla 'order_lines')
            // Aquí usamos los nombres EXACTOS de tu modelo OrderLine
            OrderLine::create([
                'order_id'         => $order->id,
                'product_id'       => $product->id,
                'quantity'         => $request->quantity,
                
                // CORRECCIÓN 1: Usamos el nombre correcto de tu modelo
                'price_at_moment'  => $product->price, 
                
                // CORRECCIÓN 2: Campo obligatorio en tu BD. Ponemos 1.0 por defecto.
                'weight_at_moment' => 1.0, 
                
                // 'real_weight' lo dejamos null de momento
            ]);

            return response()->json([
                'message' => '¡Pedido realizado correctamente! 🎉',
                'order'   => $order
            ], 201);
        });
    }

    // 📦 2. HISTORIAL DE PEDIDOS
    public function myOrders()
    {
        $orders = Order::where('buyer_id', Auth::id())
                        ->with(['seller', 'lines.product', 'pickupPoint']) 
                        ->latest()
                        ->get();

        return response()->json($orders);
    }
}