<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseItem>
 */
class PurchaseItemFactory extends Factory
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
            'purchase_id' => Purchase::factory(),
            'product_id' => Product::factory(),
            'product_unit_id' => ProductUnit::factory(),
            'unit_name' => 'Pcs',
            'conversion_factor' => 1,
            'qty' => 1,
            'qty_base' => 1,
            'price' => $price,
            'subtotal' => $price,
            'landed_unit_cost' => fake()->randomFloat(2, 500, 100000),
        ];
    }
}
