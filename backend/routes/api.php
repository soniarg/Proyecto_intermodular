<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
<<<<<<< HEAD
use App\Http\Controllers\MapController; 
use App\Http\Controllers\SellerOrderController;
=======
use App\Http\Controllers\MapController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PickupPointController;
>>>>>>> origin/main

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

use App\Http\Controllers\ChatController;

// -------------- Rutas Públicas --------------

Route::post('/login', [AuthController::class, 'login']);

// Ruta para obtener los puntos del mapa
Route::get('/mapas', [MapController::class, 'index']);

//Esta ruta la dejo pública de momento para hacer pruebas,
// esta estructura permite poder llamar a las funciones con nombres predeterminados de UserController.php (index, store, show, update, destroy)
Route::apiResource('users', UserController::class);



// -------------- Rutas Protegidas --------------

Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('products', ProductController::class);
    Route::apiResource('pickup-points', PickupPointController::class);

// Gestión de usuario
    Route::post('/logout', [AuthController::class, 'logout']);
    // Esta ruta es la primera que debería llamar Vue en caso de no tener en memoria los datos de un token al portador (comprobar la validez y contenido)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

<<<<<<< HEAD
    Route::get('/seller/orders', [SellerOrderController::class, 'index']);

    Route::get('/seller/orders/news', [SellerOrderController::class, 'news']);
=======

// Chat
    // Ver mensajes
    Route::get('/orders/{id}/messages', [ChatController::class, 'index']);
    // Enviar mensaje
    Route::post('/orders/{id}/messages', [ChatController::class, 'store']);
>>>>>>> origin/main
});
