<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\User;
use App\Models\SellerProfile;
use App\Models\Product;
use App\Notifications\OrderStatusUpdated; 
use App\Notifications\NewOrderReceived;

class SellerOrderController extends Controller
{
    public function getNew(){
        $sellerId = Auth::id();
        $orders = $this->findAllOrders($sellerId, ['new']);
        $formattedOrders = $this->formatOrders($orders);
        return response()->json($formattedOrders, 200);
    }

    public function getPending(){
        $sellerId = Auth::id();
        $orders = $this->findAllOrders($sellerId, ['pending']);
        $formattedOrders = $this->formatOrders($orders);
        return response()->json($formattedOrders, 200);
    }

    public function getAdjusted(){
        $sellerId = Auth::id();
        $orders = $this->findAllOrders($sellerId, ['weight_adjusted']);
        $formattedOrders = $this->formatOrders($orders);
        return response()->json($formattedOrders, 200);
    }

    public function getReady(){
        $sellerId = Auth::id();
        $orders = $this->findAllOrders($sellerId, ['ready']);
        $formattedOrders = $this->formatOrders($orders);
        return response()->json($formattedOrders, 200);
    }

    public function getHistory(){
        $sellerId = Auth::id();
        $orders = $this->findAllOrders($sellerId, ['completed', 'rejected', 'cancelled']);
        $formattedOrders = $this->formatOrders($orders);
        return response()->json($formattedOrders, 200);
    }

    public function show($orderId){
        $sellerId = Auth::id();
        $allStatuses = ['new', 'pending', 'weight_adjusted', 'ready', 'completed', 'rejected', 'cancelled'];
        $order = $this->findOneOrder($orderId, $sellerId, $allStatuses);
        $collection = collect([$order]);
        $formattedOrder = $this->formatOrders($collection);
        return response()->json($formattedOrder, 200);
    }

    public function store(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'pickup_id' => 'required|exists:pickup_points,id'
        ]);

        $product = Product::findOrFail($id);

        if ($product->seller_id === Auth::id()) {
            return response()->json(['message' => 'No puedes comprar tus propios productos.'], 403);
        }

        if ($product->stock < $request->quantity) {
            return response()->json(['message' => 'No hay suficiente stock disponible.'], 400);
        }

        return DB::transaction(function () use ($request, $product) {
            $order = new Order();
            $order->buyer_id = Auth::id();
            $order->seller_id = $product->seller_id;
            $order->status = 'new';
            $order->pickup_id = $request->pickup_id;
            
            $totalPrice = $product->price * $request->quantity;
            $order->total_price = $totalPrice;
            $order->save();

            $line = new \App\Models\OrderLine(); 
            $line->order_id = $order->id;
            $line->product_id = $product->id;
            $line->quantity = $request->quantity;
            $line->price_at_moment = $totalPrice;
            $line->weight_at_moment = ($product->unit === 'kg') ? $request->quantity : 0;
            $line->save();

            $product->decrement('stock', $request->quantity);

            // 🔔 AQUI ESTÁ LA CLAVE: ENVIAR NOTIFICACIÓN AL VENDEDOR
            $seller = User::find($product->seller_id);
            if ($seller) {
                $seller->notify(new NewOrderReceived($order));
            }

            return response()->json([
                'message' => 'Pedido realizado con éxito',
                'order_id' => $order->id
            ], 201);
        });
    }

    public function markAsPending($orderId){
        $sellerId = Auth::id();
        $order = $this->findOneOrder($orderId, $sellerId, ['new']);

        $order->status = 'pending';
        $order->save();

        $this->notifyBuyer($order);

        return response()->json([
            'message' => 'Pedido aceptado correctamente',
            'order_id' => $order->id
        ], 200);
    }

    public function update(Request $request, $orderId){
        $validated = $request->validate([
            'lines' => 'required|array',
            'lines.*.id' => 'required|integer|exists:order_lines,id',
            'lines.*.real_weight' => 'required|numeric|decimal:0,2|gt:0|max:999999.99',
            'lines.*.unit_price' => 'nullable|numeric|decimal:0,2|gt:0|max:999999.99',
            'lines.*.quantity' => 'nullable|numeric|integer|gt:0|max:999999'
        ]);

        $sellerId = Auth::id();
        $order = $this->findOneOrder($orderId, $sellerId, ['pending', 'weight_adjusted']);

        DB::Transaction(function () use ($order, $validated){
            $totalPrice = 0;
            $orderLines = collect($validated['lines']);

            foreach($order->lines as $line){
                $data = $orderLines->firstWhere('id', $line->id);

                if($data){
                    $realWeight = $data['real_weight'];
                    $unitPrice = 0;

                    if(isset($data['unit_price']) && $data['unit_price'] > 0) {
                        $unitPrice = $data['unit_price'];
                    } elseif ($line->price_at_moment > 0) {
                        if ($line->product->unit === 'kg' && $line->weight_at_moment > 0) {
                            $unitPrice = $line->price_at_moment / $line->weight_at_moment;
                        } elseif ($line->product->unit !== 'kg' && $line->quantity > 0) {
                            $unitPrice = $line->price_at_moment / $line->quantity;
                        }
                    } else {
                        abort(400, "No se puede determinar el precio unitario.");
                    }
                    
                    if($line->product->unit === 'kg'){
                        $weightDifference = $realWeight - $line->weight_at_moment;
                        if($weightDifference > 0 && $weightDifference > $line->product->stock){
                            abort(400, "Faltan " . ($weightDifference - $line->product->stock) . "kg de stock.");
                        }
                        $line->product->decrement('stock', $weightDifference);
                        $line->real_weight = $realWeight;
                    
                    } elseif(isset($data['quantity'])){
                        $newQuantity = $data['quantity'];
                        $qtyDifference = $newQuantity - $line->quantity;
                        if($qtyDifference > 0 && $qtyDifference > $line->product->stock){
                             abort(400, "Faltan " . ($qtyDifference - $line->product->stock) . " unidades.");
                        }
                        $line->product->decrement('stock', $qtyDifference);
                        $line->quantity = $newQuantity; 
                        $line->real_weight = $realWeight; 
                    }

                    if ($line->product->unit === 'kg') {
                        $totalLinePrice = $unitPrice * $realWeight;
                    } else {
                        $totalLinePrice = $unitPrice * $line->quantity;
                    }

                    $line->price_at_moment = $totalLinePrice;
                    $line->save();
                    $totalPrice += $totalLinePrice;

                } else {
                    $totalPrice += $line->price_at_moment;
                }                
            }

            $order->total_price = $totalPrice;
            
            if($order->status === 'pending'){
                $order->status = 'weight_adjusted';
            }
            
            $order->save();
        });

        return response()->json(['message' => 'Pedido actualizado correctamente', 'total' => $order->total_price]);
    }

    public function markAsReady($orderId){
        $sellerId = Auth::id();
        $order = $this->findOneOrder($orderId, $sellerId, ['pending', 'weight_adjusted']);

        $order->status = 'ready';
        $order->save();

        $this->notifyBuyer($order);

        return response()->json(['message' => 'Pedido marcado como listo para recoger', 'order' => $order]);
    }

    public function markAsCompleted($orderId){
        $sellerId = Auth::id();
        $order = $this->findOneOrder($orderId, $sellerId, ['ready']);

        $order->status = 'completed';
        $order->save();

        $this->notifyBuyer($order);

        return response()->json(['message' => 'Pedido completado y entregado', 'order' => $order]);
    }

    public function cancelOrReject(Request $request, $orderId){
        $request->validate(['rejection_reason' => 'required|string|min:5|max:255']);
        $userId = Auth::id();
        $order = Order::with('lines.product')->find($orderId);

        if (!$order) abort(404, 'Pedido no encontrado');

        $isBuyer = ($order->buyer_id === $userId);
        $isSeller = ($order->seller_id === $userId);

        if (!$isBuyer && !$isSeller) abort(403, 'No tienes permiso.');

        if ($isBuyer) {
            if ($order->status !== 'new') abort(400, 'Ya no se puede cancelar.');
            $newStatus = 'cancelled';
        } elseif ($isSeller) {
            if (in_array($order->status, ['completed', 'rejected', 'cancelled'])) abort(400, 'Ya finalizado.');
            $newStatus = 'rejected';
        }

        DB::transaction(function () use ($order, $request, $newStatus) {
            foreach ($order->lines as $line) {
                if ($line->product->unit === 'kg') {
                    $pesoOriginal = max($line->weight_at_moment, $line->quantity);
                    
                    $weightToReturn = ($line->real_weight > 0) ? $line->real_weight : $pesoOriginal;
                    
                    if($weightToReturn > 0) $line->product->increment('stock', $weightToReturn);
                }
            }
            $order->status = $newStatus;
            $order->rejection_reason = $request->rejection_reason; 
            $order->save();
        });

        if ($isSeller) {
            $this->notifyBuyer($order);
        }

        $message = $isBuyer ? 'Has cancelado tu pedido correctamente.' : 'Has rechazado el pedido correctamente.';
        return response()->json(['message' => $message, 'status' => $newStatus]);
    }

    private function notifyBuyer($order) {
        $buyer = User::find($order->buyer_id);
        if ($buyer) {
            $buyer->notify(new OrderStatusUpdated($order));
        }
    }

    public function findOneOrder($orderId, $sellerId, array $status){
    // 1. Primero buscamos que el pedido exista y sea del vendedor
    $order = Order::where('id', $orderId)
                ->where('seller_id', $sellerId)
                ->first();
                
    if(!$order) {
        abort(404, 'No se ha encontrado el pedido.');
    }

    // 2. Luego verificamos si el estado es el correcto para la acción
    if(!in_array($order->status, $status)) {
        abort(400, "El pedido ya no está en el estado requerido. Por favor, recarga la página.");
    }

    return $order;
}

    public function findAllOrders($sellerId, array $status){
        $currentUserId = Auth::id(); 
        $orders = Order::with([
                        'buyer', 
                        'lines.product',
                        'reviews' => function($query) use ($currentUserId) {
                            $query->where('author_id', $currentUserId);
                        }
                    ])
                    ->where('seller_id', $sellerId)
                    ->whereIn('status', $status)
                    ->latest() 
                    ->get();
        return $orders;
    }

    public function formatOrders($orders){
        return $orders->map(function($order){
            $myReview = $order->reviews->first(); 
            return [
                'id' => $order->id,
                'status' => $order->status,
                'buyer_name' => $order->buyer->name,
                'total_price' => $order->total_price,
                'rejection_reason' => $order->rejection_reason,
                'local_review' => $myReview ? [
                    'rating' => $myReview->rating,
                    'comment' => $myReview->comment
                ] : null,
                'lines' => $order->lines->map(function($line) {
                    return [
                        'id' => $line->id,
                        'name' => $line->product->title,
                        'quantity' => $line->quantity,
                        'unit' => $line->product->unit,
                        'estimated_weight' => $line->weight_at_moment,
                        'real_weight' => $line->real_weight,
                        'line_price' => $line->price_at_moment
                    ];
                })
            ];
        });
    }
}