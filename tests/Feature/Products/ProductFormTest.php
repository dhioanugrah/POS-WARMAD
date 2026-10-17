<?php

namespace Tests\Feature\Products;

use App\Enums\LocationType;
use App\Enums\StockMovementType;
use App\Livewire\Products\Form;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductFormTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        return $user;
    }

    public function test_can_create_multi_unit_product_uat_mst_03(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Kopi Kapal Api Sachet')
            ->set('units.0.unit_name', 'Pcs')
            ->set('units.0.barcode_input', '8991002101234')
            ->call('addBarcode', 0)
            ->set('units.0.price_sell', 1500)
            ->call('addUnit')
            ->set('units.1.unit_name', 'Renteng')
            ->set('units.1.conversion_factor', 10)
            ->set('units.1.barcode_input', '18991002101231')
            ->call('addBarcode', 1)
            ->set('units.1.price_sell', 14000)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('name', 'Kopi Kapal Api Sachet')->firstOrFail();
        $this->assertCount(2, $product->units);
        $this->assertTrue($product->units()->where('unit_name', 'Pcs')->first()->is_base);
        $this->assertSame('18991002101231', $product->units()->where('unit_name', 'Renteng')->first()->barcode);
    }

    public function test_duplicate_barcode_is_rejected_uat_mst_04(): void
    {
        $existing = Product::create(['code' => 'BRD-001', 'name' => 'Indomie Goreng Special', 'type' => 'stock', 'base_unit' => 'Pcs']);
        ProductUnit::create(['product_id' => $existing->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'barcode' => '8998866200301', 'price_sell' => 3000]);

        Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Barang Baru')
            ->set('units.0.unit_name', 'Pcs')
            ->set('units.0.barcode_input', '8998866200301')
            ->call('addBarcode', 0)
            ->set('units.0.price_sell', 5000)
            ->call('save');

        $this->assertDatabaseMissing('products', ['name' => 'Barang Baru']);
    }

    public function test_service_product_does_not_require_stock_movements(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Jasa Bungkus Kado')
            ->set('type', 'service')
            ->set('units.0.unit_name', 'Kali')
            ->set('units.0.price_sell', 5000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', ['name' => 'Jasa Bungkus Kado', 'type' => 'service']);
    }

    public function test_code_is_auto_generated_when_left_blank(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Barang Tanpa Kode')
            ->set('units.0.unit_name', 'Pcs')
            ->set('units.0.price_sell', 1000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', ['name' => 'Barang Tanpa Kode', 'code' => 'BRG-00001']);
    }

    public function test_one_unit_can_hold_many_barcodes_at_one_price(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Aqua Botol 600ml')
            ->set('units.0.unit_name', 'Botol')
            ->set('units.0.price_sell', 4000);

        foreach (['1111111111111', '2222222222222', '3333333333333'] as $barcode) {
            $component->set('units.0.barcode_input', $barcode)->call('addBarcode', 0);
        }

        $component->call('save')->assertHasNoErrors();

        $product = Product::where('name', 'Aqua Botol 600ml')->firstOrFail();
        $unit = $product->units()->where('unit_name', 'Botol')->first();

        $this->assertSame('1111111111111', $unit->barcode);
        $this->assertDatabaseHas('product_barcodes', ['product_unit_id' => $unit->id, 'barcode' => '2222222222222']);
        $this->assertDatabaseHas('product_barcodes', ['product_unit_id' => $unit->id, 'barcode' => '3333333333333']);
        $this->assertCount(3, $unit->all_barcodes);

        // All three barcodes resolve to the SAME unit (and its one price) via the shared scope.
        foreach (['1111111111111', '2222222222222', '3333333333333'] as $barcode) {
            $found = ProductUnit::query()->whereAnyBarcode($barcode)->first();
            $this->assertSame($unit->id, $found->id);
        }
    }

    public function test_same_barcode_on_another_unit_in_form_is_rejected(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Barang X')
            ->set('units.0.unit_name', 'Pcs')
            ->set('units.0.price_sell', 1000)
            ->set('units.0.barcode_input', '9999999999999')
            ->call('addBarcode', 0)
            ->call('addUnit');

        $component->set('units.1.barcode_input', '9999999999999')->call('addBarcode', 1);

        $component->assertHasErrors('units')->assertSet('units.1.barcodes', []);
    }

    public function test_barcode_typed_without_adding_is_still_saved(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Teh Botol')
            ->set('units.0.unit_name', 'Botol')
            ->set('units.0.price_sell', 5000)
            ->set('units.0.barcode_input', '1111111111111')
            ->call('addBarcode', 0)
            ->set('units.0.barcode_input', '2222222222222')
            ->call('save')
            ->assertHasNoErrors();

        $unit = Product::where('name', 'Teh Botol')->firstOrFail()->units()->first();
        $this->assertSame(['1111111111111', '2222222222222'], $unit->all_barcodes);
    }

    public function test_save_stops_when_pending_barcode_duplicates_one_in_form(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Barang Y')
            ->set('units.0.unit_name', 'Pcs')
            ->set('units.0.price_sell', 1000)
            ->set('units.0.barcode_input', '9999999999999')
            ->call('addBarcode', 0)
            ->call('addUnit')
            ->set('units.1.unit_name', 'Dus')
            ->set('units.1.conversion_factor', 10)
            ->set('units.1.price_sell', 9000)
            ->set('units.1.barcode_input', '9999999999999')
            ->call('save')
            ->assertHasErrors('units');

        $this->assertDatabaseMissing('products', ['name' => 'Barang Y']);
    }

    public function test_each_scan_counts_as_one_item_of_opening_stock(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Hydro Coco')
            ->set('units.0.unit_name', 'Pcs')
            ->set('units.0.price_sell', 10000)
            ->set('openingCost', 7000);

        foreach (range(1, 3) as $i) {
            $component->set('units.0.barcode_input', '8997009781585')->call('addBarcode', 0);
        }

        $component->assertSet('units.0.barcodes', ['8997009781585'])
            ->assertSet('units.0.opening_qty', '3')
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('name', 'Hydro Coco')->firstOrFail();
        $this->assertSame('3.000', $product->stocks()->where('location_id', $this->location->id)->value('qty'));
        $this->assertSame('7000.00', $product->fresh()->avg_cost);

        $movement = StockMovement::where('product_id', $product->id)->sole();
        $this->assertSame(StockMovementType::Opening, $movement->type);
    }

    public function test_scanning_a_box_barcode_adds_boxes_to_opening_stock(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Hydro Coco')
            ->set('units.0.unit_name', 'Pcs')
            ->set('units.0.price_sell', 10000)
            ->call('addUnit')
            ->set('units.1.unit_name', 'Dus')
            ->set('units.1.conversion_factor', 24)
            ->set('units.1.price_sell', 230000)
            ->set('units.1.barcode_input', '18997009781582')->call('addBarcode', 1)
            ->set('units.1.barcode_input', '18997009781582')->call('addBarcode', 1)
            ->set('units.0.barcode_input', '8997009781585')->call('addBarcode', 0)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('name', 'Hydro Coco')->firstOrFail();
        $this->assertSame('49.000', $product->stocks()->value('qty'));
    }

    public function test_opening_stock_can_be_typed_without_scanning(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Gula Curah')
            ->set('units.0.unit_name', 'Kg')
            ->set('units.0.price_sell', 15000)
            ->set('units.0.opening_qty', '12.5')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('12.500', Product::where('name', 'Gula Curah')->firstOrFail()->stocks()->value('qty'));
    }

    public function test_service_product_gets_no_opening_stock(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Jasa Bungkus')
            ->set('type', 'service')
            ->set('units.0.unit_name', 'Kali')
            ->set('units.0.price_sell', 5000)
            ->set('units.0.opening_qty', '5')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(0, StockMovement::count());
    }

    public function test_scanning_while_editing_adds_stock_at_current_average_cost(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Form::class)
            ->set('name', 'Hydro Coco')
            ->set('units.0.unit_name', 'Pcs')
            ->set('units.0.price_sell', 10000)
            ->set('openingCost', 7000)
            ->set('units.0.barcode_input', '8997009781585')->call('addBarcode', 0)
            ->call('save');

        $product = Product::where('name', 'Hydro Coco')->firstOrFail();

        Livewire::actingAs($this->admin())
            ->test(Form::class, ['product' => $product])
            ->assertSet('openingCost', '7000.00')
            ->set('units.0.barcode_input', '8997009781585')->call('addBarcode', 0)
            ->set('units.0.barcode_input', '982791793129')->call('addBarcode', 0)
            ->assertSet('units.0.opening_qty', '2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('3.000', $product->stocks()->value('qty'));
        $this->assertSame('7000.00', $product->fresh()->avg_cost);
        $this->assertSame(StockMovementType::Adjustment, StockMovement::latest('id')->first()->type);
    }

    public function test_editing_without_scanning_does_not_change_stock(): void
    {
        $product = Product::create(['code' => 'BRG-00009', 'name' => 'Teh', 'type' => 'stock', 'base_unit' => 'Pcs'])->refresh();
        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'barcode' => '1234567890123', 'price_sell' => 3000]);

        Livewire::actingAs($this->admin())
            ->test(Form::class, ['product' => $product])
            ->set('name', 'Teh Manis')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(0, StockMovement::count());
    }
}
