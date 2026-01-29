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
use App\Http\Controllers\GameTestController; // Controlador de prueba, no importar al proyecto real

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

// Obtener puntos de recogida de un vendedor específico (Para el Modal de Compra)

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
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/cancel/{id}', [SellerOrderController::class, 'cancelOrReject']);
    Route::post('/user/update', [AuthController::class, 'updateProfile']);
    Route::post('/user/become-seller', [UserController::class, 'becomeSeller']); // Vital para tu ProfileView
    Route::post('/logout', [AuthController::class, 'logout']);


    // --- 📦 GESTIÓN DE PRODUCTOS (VENDEDOR) ---
    Route::get('/seller/my-products', [ProductController::class, 'myProducts']); // Tus productos
    Route::post('/products', [ProductController::class, 'store']);        // Crear
    Route::put('/products/{product}', [ProductController::class, 'update']); // Editar
    Route::delete('/products/{product}', [ProductController::class, 'destroy']); // Borrar


    // --- 🛍️ COMPRAS (COMPRADOR) ---
    Route::post('/orders', [OrderController::class, 'store']);       // Realizar pedido
    Route::get('/my-orders', [OrderController::class, 'myOrders']);  // Historial de compras


    // --- 📍 PUNTOS DE RECOGIDA (VENDEDOR) ---
    // He mantenido TUS rutas específicas para que coincidan con tu Vue
    Route::get('/seller/pickup-points', [PickupPointController::class, 'index']);
    Route::get('/seller/pickup-points/{id}', [PickupPointController::class, 'getPointsBySeller']);
    Route::get('/seller/my-pickup-points', [PickupPointController::class, 'getOwnSellerPoints']);
    Route::post('/seller/pickup-points/store', [PickupPointController::class, 'store']);
    // Ojo: asegúrate que en Vue llames a la ruta con el ID al final para update/destroy
    Route::put('/seller/pickup-points/{pickupPoint}', [PickupPointController::class, 'update']);
    Route::delete('/seller/pickup-points/{pickupPoint}', [PickupPointController::class, 'destroy']);


    // --- 📋 GESTIÓN DE PEDIDOS (PANEL VENDEDOR) ---
    // Listados por estado
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

});