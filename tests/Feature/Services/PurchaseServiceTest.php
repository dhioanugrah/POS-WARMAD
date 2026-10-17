<?php

namespace Tests\Feature\Services;

use App\Enums\LocationType;
use App\Enums\ProductType;
use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Exceptions\PosException;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;

    private Supplier $supplier;

    private User $admin;

    private Product $indomie;

    private ProductUnit $indomiePcs;

    private ProductUnit $indomieDus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $this->supplier = Supplier::create(['code' => 'SUP-0001', 'name' => 'CV Sumber Rejeki', 'is_active' => true]);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');

        $this->indomie = Product::create(['code' => 'BRD-001', 'name' => 'Indomie Goreng Special', 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 40]);
        $this->indomiePcs = ProductUnit::create(['product_id' => $this->indomie->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'is_default_sale' => true, 'price_sell' => 3000]);
        $this->indomieDus = ProductUnit::create(['product_id' => $this->indomie->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'price_sell' => 115000]);

        app(StockService::class)->applyIncomingCost($this->indomie, $this->location->id, 100, 2500, StockMovementType::Opening, userId: $this->admin->id);
    }

    private function makeDraftPurchase(array $itemsData, float $discount = 0, float $otherCost = 0): Purchase
    {
        $purchase = Purchase::create([
            'number' => 'PB-TKO-260926-0001',
            'supplier_id' => $this->supplier->id,
            'location_id' => $this->location->id,
            'user_id' => $this->admin->id,
            'purchased_at' => now(),
            'subtotal' => collect($itemsData)->sum('subtotal'),
            'discount_amount' => $discount,
            'other_cost' => $otherCost,
            'total' => collect($itemsData)->sum('subtotal') - $discount + $otherCost,
            'status' => PurchaseStatus::Draft,
        ]);

        foreach ($itemsData as $data) {
            PurchaseItem::create(array_merge(['purchase_id' => $purchase->id], $data));
        }

        return $purchase->fresh('items');
    }

    public function test_draft_does_not_change_stock_uat_pur_01(): void
    {
        $purchase = $this->makeDraftPurchase([
            ['product_id' => $this->indomie->id, 'product_unit_id' => $this->indomieDus->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'qty' => 2, 'qty_base' => 80, 'price' => 104000, 'subtotal' => 208000],
        ]);

        $this->assertSame('100.000', $this->indomie->stocks()->first()->qty);
        $this->assertSame(PurchaseStatus::Draft, $purchase->status);
    }

    public function test_posting_computes_weighted_average_uat_pur_02(): void
    {
        $purchase = $this->makeDraftPurchase([
            ['product_id' => $this->indomie->id, 'product_unit_id' => $this->indomieDus->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'qty' => 2, 'qty_base' => 80, 'price' => 104000, 'subtotal' => 208000],
        ]);

        $result = app(PurchaseService::class)->post($purchase, $this->admin);

        $this->assertStringStartsWith('PB-', $result['purchase']->number);
        $this->assertSame('180.000', $this->indomie->fresh()->stocks()->first()->qty);
        $this->assertSame('2544.44', (string) $this->indomie->fresh()->avg_cost);
    }

    public function test_landed_cost_allocates_other_cost_proportionally_uat_pur_03(): void
    {
        $aqua = Product::create(['code' => 'BRD-002', 'name' => 'Aqua Botol 600ml', 'type' => ProductType::Stock, 'base_unit' => 'Botol', 'min_stock' => 24]);
        $karton = ProductUnit::create(['product_id' => $aqua->id, 'unit_name' => 'Karton', 'conversion_factor' => 24, 'is_base' => true, 'price_sell' => 90000]);
        app(StockService::class)->applyIncomingCost($aqua, $this->location->id, 48, 3000, StockMovementType::Opening, userId: $this->admin->id);

        $purchase = $this->makeDraftPurchase([
            ['product_id' => $this->indomie->id, 'product_unit_id' => $this->indomieDus->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'qty' => 1, 'qty_base' => 40, 'price' => 104000, 'subtotal' => 104000],
            ['product_id' => $aqua->id, 'product_unit_id' => $karton->id, 'unit_name' => 'Karton', 'conversion_factor' => 24, 'qty' => 1, 'qty_base' => 24, 'price' => 72000, 'subtotal' => 72000],
        ], discount: 0, otherCost: 8800);

        $this->assertSame(176000.0, (float) $purchase->subtotal);
        $this->assertSame(184800.0, (float) $purchase->total);

        app(PurchaseService::class)->post($purchase, $this->admin);

        $purchase->refresh();
        $this->assertSame(2730.0, (float) $purchase->items->firstWhere('product_id', $this->indomie->id)->landed_unit_cost);
        $this->assertSame(3150.0, (float) $purchase->items->firstWhere('product_id', $aqua->id)->landed_unit_cost);
    }

    public function test_void_reverses_stock_without_recalculating_avg_uat_pur_04(): void
    {
        $purchase = $this->makeDraftPurchase([
            ['product_id' => $this->indomie->id, 'product_unit_id' => $this->indomieDus->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'qty' => 2, 'qty_base' => 80, 'price' => 104000, 'subtotal' => 208000],
        ]);
        app(PurchaseService::class)->post($purchase, $this->admin);

        $owner = User::factory()->create();
        $owner->assignRole('Owner');
        app(PurchaseService::class)->void($purchase->fresh(), 'Salah input', $owner);

        $this->assertSame('100.000', $this->indomie->fresh()->stocks()->first()->qty);
        $this->assertSame(PurchaseStatus::Void, $purchase->fresh()->status);
        $this->assertSame('2544.44', (string) $this->indomie->fresh()->avg_cost);
    }

    public function test_cannot_post_twice(): void
    {
        $purchase = $this->makeDraftPurchase([
            ['product_id' => $this->indomie->id, 'product_unit_id' => $this->indomieDus->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'qty' => 1, 'qty_base' => 40, 'price' => 104000, 'subtotal' => 104000],
        ]);
        app(PurchaseService::class)->post($purchase, $this->admin);

        $this->expectException(PosException::class);
        app(PurchaseService::class)->post($purchase->fresh(), $this->admin);
    }

    public function test_margin_drop_suggestion_uat_pur_07(): void
    {
        $purchase = $this->makeDraftPurchase([
            ['product_id' => $this->indomie->id, 'product_unit_id' => $this->indomieDus->id, 'unit_name' => 'Dus', 'conversion_factor' => 40, 'qty' => 1, 'qty_base' => 40, 'price' => 124000, 'subtotal' => 124000],
        ]);

        $result = app(PurchaseService::class)->post($purchase, $this->admin);

        $this->assertNotEmpty($result['marginDrops']);
        $this->assertSame($this->indomiePcs->id, $result['marginDrops'][0]['product_unit_id']);
    }
}
