<?php

namespace Tests\Feature;

use App\Enums\LocationType;
use App\Enums\PaymentStatus;
use App\Enums\ProductType;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_todays_revenue_and_excludes_void_uat_rpt_01(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        Sale::create([
            'number' => 'PJ-TKO-260926-0001', 'location_id' => $location->id, 'user_id' => $owner->id, 'sold_at' => now(),
            'subtotal' => 30000, 'total' => 30000, 'paid_amount' => 30000, 'payment_status' => PaymentStatus::Paid,
            'status' => SaleStatus::Completed, 'cost_total' => 25000,
        ]);
        Sale::create([
            'number' => 'PJ-TKO-260926-0002', 'location_id' => $location->id, 'user_id' => $owner->id, 'sold_at' => now(),
            'subtotal' => 90000, 'total' => 90000, 'paid_amount' => 90000, 'payment_status' => PaymentStatus::Paid,
            'status' => SaleStatus::Completed, 'cost_total' => 72000,
        ]);
        Sale::create([
            'number' => 'PJ-TKO-260926-0003', 'location_id' => $location->id, 'user_id' => $owner->id, 'sold_at' => now(),
            'subtotal' => 50000, 'total' => 50000, 'paid_amount' => 50000, 'payment_status' => PaymentStatus::Paid,
            'status' => SaleStatus::Void, 'cost_total' => 40000,
        ]);

        $response = $this->actingAs($owner)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('120.000');
        $response->assertSee('23.000');
    }

    public function test_low_stock_warning_links_to_minimum_page(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        $sabun = Product::create(['code' => 'BRD-004', 'name' => 'Sabun Mandi 85g', 'type' => ProductType::Stock, 'base_unit' => 'Pcs', 'min_stock' => 10]);
        ProductUnit::create(['product_id' => $sabun->id, 'unit_name' => 'Pcs', 'conversion_factor' => 1, 'is_base' => true, 'price_sell' => 4500]);
        app(StockService::class)->applyIncomingCost($sabun, $location->id, 5, 3600, StockMovementType::Opening, userId: $owner->id);

        $response = $this->actingAs($owner)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('1 barang');
    }
}
