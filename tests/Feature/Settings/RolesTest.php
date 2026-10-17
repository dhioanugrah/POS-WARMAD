<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Roles;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_owner_can_grant_a_permission_to_gudang(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('Owner');

        Livewire::actingAs($owner)
            ->test(Roles::class)
            ->call('toggle', 'Gudang', 'customers.manage')
            ->call('save');

        $this->assertTrue(Role::findByName('Gudang')->hasPermissionTo('customers.manage'));
    }
}
