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
