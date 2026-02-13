<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        // Forzamos la creación del objeto faker para evitar el error "on null"
        $faker = \Faker\Factory::create(); 

        return [
            'seller_id' => \App\Models\User::factory(), 
            'title' => $faker->randomElement(['Tomates Eco', 'Miel', 'Naranjas']),
            'price' => $faker->randomFloat(2, 1, 50),
            'unit' => $faker->randomElement(['kg', 'unit', 'box']), 
            'estimated_weight' => $faker->randomFloat(2, 0.5, 5),
            'stock' => $faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}