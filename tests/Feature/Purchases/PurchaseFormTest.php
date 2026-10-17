<?php

namespace Tests\Feature\Purchases;

use App\Enums\LocationType;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Livewire\Purchases\Form;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PurchaseFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Location $location;

    private Supplier $supplier;

    private Product $indomie;

    private ProductUnit $indomieDus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, SettingsSeeder::class]);

        $this->location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $this->supplier = Supplier::create(['code' => 'SUP-0001', 'name' => 'CV Sumber Rejeki', 'is_active' => true]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');

        $this->indomie = Product::create(['code' => 'BRD-001', 'name' => 'Indomie Goreng Special', 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 40]);
        ProductUnit::create(['product_id' => $this->indomie->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'is_default_sale' => true, 'price_sell' => 3000]);
        $this->indomieDus = ProductUnit::create(['product_id' => $this->indomie->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'price_sell' => 115000]);

        app(StockService::class)->applyIncomingCost($this->indomie, $this->location->id, 100, 2500, StockMovementType::Opening, userId: $this->admin->id);
    }

    public function test_can_create_and_post_purchase(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('supplierId', $this->supplier->id)
            ->set('locationId', $this->location->id)
            ->call('addProduct', $this->indomie->id)
            ->call('changeUnit', 0, $this->indomieDus->id)
            ->set('items.0.qty', 2)
            ->set('items.0.price', 104000)
            ->call('saveDraft');

        $purchase = Purchase::firstOrFail();
        $this->assertSame('draft', $purchase->status->value);
        $this->assertSame('100.000', $this->indomie->fresh()->stocks()->first()->qty);

        Livewire::actingAs($this->admin)
            ->test(Form::class, ['purchase' => $purchase])
            ->call('post');

        $this->assertSame('posted', $purchase->fresh()->status->value);
        $this->assertSame('180.000', $this->indomie->fresh()->stocks()->first()->qty);
    }

    public function test_each_scan_of_the_same_barcode_adds_one_to_qty(): void
    {
        $this->indomie->units()->where('unit_name', 'Pcs')->update(['barcode' => '8998866200301']);

        $component = Livewire::actingAs($this->admin)->test(Form::class);

        foreach (range(1, 3) as $i) {
            $component->set('search', '8998866200301')->call('scan');
        }

        $component->assertCount('items', 1)
            ->assertSet('items.0.unit_name', 'Pcs')
            ->assertSet('items.0.qty', '3')
            ->assertSet('search', '');
    }

    public function test_scanning_a_box_barcode_adds_the_box_unit(): void
    {
        $this->indomieDus->update(['barcode' => '18998866200308']);

        Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('search', '18998866200308')
            ->call('scan')
            ->assertCount('items', 1)
            ->assertSet('items.0.product_unit_id', $this->indomieDus->id)
            ->assertSet('items.0.conversion_factor', 40.0);
    }

    public function test_scanning_an_additional_barcode_finds_the_same_unit(): void
    {
        $pcs = $this->indomie->units()->where('unit_name', 'Pcs')->first();
        $pcs->update(['barcode' => '1111111111111']);
        $pcs->barcodes()->create(['barcode' => '2222222222222']);

        Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('search', '1111111111111')->call('scan')
            ->set('search', '2222222222222')->call('scan')
            ->assertCount('items', 1)
            ->assertSet('items.0.qty', '2');
    }

    public function test_scanning_unknown_barcode_adds_nothing(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('search', '0000000000000')
            ->call('scan')
            ->assertCount('items', 0)
            ->assertDispatched('notify');
    }

    public function test_can_quick_add_supplier_from_purchase_form(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->call('openQuickAddSupplier')
            ->assertSet('showQuickAddSupplier', true)
            ->set('quickAddSupplierName', 'PT Hydro Indonesia')
            ->set('quickAddSupplierPhone', '08123456789')
            ->call('quickAddSupplier')
            ->assertHasNoErrors();

        $supplier = Supplier::where('name', 'PT Hydro Indonesia')->firstOrFail();
        $this->assertSame('08123456789', $supplier->phone);
        $this->assertTrue($supplier->is_active);
        $this->assertNotEmpty($supplier->code);
        $component->assertSet('supplierId', $supplier->id)->assertSet('showQuickAddSupplier', false);
    }

    public function test_quick_add_supplier_requires_a_name(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->call('openQuickAddSupplier')
            ->call('quickAddSupplier')
            ->assertHasErrors(['quickAddSupplierName' => 'required']);
    }

    public function test_missing_supplier_error_uses_readable_name(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->call('addProduct', $this->indomie->id)
            ->call('saveDraft')
            ->assertHasErrors('supplierId')
            ->assertSee('Supplier wajib diisi');
    }

    public function test_barcode_dropdown_lists_units_not_yet_in_purchase_with_empty_stock_first(): void
    {
        $this->indomie->units()->where('unit_name', 'Pcs')->update(['barcode' => '8998866200301']);
        $this->indomieDus->update(['barcode' => '18998866200308']);
        $hydro = Product::create(['code' => 'BRG-00001', 'name' => 'Hydro Coco', 'type' => ProductType::Stock, 'base_unit' => 'Pcs']);
        $hydroPcs = ProductUnit::create(['product_id' => $hydro->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'is_default_sale' => true, 'barcode' => '8997009781585', 'price_sell' => 10000]);
        $hydroPcs->barcodes()->create(['barcode' => '8997009781592']);
        $noBarcode = Product::create(['code' => 'BRG-00002', 'name' => 'Gula Curah', 'type' => ProductType::Stock, 'base_unit' => 'Kg']);
        ProductUnit::create(['product_id' => $noBarcode->id, 'unit_name' => 'Kg', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 15000]);

        $component = Livewire::actingAs($this->admin)
            ->test(Form::class)
            ->set('locationId', $this->location->id);

        $options = $component->viewData('barcodeOptions');
        $this->assertSame(['Hydro Coco', 'Indomie Goreng Special', 'Indomie Goreng Special'], $options->pluck('product_name')->all());
        $this->assertSame(['8997009781585', '8997009781592'], $options->first()['barcodes']);
        $this->assertSame(0.0, $options->first()['stock']);
        $this->assertSame(100.0, $options->last()['stock']);

        $component->call('addProductUnit', $this->indomieDus->id)
            ->assertSet('items.0.product_unit_id', $this->indomieDus->id)
            ->assertSet('items.0.qty', '1');

        $this->assertNotContains($this->indomieDus->id, $component->viewData('barcodeOptions')->pluck('unit_id')->all());
    }

    public function test_gudang_can_manage_purchases(): void
    {
        $gudang = User::factory()->create();
        $gudang->assignRole('Gudang');

        $this->actingAs($gudang)->get('/purchases')->assertOk();
    }

    public function test_kasir_cannot_access_purchases(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('Kasir');

        $this->actingAs($kasir)->get('/purchases')->assertForbidden();
    }
}
