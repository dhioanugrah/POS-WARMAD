<?php

namespace Tests\Feature\Services;

use App\Enums\LocationType;
use App\Exceptions\PosException;
use App\Models\CashCategory;
use App\Models\CashTransaction;
use App\Models\Location;
use App\Models\Payment;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_shift(): void
    {
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $user = User::factory()->create();

        $shift = app(ShiftService::class)->open($user, $location->id, 200000);

        $this->assertSame(200000.0, (float) $shift->opening_cash);
        $this->assertTrue($shift->isOpen());
    }

    public function test_cannot_open_second_shift_while_one_is_open(): void
    {
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $user = User::factory()->create();
        app(ShiftService::class)->open($user, $location->id, 200000);

        $this->expectException(PosException::class);
        app(ShiftService::class)->open($user, $location->id, 100000);
    }

    public function test_close_shift_matches_uat_pos_25(): void
    {
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $user = User::factory()->create();
        $service = app(ShiftService::class);

        $shift = $service->open($user, $location->id, 200000);

        Payment::create([
            'payable_type' => 'sale', 'payable_id' => 1, 'direction' => 'in', 'method' => 'cash',
            'amount' => 150000, 'paid_at' => now(), 'user_id' => $user->id, 'shift_id' => $shift->id, 'location_id' => $location->id, 'is_initial' => true,
        ]);
        Payment::create([
            'payable_type' => 'sale', 'payable_id' => 2, 'direction' => 'in', 'method' => 'qris',
            'amount' => 50000, 'paid_at' => now(), 'user_id' => $user->id, 'shift_id' => $shift->id, 'location_id' => $location->id, 'is_initial' => true,
        ]);

        $category = CashCategory::create(['name' => 'Lain-lain', 'type' => 'out', 'is_active' => true]);
        CashTransaction::create([
            'number' => 'KK-TKO-260926-0001', 'location_id' => $location->id, 'user_id' => $user->id, 'shift_id' => $shift->id,
            'cash_category_id' => $category->id, 'type' => 'out', 'method' => 'cash', 'amount' => 20000, 'occurred_at' => now(),
        ]);

        $this->assertSame(330000.0, $service->expectedCash($shift));

        $closed = $service->close($shift, 325000);

        $this->assertSame(330000.0, (float) $closed->expected_cash);
        $this->assertSame(-5000.0, (float) $closed->difference);

        $breakdown = $service->paymentBreakdown($shift);
        $this->assertSame(150000.0, (float) $breakdown['cash']);
        $this->assertSame(50000.0, (float) $breakdown['qris']);
    }

    public function test_voided_payment_excluded_from_expected_cash(): void
    {
        $location = Location::create(['code' => 'TKO', 'name' => 'Toko Utama', 'type' => LocationType::Store]);
        $user = User::factory()->create();
        $service = app(ShiftService::class);
        $shift = $service->open($user, $location->id, 0);

        Payment::create([
            'payable_type' => 'sale', 'payable_id' => 1, 'direction' => 'in', 'method' => 'cash',
            'amount' => 9000, 'paid_at' => now(), 'user_id' => $user->id, 'shift_id' => $shift->id, 'location_id' => $location->id, 'is_initial' => true, 'voided_at' => now(),
        ]);

        $this->assertSame(0.0, $service->expectedCash($shift));
    }
}
