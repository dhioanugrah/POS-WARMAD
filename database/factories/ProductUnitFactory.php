<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductUnit>
 */
class ProductUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'unit_name' => 'Pcs',
            'conversion_factor' => 1,
            'is_base' => true,
            'barcode' => fake()->unique()->ean13(),
            'price_sell' => fake()->randomFloat(2, 1000, 150000),
            'is_default_sale' => true,
        ];
    }
}
