<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MapController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [MapController::class, 'index'])->name('home');

// Esto hace que cualquier ruta que no sea de la API devuelva el Vue
Route::get('/{any}', function () {
    // Como está en public/dist/index.html, tenemos que indicarlo:
    $path = public_path('dist/index.html'); 
    
    if (file_exists($path)) {
        return file_get_contents($path);
    }
    
    // Si no lo encuentra, que nos diga dónde está buscando para arreglarlo
    return "No encuentro el index.html en: " . $path;
})->where('any', '.*');