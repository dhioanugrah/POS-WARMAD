<?php

namespace Database\Factories;

use App\Enums\ShiftStatus;
use App\Models\Location;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'location_id' => Location::factory(),
            'opened_at' => now(),
            'opening_cash' => 200000,
            'status' => ShiftStatus::Open,
        ];
    }
}
