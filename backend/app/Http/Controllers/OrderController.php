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

        // 3. Calculamos total_pricees
        $total_price = $product->price * $request->quantity;
        $sellerId = $product->seller_id; 

        // 4. Guardamos todo dentro de una transacción (si falla algo, no se guarda nada)
        return DB::transaction(function () use ($request, $product, $total_price, $sellerId) {
            
            // A. CREAR CABECERA DEL PEDIDO (Tabla 'orders')
            $order = Order::create([
                'buyer_id'  => Auth::id(),
                'seller_id' => $sellerId,
                'pickup_id' => $request->pickup_id,
                'status'    => 'new', // IMPORTANTE: El estado inicial suele ser 'new', no 'pending'
                'total_price' => $total_price, 
            ]);

            // B. CREAR LÍNEA DE PEDIDO (Tabla 'order_lines')
            OrderLine::create([
                'order_id'         => $order->id,
                'product_id'       => $product->id,
                'quantity'         => $request->quantity,
                'price_at_moment'  => $product->price, 
                'weight_at_moment' => 1.0, 
            ]);

            return response()->json([
                'message' => '¡Pedido realizado correctamente! 🎉',
                'order'   => $order
            ], 201);
        });
    }

    // 📦 2. HISTORIAL DE PEDIDOS (CORREGIDO PARA CARGAR REVIEWS)
    public function myOrders()
    {
        $userId = Auth::id();

        // Cargamos los pedidos con sus relaciones
        $orders = Order::where('buyer_id', $userId)
            ->with([
                'seller',           
                'lines.product', 
                'pickupPoint',
                // 👇 ESTO ES LO NUEVO: Cargar la review que YO hice
                'reviews' => function($query) use ($userId) {
                    $query->where('author_id', $userId);
                }
            ])
            ->latest()
            ->get();

        // Formateamos para que el frontend reciba el campo 'local_review' limpio
        $formattedOrders = $orders->map(function ($order) {
            
            // Buscamos mi reseña dentro de la colección de reviews cargada
            $myReview = $order->reviews->first();

            // Clonamos el objeto para no modificar el original si no queremos
            // o simplemente añadimos el campo al array de respuesta
            $orderData = $order->toArray();
            
            // Añadimos el campo clave para el frontend
            $orderData['local_review'] = $myReview ? [
                'rating' => $myReview->rating,
                'comment' => $myReview->comment
            ] : null;

            return $orderData;
        });

        return response()->json($formattedOrders);
    }
}