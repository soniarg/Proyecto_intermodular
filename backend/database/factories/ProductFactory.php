<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'seller_id' => \App\Models\User::factory(), 
            'title' => 'Producto ProxiMarkt',
            'price' => 9.99,
            'unit' => 'kg', 
            'estimated_weight' => 1.0,
            'stock' => 10,
            'is_active' => true,
        ];
    }
}