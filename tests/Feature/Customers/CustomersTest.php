<?php

namespace Tests\Feature\Customers;

use App\Livewire\Customers\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_can_create_customer_with_auto_code_uat_mst_08(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Kasir');

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('create')
            ->set('name', 'Bu Rina')
            ->set('phone', '0812xxxx')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', ['name' => 'Bu Rina', 'code' => 'PLG-0001']);
    }

    public function test_codes_increment(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');

        Livewire::actingAs($user)->test(Index::class)->call('create')->set('name', 'A')->call('save');
        Livewire::actingAs($user)->test(Index::class)->call('create')->set('name', 'B')->call('save');

        $this->assertDatabaseHas('customers', ['name' => 'A', 'code' => 'PLG-0001']);
        $this->assertDatabaseHas('customers', ['name' => 'B', 'code' => 'PLG-0002']);
    }
}
