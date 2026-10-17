<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'SUP-'.fake()->unique()->numerify('####'),
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'notes' => null,
            'is_active' => true,
        ];
    }
}
