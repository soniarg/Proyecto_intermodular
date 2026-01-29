<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Factories\Sequence; // <--- IMPORTANTE: Añade esto arriba

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \App\Models\Product::truncate();
        \App\Models\PickupPoint::truncate();

        // 1. Usuario
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Vendedor de Prueba',
                'password' => bcrypt('password'),
                'role' => 'vendedor'
            ]
        );

        // 2. Perfil Vendedor
        $seller = \App\Models\SellerProfile::firstOrCreate(
            ['user_id' => $user->user_id],
            [
                'store_name' => 'Mi Granja Valenciana',
                'nif' => '12345678Z'
            ]
        );

        // 3. PRODUCTOS (AQUÍ ESTÁ EL CAMBIO)
        // Usamos sequence para definir títulos distintos y que no se repitan
        \App\Models\Product::factory()
            ->count(3) // Indicamos que queremos 3
            ->state(new Sequence(
                ['title' => 'Naranjas de Mesa', 'price' => 1.50, 'unit' => 'kg'],
                ['title' => 'Limones Frescos',  'price' => 2.20, 'unit' => 'kg'],
                ['title' => 'Miel de Azahar',   'price' => 8.50, 'unit' => 'tarro']
            ))
            ->create([
                'user_id' => $seller->user_id,
                'image_url' => null, // Para que use tu logo local
            ]);

        // 4. Puntos de entrega manuales
        \Illuminate\Support\Facades\DB::table('pickup_points')->insert([
            [
                'user_id' => $seller->user_id,
                'latitude' => 39.4699, 'longitude' => -0.3763,
                'address' => 'C/ San Vicente Mártir, 25',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'user_id' => $seller->user_id,
                'latitude' => 39.4750, 'longitude' => -0.3700,
                'address' => 'Av. del Cid, 14',
                'created_at' => now(), 'updated_at' => now(),
            ]
        ]);

        // 5. Puntos de entrega aleatorios extra
        \App\Models\PickupPoint::factory(3)->create([
            'user_id' => $seller->user_id,
            // He quitado 'image_url' => null aquí porque los pickup_points 
            // normalmente no tienen imagen y podría dar error si la columna no existe.
        ]);

        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
    }
}