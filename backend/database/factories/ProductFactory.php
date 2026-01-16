<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 1. CAMBIO: 'seller_id' ahora es 'user_id'
            // Apuntamos a User::factory(), aunque el Seeder lo sobrescribirá con el ID correcto.
            'user_id' => \App\Models\User::factory(), 
            
            'title' => $this->faker->randomElement(['Tomates Eco', 'Miel', 'Naranjas']),
            'price' => $this->faker->randomFloat(2, 1, 50),
            
            // 2. CORRECCIÓN: Los valores deben coincidir con el ENUM de la migración ['unit', 'kg', 'box']
            'unit' => $this->faker->randomElement(['kg', 'unit', 'box']), 
            
            // 3. AÑADIDO: Este campo es obligatorio en tu tabla y faltaba aquí
            'estimated_weight' => $this->faker->randomFloat(2, 0.5, 5),

            'stock' => $this->faker->numberBetween(0, 100),
            'image_url' => 'https://via.placeholder.com/150',
            'is_active' => true,
        ];
    }
}