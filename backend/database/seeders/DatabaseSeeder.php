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
        // 1. Limpieza de tablas PRIMERO
        Schema::disableForeignKeyConstraints();
        DB::table('users')->truncate();
        DB::table('seller_profiles')->truncate();
        DB::table('products')->truncate();
        DB::table('pickup_points')->truncate();
        DB::table('orders')->truncate();
        DB::table('order_lines')->truncate();
        Schema::enableForeignKeyConstraints();

        // 2. Crear a Sonia (Admin) DESPUÉS de limpiar
        User::create([
            'name' => 'Sonia',
            'surname' => 'Roig',
            'email' => 'admin@proximarkt.com',
            'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
        ]);
        echo "✅ Admin Sonia creada.\n";

        // 3. Crear Usuario COMPRADOR
        $buyer = User::factory()->create([
            'name' => 'Comprador Test',
            'email' => 'test@example.com', 
            'password' => bcrypt('password'),
            'role' => 'buyer', 
        ]);

        // 4. Crear Usuario VENDEDOR
        $seller = User::factory()->create([
            'name' => 'Agricultor Vecino',
            'email' => 'vecino@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
        ]);

        // 5. Perfil de Tienda
        DB::table('seller_profiles')->insert([
            'seller_id'   => $seller->id,
            'store_name'  => 'Huerta de Valencia',
            'nif'         => '12345678X',
            'description' => 'Las mejores naranjas y tomates de la terreta.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // 6. Productos
        Product::factory(5)->create([
            'seller_id' => $seller->id, 
        ]);

        // 7. Puntos de Recogida
        DB::table('pickup_points')->insert([
            [
                'seller_id'   => $seller->id, 
                'latitude'    => 39.4699,
                'longitude'   => -0.3763,
                'address'     => 'C/ San Vicente Mártir, 25',
                'city'        => 'Valencia',
                'postal_code' => '46002',
                'created_at'  => now(), 
                'updated_at'  => now(),
            ]
        ]);
        
        echo "✅ Seeder completado con éxito.\n";
    }
}