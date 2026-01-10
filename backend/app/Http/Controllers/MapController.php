<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PickupPoint;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function index()
    {
        // 1. Obtenemos todos los puntos de la base de datos
        // (Quitamos 'with' de momento para evitar errores si la relación no existe aún)
        $puntos = PickupPoint::all();

        // 2. Devolvemos JSON puro (esto es lo que Vue entiende)
        return response()->json($puntos);
    }
}