<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Guardar una nueva reseña.
     */
    public function store(Request $request, $orderId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $order = Order::findOrFail($orderId);

        // 1. Validar que el usuario participa en el pedido
        if ($order->buyer_id !== $user->id && $order->seller_id !== $user->id) {
            return response()->json(['message' => 'No tienes permiso para valorar este pedido.'], 403);
        }

        // 2. Validar que el pedido esté completado
        // Nota: Asegúrate de que 'completed' es el string exacto que usas en tu DB para pedidos finalizados
        if ($order->status !== 'completed') {
            return response()->json(['message' => 'Solo se pueden valorar pedidos completados.'], 400);
        }

        // 3. Validar si ya existe una reseña de este usuario para este pedido
        $existingReview = Review::where('order_id', $order->id)
                                ->where('author_id', $user->id)
                                ->first();

        if ($existingReview) {
            return response()->json(['message' => 'Ya has enviado una valoración para este pedido.'], 409); // 409 Conflict
        }

        // 4. Crear la reseña
        $review = Review::create([
            'author_id' => $user->id,
            'order_id' => $order->id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json([
            'message' => 'Valoración enviada correctamente.',
            'data' => $review
        ], 201);
    }

    /**
     * Obtener las reseñas de un usuario (para ver su reputación).
     * Esto calcula las reseñas recibidas, no las escritas.
     */
    public function getUserReviews($userId)
    {
        // Buscamos pedidos donde el usuario participó y obtenemos la reseña de la OTRA parte
        $reviewsReceived = Review::whereHas('order', function ($query) use ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('buyer_id', $userId)   // Si soy comprador...
                  ->orWhere('seller_id', $userId); // O soy vendedor...
            });
        })
        ->where('author_id', '!=', $userId) // ...pero la reseña NO la escribí yo (es la que recibí)
        ->with('author:id,name') // Traer nombre de quien escribió
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return response()->json($reviewsReceived);
    }

    // FUNCIÓN PARA ACTUALIZAR UNA RESEÑA EXISTENTE
    public function update(Request $request, $orderId)
    {
        // 1. Validar igual que en el store
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000', // Un poco más de margen para editar
        ]);

        $userId = Auth::id();

        // 2. Buscar la reseña existente.
        // Buscamos por ID de pedido y nos aseguramos de que el autor seas TÚ.
        // Usamos firstOrFail para que si no existe, de un error 404 automáticamente.
        $review = Review::where('order_id', $orderId)
                        ->where('author_id', $userId)
                        ->firstOrFail();

        // 3. Actualizar los datos
        $review->update([
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null, // Usar null si el comentario viene vacío
        ]);

        return response()->json(['message' => 'Valoración actualizada correctamente', 'review' => $review]);
    }
}