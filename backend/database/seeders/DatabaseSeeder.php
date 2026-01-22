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
        // 1. Limpieza de tablas
        Schema::disableForeignKeyConstraints();
        DB::table('users')->truncate();
        DB::table('seller_profiles')->truncate();
        DB::table('products')->truncate();
        DB::table('pickup_points')->truncate();
        DB::table('orders')->truncate();
        DB::table('order_lines')->truncate();
        Schema::enableForeignKeyConstraints();

        // 2. Crear el Usuario (La base de todo)
        $user = User::factory()->create([
            'name' => 'Agricultor Test',
            'email' => 'test@example.com',
            'role' => 'seller', 
            'password' => bcrypt('password'), // Aseguramos una contraseña conocida
        ]);

        // 3. Crear el Perfil de Vendedor
        DB::table('seller_profiles')->insert([
            'user_id' => $user->id, 
            'store_name' => 'Huerta de Valencia',
            'nif' => '12345678X',
            'description' => 'Las mejores naranjas y tomates de la terreta.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Crear Productos vinculados al usuario
        Product::factory(5)->create([
            'user_id' => $user->id, 
        ]);

        // 5. Puntos de recogida
        // CORRECCIÓN CRÍTICA AQUÍ:
        // 1. Cambiamos 'user_id' por 'seller_id' (según tu migración nueva).
        // 2. Añadimos 'city' y 'postal_code' que ahora son obligatorios.
        DB::table('pickup_points')->insert([
            [
                'seller_id'   => $user->id,  // <--- CAMBIADO
                'latitude'    => 39.4699,
                'longitude'   => -0.3763,
                'address'     => 'C/ San Vicente Mártir, 25',
                'city'        => 'Valencia', // <--- AÑADIDO
                'postal_code' => '46002',    // <--- AÑADIDO
                'created_at'  => now(), 
                'updated_at'  => now(),
            ],
            [
                'seller_id'   => $user->id,  // <--- CAMBIADO
                'latitude'    => 39.4750,
                'longitude'   => -0.3700,
                'address'     => 'Av. del Cid, 14',
                'city'        => 'Valencia', // <--- AÑADIDO
                'postal_code' => '46014',    // <--- AÑADIDO
                'created_at'  => now(), 
                'updated_at'  => now(),
            ]
        ]);
        
        echo "¡Base de datos inicializada con éxito para el usuario ID: {$user->id}!\n";
    }
}