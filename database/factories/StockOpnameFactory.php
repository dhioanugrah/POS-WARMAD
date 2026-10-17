<?php

namespace Database\Factories;

use App\Enums\StockOpnameStatus;
use App\Models\Location;
use App\Models\StockOpname;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockOpname>
 */
class StockOpnameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'OP-TKO-'.now()->format('ymd').'-'.fake()->unique()->numerify('####'),
            'location_id' => Location::factory(),
            'status' => StockOpnameStatus::Draft,
            'user_id' => User::factory(),
        ];
    }
}
