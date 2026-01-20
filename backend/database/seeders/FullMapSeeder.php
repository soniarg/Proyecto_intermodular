<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class FullMapSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Limpieza total (Ignorando las llaves foráneas para poder borrar sin miedo)
        Schema::disableForeignKeyConstraints();
        DB::table('pickup_points')->truncate();
        DB::table('seller_profiles')->truncate();
        DB::table('users')->truncate();
        Schema::enableForeignKeyConstraints();

        // 2. Crear el USUARIO (El dueño de la tienda)
        // Guardamos el ID en una variable ($userId) para usarla después
        $userId = DB::table('users')->insertGetId([
            'name' => 'Manolo',
            'surname' => 'Garcia',      // <--- OBLIGATORIO según tu migración
            'email' => 'manolo@test.com',
            'role' => 'seller',         // Ponemos rol vendedor
            'password' => Hash::make('password'),
            'avatar_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Crear el PERFIL DEL VENDEDOR
        // IMPORTANTE: En la tabla de tu compañero, la clave primaria es 'user_id'
        // y debe coincidir con el ID del usuario.
        DB::table('seller_profiles')->insert([
            'user_id' => $userId,     // <--- Usamos el ID del usuario que acabamos de crear
            'store_name' => 'Frutas Manolo',
            'description' => 'Las mejores frutas del barrio',
            'nif' => '12345678Z',       // <--- OBLIGATORIO según tu migración
            'banner_url' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Crear los PUNTOS DE RECOGIDA
        // Los vinculamos al vendedor usando $userId
        DB::table('pickup_points')->insert([
            [
                'user_id' => $userId,
                'latitude' => 39.4699,
                'longitude' => -0.3763,
                'address' => 'C/ San Vicente Mártir, 25',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $userId,
                'latitude' => 39.4750,
                'longitude' => -0.3700,
                'address' => 'Av. del Cid, 14',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}