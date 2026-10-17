<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Location;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 10000, 500000);

        return [
            'number' => 'PJ-TKO-'.now()->format('ymd').'-'.fake()->unique()->numerify('####'),
            'location_id' => Location::factory(),
            'user_id' => User::factory(),
            'sold_at' => now(),
            'subtotal' => $total,
            'total' => $total,
            'payment_status' => PaymentStatus::Paid,
            'status' => SaleStatus::Completed,
            'cost_total' => fake()->randomFloat(2, 5000, 400000),
        ];
    }
}
