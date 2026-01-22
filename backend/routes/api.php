<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MapController; 
use App\Http\Controllers\SellerOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController; 
use App\Http\Controllers\PickupPointController;
use App\Http\Controllers\ChatController;

// -------------- Rutas Públicas --------------

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Mapa
Route::get('/mapas', [MapController::class, 'index']);

// 🛒 MARKETPLACE (SPRINT 4)
Route::get('/products', [ProductController::class, 'index']);

// 🔥 RUTA PÚBLICA PARA PUNTOS DE RECOGIDA (Para el Modal de Compra)
Route::get('/sellers/{id}/pickup-points', [PickupPointController::class, 'getBySeller']);

Route::apiResource('users', UserController::class);


// -------------- Rutas Protegidas (Necesitas Login) --------------

Route::middleware('auth:sanctum')->group(function () {

    // 👤 GESTIÓN DE USUARIO (¡ESTA ES LA QUE FALTABA!)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/user/update', [AuthController::class, 'updateProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // 📦 GESTIÓN DE PRODUCTOS (VENDEDOR)
    Route::get('/my-products', [ProductController::class, 'myProducts']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // 🛍️ COMPRAR Y PEDIDOS (SPRINT 4)
    Route::post('/orders', [OrderController::class, 'store']);       // Comprar
    Route::get('/my-orders', [OrderController::class, 'myOrders']);  // Historial

    // 📍 PUNTOS DE RECOGIDA (VENDEDOR)
    Route::apiResource('pickup-points', PickupPointController::class);

    // 📋 GESTIÓN DE PEDIDOS (VISTA VENDEDOR)
    Route::get('/seller/orders/new', [SellerOrderController::class, 'getNew']);
    Route::get('/seller/orders/pending', [SellerOrderController::class, 'getPending']);
    Route::get('/seller/orders/adjusted', [SellerOrderController::class, 'getAdjusted']);
    Route::get('/seller/orders/ready', [SellerOrderController::class, 'getReady']);
    Route::get('/seller/orders/history', [SellerOrderController::class, 'getHistory']);
    Route::get('/seller/orders/{id}', [SellerOrderController::class, 'show']);
    
    // Cambios de estado (Vendedor)
    Route::put('/seller/orders/{id}/mark-pending', [SellerOrderController::class, 'markAsPending']);
    Route::put('/seller/orders/{id}/update', [SellerOrderController::class, 'update']);
    Route::put('/seller/orders/{id}/mark-ready', [SellerOrderController::class, 'markAsReady']);
    Route::put('/seller/orders/{id}/mark-completed', [SellerOrderController::class, 'markAsCompleted']);
    Route::put('/seller/orders/{id}/cancel-reject', [SellerOrderController::class, 'cancelOrReject']);

    // 💬 CHAT
    Route::get('/orders/{id}/messages', [ChatController::class, 'index']);
    Route::post('/orders/{id}/messages', [ChatController::class, 'store']);
});