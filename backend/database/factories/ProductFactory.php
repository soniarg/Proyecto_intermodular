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
            'seller_id' => \App\Models\SellerProfile::factory(),
            'title' => $this->faker->randomElement(['Tomates Eco', 'Miel', 'Naranjas']),
            'price' => $this->faker->randomFloat(2, 1, 50),
            'unit' => $this->faker->randomElement(['kg', 'unidad', 'manojo']),
            'stock' => $this->faker->numberBetween(0, 100),
            'image_url' => 'https://via.placeholder.com/150',
            'is_active' => true,
        ];
    }
}
