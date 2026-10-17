<?php

namespace Database\Factories;

use App\Models\ProductBarcode;
use App\Models\ProductUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductBarcode>
 */
class ProductBarcodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_unit_id' => ProductUnit::factory(),
            'barcode' => fake()->unique()->ean13(),
        ];
    }
}
