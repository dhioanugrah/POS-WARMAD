<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOpnameItem>
 */
class StockOpnameItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_opname_id' => StockOpname::factory(),
            'product_id' => Product::factory(),
            'system_qty' => 10,
            'counted_qty' => null,
            'difference' => 0,
            'unit_cost' => 1000,
        ];
    }
}
