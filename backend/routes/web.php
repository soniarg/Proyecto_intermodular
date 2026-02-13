<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MapController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [MapController::class, 'index'])->name('home');

// Esto hace que cualquier ruta que no sea de la API devuelva el Vue
Route::get('/{any}', function () {
    return file_get_contents(public_path('index.html'));
})->where('any', '.*');