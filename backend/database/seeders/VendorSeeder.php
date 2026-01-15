<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
    \App\Models\Product::truncate();
    \App\Models\PickupPoint::truncate();

    // Comprobamos que el usuario existe
    $user = \App\Models\User::firstOrCreate(
        ['email' => 'test@example.com'],
        [
            'name' => 'Vendedor de Prueba',
            'password' => bcrypt('password'),
            'role' => 'vendedor'
        ]
    );

    // Comprobamos perfil del vendedor
    $seller = \App\Models\SellerProfile::firstOrCreate(
        ['user_id' => $user->user_id],
        [
            'store_name' => 'Mi Granja Valenciana',
            'nif' => '12345678Z'
        ]
    );

    // Creo 5 productos aleatorios
    \App\Models\Product::factory(5)->create([
        'seller_id' => $seller->seller_id
    ]);

    // Puntos de entrega de Valencia y 3 aleatorios
    \Illuminate\Support\Facades\DB::table('pickup_points')->insert([
        [
            'seller_id' => $seller->seller_id,
            'latitude' => 39.4699, 'longitude' => -0.3763,
            'address' => 'C/ San Vicente Mártir, 25',
            'created_at' => now(), 'updated_at' => now(),
        ],
        [
            'seller_id' => $seller->seller_id,
            'latitude' => 39.4750, 'longitude' => -0.3700,
            'address' => 'Av. del Cid, 14',
            'created_at' => now(), 'updated_at' => now(),
        ]
    ]);

    \App\Models\PickupPoint::factory(3)->create([
        'seller_id' => $seller->seller_id
    ]);

    \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
}
}
