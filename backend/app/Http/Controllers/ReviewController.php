<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NewReviewReceived;

class ReviewController extends Controller
{
    public function store(Request $request, $orderId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();
        $order = Order::findOrFail($orderId);

        if ($order->buyer_id !== $user->id && $order->seller_id !== $user->id) {
            return response()->json(['message' => 'No tienes permiso para valorar este pedido.'], 403);
        }

        if ($order->status !== 'completed') {
            return response()->json(['message' => 'Solo se pueden valorar pedidos completados.'], 400);
        }

        $existingReview = Review::where('order_id', $order->id)
                                ->where('author_id', $user->id)
                                ->first();

        if ($existingReview) {
            return response()->json(['message' => 'Ya has enviado una valoración para este pedido.'], 409);
        }

        $review = Review::create([
            'author_id' => $user->id,
            'order_id' => $order->id,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        $order = Order::with(['buyer', 'seller'])->find($orderId);

        if ($order) {
            $targetUser = null;

            if (Auth::id() === $order->buyer_id) {
                $targetUser = $order->seller;
            } 
            else {
                $targetUser = $order->buyer;
            }

            if ($targetUser) {
                $targetUser->notify(new NewReviewReceived($review, Auth::user()->name));
            }
        }

        return response()->json([
            'message' => 'Valoración enviada correctamente.',
            'data' => $review
        ], 201);
    }

    public function getUserReviews($userId)
    {
        $reviewsReceived = Review::whereHas('order', function ($query) use ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('buyer_id', $userId)   
                  ->orWhere('seller_id', $userId); 
            });
        })
        ->where('author_id', '!=', $userId)
        ->with('author:id,name') 
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return response()->json($reviewsReceived);
    }

    public function update(Request $request, $orderId)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000', 
        ]);

        $userId = Auth::id();

        $review = Review::where('order_id', $orderId)
                        ->where('author_id', $userId)
                        ->firstOrFail();

        $review->update([
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json(['message' => 'Valoración actualizada correctamente', 'review' => $review]);
    }
}