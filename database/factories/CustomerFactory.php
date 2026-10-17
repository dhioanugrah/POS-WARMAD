<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PLG-'.fake()->unique()->numerify('####'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'credit_limit' => 0,
            'notes' => null,
            'is_active' => true,
        ];
    }
}
