<?php

namespace Database\Factories;

use App\Enums\CashTransactionType;
use App\Models\CashCategory;
use App\Models\CashTransaction;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashTransaction>
 */
class CashTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'KK-TKO-'.now()->format('ymd').'-'.fake()->unique()->numerify('####'),
            'location_id' => Location::factory(),
            'user_id' => User::factory(),
            'cash_category_id' => CashCategory::factory(),
            'type' => CashTransactionType::Out,
            'method' => 'cash',
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'occurred_at' => now(),
        ];
    }
}
