<?php

namespace Database\Factories;

use App\Models\ProductPriceTier;
use App\Models\ProductUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPriceTier>
 */
class ProductPriceTierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_unit_id' => ProductUnit::factory(),
            'min_qty' => fake()->randomFloat(3, 1, 10),
            'price' => fake()->randomFloat(2, 1000, 150000),
        ];
    }
}
