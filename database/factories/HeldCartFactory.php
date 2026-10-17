<?php

namespace Database\Factories;

use App\Models\HeldCart;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeldCart>
 */
class HeldCartFactory extends Factory
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
            'label' => 'Antrian #1',
            'payload' => [],
        ];
    }
}
