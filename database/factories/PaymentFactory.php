<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Location;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'direction' => 'in',
            'method' => PaymentMethod::Cash,
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'reference' => null,
            'paid_at' => now(),
            'user_id' => User::factory(),
            'location_id' => Location::factory(),
            'is_initial' => true,
        ];
    }
}
