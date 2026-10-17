<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustmentItem>
 */
class StockAdjustmentItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_adjustment_id' => StockAdjustment::factory(),
            'product_id' => Product::factory(),
            'product_unit_id' => ProductUnit::factory(),
            'qty' => 1,
            'qty_base' => 1,
            'unit_cost' => 1000,
        ];
    }
}
