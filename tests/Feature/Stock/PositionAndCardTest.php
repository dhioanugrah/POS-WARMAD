<?php

namespace Tests\Feature\Stock;

use App\Enums\LocationType;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Livewire\Stock\Card;
use App\Livewire\Stock\Minimum;
use App\Livewire\Stock\Position;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Supplier;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PositionAndCardTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;

    private User $owner;

    private Product $indomie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');

        $this->indomie = Product::create(['code' => 'BRD-001', 'name' => 'Indomie Goreng Special', 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 40]);
        ProductUnit::create(['product_id' => $this->indomie->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'is_default_sale' => true, 'price_sell' => 3000]);
        ProductUnit::create(['product_id' => $this->indomie->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'price_sell' => 115000]);

        app(StockService::class)->applyIncomingCost($this->indomie, $this->location->id, 100, 2500, StockMovementType::Opening, userId: $this->owner->id);
    }

    public function test_position_shows_unit_breakdown_uat_inv_03(): void
    {
        app(StockService::class)->move($this->indomie, $this->location->id, -15, StockMovementType::Sale, userId: $this->owner->id);

        Livewire::actingAs($this->owner)
            ->test(Position::class)
            ->assertSee('2 Dus 5 Pcs');
    }

    public function test_total_inventory_value_visible_to_owner_only_uat_inv_09(): void
    {
        $aqua = Product::create(['code' => 'BRD-002', 'name' => 'Aqua Botol 600ml', 'type' => ProductType::Stock, 'base_unit' => 'Botol', 'min_stock' => 24]);
        ProductUnit::create(['product_id' => $aqua->id, 'unit_name' => 'Botol', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 4000]);
        app(StockService::class)->applyIncomingCost($aqua, $this->location->id, 48, 3000, StockMovementType::Opening, userId: $this->owner->id);

        $gula = Product::create(['code' => 'BRD-003', 'name' => 'Gula Pasir Curah', 'type' => ProductType::Stock, 'base_unit' => 'Kg', 'min_stock' => 10]);
        ProductUnit::create(['product_id' => $gula->id, 'unit_name' => 'Kg', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 17000]);
        app(StockService::class)->applyIncomingCost($gula, $this->location->id, 25, 14500, StockMovementType::Opening, userId: $this->owner->id);

        $sabun = Product::create(['code' => 'BRD-004', 'name' => 'Sabun Mandi 85g', 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 10]);
        ProductUnit::create(['product_id' => $sabun->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 4500]);
        app(StockService::class)->applyIncomingCost($sabun, $this->location->id, 5, 3600, StockMovementType::Opening, userId: $this->owner->id);

        Livewire::actingAs($this->owner)
            ->test(Position::class)
            ->assertSee('774.500');

        $gudang = User::factory()->create();
        $gudang->assignRole('Gudang');

        Livewire::actingAs($gudang)
            ->test(Position::class)
            ->assertDontSee('774.500');
    }

    public function test_card_shows_movements_and_opening_balance_uat_inv_02(): void
    {
        app(StockService::class)->move($this->indomie, $this->location->id, -3, StockMovementType::Sale, userId: $this->owner->id);

        Livewire::actingAs($this->owner)
            ->test(Card::class, ['product' => $this->indomie])
            ->assertSee('Saldo Awal Periode')
            ->assertSee('Penjualan');
    }

    public function test_minimum_stock_suggests_purchase_qty_uat_inv_07(): void
    {
        $sabun = Product::create(['code' => 'BRD-004', 'name' => 'Sabun Mandi 85g', 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 10]);
        $unit = ProductUnit::create(['product_id' => $sabun->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 4500]);
        app(StockService::class)->applyIncomingCost($sabun, $this->location->id, 5, 3600, StockMovementType::Opening, userId: $this->owner->id);

        $supplier = Supplier::create(['code' => 'SUP-0001', 'name' => 'CV Sumber Rejeki', 'is_active' => true]);

        $component = Livewire::actingAs($this->owner)->test(Minimum::class);
        $component->assertSee('Sabun Mandi 85g')->assertSee('15');

        $component->set('supplierId', $supplier->id)->call('createDraftPurchase');

        $this->assertDatabaseHas('purchase_items', ['product_id' => $sabun->id, 'qty' => 15]);
    }
}
