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
        // 1. Limpieza de tablas (Desactivamos FKs para evitar errores al borrar)
        Schema::disableForeignKeyConstraints();
        DB::table('users')->truncate();
        DB::table('seller_profiles')->truncate();
        DB::table('products')->truncate();
        DB::table('pickup_points')->truncate();
        DB::table('orders')->truncate();
        DB::table('order_lines')->truncate();
        Schema::enableForeignKeyConstraints();

        // 2. Crear Usuario COMPRADOR
        $buyer = User::factory()->create([
            'name' => 'Comprador Test',
            'email' => 'test@example.com', 
            'password' => bcrypt('password'),
            'role' => 'buyer', 
        ]);
        echo "✅ Usuario COMPRADOR: test@example.com (Pass: password)\n";

        // 3. Crear Usuario VENDEDOR
        $seller = User::factory()->create([
            'name' => 'Agricultor Vecino',
            'email' => 'vecino@example.com',
            'password' => bcrypt('password'),
            'role' => 'seller',
        ]);

        // 4. Crear Perfil de Tienda para el vendedor
        DB::table('seller_profiles')->insert([
            'seller_id'   => $seller->id, // Usamos seller_id como PK
            'store_name'  => 'Huerta de Valencia',
            'nif'         => '12345678X',
            'description' => 'Las mejores naranjas y tomates de la terreta.',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        echo "✅ Usuario VENDEDOR: vecino@example.com (Pass: password)\n";

        // 5. Crear Productos para el vendedor
        // Asegúrate de que en ProductFactory uses 'seller_id' => User::factory() 
        // o pásalo manualmente aquí si el factory está antiguo.
        Product::factory(5)->create([
            'seller_id' => $seller->id, 
        ]);

        // 6. Crear Puntos de Recogida (CORREGIDO)
        DB::table('pickup_points')->insert([
            [
                'seller_id'   => $seller->id, 
                'latitude'    => 39.4699,
                'longitude'   => -0.3763,
                'address'     => 'C/ San Vicente Mártir, 25',
                'city'        => 'Valencia', // Campo nuevo obligatorio
                'postal_code' => '46002',    // Campo nuevo obligatorio
                'created_at'  => now(), 
                'updated_at'  => now(),
            ],
            [
                'seller_id'   => $seller->id,
                'latitude'    => 39.4750,
                'longitude'   => -0.3700,
                'address'     => 'Av. del Cid, 14',
                'city'        => 'Valencia',
                'postal_code' => '46014',
                'created_at'  => now(), 
                'updated_at'  => now(),
            ]
        ]);
        
        echo "✅ Datos de prueba (Productos y Puntos) generados correctamente.\n";
    }
}