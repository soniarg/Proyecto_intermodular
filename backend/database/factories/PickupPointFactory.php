<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PickupPoint>
 */
class PickupPointFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => SellerProfile::factory(),
            'address' => $this->faker->streetAddress(),
            'latitude' => $this->faker->latitude(39.4, 39.5),
            'longitude' => $this->faker->longitude(-0.4, -0.3),
        ];
    }
}
