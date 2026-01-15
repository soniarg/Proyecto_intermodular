<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Borramos todo antes de empezar para evitar errores
        Schema::disableForeignKeyConstraints();
        DB::table('users')->truncate();
        DB::table('seller_profiles')->truncate();
        DB::table('products')->truncate();
        DB::table('pickup_points')->truncate();
        Schema::enableForeignKeyConstraints();

        // Creo el usuario vendedor
        $user = User::factory()->create([
            'name' => 'Agricultor Test',
            'email' => 'test@example.com',
            'role' => 'vendedor',
        ]);

        // Como no tengo factory del perfil del vendedor, lo creo
        $sellerId = DB::table('seller_profiles')->insertGetId([
            'user_id' => $user->user_id, // Usamos el ID del usuario recién creado
            'store_name' => 'Huerta de Valencia',
            'nif' => '12345678X',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Creao productos relacionados con el id del vendedor
        Product::factory(5)->create([
            'seller_id' => $sellerId,
        ]);

        // Datos fijos de Valencia (puntos de entrega)
        DB::table('pickup_points')->insert([
            [
                'seller_id' => $sellerId,
                'latitude' => 39.4699,
                'longitude' => -0.3763,
                'address' => 'C/ San Vicente Mártir, 25',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'seller_id' => $sellerId,
                'latitude' => 39.4750,
                'longitude' => -0.3700,
                'address' => 'Av. del Cid, 14',
                'created_at' => now(), 'updated_at' => now(),
            ]
        ]);
    }
}