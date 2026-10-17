<?php

namespace Database\Factories;

use App\Enums\StockAdjustmentReason;
use App\Models\Location;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'PS-TKO-'.now()->format('ymd').'-'.fake()->unique()->numerify('####'),
            'location_id' => Location::factory(),
            'reason' => StockAdjustmentReason::Damaged,
            'user_id' => User::factory(),
            'adjusted_at' => now(),
        ];
    }
}
