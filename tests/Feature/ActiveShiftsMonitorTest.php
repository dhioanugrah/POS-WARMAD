<?php

namespace Tests\Feature;

use App\Enums\LocationType;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Livewire\ActiveShiftsMonitor;
use App\Models\Location;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActiveShiftsMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_open_shift_with_live_totals(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $kasir = User::factory()->create(['name' => 'Budi']);
        $kasir->assignRole('Kasir');

        $shift = Shift::create([
            'user_id' => $kasir->id, 'location_id' => $location->id, 'opened_at' => now(),
            'opening_cash' => 200000, 'status' => 'open',
        ]);

        $sale = Sale::create([
            'number' => 'PJ-TKO-260926-0001', 'location_id' => $location->id, 'user_id' => $kasir->id, 'shift_id' => $shift->id,
            'sold_at' => now(), 'subtotal' => 9000, 'total' => 9000, 'paid_amount' => 9000,
            'payment_status' => PaymentStatus::Paid, 'status' => SaleStatus::Completed, 'cost_total' => 7500,
        ]);

        $sale->payments()->create([
            'direction' => 'in', 'method' => 'cash', 'amount' => 9000, 'paid_at' => now(),
            'user_id' => $kasir->id, 'shift_id' => $shift->id, 'location_id' => $location->id, 'is_initial' => true,
        ]);

        $component = Livewire::test(ActiveShiftsMonitor::class);

        $component->assertSee('Budi')->assertSee('209.000')->assertSee('1 nota');
    }

    public function test_owner_dashboard_shows_monitor_but_gudang_does_not(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $owner = User::factory()->create();
        $owner->assignRole('Owner');
        $gudang = User::factory()->create();
        $gudang->assignRole('Gudang');

        $this->actingAs($owner)->get('/dashboard')->assertSee('Shift Aktif Sekarang');
        $this->actingAs($gudang)->get('/dashboard')->assertDontSee('Shift Aktif Sekarang');
    }
}
