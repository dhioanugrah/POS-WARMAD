<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
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
            'number' => 'PB-TKO-'.now()->format('ymd').'-'.fake()->unique()->numerify('####'),
            'supplier_id' => Supplier::factory(),
            'location_id' => Location::factory(),
            'user_id' => User::factory(),
            'purchased_at' => now(),
            'subtotal' => $total,
            'total' => $total,
            'payment_status' => PaymentStatus::Paid,
            'status' => PurchaseStatus::Draft,
        ];
    }
}
