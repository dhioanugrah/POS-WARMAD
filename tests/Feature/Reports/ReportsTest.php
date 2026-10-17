<?php

namespace Tests\Feature\Reports;

use App\Enums\LocationType;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Livewire\Reports\ProfitLoss;
use App\Livewire\Reports\SalesByProduct;
use App\Livewire\Reports\StockMovement;
use App\Models\CashCategory;
use App\Models\CashTransaction;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');
    }

    private function makeSaleWithItem(string $productName, float $qty, float $price, float $unitCost): Sale
    {
        $product = Product::create(['code' => 'P-'.random_int(10000, 99999), 'name' => $productName, 'type' => ProductType::Stock, 'base_unit' => 'Pcs']);
        $unit = ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => $price]);

        $total = $qty * $price;
        $sale = Sale::create([
            'number' => 'PJ-TKO-260926-'.random_int(1000, 9999),
            'location_id' => $this->location->id,
            'user_id' => $this->owner->id,
            'sold_at' => now(),
            'subtotal' => $total,
            'total' => $total,
            'paid_amount' => $total,
            'payment_status' => PaymentStatus::Paid,
            'status' => SaleStatus::Completed,
            'cost_total' => $qty * $unitCost,
        ]);

        SaleItem::create([
            'sale_id' => $sale->id, 'product_id' => $product->id, 'product_unit_id' => $unit->id,
            'product_name' => $productName, 'unit_name' => 'Pcs', 'conversion_factor' => 1,
            'qty' => $qty, 'qty_base' => $qty, 'price' => $price, 'subtotal' => $total, 'unit_cost' => $unitCost,
        ]);

        return $sale;
    }

    public function test_sales_by_product_uat_rpt_04(): void
    {
        $this->makeSaleWithItem('Indomie Goreng Special', 10, 3000, 2500);
        $this->makeSaleWithItem('Aqua Botol 600ml (Karton)', 1, 90000, 72000);

        $component = Livewire::actingAs($this->owner)->test(SalesByProduct::class);
        $rows = $component->viewData('rows')->keyBy('product_name');

        $this->assertSame(30000.0, (float) $rows['Indomie Goreng Special']->revenue);
        $this->assertSame(25000.0, (float) $rows['Indomie Goreng Special']->cost);
        $this->assertSame(5000.0, (float) $rows['Indomie Goreng Special']->profit);
        $this->assertEqualsWithDelta(16.7, $rows['Indomie Goreng Special']->margin, 0.1);

        $this->assertSame(90000.0, (float) $rows['Aqua Botol 600ml (Karton)']->revenue);
        $this->assertSame(18000.0, (float) $rows['Aqua Botol 600ml (Karton)']->profit);
    }

    public function test_profit_loss_uat_fin_03(): void
    {
        $this->makeSaleWithItem('Indomie Goreng Special', 10, 3000, 2500);
        $this->makeSaleWithItem('Aqua Botol 600ml (Karton)', 1, 90000, 72000);

        // Voided sale must be excluded entirely.
        $voided = $this->makeSaleWithItem('Barang Dibatalkan', 1, 50000, 40000);
        $voided->update(['status' => SaleStatus::Void]);

        $category = CashCategory::create(['name' => 'Listrik & Air', 'type' => 'out', 'is_active' => true]);
        CashTransaction::create([
            'number' => 'KK-TKO-260926-0001', 'location_id' => $this->location->id, 'user_id' => $this->owner->id,
            'cash_category_id' => $category->id, 'type' => 'out', 'method' => 'cash', 'amount' => 10000, 'occurred_at' => now(),
        ]);

        $component = Livewire::actingAs($this->owner)->test(ProfitLoss::class);

        $this->assertSame(120000.0, $component->viewData('netSales'));
        $this->assertSame(97000.0, $component->viewData('cogs'));
        $this->assertSame(23000.0, $component->viewData('grossProfit'));
        $this->assertSame(10000.0, $component->viewData('expenses'));
        $this->assertSame(13000.0, $component->viewData('netProfit'));
    }

    public function test_gudang_can_only_access_stock_movement_report_uat_rpt_08(): void
    {
        $gudang = User::factory()->create();
        $gudang->assignRole('Gudang');

        $this->actingAs($gudang)->get('/reports/stock-movement')->assertOk();
        $this->actingAs($gudang)->get('/reports/sales')->assertForbidden();
        $this->actingAs($gudang)->get('/reports/profit-loss')->assertForbidden();
    }

    public function test_kasir_cannot_access_reports(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('Kasir');

        $this->actingAs($kasir)->get('/reports')->assertForbidden();
    }

    public function test_stock_movement_report_balances_uat_rpt_05(): void
    {
        $product = Product::create(['code' => 'BRD-001', 'name' => 'Indomie Goreng Special', 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 40]);
        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 3000]);
        app(StockService::class)->applyIncomingCost($product, $this->location->id, 100, 2500, StockMovementType::Opening, userId: $this->owner->id);
        app(StockService::class)->move($product, $this->location->id, -3, StockMovementType::Sale, userId: $this->owner->id);

        $component = Livewire::actingAs($this->owner)->test(StockMovement::class);
        $row = $component->viewData('rows')->firstWhere('product.id', $product->id);

        $this->assertSame(0.0, $row['opening']);
        $this->assertSame(100.0, $row['in']);
        $this->assertSame(3.0, $row['out']);
        $this->assertSame(97.0, $row['closing']);
    }
}
