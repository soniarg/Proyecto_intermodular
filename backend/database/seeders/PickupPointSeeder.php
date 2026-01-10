<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PickupPointSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Desactivamos seguridad para poder limpiar la tabla y usar IDs inventados
        Schema::disableForeignKeyConstraints();
        DB::table('pickup_points')->truncate();
        
        // 2. Insertamos los datos CON LAS COLUMNAS CORRECTAS
        DB::table('pickup_points')->insert([
            [
                'seller_id' => 1, // Asignamos esto al vendedor con ID 1
                'latitude' => 39.4699,
                'longitude' => -0.3763,
                'address' => 'C/ San Vicente Mártir, 25', // <--- CAMPO OBLIGATORIO
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'seller_id' => 1, // También del vendedor 1 (o pon 2 si existe)
                'latitude' => 39.4750,
                'longitude' => -0.3700,
                'address' => 'Av. del Cid, 14', // <--- CAMPO OBLIGATORIO
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
        
        // 3. Reactivamos seguridad
        Schema::enableForeignKeyConstraints();
    }
}