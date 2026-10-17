<?php

namespace Database\Factories;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'BRG-'.fake()->unique()->numerify('#####'),
            'name' => fake()->words(3, true),
            'category_id' => Category::factory(),
            'brand_id' => null,
            'type' => ProductType::Stock,
            'base_unit' => 'Pcs',
            'min_stock' => 0,
            'avg_cost' => fake()->randomFloat(2, 1000, 100000),
            'last_purchase_cost' => fake()->randomFloat(2, 1000, 100000),
            'image_path' => null,
            'notes' => null,
            'is_active' => true,
        ];
    }
}
