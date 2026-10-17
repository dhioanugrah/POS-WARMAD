<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
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
            'location_id' => Location::factory(),
            'type' => StockMovementType::Opening,
            'qty_change' => 10,
            'qty_after' => 10,
            'unit_cost' => 1000,
            'note' => null,
            'user_id' => User::factory(),
            'occurred_at' => now(),
        ];
    }
}
