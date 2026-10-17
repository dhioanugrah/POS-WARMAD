<?php

namespace Tests\Feature\Stock;

use App\Enums\LocationType;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Livewire\StockAdjustments\Form;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_damaged_goods_reduce_stock_uat_inv_06(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $gudang = User::factory()->create();
        $gudang->assignRole('Gudang');

        $sabun = Product::create(['code' => 'BRD-004', 'name' => 'Sabun Mandi 85g', 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 10]);
        ProductUnit::create(['product_id' => $sabun->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'is_default_sale' => true, 'price_sell' => 4500]);
        app(StockService::class)->applyIncomingCost($sabun, $location->id, 5, 3600, StockMovementType::Opening, userId: $gudang->id);

        Livewire::actingAs($gudang)
            ->test(Form::class)
            ->set('locationId', $location->id)
            ->set('reason', 'damaged')
            ->call('addProduct', $sabun->id)
            ->set('items.0.qty', 2)
            ->call('save');

        $this->assertSame('3.000', $sabun->fresh()->stocks()->first()->qty);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $sabun->id, 'type' => 'adjustment', 'qty_change' => -2]);
    }
}
