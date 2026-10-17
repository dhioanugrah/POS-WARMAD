<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 1000, 150000);

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'product_unit_id' => ProductUnit::factory(),
            'product_name' => fake()->words(3, true),
            'unit_name' => 'Pcs',
            'conversion_factor' => 1,
            'qty' => 1,
            'qty_base' => 1,
            'price' => $price,
            'subtotal' => $price,
            'unit_cost' => fake()->randomFloat(2, 500, 100000),
        ];
    }
}
