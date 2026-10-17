<?php

namespace Database\Factories;

use App\Models\DocumentSequence;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentSequence>
 */
class DocumentSequenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doc_type' => 'sale',
            'location_id' => Location::factory(),
            'period' => now()->format('ymd'),
            'last_number' => 0,
        ];
    }
}
