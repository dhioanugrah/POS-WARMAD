<?php

namespace Tests\Feature\Stock;

use App\Enums\LocationType;
use App\Enums\ProductType;
use App\Livewire\Stock\Opening;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OpeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_can_input_opening_balance_uat_inv_01(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $product = Product::create(['code' => 'BRD-001', 'name' => 'Indomie Goreng Special', 'type' => ProductType::Stock, 'base_unit' => 'Pcs']);
        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 3000]);

        Livewire::actingAs($owner)
            ->test(Opening::class)
            ->set('locationId', $location->id)
            ->call('addProduct', $product->id)
            ->set('rows.0.qty', 100)
            ->set('rows.0.unit_cost', 2500)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('100.000', $product->stocks()->where('location_id', $location->id)->first()->qty);
        $this->assertSame('2500.00', (string) $product->fresh()->avg_cost);
        $this->assertDatabaseHas('stock_movements', ['type' => 'opening', 'product_id' => $product->id]);
    }

    public function test_unit_cost_is_converted_from_larger_unit(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $product = Product::create(['code' => 'BRD-002', 'name' => 'Aqua Botol 600ml', 'type' => ProductType::Stock, 'base_unit' => 'Botol']);
        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Botol', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 4000]);
        $karton = ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Karton', 'conversion_factor' => 24, 'is_base' => false, 'price_sell' => 90000]);

        Livewire::actingAs($owner)
            ->test(Opening::class)
            ->set('locationId', $location->id)
            ->call('addProduct', $product->id)
            ->call('changeUnit', 0, $karton->id)
            ->set('rows.0.qty', 2)
            ->set('rows.0.unit_cost', 72000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('48.000', $product->stocks()->where('location_id', $location->id)->first()->qty);
        $this->assertSame('3000.00', (string) $product->fresh()->avg_cost);
    }

    public function test_repeated_barcode_scan_increments_qty_without_retyping_cost(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $product = Product::create(['code' => 'BRD-002', 'name' => 'Aqua Botol 600ml', 'type' => ProductType::Stock, 'base_unit' => 'Botol']);
        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Botol', 'conversion_factor' => 1, 'is_base' => true, 'is_default_sale' => true, 'barcode' => '8886008101053', 'price_sell' => 4000]);

        $component = Livewire::actingAs($owner)
            ->test(Opening::class)
            ->set('locationId', $location->id)
            ->set('search', '8886008101053')
            ->call('scan');

        $this->assertCount(1, $component->get('rows'));
        $component->assertSet('rows.0.qty', 1.0);

        // Scanning the same barcode again just bumps qty — no need to retype cost.
        $component->set('search', '8886008101053')->call('scan');
        $component->set('search', '8886008101053')->call('scan');

        $this->assertCount(1, $component->get('rows'));
        $component->assertSet('rows.0.qty', 3.0);

        $component->set('rows.0.unit_cost', 3000)->call('save')->assertHasNoErrors();

        $this->assertSame('3.000', $product->stocks()->where('location_id', $location->id)->first()->qty);
    }

    public function test_scanning_different_units_creates_separate_rows(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $product = Product::create(['code' => 'BRD-002', 'name' => 'Aqua Botol 600ml', 'type' => ProductType::Stock, 'base_unit' => 'Botol']);
        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Botol', 'conversion_factor' => 1, 'is_base' => true, 'is_default_sale' => true, 'barcode' => '8886008101053', 'price_sell' => 4000]);
        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Karton', 'conversion_factor' => 24, 'barcode' => '18886008101050', 'price_sell' => 90000]);

        $component = Livewire::actingAs($owner)
            ->test(Opening::class)
            ->set('locationId', $location->id)
            ->set('search', '8886008101053')
            ->call('scan')
            ->set('search', '18886008101050')
            ->call('scan');

        $this->assertCount(2, $component->get('rows'));
    }

    public function test_barcode_dropdown_offers_each_remaining_barcode(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $product = Product::create(['code' => 'BRG-00001', 'name' => 'Hydro Coco', 'type' => ProductType::Stock, 'base_unit' => 'Pcs']);
        $pcs = ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'barcode' => '8997009781585', 'price_sell' => 10000]);
        $pcs->barcodes()->create(['barcode' => '982791793129']);

        $component = Livewire::actingAs($owner)
            ->test(Opening::class)
            ->set('locationId', $location->id);

        $this->assertSame(['8997009781585', '982791793129'], $component->viewData('barcodeOptions')->pluck('barcode')->all());

        $component->call('pickBarcode', '8997009781585')
            ->assertSet('rows.0.unit_id', $pcs->id)
            ->assertSet('rows.0.qty', 1);
        $this->assertSame(['982791793129'], $component->viewData('barcodeOptions')->pluck('barcode')->all());

        $component->call('pickBarcode', '982791793129')->assertCount('rows', 1)->assertSet('rows.0.qty', 2.0);
        $this->assertSame([], $component->viewData('barcodeOptions')->pluck('barcode')->all());
    }
}
