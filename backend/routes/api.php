<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


// -------------- Rutas Públicas --------------

Route::post('/login', [AuthController::class, 'login']);


//Esta ruta la dejo pública de momento para hacer pruebas,
// esta estructura permite poder llamar a las funciones con nombres predeterminados de UserController.php (index, store, show, update, destroy)
Route::apiResource('users', UserController::class);

// -------------- Rutas Protegidas --------------

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // Esta ruta es la primera que debería llamar Vue en caso de no tener en memoria los datos de un token al portador (comprobar la validez y contenido)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
