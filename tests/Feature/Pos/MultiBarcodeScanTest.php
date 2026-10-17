<?php

namespace Tests\Feature\Pos;

use App\Enums\LocationType;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Livewire\Pos\Cashier;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductUnit;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiBarcodeScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_can_scan_a_secondary_barcode_for_the_same_unit(): void
    {
        $this->seed([RolePermissionSeeder::class, SettingsSeeder::class]);

        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $kasir = User::factory()->create(['default_location_id' => $location->id]);
        $kasir->assignRole('Kasir');

        $product = Product::create(['code' => 'BRD-002', 'name' => 'Aqua Botol 600ml', 'type' => ProductType::Stock, 'base_unit' => 'Botol']);
        $unit = ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Botol', 'conversion_factor' => 1, 'is_base' => true, 'is_default_sale' => true, 'barcode' => '1111111111111', 'price_sell' => 4000]);
        ProductBarcode::create(['product_unit_id' => $unit->id, 'barcode' => '2222222222222']);

        app(StockService::class)->applyIncomingCost($product, $location->id, 10, 3000, StockMovementType::Opening, userId: $kasir->id);

        Livewire::actingAs($kasir)
            ->test(Cashier::class)
            ->set('openingCash', 200000)
            ->call('openShift')
            ->set('search', '2222222222222')
            ->assertSet('cart.'.$unit->id.'.qty', 1)
            ->assertSet('cart.'.$unit->id.'.price', 4000.0);
    }
}
