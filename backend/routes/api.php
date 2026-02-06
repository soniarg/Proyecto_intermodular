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
use App\Http\Controllers\GameTestController;
use App\Http\Controllers\ReviewController; // ⬅️ NUEVO: Importar el controlador

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS (No requieren Login)
|--------------------------------------------------------------------------
*/

// Autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Mapa (Explorar)
Route::get('/mapas', [MapController::class, 'index']);

// Marketplace (Ver productos disponibles)
Route::get('/products', [ProductController::class, 'index']);

// Usuarios (General)
Route::apiResource('users', UserController::class);

// Obtener juegos (controlador de prueba)
Route::get('/juegos', [GameTestController::class, 'index']);

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (Requieren Token / Login)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // --- 👤 GESTIÓN DE USUARIO ---
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/cancel/{id}', [SellerOrderController::class, 'cancelOrReject']);
    Route::post('/user/update', [AuthController::class, 'updateProfile']);
    Route::post('/user/become-seller', [UserController::class, 'becomeSeller']); 
    Route::post('/logout', [AuthController::class, 'logout']);


    // --- 📦 GESTIÓN DE PRODUCTOS (VENDEDOR) ---
    Route::get('/seller/my-products', [ProductController::class, 'myProducts']); 
    Route::post('/products', [ProductController::class, 'store']);        
    Route::put('/products/{product}', [ProductController::class, 'update']); 
    Route::delete('/products/{product}', [ProductController::class, 'destroy']); 


    // --- 🛍️ COMPRAS (COMPRADOR) ---
    Route::post('/orders', [OrderController::class, 'store']);       
    Route::get('/my-orders', [OrderController::class, 'myOrders']);  


    // --- 📍 PUNTOS DE RECOGIDA (VENDEDOR) ---
    Route::get('/seller/pickup-points', [PickupPointController::class, 'index']);
    Route::get('/seller/pickup-points/{id}', [PickupPointController::class, 'getPointsBySeller']);
    Route::get('/seller/my-pickup-points', [PickupPointController::class, 'getOwnSellerPoints']);
    Route::post('/seller/pickup-points/store', [PickupPointController::class, 'store']);
    Route::put('/seller/pickup-points/{pickupPoint}', [PickupPointController::class, 'update']);
    Route::delete('/seller/pickup-points/{pickupPoint}', [PickupPointController::class, 'destroy']);


    // --- 📋 GESTIÓN DE PEDIDOS (PANEL VENDEDOR) ---
    Route::get('/seller/orders/new', [SellerOrderController::class, 'getNew']);
    Route::get('/seller/orders/pending', [SellerOrderController::class, 'getPending']);
    Route::get('/seller/orders/adjusted', [SellerOrderController::class, 'getAdjusted']);
    Route::get('/seller/orders/ready', [SellerOrderController::class, 'getReady']);
    Route::get('/seller/orders/history', [SellerOrderController::class, 'getHistory']);
    
    // Detalles y Acciones
    Route::get('/seller/orders/{id}', [SellerOrderController::class, 'show']);
    Route::post('/seller/orders/{id}/store', [SellerOrderController::class, 'store']);
    Route::put('/seller/orders/{id}/mark-pending', [SellerOrderController::class, 'markAsPending']);
    Route::put('/seller/orders/{id}/update', [SellerOrderController::class, 'update']);
    Route::put('/seller/orders/{id}/mark-ready', [SellerOrderController::class, 'markAsReady']);
    Route::put('/seller/orders/{id}/mark-completed', [SellerOrderController::class, 'markAsCompleted']);
    Route::put('/seller/orders/{id}/reject', [SellerOrderController::class, 'cancelOrReject']);


    // --- 💬 CHAT ---
    Route::get('/orders/{id}/messages', [ChatController::class, 'index']);
    Route::post('/orders/{id}/messages', [ChatController::class, 'store']);

    
    // --- ⭐ SISTEMA DE RESEÑAS (NUEVO) --- ⬅️ AQUÍ ESTÁN LAS RUTAS NUEVAS
    // Crear una reseña para un pedido específico
    Route::post('/orders/{id}/reviews', [ReviewController::class, 'store']);
    
    // (Opcional) Ver las reseñas de un usuario específico de forma aislada
    // Nota: Aunque ya salen en el perfil de usuario con la modificación anterior, esta ruta es útil si quieres paginarlas aparte.
    Route::get('/users/{id}/reviews', [ReviewController::class, 'getUserReviews']);

});