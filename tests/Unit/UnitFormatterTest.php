<?php

namespace Tests\Unit;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Support\UnitFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitFormatterTest extends TestCase
{
    use RefreshDatabase;

    public function test_breaks_down_base_qty_into_largest_units_first(): void
    {
        $product = Product::create([
            'code' => 'BRD-001',
            'name' => 'Indomie Goreng Special',
            'type' => ProductType::Stock,
            'base_unit' => 'Pcs',
        ]);

        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 3000]);
        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'is_base' => false, 'price_sell' => 115000]);

        $this->assertSame('2 Dus 5 Pcs', UnitFormatter::breakdown($product->fresh(), 85));
    }

    public function test_whole_multiple_has_no_remainder(): void
    {
        $product = Product::create([
            'code' => 'BRD-001',
            'name' => 'Indomie Goreng Special',
            'type' => ProductType::Stock,
            'base_unit' => 'Pcs',
        ]);

        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 3000]);
        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'is_base' => false, 'price_sell' => 115000]);

        $this->assertSame('2 Dus', UnitFormatter::breakdown($product->fresh(), 80));
    }

    public function test_single_unit_product_shows_base_only(): void
    {
        $product = Product::create([
            'code' => 'BRD-003',
            'name' => 'Gula Pasir Curah',
            'type' => ProductType::Stock,
            'base_unit' => 'Kg',
        ]);

        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Kg', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 17000]);

        $this->assertSame('25 Kg', UnitFormatter::breakdown($product->fresh(), 25));
    }
}
