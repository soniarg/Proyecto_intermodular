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
            'role' => 'seller', // Usamos 'seller' para ser consistentes con el enum
        ]);

        // 3. Crear el Perfil de Vendedor
        // NOTA: Usamos $user->id directamente como user_id
        DB::table('seller_profiles')->insert([
            'user_id' => $user->id, 
            'store_name' => 'Huerta de Valencia',
            'nif' => '12345678X',
            'description' => 'Las mejores naranjas y tomates de la terreta.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Crear Productos vinculados al usuario (user_id)
        // Sobrescribimos 'user_id' para que no use el del factory por defecto si está mal
        Product::factory(5)->create([
            'user_id' => $user->id, 
        ]);

        // 5. Puntos de recogida (usando user_id)
        DB::table('pickup_points')->insert([
            [
                'user_id' => $user->id, // CORREGIDO: Antes era seller_id
                'latitude' => 39.4699,
                'longitude' => -0.3763,
                'address' => 'C/ San Vicente Mártir, 25',
                'created_at' => now(), 
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id, // CORREGIDO: Antes era seller_id
                'latitude' => 39.4750,
                'longitude' => -0.3700,
                'address' => 'Av. del Cid, 14',
                'created_at' => now(), 
                'updated_at' => now(),
            ]
        ]);
        
        echo "¡Base de datos inicializada con éxito para el usuario ID: {$user->id}!\n";
    }
}