<?php

namespace App\Http\Controllers;

use App\Models\PickupPoint;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function index()
    {      
        $puntos = PickupPoint::with('sellerProfile')->get();

        $marcadores = $puntos->map(function ($punto) {
            return [
                'lat' => $punto->latitude,
                'long' => $punto->longitude,
                'info' => $punto->sellerProfile->store_name
            ];
        });

        $latInicial = 40.416;
        $longInicial = -3.703;

        $marcadores->push([
            'lat' => $latInicial,
            'long' => $longInicial,
            'info' => 'Punto de inicio'
        ]);

        return view('mapa', ['marcadores' => $marcadores->toArray()]);
    }
}
