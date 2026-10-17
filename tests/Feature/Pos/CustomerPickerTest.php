<?php

namespace Tests\Feature\Pos;

use App\Livewire\Pos\CustomerPicker;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_customer_by_phone(): void
    {
        Customer::create(['code' => 'PLG-0001', 'name' => 'Bu Sari', 'phone' => '081234567890', 'is_active' => true]);

        Livewire::test(CustomerPicker::class)
            ->call('open')
            ->set('search', '081234567890')
            ->assertSee('Bu Sari');
    }

    public function test_selecting_dispatches_event_with_customer_id(): void
    {
        $customer = Customer::create(['code' => 'PLG-0001', 'name' => 'Bu Sari', 'is_active' => true]);

        Livewire::test(CustomerPicker::class)
            ->call('select', $customer->id)
            ->assertDispatched('customer-selected', customerId: $customer->id);
    }

    public function test_quick_add_creates_customer_with_auto_code(): void
    {
        Livewire::test(CustomerPicker::class)
            ->set('search', 'Bu Rina')
            ->call('openQuickAdd')
            ->set('quickAddPhone', '0812xxxx')
            ->call('quickAdd')
            ->assertDispatched('customer-selected');

        $this->assertDatabaseHas('customers', ['name' => 'Bu Rina', 'code' => 'PLG-0001']);
    }
}
