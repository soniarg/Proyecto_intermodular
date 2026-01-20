<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MapController; 
use App\Http\Controllers\SellerOrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PickupPointController;
use App\Http\Controllers\ChatController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


// -------------- Rutas Públicas --------------

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Ruta para obtener los puntos del mapa
Route::get('/mapas', [MapController::class, 'index']);

// Esta ruta la dejo pública de momento para hacer pruebas,
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
    Route::post('/user/update', [AuthController::class, 'updateProfile']);

// Gestión de los pedidos (añadir un middleware para validar que el usuario que accede a los endpoints es un vendedor)
    //Obtener pedidos
    Route::get('/seller/orders/new', [SellerOrderController::class, 'getNew']);
    Route::get('/seller/orders/pending', [SellerOrderController::class, 'getPending']);
    Route::get('/seller/orders/adjusted', [SellerOrderController::class, 'getAdjusted']);
    Route::get('/seller/orders/ready', [SellerOrderController::class, 'getReady']);
    Route::get('/seller/orders/pending', [SellerOrderController::class, 'getPending']);
    Route::get('/seller/orders/history', [SellerOrderController::class, 'getHistory']);
    Route::get('/seller/orders/{id}', [SellerOrderController::class, 'show']);

    //Cambiar estado y actualizar
    Route::put('/seller/orders/{id}/mark-pending', [SellerOrderController::class, 'markAsPending']);
    Route::put('/seller/orders/{id}/update', [SellerOrderController::class, 'update']);
    Route::put('/seller/orders/{id}/mark-ready', [SellerOrderController::class, 'markAsReady']);
    Route::put('/seller/orders/{id}/mark-completed', [SellerOrderController::class, 'markAsCompleted']);
    Route::put('/seller/orders/{id}/cancel-reject', [SellerOrderController::class, 'cancelOrReject']);


// Chat
    // Ver mensajes
    Route::get('/orders/{id}/messages', [ChatController::class, 'index']);
    // Enviar mensaje
    Route::post('/orders/{id}/messages', [ChatController::class, 'store']);

});
