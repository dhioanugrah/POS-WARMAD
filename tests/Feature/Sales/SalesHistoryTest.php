<?php

namespace Tests\Feature\Sales;

use App\Enums\LocationType;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Livewire\Sales\Index;
use App\Livewire\Sales\Show;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesHistoryTest extends TestCase
{
    use RefreshDatabase;

    private int $saleSequence = 0;

    private function makeSale(Location $location, User $cashier, float $total = 9000): Sale
    {
        return Sale::create([
            'number' => 'PJ-TKO-260926-'.str_pad((string) ++$this->saleSequence, 4, '0', STR_PAD_LEFT),
            'location_id' => $location->id,
            'user_id' => $cashier->id,
            'sold_at' => now(),
            'subtotal' => $total,
            'total' => $total,
            'paid_amount' => $total,
            'payment_status' => PaymentStatus::Paid,
            'status' => SaleStatus::Completed,
            'cost_total' => $total * 0.7,
        ]);
    }

    public function test_kasir_only_sees_own_sales(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);

        $kasir1 = User::factory()->create();
        $kasir1->assignRole('Kasir');
        $kasir2 = User::factory()->create();
        $kasir2->assignRole('Kasir');

        $this->makeSale($location, $kasir1);
        $this->makeSale($location, $kasir2);

        $component = Livewire::actingAs($kasir1)->test(Index::class);
        $this->assertCount(1, $component->viewData('sales'));
    }

    public function test_owner_sees_all_sales_uat_rpt_03(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $owner = User::factory()->create();
        $owner->assignRole('Owner');
        $kasir = User::factory()->create();
        $kasir->assignRole('Kasir');

        $this->makeSale($location, $kasir);
        $this->makeSale($location, $kasir);

        $component = Livewire::actingAs($owner)->test(Index::class);
        $this->assertCount(2, $component->viewData('sales'));
    }

    public function test_kasir_cannot_view_other_cashiers_sale_detail(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $kasir1 = User::factory()->create();
        $kasir1->assignRole('Kasir');
        $kasir2 = User::factory()->create();
        $kasir2->assignRole('Kasir');

        $sale = $this->makeSale($location, $kasir2);

        $this->actingAs($kasir1)->get(route('sales.show', $sale))->assertForbidden();
    }

    public function test_admin_can_void_from_backoffice_uat_pos_22(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $kasir = User::factory()->create();
        $kasir->assignRole('Kasir');

        $product = Product::create(['code' => 'BRD-001', 'name' => 'Indomie', 'type' => ProductType::Stock, 'base_unit' => 'Pcs']);
        ProductUnit::create(['product_id' => $product->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 3000]);
        app(StockService::class)->applyIncomingCost($product, $location->id, 100, 2500, StockMovementType::Opening, userId: $admin->id);

        $sale = $this->makeSale($location, $kasir);
        $sale->items()->create([
            'product_id' => $product->id, 'product_unit_id' => 1, 'product_name' => 'Indomie', 'unit_name' => 'Pcs',
            'conversion_factor' => 1, 'qty' => 3, 'qty_base' => 3, 'price' => 3000, 'subtotal' => 9000, 'unit_cost' => 2500,
        ]);
        app(StockService::class)->move($product, $location->id, -3, StockMovementType::Sale, reference: $sale, unitCost: 2500, userId: $kasir->id);

        Livewire::actingAs($admin)
            ->test(Show::class, ['sale' => $sale])
            ->call('void', 'Salah input');

        $this->assertSame('void', $sale->fresh()->status->value);
        $this->assertSame('100.000', $product->fresh()->stocks()->first()->qty);
    }
}
