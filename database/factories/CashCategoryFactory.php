<?php

namespace Database\Factories;

use App\Enums\CashTransactionType;
use App\Models\CashCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashCategory>
 */
class CashCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => CashTransactionType::Out,
            'is_active' => true,
        ];
    }
}
