<?php

namespace Tests\Feature\Pos;

use App\Enums\LocationType;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Livewire\Pos\Cashier;
use App\Models\HeldCart;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CashierTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;

    private Location $location;

    private Product $indomie;

    private ProductUnit $indomiePcs;

    private ProductUnit $indomieDus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, SettingsSeeder::class]);

        $this->location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);

        $this->kasir = User::factory()->create(['default_location_id' => $this->location->id]);
        $this->kasir->assignRole('Kasir');

        $this->indomie = Product::create(['code' => 'BRD-001', 'name' => 'Indomie Goreng Special', 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 40]);
        $this->indomiePcs = ProductUnit::create(['product_id' => $this->indomie->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'is_default_sale' => true, 'barcode' => '8998866200301', 'price_sell' => 3000]);
        $this->indomieDus = ProductUnit::create(['product_id' => $this->indomie->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'barcode' => '18998866200308', 'price_sell' => 115000]);

        app(StockService::class)->applyIncomingCost($this->indomie, $this->location->id, 100, 2500, StockMovementType::Opening, userId: $this->kasir->id);
    }

    public function test_must_open_shift_before_adding_items_uat_pos_01(): void
    {
        Livewire::actingAs($this->kasir)
            ->test(Cashier::class)
            ->assertSet('showOpenShiftModal', true)
            ->set('openingCash', 200000)
            ->call('openShift')
            ->assertSet('showOpenShiftModal', false);

        $this->assertDatabaseHas('shifts', ['user_id' => $this->kasir->id, 'opening_cash' => 200000]);
    }

    private function withOpenShift(): Testable
    {
        return Livewire::actingAs($this->kasir)
            ->test(Cashier::class)
            ->set('openingCash', 200000)
            ->call('openShift');
    }

    public function test_scan_barcode_pcs_uat_pos_02(): void
    {
        $this->withOpenShift()
            ->set('search', '8998866200301')
            ->assertSet('cart.'.$this->indomiePcs->id.'.qty', 1)
            ->assertSet('cart.'.$this->indomiePcs->id.'.unit_name', 'Pcs');
    }

    public function test_scan_barcode_dus_uat_pos_03(): void
    {
        $this->withOpenShift()
            ->set('search', '18998866200308')
            ->assertSet('cart.'.$this->indomieDus->id.'.qty', 1)
            ->assertSet('cart.'.$this->indomieDus->id.'.price', 115000.0);
    }

    public function test_scan_same_barcode_three_times_merges_qty_uat_pos_04(): void
    {
        $component = $this->withOpenShift();
        $component->set('search', '8998866200301');
        $component->set('search', '8998866200301');
        $component->set('search', '8998866200301');

        $component->assertSet('cart.'.$this->indomiePcs->id.'.qty', 3);
        $this->assertCount(1, $component->get('cart'));
    }

    public function test_barcode_list_shows_when_search_is_empty_and_picking_adds_to_cart(): void
    {
        $this->indomiePcs->barcodes()->create(['barcode' => '8998866200999']);

        $component = $this->withOpenShift();
        $this->assertSame(['18998866200308', '8998866200301', '8998866200999'], $component->viewData('barcodeOptions')->pluck('barcode')->all());

        $component->call('pickBarcode', '8998866200999');
        $this->assertSame(1.0, (float) $component->get('cart')[$this->indomiePcs->id]['qty']);
        $this->assertSame(['18998866200308', '8998866200301'], $component->viewData('barcodeOptions')->pluck('barcode')->all());

        $component->call('clearCart');
        $this->assertCount(3, $component->viewData('barcodeOptions'));
    }

    public function test_search_by_name_uat_pos_05(): void
    {
        $aqua = Product::create(['code' => 'BRD-002', 'name' => 'Aqua Botol 600ml', 'type' => ProductType::Stock, 'base_unit' => 'Botol']);
        $botol = ProductUnit::create(['product_id' => $aqua->id, 'unit_name' => 'Botol', 'conversion_factor' => 1, 'is_base' => true, 'is_default_sale' => true, 'price_sell' => 4000]);

        $this->withOpenShift()
            ->set('search', 'aqua')
            ->call('handleEnter')
            ->assertSet('cart.'.$botol->id.'.qty', 1);
    }

    public function test_qty_times_code_format_uat_pos_06(): void
    {
        $this->withOpenShift()
            ->set('search', '3*BRD-001')
            ->assertSet('cart.'.$this->indomiePcs->id.'.qty', 3.0);
    }

    public function test_change_unit_recalculates_price_uat_pos_07(): void
    {
        $this->withOpenShift()
            ->set('search', '8998866200301')
            ->set('search', '8998866200301')
            ->call('changeUnit', $this->indomiePcs->id, $this->indomieDus->id)
            ->assertSet('cart.'.$this->indomieDus->id.'.price', 115000.0);
    }

    public function test_item_discount_uat_pos_08(): void
    {
        $component = $this->withOpenShift()
            ->set('search', '3*BRD-001')
            ->call('setItemDiscount', $this->indomiePcs->id, 'percent', 10);

        $row = $component->get('cart')[$this->indomiePcs->id];
        $this->assertSame(8100.0, $component->instance()->rowSubtotal($row));
    }

    public function test_insufficient_stock_warning_badge_uat_pos_21(): void
    {
        $sabun = Product::create(['code' => 'BRD-004', 'name' => 'Sabun Mandi 85g', 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 10]);
        $pcs = ProductUnit::create(['product_id' => $sabun->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'is_default_sale' => true, 'price_sell' => 4500]);
        app(StockService::class)->applyIncomingCost($sabun, $this->location->id, 5, 3600, StockMovementType::Opening, userId: $this->kasir->id);

        $component = $this->withOpenShift()->call('addToCart', $pcs->id);
        $row = $component->get('cart')[$pcs->id];

        $this->assertSame(5.0, $row['stock_qty']);
        $this->assertSame(10.0, $row['min_stock']);
    }

    public function test_full_checkout_flow_uat_pos_11(): void
    {
        $component = $this->withOpenShift()
            ->set('search', '3*BRD-001')
            ->call('openPayment')
            ->set('paymentMethod', 'cash')
            ->set('paymentAmount', 10000)
            ->call('pay');

        $component->assertSet('cart', []);
        $this->assertDatabaseHas('sales', ['change_amount' => 1000, 'total' => 9000]);
        $this->assertSame('97.000', $this->indomie->fresh()->stocks()->first()->qty);
    }

    public function test_hold_and_resume_uat_pos_15(): void
    {
        $component = $this->withOpenShift()
            ->set('search', '8998866200301')
            ->set('holdLabel', 'Bu Sari')
            ->call('holdCart');

        $component->assertSet('cart', []);
        $this->assertDatabaseHas('held_carts', ['label' => 'Bu Sari']);

        $heldId = HeldCart::first()->id;
        $component->call('resumeHeldCart', $heldId)
            ->assertSet('cart.'.$this->indomiePcs->id.'.qty', 1);

        $this->assertDatabaseCount('held_carts', 0);
    }

    public function test_void_by_admin_uat_pos_22(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $component = $this->withOpenShift()
            ->set('search', '3*BRD-001')
            ->call('openPayment')
            ->set('paymentAmount', 10000)
            ->call('pay');

        $saleId = Sale::first()->id;

        Livewire::actingAs($admin)
            ->test(Cashier::class)
            ->call('voidSale', $saleId, 'Salah input');

        $this->assertSame('void', Sale::find($saleId)->status->value);
        $this->assertSame('100.000', $this->indomie->fresh()->stocks()->first()->qty);
    }

    public function test_kasir_cannot_void(): void
    {
        $component = $this->withOpenShift()
            ->set('search', '3*BRD-001')
            ->call('openPayment')
            ->set('paymentAmount', 10000)
            ->call('pay');

        $saleId = Sale::first()->id;

        Livewire::actingAs($this->kasir)
            ->test(Cashier::class)
            ->call('voidSale', $saleId, 'Salah input');

        $this->assertSame('completed', Sale::find($saleId)->status->value);
    }

    public function test_close_shift_uat_pos_25(): void
    {
        $component = $this->withOpenShift()
            ->set('search', '3*BRD-001')
            ->call('openPayment')
            ->set('paymentAmount', 10000)
            ->call('pay')
            ->set('countedCash', 209000)
            ->call('closeShift');

        $this->assertDatabaseHas('shifts', ['user_id' => $this->kasir->id, 'status' => 'closed']);
    }
}
