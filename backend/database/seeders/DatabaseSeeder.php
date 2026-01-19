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
        Schema::disableForeignKeyConstraints();
        DB::table('users')->truncate();
        DB::table('seller_profiles')->truncate();
        DB::table('products')->truncate();
        DB::table('pickup_points')->truncate();
        DB::table('orders')->truncate();
        DB::table('order_lines')->truncate();
        Schema::enableForeignKeyConstraints();

        $buyer = User::factory()->create([
            'name' => 'Yo Comprador',
            'email' => 'test@example.com', 
            'password' => bcrypt('password'),
            'role' => 'buyer', 
        ]);

        echo "Usuario COMPRADOR creado: test@example.com (ID: {$buyer->id})\n";

        $seller = User::factory()->create([
            'name' => 'Agricultor Vecino',
            'email' => 'vecino@example.com',
            'role' => 'seller',
        ]);

        DB::table('seller_profiles')->insert([
            'user_id' => $seller->id,
            'store_name' => 'Huerta de Valencia',
            'nif' => '12345678X',
            'description' => 'Las mejores naranjas y tomates de la terreta.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Product::factory(5)->create([
            'user_id' => $seller->id, 
        ]);

        DB::table('pickup_points')->insert([
            [
                'user_id' => $seller->id, 
                'latitude' => 39.4699,
                'longitude' => -0.3763,
                'address' => 'C/ San Vicente Mártir, 25',
                'created_at' => now(), 
                'updated_at' => now(),
            ],
            [
                'user_id' => $seller->id,
                'latitude' => 39.4750,
                'longitude' => -0.3700,
                'address' => 'Av. del Cid, 14',
                'created_at' => now(), 
                'updated_at' => now(),
            ]
        ]);
        
        echo "Usuario VENDEDOR creado: vecino@example.com (ID: {$seller->id}) con productos y tienda.\n";
    }
}